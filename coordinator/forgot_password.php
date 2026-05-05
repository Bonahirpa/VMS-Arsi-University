<?php
// coordinator/forgot_password.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../DBConnect.php';
require_once __DIR__ . '/../forgot_password_functions.php';

$message = '';
$error = '';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if (isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if coordinator exists
        $user = getRow($db, 
            "SELECT u.user_id, u.full_name, u.email, u.username 
             FROM users u 
             JOIN coordinators c ON u.user_id = c.coordinator_id 
             WHERE u.email = ? AND u.role = 'coordinator'", 
            "s", $email
        );
        
        if ($user) {
            // Generate token
            $token = generateResetToken();
            
            // Store token
            storeResetToken($db, $user['user_id'], $email, $token, 'coordinator');
            
            // Send email
            $email_sent = sendResetEmail($db, $email, $token, 'coordinator');
            
            if ($email_sent) {
                $message = "Password reset instructions have been sent to your email address.";
            } else {
                $error = "Failed to send email. Please try again later.";
                error_log("Failed to send reset email to: $email");
            }
        } else {
            // Don't reveal if email exists or not (security)
            $message = "If your email is registered, you will receive password reset instructions.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - Coordinator</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); 
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .reset-box { 
            background: white; 
            border-radius: 15px; 
            padding: 40px; 
            max-width: 450px; 
            margin: 0 auto; 
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .reset-box h2 { 
            text-align: center; 
            margin-bottom: 30px; 
            color: #2c3e50;
        }
        .form-group { margin-bottom: 20px; }
        .form-control { 
            height: 45px; 
            border: 2px solid #e0e0e0; 
            border-radius: 8px;
            padding-left: 15px;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: none;
        }
        .btn-reset { 
            background: #3498db; 
            color: white; 
            border: none; 
            height: 50px; 
            font-size: 16px; 
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
        }
        .btn-reset:hover {
            background: #2980b9;
        }
        .alert { 
            border-radius: 8px; 
            margin-bottom: 20px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #666;
            text-decoration: none;
        }
        .back-link a:hover {
            color: #3498db;
        }
        .info-text {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="reset-box">
        <h2><i class="fa fa-lock"></i> Forgot Password</h2>
        
        <div class="info-text">
            Enter your email address and we'll send you instructions to reset your password.
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <input type="email" class="form-control" name="email" placeholder="Your Email Address" required>
            </div>
            
            <button type="submit" name="submit" class="btn-reset">
                <i class="fa fa-paper-plane"></i> Send Reset Instructions
            </button>
        </form>
        
        <div class="back-link">
            <a href="companyLogin.php"><i class="fa fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>