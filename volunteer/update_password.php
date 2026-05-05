<?php
// volunteer/update_password.php
require_once __DIR__ . '/../DBConnect.php';
checkAuth('volunteer');

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if (isset($_POST['submit'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    $user = getRow($db, "SELECT password_hash FROM users WHERE user_id = ?", "i", $user_id);
    
    if (!verifyPassword($current, $user['password_hash'])) {
        $error = "Current password is incorrect";
    } elseif (strlen($new) < 8) {
        $error = "New password must be at least 8 characters";
    } elseif ($new !== $confirm) {
        $error = "Passwords do not match";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        executeQuery($db, "UPDATE users SET password_hash = ? WHERE user_id = ?", "si", $hash, $user_id);
        $success = "Password updated successfully!";
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        .password-strength { margin-top: 5px; font-size: 12px; }
.strength-bar { height: 4px; background: #e0e0e0; border-radius: 2px; margin-top: 5px; overflow: hidden; }
.strength-bar-fill { height: 100%; width: 0; transition: width 0.3s; }
.strength-weak { background: #dc3545; }
.strength-medium { background: #ffc107; }
.strength-strong { background: #28a745; }
.req-valid { color: #28a745; }
.req-invalid { color: #dc3545; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container" style="margin-top: 30px; max-width: 500px;">
    <h2>Change Password</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    
    <div class="form-group password-wrapper" style="position: relative;">
        <label>Current Password</label>
        <input type="password" class="form-control" name="current_password" id="current_password" required>
        <span class="password-toggle" style="position: absolute; right: 12px; top: 38px; cursor: pointer; color: #999;">
            <i class="fa fa-eye-slash"></i>
        </span>
    </div>
    
    <div class="form-group password-wrapper" style="position: relative;">
        <label>New Password</label>
        <input type="password" class="form-control" name="new_password" id="new_password" required>
        <span class="password-toggle" style="position: absolute; right: 12px; top: 38px; cursor: pointer; color: #999;">
            <i class="fa fa-eye-slash"></i>
        </span>
    </div>
    <div id="password-strength" style="margin-bottom: 10px;"></div>
    <div id="requirements" style="font-size: 11px; margin-bottom: 10px;"></div>
    
    <div class="form-group password-wrapper" style="position: relative;">
        <label>Confirm New Password</label>
        <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
        <span class="password-toggle" style="position: absolute; right: 12px; top: 38px; cursor: pointer; color: #999;">
            <i class="fa fa-eye-slash"></i>
        </span>
    </div>
    <div id="match-message" style="font-size: 12px; margin-bottom: 10px;"></div>
    
    <button type="submit" name="submit" class="btn btn-primary" id="updateBtn">Update Password</button>
    <a href="Volunteer.php" class="btn btn-default">Cancel</a>
</form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
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
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    return strength;
}

function updateStrength(password) {
    const strength = checkStrength(password);
    const strengthDiv = document.getElementById('password-strength');
    const requirementsDiv = document.getElementById('requirements');
    
    if (password.length === 0) {
        strengthDiv.innerHTML = '';
        requirementsDiv.innerHTML = '';
        return;
    }
    
    let strengthText = '', strengthClass = '', width = 0;
    if (strength <= 2) { strengthText = 'Weak'; strengthClass = 'strength-weak'; width = 33; }
    else if (strength <= 3) { strengthText = 'Medium'; strengthClass = 'strength-medium'; width = 66; }
    else { strengthText = 'Strong'; strengthClass = 'strength-strong'; width = 100; }
    
    strengthDiv.innerHTML = '<div class="strength-bar"><div class="strength-bar-fill ' + strengthClass + '" style="width: ' + width + '%;"></div></div>' +
                           '<span>Password strength: ' + strengthText + '</span>';
    
    requirementsDiv.innerHTML = '<small>' +
        '<span class="' + (password.length >= 8 ? 'req-valid' : 'req-invalid') + '">✓ ' + (password.length >= 8 ? '✓' : '✗') + ' 8+ characters</span> | ' +
        '<span class="' + (password.match(/[a-z]/) ? 'req-valid' : 'req-invalid') + '">✓ ' + (password.match(/[a-z]/) ? '✓' : '✗') + ' lowercase</span> | ' +
        '<span class="' + (password.match(/[A-Z]/) ? 'req-valid' : 'req-invalid') + '">✓ ' + (password.match(/[A-Z]/) ? '✓' : '✗') + ' uppercase</span> | ' +
        '<span class="' + (password.match(/[0-9]/) ? 'req-valid' : 'req-invalid') + '">✓ ' + (password.match(/[0-9]/) ? '✓' : '✗') + ' number</span>' +
        '</small>';
}

function checkMatch() {
    const password = document.getElementById('new_password').value;
    const confirm = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('match-message');
    const updateBtn = document.getElementById('updateBtn');
    
    if (confirm.length === 0) {
        matchDiv.innerHTML = '';
        updateBtn.disabled = false;
    } else if (password === confirm) {
        const strength = checkStrength(password);
        if (strength < 4) {
            matchDiv.innerHTML = '<span style="color: orange;">⚠️ Password is not strong enough</span>';
            updateBtn.disabled = true;
        } else {
            matchDiv.innerHTML = '<span style="color: green;"><i class="fa fa-check"></i> Passwords match</span>';
            updateBtn.disabled = false;
        }
    } else {
        matchDiv.innerHTML = '<span style="color: red;"><i class="fa fa-times"></i> Passwords do not match</span>';
        updateBtn.disabled = true;
    }
}

document.getElementById('new_password').addEventListener('input', function() {
    updateStrength(this.value);
    checkMatch();
});
document.getElementById('confirm_password').addEventListener('input', checkMatch);

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('new_password').value;
    if (checkStrength(password) < 4) {
        e.preventDefault();
        alert('Password must contain:\n- At least 8 characters\n- Uppercase letter\n- Lowercase letter\n- Number');
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