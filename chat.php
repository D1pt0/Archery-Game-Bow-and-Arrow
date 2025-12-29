<?php
include 'session_check.php';
include 'db_connection.php';

$player_id = $_SESSION['player_id'];
$username = $_SESSION['username'];

// Handle sending messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['receiver_id'])) {
    $message = trim($_POST['message']);
    $receiver_id = $_POST['receiver_id'];
    
    if (!empty($message)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO Chat (Sender, Receiver, Content) VALUES (:sender, :receiver, :content)");
            $stmt->bindParam(':sender', $player_id);
            $stmt->bindParam(':receiver', $receiver_id);
            $stmt->bindParam(':content', $message);
            $stmt->execute();
        } catch(PDOException $e) {
            // Handle error
        }
    }
}

// Get friends for chat
try {
    $friendsStmt = $pdo->prepare("
        SELECT p.Player_ID, p.Username, p.Status 
        FROM Friendship f 
        JOIN Player p ON (f.Requester_id = p.Player_ID OR f.Recipient_id = p.Player_ID) AND p.Player_ID != :player_id 
        WHERE (f.Requester_id = :player_id OR f.Recipient_id = :player_id) AND f.Status = 'accepted'
        ORDER BY p.Username
    ");
    $friendsStmt->bindParam(':player_id', $player_id);
    $friendsStmt->execute();
    $friends = $friendsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get messages for selected friend
    $selected_friend_id = isset($_GET['friend_id']) ? $_GET['friend_id'] : (count($friends) > 0 ? $friends[0]['Player_ID'] : null);
    $messages = [];
    
    if ($selected_friend_id) {
        $messagesStmt = $pdo->prepare("
            SELECT c.*, s.Username as SenderName 
            FROM Chat c 
            JOIN Player s ON c.Sender = s.Player_ID 
            WHERE (c.Sender = :player_id AND c.Receiver = :friend_id) OR (c.Sender = :friend_id AND c.Receiver = :player_id) 
            ORDER BY c.Timestamp
        ");
        $messagesStmt->bindParam(':player_id', $player_id);
        $messagesStmt->bindParam(':friend_id', $selected_friend_id);
        $messagesStmt->execute();
        $messages = $messagesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark messages as read
        $readStmt = $pdo->prepare("UPDATE Chat SET Is_read = TRUE WHERE Receiver = :player_id AND Sender = :friend_id AND Is_read = FALSE");
        $readStmt->bindParam(':player_id', $player_id);
        $readStmt->bindParam(':friend_id', $selected_friend_id);
        $readStmt->execute();
    }
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>DartMaster - Chat</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg:#080a12;
    --panel:#0f1326cc;
    --text:#e7ecff;
    --muted:#b7c0ff;
    --accent:#7c5cff;
    --accent-2:#00ffd1;
    --radius:18px;
    --shadow:0 10px 30px rgba(0,0,0,.35);
}
body {
    margin:0;
    font-family:"Chakra Petch", sans-serif;
    background: var(--bg);
    color: var(--text);
    height:100vh;
    display:flex;
    flex-direction:column;
}
body::before {
    content:"";
    position:fixed;
    top:0; left:0; right:0; bottom:0;
    background: radial-gradient(circle at 20% 20%, #7c5cff55, transparent 60%),
                radial-gradient(circle at 80% 80%, #00ffd155, transparent 60%),
                radial-gradient(circle at 50% 50%, #ff3d7555, transparent 70%);
    animation: moveBg 20s infinite alternate ease-in-out;
    z-index:-1;
}
@keyframes moveBg {
    0% { background-position: 20% 20%, 80% 80%, 50% 50%; }
    50% { background-position: 30% 10%, 70% 90%, 40% 60%; }
    100% { background-position: 20% 30%, 80% 70%, 60% 40%; }
}
header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:14px 22px;
    background: var(--panel);
    backdrop-filter: blur(10px);
}
.header-btn {
    background:none;
    border:none;
    color: var(--text);
    font-size:1.1rem;
    cursor:pointer;
    padding:8px 12px;
    border-radius:12px;
    transition: background .2s;
}
.header-btn:hover {
    background: var(--accent);
    color:#fff;
}
main {
    flex:1;
    display:flex;
    overflow:hidden;
}
/* Sidebar friends list */
.sidebar {
    width:250px;
    background: var(--panel);
    display:flex;
    flex-direction:column;
    padding:20px;
    border-right:2px solid rgba(255,255,255,0.1);
}
.friend {
    padding:10px;
    border-radius:var(--radius);
    cursor:pointer;
    transition: background .2s;
    margin-bottom:10px;
    color:var(--muted);
    display: flex;
    align-items: center;
    gap: 10px;
}
.friend:hover, .friend.active {
    background:linear-gradient(135deg, var(--accent), var(--accent-2));
    color:#fff;
}
.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}
.status-online { background: #31e981; }
.status-offline { background: #b7c0ff; }
.status-busy { background: #ff6b6b; }
.status-away { background: #ffc107; }
/* Chat window */
.chat-window {
    flex:1;
    display:flex;
    flex-direction:column;
    background: rgba(255,255,255,0.02);
    padding:20px;
}
.messages {
    flex:1;
    overflow-y:auto;
    margin-bottom:20px;
}
.msg {
    margin:8px 0;
    padding:10px 14px;
    border-radius:var(--radius);
    max-width:70%;
    word-wrap:break-word;
    box-shadow: var(--shadow);
    position:relative;
    font-size:1rem;
}
.msg.you {
    background: var(--accent);
    color:#fff;
    margin-left:auto;
}
.msg.friend {
    background: var(--panel);
    color: var(--text);
    margin-right:auto;
}
.meta {
    font-size:0.75rem;
    opacity:0.7;
    margin-top:4px;
    display:flex;
    justify-content:flex-end;
    gap:8px;
}
.msg.friend .meta {
    justify-content:flex-start;
}
/* Input bar */
.input-bar {
    display:flex;
    gap:10px;
}
.input-bar input {
    flex:1;
    padding:12px;
    border-radius:var(--radius);
    border:none;
    outline:none;
    font-size:1rem;
    background: var(--panel);
    color: var(--text);
}
.input-bar button {
    padding:12px 20px;
    border:none;
    border-radius:var(--radius);
    background: linear-gradient(135deg, var(--accent), var(--accent-2));
    color:#fff;
    cursor:pointer;
    transition: transform .2s;
}
.input-bar button:hover {
    transform:scale(1.05);
}
footer {
    padding:12px 20px;
    text-align:center;
    color:var(--muted);
}
.no-messages {
    text-align: center;
    color: var(--muted);
    margin-top: 50px;
}
</style>
</head>
<body>
<header>
    <button class="header-btn" onclick="window.location.href='home.php'">← Home</button>
    <h2>Chat</h2>
    <div style="width:40px;"></div>
</header>
<main>
    <!-- Sidebar with friends -->
    <div class="sidebar">
        <?php if (count($friends) > 0): ?>
            <?php foreach ($friends as $friend): ?>
                <div class="friend <?php echo $friend['Player_ID'] == $selected_friend_id ? 'active' : ''; ?>" 
                     onclick="window.location.href='chat.php?friend_id=<?php echo $friend['Player_ID']; ?>'">
                    <span class="status-indicator status-<?php echo $friend['Status']; ?>"></span>
                    <span><?php echo htmlspecialchars($friend['Username']); ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div>No friends yet</div>
        <?php endif; ?>
    </div>
    
    <!-- Chat window -->
    <div class="chat-window">
        <div class="messages" id="messages">
            <?php if ($selected_friend_id && count($messages) > 0): ?>
                <?php foreach ($messages as $message): ?>
                    <div class="msg <?php echo $message['Sender'] == $player_id ? 'you' : 'friend'; ?>">
                        <?php echo htmlspecialchars($message['Content']); ?>
                        <div class="meta">
                            <?php echo date('g:i A', strtotime($message['Timestamp'])); ?>
                            <?php if ($message['Sender'] == $player_id && $message['Is_read']): ?>
                                ✓ Read
                            <?php elseif ($message['Sender'] == $player_id): ?>
                                ✓ Sent
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-messages">No messages yet. Start a conversation!</div>
            <?php endif; ?>
        </div>
        
        <?php if ($selected_friend_id): ?>
        <form method="POST" class="input-bar">
            <input type="hidden" name="receiver_id" value="<?php echo $selected_friend_id; ?>">
            <input type="text" name="message" placeholder="Type a message..." required>
            <button type="submit">Send</button>
        </form>
        <?php endif; ?>
    </div>
</main>
<footer>© 2025 DartMaster</footer>
<script>
// Auto-scroll to bottom of messages
const messagesContainer = document.getElementById('messages');
if (messagesContainer) {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

// Refresh page every 30 seconds to check for new messages
setTimeout(() => {
    window.location.reload();
}, 30000);
</script>
</body>
</html>