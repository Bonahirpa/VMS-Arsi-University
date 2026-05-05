<?php
// coordinator/addevent3.php - WITH WORKING NOTIFICATION
require_once __DIR__ . '/../DBConnect.php';
checkAuth('coordinator');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Check if coordinator is approved
$check = getRow($db, "SELECT approved FROM coordinators WHERE coordinator_id = ?", "i", $user_id);
if (!$check || $check['approved'] == 0) {
    session_destroy();
    header("Location: /VMS2/coordinator/companyLogin.php?error=pending");
    exit();
}

if (isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $location = sanitize($_POST['location']);
    $activity_date = $_POST['activity_date'];
    $capacity = (int)$_POST['capacity'];
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;
    
    $event_id = insertData($db,
        "INSERT INTO activities (title, description, location, activity_date, start_time, end_time, capacity, coordinator_id, status) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published')",
        "ssssssii", $title, $description, $location, $activity_date, $start_time, $end_time, $capacity, $user_id
    );
    
    if ($event_id) {
        // ============================================
        // NOTIFICATION: New Event Created (DIRECT INSERT)
        // ============================================
        $admin_ids = [11, 13]; // Your admin IDs
        foreach ($admin_ids as $admin_id) {
            $db->query("INSERT INTO notifications (user_id, title, message, type, created_at, is_read) 
                       VALUES ($admin_id, 'New Event', 'Coordinator $full_name created a new event: $title', 'info', NOW(), 0)");
        }
    }
    
    $success = "Event created successfully!";
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Event</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body>

<?php include __DIR__ . '/../includes/navbarcompany.php'; ?>

<div class="container" style="margin-top: 30px; max-width: 600px;">
    <h2>Create New Event</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="form-group">
            <label>Event Title</label>
            <input type="text" class="form-control" name="title" required>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea class="form-control" name="description" rows="4"></textarea>
        </div>
        
        <div class="form-group">
            <label>Location</label>
            <input type="text" class="form-control" name="location" required>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" class="form-control" name="activity_date" required min="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Capacity (0 for unlimited)</label>
                    <input type="number" class="form-control" name="capacity" value="0" min="0">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Start Time (Optional)</label>
                    <input type="time" class="form-control" name="start_time">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>End Time (Optional)</label>
                    <input type="time" class="form-control" name="end_time">
                </div>
            </div>
        </div>
        
        <button type="submit" name="submit" class="btn btn-primary">Create Event</button>
        <a href="coordinator_dashboard.php" class="btn btn-default">Cancel</a>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>