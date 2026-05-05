<?php
// volunteer/Login.php
require_once __DIR__ . '/../DBConnect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /VMS2/index.php");
    exit();
}

$error = '';

if (isset($_POST['submit'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die("Invalid CSRF token");
    }
    
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $user = getRow($db, 
    "SELECT u.*, v.volunteer_id FROM users u 
     LEFT JOIN volunteers v ON u.user_id = v.volunteer_id 
     WHERE u.username = ? AND u.role = 'volunteer'", 
    "s", $username
);
    
    if ($user && verifyPassword($password, $user['password_hash'])) {
        if ($user['status'] == 'active') {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            executeQuery($db, "UPDATE users SET last_login = NOW() WHERE user_id = ?", "i", $user['user_id']);
            logActivity($db, $user['user_id'], 'LOGIN', 'users', $user['user_id'], 'Volunteer logged in');
            
            header("Location: Volunteer.php");
            exit();
        } else {
            $error = "Account not active";
        }
    } else {
        $error = "Invalid username or password";
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Volunteer Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100vh; display: flex; align-items: center; }
        .login-box { background: white; border-radius: 15px; padding: 40px; max-width: 400px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; }
        .login-box h2 { text-align: center; margin-bottom: 30px; color: #333; }
        .btn-login { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; width: 100%; padding: 12px; border: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2><i class="fa fa-handshake-o"></i> Volunteer Login</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <input type="text" class="form-control" name="username" placeholder="Student ID / Username" required>
            </div>
            
            <div class="form-group password-wrapper" style="position: relative;">
    <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
    <span class="password-toggle" style="position: absolute; right: 12px; top: 12px; cursor: pointer; color: #999;">
        <i class="fa fa-eye-slash"></i>
    </span>
</div>
            
            <button type="submit" name="submit" class="btn-login">Login</button>
        </form>
        
    
<p class="text-center" style="margin-top: 20px;">
    <a href="forgot_password.php">Forgot Password?</a> | 
    <a href="VolunteerRegistration.php">Register</a> | 
    <a href="/VMS2/index.php">Home</a>
</p>
    </div>
    <script>
document.querySelector('.password-toggle')?.addEventListener('click', function() {
    var input = document.getElementById('password');
    var icon = this.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    }
});
</script>
</body>
</html>