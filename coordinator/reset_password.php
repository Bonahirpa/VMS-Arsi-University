<?php
// coordinator/reset_password.php - DEBUG VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../DBConnect.php';
require_once __DIR__ . '/../forgot_password_functions.php';

$message = '';
$error = '';
$show_form = false;
$token = $_GET['token'] ?? '';
$debug_info = [];

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Debug: Show token being used
$debug_info['token_received'] = $token;

// Validate token
if (!empty($token)) {
    // Debug: Check token in database
    $token_check = getRow($db,
        "SELECT * FROM password_resets WHERE token = ?",
        "s", $token
    );
    
    if ($token_check) {
        $debug_info['token_found'] = true;
        $debug_info['token_expires'] = $token_check['expires_at'];
        $debug_info['token_used'] = $token_check['used'];
        $debug_info['token_role'] = $token_check['role'];
        $debug_info['current_time'] = date('Y-m-d H:i:s');
        
        // Check if expired
        if (strtotime($token_check['expires_at']) < time()) {
            $debug_info['token_expired'] = true;
            $error = "Token has expired. Please request a new password reset.";
        } elseif ($token_check['used'] == 1) {
            $debug_info['token_already_used'] = true;
            $error = "This token has already been used. Please request a new password reset.";
        } elseif ($token_check['role'] != 'coordinator') {
            $debug_info['wrong_role'] = true;
            $error = "Invalid token for coordinator login.";
        } else {
            $debug_info['token_valid'] = true;
            $show_form = true;
        }
    } else {
        $debug_info['token_found'] = false;
        $error = "Invalid token. Please request a new password reset.";
    }
} else {
    $error = "No reset token provided.";
}

// Handle password reset
// Handle password reset
if (isset($_POST['submit']) && $show_form) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $token = $_POST['token'] ?? '';
    
    // Start output buffering to catch debug messages
    ob_start();
    
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        // Validate token again
        $reset = validateResetToken($db, $token, 'coordinator');
        
        if ($reset) {
            $debug_info['password_update_user'] = $reset['user_id'];
            
            // First, try a direct SQL update as a test
            $test_hash = password_hash($password, PASSWORD_DEFAULT);
            $test_sql = "UPDATE users SET password_hash = '" . $test_hash . "' WHERE user_id = " . $reset['user_id'];
            // Don't run this - just for debug
            $debug_info['test_sql'] = $test_sql;
            
            // Use the function
            $updated = updateUserPassword($db, $reset['user_id'], $password);
            $debug_output = ob_get_clean();
            
            if ($updated) {
                // Mark token as used
                $token_updated = markTokenAsUsed($db, $token);
                
                if ($token_updated) {
                    logActivity($db, $reset['user_id'], 'PASSWORD_RESET', 'users', $reset['user_id'], 'Password reset via email');
                    $message = "Your password has been reset successfully!";
                    $show_form = false;
                } else {
                    $error = "Password updated but failed to mark token as used.";
                }
            } else {
                $error = "Failed to update password in database.";
            }
        } else {
            $error = "Token validation failed.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - Coordinator</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); 
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        .reset-box { 
            background: white; 
            border-radius: 15px; 
            padding: 40px; 
            max-width: 600px; 
            margin: 0 auto; 
            width: 100%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .debug-box {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
        }
        .debug-box pre {
            margin: 0;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="reset-box">
        <h2 class="text-center"><i class="fa fa-key"></i> Reset Password</h2>
        
        <!-- Debug Information -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i> <?php echo $message; ?>
                <hr>
                <a href="companyLogin.php" class="btn btn-success btn-sm">Go to Login</a>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
                <hr>
                <a href="forgot_password.php" class="btn btn-primary btn-sm">Request New Reset</a>
                <a href="companyLogin.php" class="btn btn-default btn-sm">Back to Login</a>
            </div>
        <?php endif; ?>
        
        <?php if ($show_form): ?>
            <form method="POST" id="resetForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" class="form-control" name="password" id="password" 
                           placeholder="Enter new password" required>
                </div>
                
                <div class="password-strength">
                    <div class="strength-bar">
                        <div class="strength-bar-fill" id="strengthBar"></div>
                    </div>
                    <span id="strengthText">Enter password</span>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" 
                           placeholder="Confirm new password" required>
                </div>
                <div id="matchMessage" style="font-size: 12px; margin-bottom: 10px;"></div>
                
                <button type="submit" name="submit" class="btn btn-primary btn-block">
                    <i class="fa fa-save"></i> Reset Password
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        // Password strength checker
        document.getElementById('password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            
            strengthBar.className = 'strength-bar-fill';
            
            if (password.length === 0) {
                strengthBar.style.width = '0';
                strengthText.innerHTML = 'Enter password';
            } else if (strength <= 2) {
                strengthBar.style.width = '33%';
                strengthBar.classList.add('strength-weak');
                strengthText.innerHTML = 'Weak password';
            } else if (strength <= 3) {
                strengthBar.style.width = '66%';
                strengthBar.classList.add('strength-medium');
                strengthText.innerHTML = 'Medium password';
            } else {
                strengthBar.style.width = '100%';
                strengthBar.classList.add('strength-strong');
                strengthText.innerHTML = 'Strong password';
            }
        });
        
        // Password match checker
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            const matchMessage = document.getElementById('matchMessage');
            
            if (confirm.length === 0) {
                matchMessage.innerHTML = '';
            } else if (password === confirm) {
                matchMessage.innerHTML = '<i class="fa fa-check" style="color: green;"></i> Passwords match';
                matchMessage.style.color = 'green';
            } else {
                matchMessage.innerHTML = '<i class="fa fa-times" style="color: red;"></i> Passwords do not match';
                matchMessage.style.color = 'red';
            }
        });
    </script>
</body>
</html>