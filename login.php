<?php
session_start();
require 'db.php';

$msg = '';
$login_type = isset($_POST['login_type']) ? $_POST['login_type'] : (isset($_GET['type']) ? $_GET['type'] : 'student');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $type     = $_POST['login_type'] ?? 'student';

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($type === 'admin' && $user['role'] !== 'admin') {
                $msg = 'error:This account does not have admin privileges.';
            } elseif ($type === 'student' && $user['role'] === 'admin') {
                $msg = 'error:Admin accounts must use the Admin Login tab.';
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role']      = $user['role'];
                header("Location: " . ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
                exit();
            }
        } else {
            $msg = 'error:Incorrect password. Please try again.';
        }
    } else {
        $msg = 'error:No account found with that email address.';
    }
    $login_type = $type;
}

$is_error = str_starts_with($msg, 'error:');
$msg_text = $is_error ? substr($msg, 6) : $msg;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Digital Library</title>
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
            max-width: 800px;
            min-height: 420px;
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
            text-align: center;
            justify-content: center;
        }
        .auth-brand::before {
            content: ''; position: absolute; width: 400px; height: 400px;
            background: var(--primary); filter: blur(120px); border-radius: 50%;
            top: -50px; left: -100px; opacity: 0.3; pointer-events: none;
        }
        .auth-logo {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: absolute;
            top: 40px; left: 0; width: 100%;
            z-index: 2;
            text-decoration: none;
            color: #fff;
        }
        .auth-logo ion-icon { color: var(--primary); font-size: 2.2rem; }
        
        .brand-content { position: relative; z-index: 2; margin-top: 20px; }
        .book-icon { font-size: 4rem; filter: drop-shadow(0 15px 30px rgba(59,130,246,0.3)); animation: float 4s ease-in-out infinite; margin-bottom: 15px; display: block; }
        .brand-title { font-size: 2rem; font-weight: 800; font-family: 'Montserrat', sans-serif; line-height: 1.2; margin-bottom: 10px; }
        .brand-title span { color: var(--primary); }
        .brand-desc { font-size: 0.85rem; color: rgba(255,255,255,0.7); line-height: 1.5; max-width: 250px; margin: 0 auto; }
        
        /* Right Form Panel */
        .auth-form-wrapper {
            width: 360px;
            padding: 30px 30px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--bg-card);
            position: relative;
        }
        
        .form-header { margin-bottom: 20px; }
        .form-eyebrow { font-size: 0.75rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; display: inline-block; background: rgba(59,130,246,0.1); padding: 4px 10px; border-radius: 20px; transition: var(--transition); }
        .form-eyebrow.admin { color: var(--danger); background: rgba(239,68,68,0.1); }
        .form-title { font-size: 1.8rem; font-family: 'Montserrat', sans-serif; color: var(--text-main); margin-bottom: 2px; }
        .form-subtitle { color: var(--text-muted); font-size: 0.9rem; }
        
        /* Tab Switcher */
        .role-tabs { display: flex; background: var(--bg-body); border-radius: var(--radius-md); padding: 5px; margin-bottom: 25px; border: 1px solid var(--border-color); }
        .tab-btn { flex: 1; padding: 10px; border: none; background: transparent; border-radius: var(--radius-sm); font-weight: 600; font-family: 'Poppins', sans-serif; font-size: 0.9rem; cursor: pointer; color: var(--text-muted); transition: var(--transition); display: flex; align-items: center; justify-content: center; gap: 8px; }
        .tab-btn.active.student { background: var(--bg-card); color: var(--primary); box-shadow: var(--shadow-sm); }
        .tab-btn.active.admin { background: var(--bg-card); color: var(--danger); box-shadow: var(--shadow-sm); }
        
        .form-control-icon { position:relative; }
        .form-control-icon ion-icon { position:absolute; left:16px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:1.2rem; z-index:10; }
        .form-control-icon .form-control { padding-left: 45px; }
        
        /* Admin Hint */
        .admin-hint { background: var(--warning-bg); border: 1px dashed var(--warning); border-radius: var(--radius-md); padding: 12px 15px; font-size: 0.85rem; color: #92400e; margin-top: 15px; }
        .admin-hint code { background: rgba(255,255,255,0.5); padding: 2px 6px; border-radius: 4px; font-weight: 600; }
        
        .auth-footer { margin-top: 25px; text-align: center; }
        .auth-footer p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 10px; }
        .auth-footer a { color: var(--primary); font-weight: 600; text-decoration: none; }
        .auth-footer a:hover { text-decoration: underline; }
        
        .divider { display: flex; align-items: center; text-align: center; color: var(--text-muted); font-size: 0.85rem; margin: 25px 0; }
        .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 1px solid var(--border-color); }
        .divider:not(:empty)::before { margin-right: 15px; }
        .divider:not(:empty)::after { margin-left: 15px; }

        @media (max-width: 900px) {
            .auth-container { flex-direction: column; max-width: 450px; }
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
                <span class="book-icon">📚</span>
                <h1 class="brand-title">Read. Learn.<br><span>Grow.</span></h1>
                <p class="brand-desc">Your personal portal to a world of curated academic and leisure reading materials.</p>
            </div>
        </div>
        
        <!-- Right Form Side -->
        <div class="auth-form-wrapper">
            <a href="index.php" style="position:absolute; top:25px; left:30px; display:flex; align-items:center; gap:5px; color:var(--text-muted); font-weight:600; font-size:0.85rem; text-decoration:none;">
                <ion-icon name="arrow-back"></ion-icon> Return Home
            </a>
            
            <div class="form-header" style="margin-top:15px;">
                <span class="form-eyebrow <?php echo $login_type === 'admin' ? 'admin' : ''; ?>" id="eyebrow">
                    <?php echo $login_type === 'admin' ? 'Admin Portal' : 'Student Portal'; ?>
                </span>
                <h2 class="form-title">Welcome back</h2>
                <p class="form-subtitle" id="sub-text">Please sign in to access your dashboard.</p>
            </div>
            
            <!-- Type Tabs -->
            <div class="role-tabs">
                <button type="button" class="tab-btn student <?php echo $login_type !== 'admin' ? 'active' : ''; ?>" onclick="switchTab('student')" id="tab-student">
                    <ion-icon name="school-outline"></ion-icon> Student
                </button>
                <button type="button" class="tab-btn admin <?php echo $login_type === 'admin' ? 'active' : ''; ?>" onclick="switchTab('admin')" id="tab-admin">
                    <ion-icon name="shield-checkmark-outline"></ion-icon> Admin
                </button>
            </div>
            
            <?php if ($msg_text): ?>
                <div class="flash flash-error">
                    <ion-icon name="alert-circle" style="font-size:1.5rem;"></ion-icon> 
                    <span><?php echo htmlspecialchars($msg_text); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <input type="hidden" name="login_type" id="login_type_field" value="<?php echo htmlspecialchars($login_type); ?>">
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="form-control-icon">
                        <ion-icon name="mail-outline"></ion-icon>
                        <input type="email" name="email" id="email" class="form-control" placeholder="account@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Security Password</label>
                    <div class="form-control-icon">
                        <ion-icon name="lock-closed-outline"></ion-icon>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn <?php echo $login_type === 'admin' ? 'btn-danger' : 'btn-primary'; ?>" id="submitBtn" style="width:100%; margin-top:10px; padding:15px; font-size:1rem;">
                    <?php echo $login_type === 'admin' ? 'Archive Access Request' : 'Access Library <ion-icon name="arrow-forward"></ion-icon>'; ?>
                </button>
            </form>

            <div class="admin-hint" id="adminHint" style="<?php echo $login_type !== 'admin' ? 'display:none' : ''; ?>">
                <ion-icon name="information-circle"></ion-icon> <strong>Admin Demo Login</strong><br>
                Email: <code>admin@library.com</code> <br> Password: <code>password</code>
            </div>

            <div class="divider">or</div>

            <div class="auth-footer">
                <p>New to Digital Library? <a href="register.php">Create Free Account</a></p>
            </div>
        </div>
    </div>

    <script>
        function switchTab(type) {
            document.getElementById('login_type_field').value = type;
            
            const btn = document.getElementById('submitBtn');
            const eyebrow = document.getElementById('eyebrow');
            
            if (type === 'student') {
                document.getElementById('tab-student').classList.add('active');
                document.getElementById('tab-admin').classList.remove('active');
                
                eyebrow.innerText = 'Student Portal';
                eyebrow.className = 'form-eyebrow';
                
                btn.className = 'btn btn-primary';
                btn.innerHTML = 'Access Library <ion-icon name="arrow-forward"></ion-icon>';
                
                document.getElementById('adminHint').style.display = 'none';
            } else {
                document.getElementById('tab-admin').classList.add('active');
                document.getElementById('tab-student').classList.remove('active');
                
                eyebrow.innerText = 'Admin Portal';
                eyebrow.className = 'form-eyebrow admin';
                
                btn.className = 'btn btn-danger';
                btn.innerHTML = 'Archive Access Request <ion-icon name="checkmark-done"></ion-icon>';
                
                document.getElementById('adminHint').style.display = 'block';
            }
        }
    </script>
</body>
</html>
