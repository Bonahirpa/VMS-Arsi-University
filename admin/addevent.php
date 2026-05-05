<?php
// admin/addevent.php - Admin Add Event
require_once __DIR__ . '/../DBConnect.php';
checkAuth('admin');

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get all coordinators for dropdown
$coordinators = executeQuery($db,
    "SELECT u.user_id, u.full_name, c.college, c.department 
     FROM users u 
     JOIN coordinators c ON u.user_id = c.coordinator_id 
     WHERE u.status = 'active'
     ORDER BY u.full_name"
);

if (isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $location = sanitize($_POST['location']);
    $activity_date = $_POST['activity_date'];
    $start_time = $_POST['start_time'] ?: null;
    $end_time = $_POST['end_time'] ?: null;
    $capacity = (int)$_POST['capacity'];
    $coordinator_id = (int)$_POST['coordinator_id'];
    $status = sanitize($_POST['status']);
    
    $errors = [];
    
    if (empty($title)) $errors[] = "Title is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($activity_date)) $errors[] = "Date is required";
    if ($coordinator_id <= 0) $errors[] = "Please select a coordinator";
    
    if (strtotime($activity_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Event date cannot be in the past";
    }
    
    if ($start_time && $end_time && strtotime($end_time) <= strtotime($start_time)) {
        $errors[] = "End time must be after start time";
    }
    
    if (empty($errors)) {
        $activity_id = insertData($db,
            "INSERT INTO activities (title, description, location, activity_date, start_time, end_time, capacity, coordinator_id, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "ssssssiis", $title, $description, $location, $activity_date, $start_time, $end_time, $capacity, $coordinator_id, $status
        );
        
        if ($activity_id) {
            logActivity($db, $user_id, 'CREATE_EVENT', 'activities', $activity_id, "Admin created event: $title");
            $success = "Event created successfully!";
            $_POST = [];
        } else {
            $error = "Failed to create event";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Event - VMS Admin</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <style>
        body { background: #f4f7fc; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 0; }
        .form-container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        .form-card { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .form-header { text-align: center; margin-bottom: 30px; }
        .form-header h2 { color: #333; font-weight: 600; }
        .form-header i { font-size: 50px; color: #667eea; margin-bottom: 15px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: 600; color: #555; margin-bottom: 5px; display: block; }
        .form-control { height: 45px; border: 2px solid #e0e0e0; border-radius: 8px; }
        .form-control:focus { border-color: #667eea; box-shadow: none; }
        textarea.form-control { height: 120px; }
        .btn-submit { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 30px; border-radius: 25px; font-size: 16px; font-weight: 600; width: 100%; transition: all 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); color: white; }
        .alert { border-radius: 10px; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="form-container">
    <div class="form-card">
        <div class="form-header">
            <i class="fa fa-calendar-plus-o"></i>
            <h2>Create New Event</h2>
            <p class="text-muted">Add a new volunteer opportunity</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-group">
                <label>Event Title *</label>
                <input type="text" class="form-control" name="title" value="<?php echo $_POST['title'] ?? ''; ?>" required placeholder="e.g., Campus Cleanup Campaign">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="description" placeholder="Describe the event..."><?php echo $_POST['description'] ?? ''; ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Location *</label>
                        <input type="text" class="form-control" name="location" value="<?php echo $_POST['location'] ?? ''; ?>" required placeholder="e.g., Main Campus">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Event Date *</label>
                        <input type="date" class="form-control" name="activity_date" value="<?php echo $_POST['activity_date'] ?? ''; ?>" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" class="form-control" name="start_time" value="<?php echo $_POST['start_time'] ?? ''; ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" class="form-control" name="end_time" value="<?php echo $_POST['end_time'] ?? ''; ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Capacity (0 for unlimited)</label>
                        <input type="number" class="form-control" name="capacity" value="<?php echo $_POST['capacity'] ?? 0; ?>" min="0">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status">
                            <option value="draft">Draft</option>
                            <option value="published" selected>Published</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Assign Coordinator *</label>
                <select class="form-control" name="coordinator_id" required>
                    <option value="">Select Coordinator</option>
                    <?php if ($coordinators): while($coord = $coordinators->fetch_assoc()): ?>
                        <option value="<?php echo $coord['user_id']; ?>" <?php echo ($_POST['coordinator_id'] ?? '') == $coord['user_id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($coord['full_name']); ?> - <?php echo sanitize($coord['college']); ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            
            <button type="submit" name="submit" class="btn-submit"><i class="fa fa-plus-circle"></i> Create Event</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>