<?php
// admin/admin_login.php
require_once __DIR__ . '/../DBConnect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /VMS2/index.php");
    exit();
}

$error = '';

if (isset($_POST['submit'])) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $user = getRow($db, 
        "SELECT u.*, a.admin_id FROM users u 
         JOIN admins a ON u.user_id = a.admin_id 
         WHERE u.username = ? AND u.role = 'admin'", 
        "s", $username
    );
    
    if ($user && verifyPassword($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        executeQuery($db, "UPDATE users SET last_login = NOW() WHERE user_id = ?", "i", $user['user_id']);
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid credentials";
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%); height: 100vh; display: flex; align-items: center; }
        .login-box { background: white; border-radius: 15px; padding: 40px; max-width: 400px; margin: 0 auto; width: 100%; }
        .login-box h2 { text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-control { height: 45px; border: 2px solid #e0e0e0; border-radius: 8px; }
        .form-control:focus { border-color: #667eea; box-shadow: none; }
        .btn-login { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; width: 100%; padding: 12px; border: none; border-radius: 5px; }
        
        /* Password wrapper styles */
        .password-wrapper { position: relative; }
        .password-wrapper .password-toggle {
            position: absolute;
            right: 12px;
            top: 12px;
            cursor: pointer;
            color: #999;
            z-index: 10;
        }
        .password-wrapper .password-toggle:hover { color: #667eea; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Login</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group"><input type="text" class="form-control" name="username" placeholder="Username" required></div>
            <div class="form-group password-wrapper">
                <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                <span class="password-toggle" onclick="togglePassword()">
                    <i class="fa fa-eye-slash"></i>
                </span>
            </div>
            <button type="submit" name="submit" class="btn-login">Login</button>
            <a href="/VMS2/index.php" style="display: block; text-align: center; margin-top: 15px;">Back Home</a>
        </form>
    </div>

    <script>
    function togglePassword() {
        var input = document.getElementById('password');
        var icon = document.querySelector('.password-toggle i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }
    </script>
</body>
</html>