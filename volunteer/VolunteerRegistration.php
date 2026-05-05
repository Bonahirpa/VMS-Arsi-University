<?php
// volunteer/VolunteerRegistration.php - WORKING VERSION ✅
ob_start();
require_once __DIR__ . '/../DBConnect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: /VMS2/index.php");
    exit();
}

$error = '';
$success = '';

if (isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $student_id = trim($_POST['student_id']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    $errors = [];
    
    if (empty($student_id)) $errors[] = "Student ID required";
    if (empty($full_name)) $errors[] = "Full name required";
    if (empty($email)) $errors[] = "Email required";
    if (empty($password)) $errors[] = "Password required";
    
    if (!preg_match('/^UGR\/\d{5}\/\d{2}$/', $student_id)) {
        $errors[] = "Student ID must be format: UGR/12345/14";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (strlen($password) < 8) $errors[] = "Password must be 8+ characters";
    if ($password !== $confirm) $errors[] = "Passwords do not match";
    
    $existing = getRow($db, "SELECT user_id FROM users WHERE username = ?", "s", $student_id);
    if ($existing) $errors[] = "Student ID already registered";
    
    $existing = getRow($db, "SELECT user_id FROM users WHERE email = ?", "s", $email);
    if ($existing) $errors[] = "Email already registered";
    
    if (empty($errors)) {
        $db->begin_transaction();
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, full_name, role, status, created_at) 
                                  VALUES (?, ?, ?, ?, 'volunteer', 'active', NOW())");
            $stmt->bind_param("ssss", $student_id, $email, $hash, $full_name);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            $stmt->close();
            
            $stmt = $db->prepare("INSERT INTO volunteers (volunteer_id, student_id, department, phone) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $student_id, $department, $phone);
            $stmt->execute();
            $stmt->close();
            
            $db->commit();
            
            // ============================================
            // NOTIFICATION: New Volunteer (DIRECT INSERT) - WORKING ✅
            // ============================================
            $admin_ids = [11, 13]; // Your admin IDs
            foreach ($admin_ids as $admin_id) {
                $db->query("INSERT INTO notifications (user_id, title, message, type, created_at, is_read) 
                           VALUES ($admin_id, 'New Volunteer', 'New volunteer: $full_name (ID: $student_id)', 'info', NOW(), 0)");
            }
            
            $success = "Registration successful! You can now login.";
            $_POST = [];
            
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
    
    if (!empty($errors)) $error = implode("<br>", $errors);
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Volunteer Registration</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 50px 0; }
        .register-box { background: white; border-radius: 15px; padding: 30px; max-width: 500px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .register-box h2 { text-align: center; margin-bottom: 30px; color: #333; }
        .form-group { margin-bottom: 20px; }
        .btn-register { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; width: 100%; padding: 12px; border: none; border-radius: 5px; font-size: 16px; }
        .btn-register:hover { opacity: 0.9; }
        /* Password strength meter */
.password-strength {
    margin-top: 5px;
    font-size: 12px;
}
.strength-bar {
    height: 4px;
    background: #e0e0e0;
    border-radius: 2px;
    margin-top: 5px;
    overflow: hidden;
}
.strength-bar-fill {
    height: 100%;
    width: 0;
    transition: width 0.3s;
}
.strength-weak { background: #dc3545; }
.strength-medium { background: #ffc107; }
.strength-strong { background: #28a745; }
.requirements {
    font-size: 11px;
    color: #666;
    margin-top: 5px;
}
.requirements ul {
    margin: 5px 0 0 20px;
    padding: 0;
}
.requirements li {
    list-style: none;
    margin: 3px 0;
}
.requirements li.valid { color: #28a745; }
.requirements li.invalid { color: #dc3545; }
    </style>
</head>
<body>
    <div class="register-box">
        <h2><i class="fa fa-handshake-o"></i> Volunteer Registration</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?> <a href="Login.php">Login here</a></div>
        <?php endif; ?>
        
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    
    <div class="form-group">
        <input type="text" class="form-control" name="student_id" placeholder="Student ID (UGR/12345/14)" required
               value="<?php echo $_POST['student_id'] ?? ''; ?>" pattern="UGR\/\d{5}\/\d{2}">
    </div>
    
    <div class="form-group">
        <input type="text" class="form-control" name="full_name" placeholder="Full Name" required
               value="<?php echo $_POST['full_name'] ?? ''; ?>">
    </div>
    
    <div class="form-group">
        <input type="email" class="form-control" name="email" placeholder="Email Address" required
               value="<?php echo $_POST['email'] ?? ''; ?>">
    </div>
    
    <div class="form-group">
        <input type="text" class="form-control" name="phone" placeholder="Phone (Optional)"
               value="<?php echo $_POST['phone'] ?? ''; ?>">
    </div>
    
    <div class="form-group">
        <select class="form-control" name="department">
            <option value="">Select Department (Optional)</option>
            <option value="Computer Science">Computer Science</option>
            <option value="Information Technology">Information Technology</option>
            <option value="Information Science">Information Science</option>
            <option value="Business Administration">Business Administration</option>
            <option value="Accounting and Finance">Accounting and Finance</option>
            <option value="Economics">Economics</option>
            <option value="Management">Management</option>
            <option value="Public Administration">Public Administration</option>
            <option value="Biology">Biology</option>
            <option value="Chemistry">Chemistry</option>
            <option value="Physics">Physics</option>
            <option value="Mathematics">Mathematics</option>
            <option value="Statistics">Statistics</option>
            <option value="Sport Science">Sport Science</option>
            <option value="Psychology">Psychology</option>
            <option value="Sociology">Sociology</option>
            <option value="Social Work">Social Work</option>
            <option value="Law">Law</option>
            <option value="Medicine">Medicine</option>
            <option value="Pharmacy">Pharmacy</option>
            <option value="Nursing">Nursing</option>
            <option value="Public Health">Public Health</option>
            <option value="Agriculture">Agriculture</option>
            <option value="Veterinary Medicine">Veterinary Medicine</option>
            <option value="Journalism and Communication">Journalism and Communication</option>
            <option value="Other">Other</option>
        </select>
    </div>
    
    <!-- PASSWORD FIELD WITH EYE ICON -->
    <div class="form-group password-wrapper" style="position: relative;">
        <input type="password" class="form-control" name="password" id="password" placeholder="Password (min. 8 chars)" required>
        <span class="password-toggle" style="position: absolute; right: 12px; top: 12px; cursor: pointer; color: #999;">
            <i class="fa fa-eye-slash"></i>
        </span>
    </div>
    <div id="password-strength" class="password-strength" style="margin-bottom: 10px;"></div>
    
    <!-- CONFIRM PASSWORD FIELD WITH EYE ICON -->
    <div class="form-group password-wrapper" style="position: relative;">
        <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
        <span class="password-toggle" style="position: absolute; right: 12px; top: 12px; cursor: pointer; color: #999;">
            <i class="fa fa-eye-slash"></i>
        </span>
    </div>
    <div id="password-match" style="font-size: 12px; margin-bottom: 10px;"></div>
    
    <div class="checkbox">
        <label>
            <input type="checkbox" name="terms" required> I agree to the Terms and Conditions
        </label>
    </div>
    
    <button type="submit" name="submit" class="btn-register" id="registerBtn">Register</button>
</form>
        
        <p class="text-center" style="margin-top: 20px;">
            Already have an account? <a href="Login.php">Login here</a>
        </p>
    </div>
    <script>
// Password Hide/Show Toggle
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

// Password Strength Checker
function checkPasswordStrength(password) {
    let strength = 0;
    let messages = [];
    
    if (password.length >= 8) { strength++; } else { messages.push('At least 8 characters'); }
    if (password.match(/[a-z]+/)) { strength++; } else { messages.push('Lowercase letter'); }
    if (password.match(/[A-Z]+/)) { strength++; } else { messages.push('Uppercase letter'); }
    if (password.match(/[0-9]+/)) { strength++; } else { messages.push('Number'); }
    
    return { strength: strength, messages: messages };
}

function updateStrengthDisplay(password) {
    const result = checkPasswordStrength(password);
    const strengthDiv = document.getElementById('password-strength');
    
    if (password.length === 0) {
        strengthDiv.innerHTML = '';
        return;
    }
    
    let strengthText = '';
    let strengthClass = '';
    
    if (result.strength <= 2) {
        strengthText = 'Weak password';
        strengthClass = 'strength-weak';
    } else if (result.strength <= 3) {
        strengthText = 'Medium password';
        strengthClass = 'strength-medium';
    } else {
        strengthText = 'Strong password';
        strengthClass = 'strength-strong';
    }
    
    let requirementsHtml = '<div class="requirements"><ul>';
    requirementsHtml += '<li class="' + (password.length >= 8 ? 'valid' : 'invalid') + '">✓ ' + (password.length >= 8 ? '' : '✗ ') + 'Minimum 8 characters</li>';
    requirementsHtml += '<li class="' + (password.match(/[a-z]/) ? 'valid' : 'invalid') + '">✓ ' + (password.match(/[a-z]/) ? '' : '✗ ') + 'Lowercase letter (a-z)</li>';
    requirementsHtml += '<li class="' + (password.match(/[A-Z]/) ? 'valid' : 'invalid') + '">✓ ' + (password.match(/[A-Z]/) ? '' : '✗ ') + 'Uppercase letter (A-Z)</li>';
    requirementsHtml += '<li class="' + (password.match(/[0-9]/) ? 'valid' : 'invalid') + '">✓ ' + (password.match(/[0-9]/) ? '' : '✗ ') + 'Number (0-9)</li>';
    requirementsHtml += '</ul></div>';
    
    strengthDiv.innerHTML = '<div class="strength-bar"><div class="strength-bar-fill ' + strengthClass + '" style="width: ' + (result.strength * 25) + '%;"></div></div>' +
                           '<span>' + strengthText + '</span>' + requirementsHtml;
}

// Password Match Checker
function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('password-match');
    const registerBtn = document.getElementById('registerBtn');
    
    if (confirm.length === 0) {
        matchDiv.innerHTML = '';
        registerBtn.disabled = false;
    } else if (password === confirm) {
        matchDiv.innerHTML = '<i class="fa fa-check" style="color: green;"></i> Passwords match';
        matchDiv.style.color = 'green';
        
        // Check if password is strong enough
        const strength = checkPasswordStrength(password);
        if (strength.strength < 4) {
            registerBtn.disabled = true;
            matchDiv.innerHTML += '<br><small style="color: orange;">⚠️ Please make password stronger (all requirements met)</small>';
        } else {
            registerBtn.disabled = false;
        }
    } else {
        matchDiv.innerHTML = '<i class="fa fa-times" style="color: red;"></i> Passwords do not match';
        matchDiv.style.color = 'red';
        registerBtn.disabled = true;
    }
}

// Form validation before submit
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const strength = checkPasswordStrength(password);
    
    if (strength.strength < 4) {
        e.preventDefault();
        alert('Please use a stronger password. It must contain:\n- At least 8 characters\n- Uppercase letter\n- Lowercase letter\n- Number');
        return false;
    }
    
    const passwordVal = document.getElementById('password').value;
    const confirmVal = document.getElementById('confirm_password').value;
    
    if (passwordVal !== confirmVal) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
    
    return true;
});

// Event listeners
document.getElementById('password').addEventListener('input', function() {
    updateStrengthDisplay(this.value);
    checkPasswordMatch();
});
document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
</script>
</body>
</html>
<?php ob_end_flush(); ?>