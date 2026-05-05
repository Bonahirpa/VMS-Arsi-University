<?php
// volunteer/edit.php
require_once __DIR__ . '/../DBConnect.php';
checkAuth('volunteer');

$user_id = $_SESSION['user_id'];

$volunteer = getRow($db,
    "SELECT u.*, v.* FROM users u JOIN volunteers v ON u.user_id = v.volunteer_id WHERE u.user_id = ?",
    "i", $user_id
);

if (isset($_POST['submit'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $department = sanitize($_POST['department']);
    $interests = sanitize($_POST['interests']);
    $location = sanitize($_POST['location']);
    
    $db->begin_transaction();
    
    executeQuery($db, "UPDATE users SET full_name = ?, email = ? WHERE user_id = ?", "ssi", $full_name, $email, $user_id);
    executeQuery($db, "UPDATE volunteers SET phone = ?, department = ?, interests = ?, location = ? WHERE volunteer_id = ?",
                "ssssi", $phone, $department, $interests, $location, $user_id);
    
    $db->commit();
    $_SESSION['full_name'] = $full_name;
    $success = "Profile updated!";
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container" style="margin-top: 30px; max-width: 600px;">
    <h2>Edit Profile</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" class="form-control" name="full_name" value="<?php echo sanitize($volunteer['full_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo sanitize($volunteer['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Phone</label>
            <input type="text" class="form-control" name="phone" value="<?php echo sanitize($volunteer['phone'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label>Department</label>
            <input type="text" class="form-control" name="department" value="<?php echo sanitize($volunteer['department'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label>Location</label>
            <input type="text" class="form-control" name="location" value="<?php echo sanitize($volunteer['location'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label>Interests</label>
            <textarea class="form-control" name="interests" rows="3"><?php echo sanitize($volunteer['interests'] ?? ''); ?></textarea>
        </div>
        
        <button type="submit" name="submit" class="btn btn-primary">Save Changes</button>
        <a href="Volunteer.php" class="btn btn-default">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>