<?php
// coordinator/companySignup.php - CLEAN WORKING VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(60);
ob_start();
require_once __DIR__ . '/../DBConnect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /VMS2/index.php");
    exit();
}

$error = '';
$success = '';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if (isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $college = trim($_POST['college'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    $errors = [];
    
    if (empty($username)) $errors[] = "Username is required";
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    if (empty($college)) $errors[] = "College is required";
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match";
    }
    
    $existing = getRow($db, "SELECT user_id FROM users WHERE username = ?", "s", $username);
    if ($existing) $errors[] = "Username already taken";
    
    $existing = getRow($db, "SELECT user_id FROM users WHERE email = ?", "s", $email);
    if ($existing) $errors[] = "Email already registered";
    
    if (empty($errors)) {
        $db->begin_transaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, full_name, role, status, created_at) 
                                  VALUES (?, ?, ?, ?, 'coordinator', 'inactive', NOW())");
            $stmt->bind_param("ssss", $username, $email, $hash, $full_name);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            $stmt->close();
            
            $stmt = $db->prepare("INSERT INTO coordinators (coordinator_id, college, department, phone, approved, email_sent) 
                                  VALUES (?, ?, ?, ?, 0, 0)");
            $stmt->bind_param("isss", $user_id, $college, $department, $phone);
            $stmt->execute();
            $stmt->close();
            
            $db->commit();
            
            // NOTIFICATION: New Coordinator
            $admin_ids = [11, 13];
            foreach ($admin_ids as $admin_id) {
                $db->query("INSERT INTO notifications (user_id, title, message, type, created_at, is_read) 
                           VALUES ($admin_id, 'New Coordinator', 'New coordinator: $full_name from $college needs approval', 'warning', NOW(), 0)");
            }
            
            $success = "Registration successful! Your account is pending admin approval.";
            $_POST = [];
            
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Coordinator Registration - VMS</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); 
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            padding: 40px 0;
        }
        .register-box { 
            background: white; 
            border-radius: 15px; 
            padding: 40px; 
            max-width: 600px; 
            margin: 0 auto; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .pending-badge { 
            background: #fff3cd; 
            color: #856404; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 25px; 
            text-align: center; 
            border-left: 4px solid #ffc107;
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
        .btn-primary { 
            background: #3498db; 
            border: none; 
            height: 50px; 
            font-size: 16px; 
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .alert { 
            border-radius: 8px; 
            margin-bottom: 20px;
        }
        .note { 
            background: #e3f2fd; 
            padding: 10px; 
            border-radius: 5px; 
            margin-top: 15px;
            font-size: 13px;
        }
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
.password-strength { margin-top: 5px; font-size: 12px; }
.strength-bar { height: 4px; background: #e0e0e0; border-radius: 2px; margin-top: 5px; overflow: hidden; }
.strength-bar-fill { height: 100%; width: 0; transition: width 0.3s; }
.strength-weak { background: #dc3545; }
.strength-medium { background: #ffc107; }
.strength-strong { background: #28a745; }
.requirements { font-size: 11px; margin-top: 5px; }
.req-valid { color: #28a745; }
.req-invalid { color: #dc3545; }
.match-success { color: #28a745; }
.match-error { color: #dc3545; }
.match-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="register-box">
        <h2 class="text-center">Coordinator Registration</h2>
        
        <div class="pending-badge">
            <i class="fa fa-info-circle"></i> 
            <strong>Note:</strong> All coordinator accounts require admin approval.
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i> <?php echo $success; ?>
                <hr>
                <a href="/VMS2/index.php" class="btn btn-success btn-sm">Return to Home</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <input type="text" class="form-control" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="full_name" placeholder="Full Name" required>
                </div>
                <div class="form-group">
                    <input type="email" class="form-control" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="college" placeholder="College/Institution" required>
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="department" placeholder="Department (Optional)">
                </div>
                <div class="form-group">
                    <input type="text" class="form-control" name="phone" placeholder="Phone (Optional)">
                </div>
                <!-- Password Field -->
<div class="form-group password-wrapper" style="position: relative;">
    <input type="password" class="form-control" name="password" id="password" placeholder="Password (min. 8 chars)" required>
    <span class="password-toggle" style="position: absolute; right: 12px; top: 12px; cursor: pointer; color: #999;">
        <i class="fa fa-eye-slash"></i>
    </span>
</div>
<div id="password-strength" class="password-strength" style="margin-bottom: 10px;"></div>

<!-- Confirm Password Field -->
<div class="form-group password-wrapper" style="position: relative;">
    <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
    <span class="password-toggle" style="position: absolute; right: 12px; top: 12px; cursor: pointer; color: #999;">
        <i class="fa fa-eye-slash"></i>
    </span>
</div>
<div id="password-match" style="font-size: 12px; margin-bottom: 10px;"></div>
                
                <button type="submit" name="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            
            <div class="note">
                <i class="fa fa-info-circle"></i> 
                <strong>Note:</strong> Admins will be notified of your registration.
            </div>
            
            <p class="text-center" style="margin-top: 15px;">
                Already have an account? <a href="companyLogin.php">Login here</a>
            </p>
        <?php endif; ?>
    </div>
    <script>
// Toggle password visibility
document.querySelectorAll('.password-toggle').forEach(function(toggle) {
    toggle.addEventListener('click', function() {
        var input = this.parentElement.querySelector('input');
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
});

// Password strength checker
function checkStrength(password) {
    let strength = 0;
    let checks = {
        length: password.length >= 8,
        lowercase: /[a-z]/.test(password),
        uppercase: /[A-Z]/.test(password),
        number: /[0-9]/.test(password)
    };
    if (checks.length) strength++;
    if (checks.lowercase) strength++;
    if (checks.uppercase) strength++;
    if (checks.number) strength++;
    return { strength: strength, checks: checks };
}

function updateStrength(password) {
    const result = checkStrength(password);
    const strengthDiv = document.getElementById('password-strength');
    
    if (password.length === 0) {
        strengthDiv.innerHTML = '';
        return;
    }
    
    let strengthText = '', strengthClass = '', width = 0;
    if (result.strength <= 2) {
        strengthText = 'Weak';
        strengthClass = 'strength-weak';
        width = 33;
    } else if (result.strength <= 3) {
        strengthText = 'Medium';
        strengthClass = 'strength-medium';
        width = 66;
    } else {
        strengthText = 'Strong';
        strengthClass = 'strength-strong';
        width = 100;
    }
    
    strengthDiv.innerHTML = '<div class="strength-bar"><div class="strength-bar-fill ' + strengthClass + '" style="width: ' + width + '%;"></div></div>' +
                           '<span>Strength: ' + strengthText + '</span>' +
                           '<div class="requirements">' +
                           '<span class="' + (result.checks.length ? 'req-valid' : 'req-invalid') + '">' + (result.checks.length ? '✓' : '✗') + ' 8+ chars</span> | ' +
                           '<span class="' + (result.checks.lowercase ? 'req-valid' : 'req-invalid') + '">' + (result.checks.lowercase ? '✓' : '✗') + ' a-z</span> | ' +
                           '<span class="' + (result.checks.uppercase ? 'req-valid' : 'req-invalid') + '">' + (result.checks.uppercase ? '✓' : '✗') + ' A-Z</span> | ' +
                           '<span class="' + (result.checks.number ? 'req-valid' : 'req-invalid') + '">' + (result.checks.number ? '✓' : '✗') + ' 0-9</span>' +
                           '</div>';
}

function checkMatch() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('password-match');
    const submitBtn = document.querySelector('button[type="submit"]');
    
    if (confirm.length === 0) {
        matchDiv.innerHTML = '';
        submitBtn.disabled = false;
    } else if (password === confirm) {
        const result = checkStrength(password);
        if (result.strength < 4) {
            matchDiv.innerHTML = '<span class="match-warning">⚠️ Password is not strong enough</span>';
            submitBtn.disabled = true;
        } else {
            matchDiv.innerHTML = '<span class="match-success"><i class="fa fa-check"></i> Passwords match</span>';
            submitBtn.disabled = false;
        }
    } else {
        matchDiv.innerHTML = '<span class="match-error"><i class="fa fa-times"></i> Passwords do not match</span>';
        submitBtn.disabled = true;
    }
}

document.getElementById('password').addEventListener('input', function() {
    updateStrength(this.value);
    checkMatch();
});
document.getElementById('confirm_password').addEventListener('input', checkMatch);

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const result = checkStrength(password);
    if (result.strength < 4) {
        e.preventDefault();
        alert('Please use a stronger password!\n\nRequirements:\n• Minimum 8 characters\n• Uppercase letter (A-Z)\n• Lowercase letter (a-z)\n• Number (0-9)');
        return false;
    }
    if (password !== document.getElementById('confirm_password').value) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
    return true;
});
</script>
</body>
</html>
<?php ob_end_flush(); ?>