<?php
// admin/update_passwordadmin.php
require_once __DIR__ . '/../DBConnect.php';
checkAuth('admin');

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if (isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    $user = getRow($db, "SELECT password_hash FROM users WHERE user_id = ?", "i", $user_id);
    
    if (!verifyPassword($current, $user['password_hash'])) {
        $error = "Current password is incorrect";
    } elseif (strlen($new) < 8) {
        $error = "Password must be at least 8 characters";
    } elseif (!preg_match('/[A-Z]/', $new)) {
        $error = "Password must contain an uppercase letter";
    } elseif (!preg_match('/[a-z]/', $new)) {
        $error = "Password must contain a lowercase letter";
    } elseif (!preg_match('/[0-9]/', $new)) {
        $error = "Password must contain a number";
    } elseif ($new !== $confirm) {
        $error = "Passwords do not match";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        executeQuery($db, "UPDATE users SET password_hash = ? WHERE user_id = ?", "si", $hash, $user_id);
        logActivity($db, $user_id, 'PASSWORD_CHANGE', 'users', $user_id, 'Admin password changed');
        $success = "Password updated successfully!";
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Change Password - Admin</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: #f4f7fc; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%); border: none; border-radius: 0; }
        .container { max-width: 550px; margin: 50px auto; background: white; border-radius: 15px; padding: 40px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: 600; color: #555; margin-bottom: 5px; display: block; }
        .form-control { height: 45px; border: 2px solid #e0e0e0; border-radius: 8px; }
        .form-control:focus { border-color: #667eea; box-shadow: none; }
        .btn-update { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px; border-radius: 25px; width: 100%; font-size: 16px; }
        
        /* Password wrapper styles */
        .password-wrapper { position: relative; }
        .password-wrapper .password-toggle {
            position: absolute;
            right: 12px;
            top: 38px;
            cursor: pointer;
            color: #999;
            z-index: 10;
        }
        .password-wrapper .password-toggle:hover { color: #667eea; }
        
        /* Password strength meter */
        .password-strength { margin-top: 5px; font-size: 12px; }
        .strength-bar { height: 4px; background: #e0e0e0; border-radius: 2px; margin-top: 5px; overflow: hidden; }
        .strength-bar-fill { height: 100%; width: 0; transition: width 0.3s; }
        .strength-weak { background: #dc3545; }
        .strength-medium { background: #ffc107; }
        .strength-strong { background: #28a745; }
        
        /* Requirements */
        .requirements { font-size: 11px; margin-top: 5px; }
        .requirements span { margin-right: 10px; }
        .req-valid { color: #28a745; }
        .req-invalid { color: #dc3545; }
        
        /* Match message */
        .match-success { color: #28a745; }
        .match-error { color: #dc3545; }
        .match-warning { color: #ffc107; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="container">
    <h2 class="text-center"><i class="fa fa-key"></i> Change Password</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" id="passwordForm">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <!-- Current Password -->
        <div class="form-group password-wrapper">
            <label>Current Password</label>
            <input type="password" class="form-control" name="current_password" id="current_password" required>
            <span class="password-toggle" onclick="togglePassword('current_password')">
                <i class="fa fa-eye-slash"></i>
            </span>
        </div>
        
        <!-- New Password -->
        <div class="form-group password-wrapper">
            <label>New Password</label>
            <input type="password" class="form-control" name="new_password" id="new_password" required>
            <span class="password-toggle" onclick="togglePassword('new_password')">
                <i class="fa fa-eye-slash"></i>
            </span>
        </div>
        
        <!-- Password Strength -->
        <div id="password-strength" class="password-strength"></div>
        <div id="requirements" class="requirements"></div>
        
        <!-- Confirm Password -->
        <div class="form-group password-wrapper">
            <label>Confirm New Password</label>
            <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
            <span class="password-toggle" onclick="togglePassword('confirm_password')">
                <i class="fa fa-eye-slash"></i>
            </span>
        </div>
        <div id="match-message" style="font-size: 12px; margin-bottom: 15px;"></div>
        
        <button type="submit" name="submit" class="btn-update" id="updateBtn">
            <i class="fa fa-save"></i> Update Password
        </button>
    </form>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    var input = document.getElementById(fieldId);
    var icon = event.currentTarget.querySelector('i');
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

// Check password strength
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

// Update strength display
function updateStrength(password) {
    const result = checkStrength(password);
    const strengthDiv = document.getElementById('password-strength');
    const requirementsDiv = document.getElementById('requirements');
    
    if (password.length === 0) {
        strengthDiv.innerHTML = '';
        requirementsDiv.innerHTML = '';
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
                           '<span>Password strength: <strong>' + strengthText + '</strong></span>';
    
    requirementsDiv.innerHTML = 
        '<span class="' + (result.checks.length ? 'req-valid' : 'req-invalid') + '">' + (result.checks.length ? '✓' : '✗') + ' 8+ characters</span> | ' +
        '<span class="' + (result.checks.lowercase ? 'req-valid' : 'req-invalid') + '">' + (result.checks.lowercase ? '✓' : '✗') + ' lowercase (a-z)</span> | ' +
        '<span class="' + (result.checks.uppercase ? 'req-valid' : 'req-invalid') + '">' + (result.checks.uppercase ? '✓' : '✗') + ' uppercase (A-Z)</span> | ' +
        '<span class="' + (result.checks.number ? 'req-valid' : 'req-invalid') + '">' + (result.checks.number ? '✓' : '✗') + ' number (0-9)</span>';
}

// Check password match
function checkMatch() {
    const password = document.getElementById('new_password').value;
    const confirm = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('match-message');
    const updateBtn = document.getElementById('updateBtn');
    
    if (confirm.length === 0) {
        matchDiv.innerHTML = '';
        updateBtn.disabled = false;
    } else if (password === confirm) {
        const result = checkStrength(password);
        if (result.strength < 4) {
            matchDiv.innerHTML = '<span class="match-warning"><i class="fa fa-exclamation-triangle"></i> Password is not strong enough</span>';
            updateBtn.disabled = true;
        } else {
            matchDiv.innerHTML = '<span class="match-success"><i class="fa fa-check-circle"></i> Passwords match</span>';
            updateBtn.disabled = false;
        }
    } else {
        matchDiv.innerHTML = '<span class="match-error"><i class="fa fa-times-circle"></i> Passwords do not match</span>';
        updateBtn.disabled = true;
    }
}

// Form validation before submit
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const password = document.getElementById('new_password').value;
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

// Event listeners
document.getElementById('new_password').addEventListener('input', function() {
    updateStrength(this.value);
    checkMatch();
});
document.getElementById('confirm_password').addEventListener('input', checkMatch);

// Initialize
updateStrength('');
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>