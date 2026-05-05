<?php
// coordinator/CompanyProfile.php
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
$error = '';
$success = '';

if (isset($_POST['update_profile'])) {
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
            $success = "Profile updated!";
        } catch (Exception $e) {
            $db->rollback();
            $error = "Update failed";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

if (isset($_POST['upload_pic']) && isset($_FILES['profile_pic'])) {
    $target_dir = __DIR__ . "/../uploads/profiles/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    
    $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
    $new_filename = "coordinator_" . $user_id . "_" . time() . "." . $ext;
    $target_file = $target_dir . $new_filename;
    
    $check = getimagesize($_FILES['profile_pic']['tmp_name']);
    if ($check && in_array($ext, ['jpg','jpeg','png','gif']) && $_FILES['profile_pic']['size'] <= 5000000) {
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
            executeQuery($db, "UPDATE users SET profile_pic = ? WHERE user_id = ?", "si", $new_filename, $user_id);
            $success = "Profile picture updated!";
        }
    }
}

$coordinator = getRow($db,
    "SELECT u.*, c.college, c.department, c.phone FROM users u JOIN coordinators c ON u.user_id = c.coordinator_id WHERE u.user_id = ?",
    "i", $user_id
);

$stats = [
    'events' => getRow($db, "SELECT COUNT(*) as count FROM activities WHERE coordinator_id = ?", "i", $user_id)['count'],
    'volunteers' => getRow($db, "SELECT COUNT(DISTINCT volunteer_id) as count FROM participation p JOIN activities a ON p.activity_id=a.activity_id WHERE a.coordinator_id=?", "i", $user_id)['count']
];

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Coordinator Profile</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: #f4f7fc; }
        .navbar { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); }
        .profile-container { max-width: 800px; margin: 30px auto; background: white; border-radius: 15px; padding: 30px; }
        .profile-pic { width: 150px; height: 150px; border-radius: 50%; border: 4px solid #3498db; margin: 0 auto 20px; object-fit: cover; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 10px; text-align: center; border-left: 4px solid #3498db; margin-bottom: 20px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbarcompany.php'; ?>

<div class="profile-container">
    <h2 class="text-center">Coordinator Profile</h2>
    
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    
    <div class="text-center">
        <img src="/VMS2/uploads/profiles/<?php echo $coordinator['profile_pic'] ?? 'default.jpg'; ?>" class="profile-pic" id="profilePreview">
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="profile_pic" id="file-input" accept="image/*" style="display: none;">
            <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('file-input').click();">Change Photo</button>
            <button type="submit" name="upload_pic" class="btn btn-sm btn-success" id="uploadBtn" style="display: none;">Upload</button>
        </form>
    </div>
    
    <div class="row">
        <div class="col-md-6"><div class="stat-card"><h3><?php echo $stats['events']; ?></h3>Events Created</div></div>
        <div class="col-md-6"><div class="stat-card"><h3><?php echo $stats['volunteers']; ?></h3>Volunteers</div></div>
    </div>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="form-group"><label>Username</label><input type="text" class="form-control" value="<?php echo sanitize($coordinator['username']); ?>" readonly></div>
        <div class="form-group"><label>Full Name</label><input type="text" class="form-control" name="full_name" value="<?php echo sanitize($coordinator['full_name']); ?>" required></div>
        <div class="form-group"><label>Email</label><input type="email" class="form-control" name="email" value="<?php echo sanitize($coordinator['email']); ?>" required></div>
        <div class="form-group"><label>Phone</label><input type="text" class="form-control" name="phone" value="<?php echo sanitize($coordinator['phone'] ?? ''); ?>"></div>
        <div class="form-group"><label>College</label><input type="text" class="form-control" name="college" value="<?php echo sanitize($coordinator['college']); ?>" required></div>
        <div class="form-group"><label>Department</label><input type="text" class="form-control" name="department" value="<?php echo sanitize($coordinator['department'] ?? ''); ?>"></div>
        
        <button type="submit" name="update_profile" class="btn btn-primary btn-block">Update Profile</button>
    </form>
</div>

<script>
document.getElementById('file-input').addEventListener('change', function() {
    if (this.files && this.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) { document.getElementById('profilePreview').src = e.target.result; }
        reader.readAsDataURL(this.files[0]);
        document.getElementById('uploadBtn').click();
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>