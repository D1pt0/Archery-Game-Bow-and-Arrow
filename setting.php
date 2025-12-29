<?php
include 'session_check.php';
include 'db_connection.php';

$player_id = $_SESSION['player_id'];
$username = $_SESSION['username'];

// Get player settings
try {
    $stmt = $pdo->prepare("SELECT * FROM Player WHERE Player_ID = :id");
    $stmt->bindParam(':id', $player_id);
    $stmt->execute();
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle settings update
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $status = $_POST['status'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        try {
            $updateStmt = $pdo->prepare("UPDATE Player SET Email = :email, Status = :status WHERE Player_ID = :id");
            $updateStmt->bindParam(':email', $email);
            $updateStmt->bindParam(':status', $status);
            $updateStmt->bindParam(':id', $player_id);
            $updateStmt->execute();
            
            $message = 'Settings updated successfully';
            
            // Refresh player data
            $stmt->execute();
            $player = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>DartMaster - Settings</title>
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
    --radius:22px;
    --shadow:0 10px 30px rgba(0,0,0,.35);
}
body {
    margin:0;
    font-family:"Chakra Petch", sans-serif;
    background: var(--bg);
    color: var(--text);
    display:flex;
    flex-direction:column;
    min-height:100vh;
}
body.light {
    --bg:#f5f6fa;
    --panel:#fff9;
    --text:#20222a;
    --muted:#555;
    --accent:#ff6a3d;
    --accent-2:#0099ff;
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
    justify-content:flex-start;
    align-items:center;
    padding:14px 22px;
    background: var(--panel);
    backdrop-filter: blur(10px);
}
.header-btn {
    background:none;
    border:none;
    color:var(--text);
    font-size:1.1rem;
    cursor:pointer;
    padding:8px 12px;
    border-radius:12px;
    transition:background .2s;
}
.header-btn:hover { background:var(--accent); color:#fff; }
main {
    flex:1;
    padding:30px 20px;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:30px;
}
h1 {
    font-size:2.6rem;
    margin-bottom:8px;
    background:linear-gradient(90deg, var(--accent), var(--accent-2));
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}
.greeting {color:var(--muted); margin-bottom:20px;}
.settings-card {
    background: var(--panel);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding:30px;
    width:100%;
    max-width:600px;
}
.settings-card h2 {
    margin-bottom:20px;
    font-size:1.8rem;
    color:var(--accent-2);
}
.setting {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:12px 0;
    border-bottom:1px solid #ffffff22;
}
.setting:last-child {border-bottom:none;}
.setting label {
    font-weight:600;
    color:var(--accent);
}
.setting input, .setting select {
    padding:8px 12px;
    border-radius:8px;
    border:1px solid rgba(255,255,255,0.1);
    background: rgba(10,14,33,.65);
    color: var(--text);
    outline: none;
}
.setting select {
    cursor: pointer;
}
.save-btn {
    appearance: none;
    border: none;
    cursor: pointer;
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 700;
    background: linear-gradient(90deg, var(--accent), var(--accent-2));
    color: #08131a;
    box-shadow: 0 4px 12px rgba(124,92,255,.35);
    margin-top: 20px;
    width: 100%;
}
.save-btn:hover {
    filter: saturate(115%);
}
.message {
    padding: 10px;
    border-radius: 8px;
    margin-top: 15px;
    text-align: center;
}
.message.success {
    background: rgba(49,233,129,0.2);
    color: var(--accent-2);
}
.message.error {
    background: rgba(255,107,107,0.2);
    color: #ff6b6b;
}
footer {
    padding:12px 20px;
    text-align:center;
    color:var(--muted);
}
.controls {
    position:fixed;
    bottom:16px;
    left:16px;
    display:flex;
    gap:12px;
}
.control-btn {
    width:50px; height:50px;
    border-radius:50%;
    border:none;
    display:grid; place-items:center;
    cursor:pointer;
    font-size:1.3rem;
    color:#fff;
    background:linear-gradient(135deg, var(--accent), var(--accent-2));
    box-shadow:0 4px 12px rgba(0,0,0,.3);
    transition:transform .15s;
}
.control-btn:hover {transform:scale(1.1);}
</style>
</head>
<body>
<header>
    <button class="header-btn" onclick="window.location.href='home.php'">← Home</button>
    <button class="header-btn" onclick="window.location.href='profile.php'">👤 Profile</button>
</header>
<main>
    <h1>Settings</h1>
    <p class="greeting">Your account information</p>
    
    <section class="settings-card">
        <h2>Account Settings</h2>
        <form method="POST" action="settings.php">
            <div class="setting">
                <label for="username">Username:</label>
                <span id="username"><?php echo htmlspecialchars($player['Username']); ?></span>
            </div>
            
            <div class="setting">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($player['Email']); ?>" required>
            </div>
            
            <div class="setting">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="online" <?php echo $player['Status'] == 'online' ? 'selected' : ''; ?>>Online</option>
                    <option value="away" <?php echo $player['Status'] == 'away' ? 'selected' : ''; ?>>Away</option>
                    <option value="busy" <?php echo $player['Status'] == 'busy' ? 'selected' : ''; ?>>Busy</option>
                    <option value="offline" <?php echo $player['Status'] == 'offline' ? 'selected' : ''; ?>>Offline</option>
                </select>
            </div>
            
            <div class="setting">
                <label>Password:</label>
                <span>••••••••</span>
            </div>
            
            <div class="setting">
                <label>Created At:</label>
                <span><?php echo date('M j, Y', strtotime($player['Created_at'])); ?></span>
            </div>
            
            <button type="submit" class="save-btn">Save Changes</button>
            
            <?php if (!empty($message)): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </form>
    </section>
    
    <section class="settings-card">
        <h2>Preferences</h2>
        <div class="setting">
            <label for="notifications">Notifications:</label>
            <input type="checkbox" id="notifications" checked>
        </div>
        
        <div class="setting">
            <label for="sound">Sound Effects:</label>
            <input type="checkbox" id="sound" checked>
        </div>
        
        <div class="setting">
            <label for="music">Background Music:</label>
            <input type="checkbox" id="music" checked>
        </div>
        
        <div class="setting">
            <label for="theme">Theme:</label>
            <select id="theme">
                <option value="dark">Dark</option>
                <option value="light">Light</option>
            </select>
        </div>
    </section>
</main>
<footer>© 2025 DartMaster</footer>
<div class="controls">
    <button class="control-btn" id="musicBtn">🎵</button>
    <button class="control-btn" id="themeBtn">🌙</button>
</div>
<audio id="bgMusic" loop>
    <source src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3" type="audio/mpeg">
</audio>
<script>
const music = document.getElementById("bgMusic");
const musicBtn = document.getElementById("musicBtn");
let playing = false;
musicBtn.addEventListener("click",()=>{
    if(playing){ music.pause(); playing=false; musicBtn.textContent="🎵"; }
    else { music.play(); playing=true; musicBtn.textContent="⏸"; }
});

const themeBtn = document.getElementById("themeBtn");
themeBtn.addEventListener("click",()=>{
    document.body.classList.toggle("light");
});

const themeSelect = document.getElementById("theme");
themeSelect.addEventListener("change", function() {
    if (this.value === "light") {
        document.body.classList.add("light");
    } else {
        document.body.classList.remove("light");
    }
});
</script>
</body>
</html>