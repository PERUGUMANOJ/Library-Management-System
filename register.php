<?php
session_start();
require 'db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $msg = 'error:Passwords do not match. Please try again.';
    } elseif (strlen($password) < 6) {
        $msg = 'error:Password must be at least 6 characters long.';
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $msg = 'error:An account with this email already exists.';
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param("sss", $name, $email, $hashed);
            if ($stmt->execute()) {
                $msg = 'success:Registration successful! You can now log in.';
            } else {
                $msg = 'error:Something went wrong. Please try again.';
            }
        }
    }
}

$is_error   = str_starts_with($msg, 'error:');
$is_success = str_starts_with($msg, 'success:');
$msg_text   = ($is_error || $is_success) ? substr($msg, strpos($msg, ':') + 1) : $msg;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Digital Library</title>
    <link rel="stylesheet" href="style.css">
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <style>
        body { 
            background: var(--bg-body); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 40px 20px;
        }
        
        .auth-container {
            display: flex;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            width: 100%;
            max-width: 850px;
            min-height: 500px;
        }
        
        /* Left Brand Panel */
        .auth-brand {
            flex: 1;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
            padding: 60px;
            display: flex;
            flex-direction: column;
            position: relative;
            color: #fff;
            overflow: hidden;
        }
        .auth-brand::before {
            content: ''; position: absolute; width: 400px; height: 400px;
            background: var(--primary); filter: blur(120px); border-radius: 50%;
            top: -100px; left: -100px; opacity: 0.3; pointer-events: none;
        }
        .auth-brand::after {
            content: ''; position: absolute; width: 300px; height: 300px;
            background: var(--accent); filter: blur(100px); border-radius: 50%;
            bottom: -50px; right: -50px; opacity: 0.2; pointer-events: none;
        }
        
        .auth-logo {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 2;
            text-decoration: none;
            color: #fff;
            margin-bottom: auto;
        }
        .auth-logo ion-icon { color: var(--primary); font-size: 2.2rem; }
        
        .brand-content { position: relative; z-index: 2; }
        .brand-title { font-size: 2.2rem; font-weight: 800; font-family: 'Montserrat', sans-serif; line-height: 1.1; margin-bottom: 12px; letter-spacing: -1px; }
        .brand-title span { color: var(--primary); }
        .brand-desc { font-size: 0.9rem; color: rgba(255,255,255,0.8); line-height: 1.6; margin-bottom: 25px; }
        
        .feature-list { list-style: none; display: flex; flex-direction: column; gap: 12px; }
        .feature-list li { display: flex; align-items: center; gap: 12px; font-size: 0.9rem; color: rgba(255,255,255,0.9); background: rgba(255,255,255,0.05); padding: 10px 20px; border-radius: var(--radius-md); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }
        .feature-list li ion-icon { color: var(--primary); font-size: 1.2rem; }
        
        /* Right Form Panel */
        .auth-form-wrapper {
            width: 400px;
            padding: 30px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--bg-card);
            position: relative;
        }
        
        .form-header { margin-bottom: 25px; }
        .form-eyebrow { font-size: 0.75rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: inline-block; background: rgba(59,130,246,0.1); padding: 4px 10px; border-radius: 20px; }
        .form-title { font-size: 1.8rem; font-family: 'Montserrat', sans-serif; color: var(--text-main); margin-bottom: 5px; letter-spacing: -0.5px; }
        .form-subtitle { color: var(--text-muted); font-size: 0.85rem; }
        
        .form-control-icon { position:relative; }
        .form-control-icon ion-icon { position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:1.2rem; z-index:10; }
        .form-control-icon .form-control { padding-left: 45px; }
        
        /* Password Strength */
        .strength-meter { height: 4px; background: var(--border-color); border-radius: 2px; margin-top: 10px; overflow: hidden; display: flex; }
        .strength-fill { height: 100%; width: 0; background: var(--danger); transition: all 0.3s; }
        .strength-text { font-size: 0.75rem; color: var(--text-muted); margin-top: 5px; font-weight: 600; }
        
        .auth-footer { margin-top: 30px; text-align: center; }
        .auth-footer p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 15px; }
        .auth-footer a { color: var(--primary); font-weight: 600; text-decoration: none; }
        .auth-footer a:hover { text-decoration: underline; }
        
        .divider { display: flex; align-items: center; text-align: center; color: var(--text-muted); font-size: 0.85rem; margin: 25px 0; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid var(--border-color); }
        .divider:not(:empty)::before { margin-right: 15px; }
        .divider:not(:empty)::after { margin-left: 15px; }

        @media (max-width: 992px) {
            .auth-container { flex-direction: column; max-width: 500px; }
            .auth-brand { display: none; }
            .auth-form-wrapper { width: 100%; padding: 40px; }
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <!-- Left Brand Side -->
        <div class="auth-brand">
            <a href="index.php" class="auth-logo">
                <ion-icon name="library"></ion-icon> LibSys.
            </a>
            
            <div class="brand-content">
                <h1 class="brand-title">Start your reading<br>journey <span>today.</span></h1>
                <p class="brand-desc">Join thousands of students and professionals who are expanding their knowledge through our premium digital library platform.</p>
                
                <ul class="feature-list">
                    <li><ion-icon name="book"></ion-icon> Access over 5,000+ curated premium books</li>
                    <li><ion-icon name="bookmark"></ion-icon> Smart bookmarks and reading progress tracking</li>
                    <li><ion-icon name="volume-high"></ion-icon> Advanced text-to-speech for accessible learning</li>
                </ul>
            </div>
            
            <!-- Book visual -->
            <div style="position:absolute; right:-50px; bottom:-50px; font-size:15rem; filter:drop-shadow(0 20px 40px rgba(0,0,0,0.5)); opacity:0.6; transform:rotate(-15deg); pointer-events:none;">
                📖
            </div>
        </div>
        
        <!-- Right Form Side -->
        <div class="auth-form-wrapper">
            <a href="index.php" style="position:absolute; top:30px; left:40px; display:flex; align-items:center; gap:5px; color:var(--text-muted); font-weight:600; font-size:0.9rem;">
                <ion-icon name="arrow-back"></ion-icon> Back
            </a>
            
            <div class="form-header" style="margin-top:20px;">
                <span class="form-eyebrow">Student Portal</span>
                <h2 class="form-title">Create Account</h2>
                <p class="form-subtitle">Fill in your details to get free access instantly.</p>
            </div>
            
            <?php if ($msg_text): ?>
                <div class="flash <?php echo $is_error ? 'flash-error' : 'flash-success'; ?>">
                    <ion-icon name="<?php echo $is_error ? 'warning' : 'checkmark-circle'; ?>" style="font-size:1.5rem;"></ion-icon> 
                    <span>
                        <?php echo htmlspecialchars($msg_text); ?>
                        <?php if ($is_success): ?> <a href="login.php" style="font-weight:700; text-decoration:underline;">Login here</a> <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>

            <form method="POST" id="regForm">
                
                <div class="form-group">
                    <label class="form-label" for="name">Full Legal Name</label>
                    <div class="form-control-icon">
                        <ion-icon name="person-outline"></ion-icon>
                        <input type="text" name="name" id="name" class="form-control" placeholder="E.g. John Doe" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">University / Personal Email</label>
                    <div class="form-control-icon">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="email" name="email" id="email" class="form-control" placeholder="student@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" for="password">Create Password</label>
                        <div class="form-control-icon">
                            <ion-icon name="lock-closed-outline"></ion-icon>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Min 6 chars" required oninput="checkStrength(this.value)">
                        </div>
                        <div class="strength-meter"><div class="strength-fill" id="str-fill"></div></div>
                        <div class="strength-text" id="str-label">Enter a password</div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <div class="form-control-icon">
                            <ion-icon name="shield-checkmark-outline"></ion-icon>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat it" required >
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:25px; padding:15px; font-size:1rem;">
                    Create Free Account <ion-icon name="arrow-forward"></ion-icon>
                </button>
            </form>

            <div class="divider">already enrolled?</div>

            <div class="auth-footer">
                <p>Have an existing account? <a href="login.php">Sign in to your portal</a></p>
            </div>
        </div>
    </div>

    <script>
        function checkStrength(val) {
            const fill  = document.getElementById('str-fill');
            const label = document.getElementById('str-label');
            let strength = 0;
            if (val.length >= 6)  strength++;
            if (val.length >= 10) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            const levels = [
                { w: '0%',   c: 'var(--border-color)', t: 'Enter a password' },
                { w: '25%',  c: 'var(--danger)', t: 'Weak' },
                { w: '50%',  c: 'var(--warning)', t: 'Fair' },
                { w: '75%',  c: 'var(--primary)', t: 'Good' },
                { w: '100%', c: 'var(--success)', t: 'Strong' },
            ];
            const lvl = levels[Math.min(strength, 4)];
            fill.style.width      = lvl.w;
            fill.style.background = lvl.c;
            label.innerText       = lvl.t;
            label.style.color     = lvl.c;
        }
    </script>
</body>
</html>
