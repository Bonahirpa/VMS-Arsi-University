<?php
// coordinator/editcompany.php
require_once __DIR__ . '/../DBConnect.php';
checkAuth('coordinator');
// Check if user is logged in as coordinator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'coordinator') {
    header("Location: /VMS2/coordinator/companyLogin.php");
    exit();
}

// Check if coordinator is approved
$user_id = $_SESSION['user_id'];
$check = getRow($db, "SELECT approved FROM coordinators WHERE coordinator_id = ?", "i", $user_id);
if (!$check || $check['approved'] == 0) {
    session_destroy();
    header("Location: /VMS2/coordinator/companyLogin.php?error=pending");
    exit();
}
$user_id = $_SESSION['user_id'];

$coordinator = getRow($db,
    "SELECT u.*, c.college, c.department, c.phone FROM users u JOIN coordinators c ON u.user_id = c.coordinator_id WHERE u.user_id = ?",
    "i", $user_id
);

if (!$coordinator) die("Coordinator not found");

if (isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $college = sanitize($_POST['college']);
    $department = sanitize($_POST['department']);
    
    $errors = [];
    if (empty($full_name)) $errors[] = "Full name required";
    if (empty($email)) $errors[] = "Email required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email";
    if (empty($college)) $errors[] = "College required";
    
    if (empty($errors)) {
        $db->begin_transaction();
        try {
            executeQuery($db, "UPDATE users SET full_name = ?, email = ? WHERE user_id = ?", "ssi", $full_name, $email, $user_id);
            executeQuery($db, "UPDATE coordinators SET college = ?, department = ?, phone = ? WHERE coordinator_id = ?", "sssi", $college, $department, $phone, $user_id);
            $db->commit();
            $_SESSION['full_name'] = $full_name;
            header("Location: CompanyProfile.php?success=1");
            exit();
        } catch (Exception $e) {
            $db->rollback();
            $error = "Update failed";
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        body { background: #f4f7fc; }
        .navbar { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); }
        .edit-container { max-width: 600px; margin: 30px auto; background: white; border-radius: 15px; padding: 30px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbarcompany.php'; ?>

<div class="edit-container">
    <h2>Edit Profile</h2>
    
    <?php if (isset($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="form-group">
            <label>Username</label>
            <input type="text" class="form-control" value="<?php echo sanitize($coordinator['username']); ?>" readonly>
        </div>
        
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" class="form-control" name="full_name" value="<?php echo sanitize($coordinator['full_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo sanitize($coordinator['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Phone</label>
            <input type="text" class="form-control" name="phone" value="<?php echo sanitize($coordinator['phone'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label>College</label>
            <input type="text" class="form-control" name="college" value="<?php echo sanitize($coordinator['college']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Department</label>
            <input type="text" class="form-control" name="department" value="<?php echo sanitize($coordinator['department'] ?? ''); ?>">
        </div>
        
        <button type="submit" name="submit" class="btn btn-primary">Save Changes</button>
        <a href="CompanyProfile.php" class="btn btn-default">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>