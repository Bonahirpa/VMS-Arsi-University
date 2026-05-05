<?php
// admin/editadmin.php - Edit Admin Profile
require_once __DIR__ . '/../DBConnect.php';
checkAuth('admin');

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$admin = getRow($db,
    "SELECT u.*, a.phone FROM users u JOIN admins a ON u.user_id = a.admin_id WHERE u.user_id = ?",
    "i", $user_id
);

if (!$admin) die("Admin not found");

if (isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
    $errors = [];
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    
    if (empty($errors)) {
        $db->begin_transaction();
        try {
            executeQuery($db, "UPDATE users SET full_name = ?, email = ? WHERE user_id = ?", "ssi", $full_name, $email, $user_id);
            executeQuery($db, "UPDATE admins SET phone = ? WHERE admin_id = ?", "si", $phone, $user_id);
            $db->commit();
            
            $_SESSION['full_name'] = $full_name;
            logActivity($db, $user_id, 'UPDATE_PROFILE', 'admins', $user_id, 'Updated profile from edit page');
            header("Location: AdminProfile.php?success=1");
            exit();
        } catch (Exception $e) {
            $db->rollback();
            $error = "Update failed: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Admin Profile</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: #f4f7fc; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 0; }
        .edit-container { max-width: 600px; margin: 50px auto; padding: 0 20px; }
        .edit-card { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .edit-header { text-align: center; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-control { height: 45px; border: 2px solid #e0e0e0; border-radius: 8px; }
        .btn-save { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px; border-radius: 25px; width: 100%; }
        .btn-cancel { background: #f8f9fa; color: #666; border: 2px solid #e0e0e0; padding: 12px; border-radius: 25px; width: 100%; margin-top: 10px; display: block; text-align: center; text-decoration: none; }
        .readonly-field { background: #f8f9fa; cursor: not-allowed; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="edit-container">
    <div class="edit-card">
        <div class="edit-header">
            <h2>Edit Profile</h2>
        </div>
        
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" class="form-control readonly-field" value="<?php echo sanitize($admin['username']); ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" class="form-control" name="full_name" value="<?php echo sanitize($admin['full_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="email" value="<?php echo sanitize($admin['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" class="form-control" name="phone" value="<?php echo sanitize($admin['phone'] ?? ''); ?>">
            </div>
            
            <button type="submit" name="submit" class="btn-save">Save Changes</button>
            <a href="AdminProfile.php" class="btn-cancel">Cancel</a>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>