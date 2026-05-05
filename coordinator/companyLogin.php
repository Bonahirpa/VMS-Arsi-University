<?php
// coordinator/companyLogin.php - WITH APPROVAL CHECK
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../DBConnect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /VMS2/index.php");
    exit();
}

$error = '';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if (isset($_POST['submit'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Get user with approval status
    $user = getRow($db, 
        "SELECT u.*, c.coordinator_id, c.approved 
         FROM users u 
         JOIN coordinators c ON u.user_id = c.coordinator_id 
         WHERE u.username = ? AND u.role = 'coordinator'", 
        "s", $username
    );
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Check if approved
        if ($user['approved'] == 0) {
            $error = "Your account is pending admin approval. You will be notified once approved.";
        } elseif ($user['status'] != 'active') {
            $error = "Your account is not active. Contact administrator.";
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            executeQuery($db, "UPDATE users SET last_login = NOW() WHERE user_id = ?", "i", $user['user_id']);
            
            header("Location: coordinator_dashboard.php");
            exit();
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Coordinator Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); height: 100vh; display: flex; align-items: center; }
        .login-box { background: white; border-radius: 15px; padding: 40px; max-width: 400px; margin: 0 auto; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .login-box h2 { text-align: center; margin-bottom: 30px; color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-control { height: 45px; border: 2px solid #e0e0e0; border-radius: 8px; }
        .form-control:focus { border-color: #3498db; box-shadow: none; }
        .btn-login { background: #3498db; color: white; width: 100%; padding: 12px; border: none; border-radius: 5px; font-size: 16px; }
        .btn-login:hover { background: #2980b9; }
        
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
        .password-wrapper .password-toggle:hover { color: #3498db; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Coordinator Login</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-group">
                <input type="text" class="form-control" name="username" placeholder="Username" required>
            </div>
            <div class="form-group password-wrapper">
                <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                <span class="password-toggle" onclick="togglePassword()">
                    <i class="fa fa-eye-slash"></i>
                </span>
            </div>
            <button type="submit" name="submit" class="btn-login">Login</button>
        </form>
        <p class="text-center mt-3" style="margin-top: 15px; text-align: center;">
            <a href="forgot_password.php">Forgot Password?</a> | 
            <a href="companySignup.php">Register</a> | 
            <a href="/VMS2/index.php">Home</a>
        </p>
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