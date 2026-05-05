<?php
// coordinator/modifyevent.php - DEBUG VERSION
require_once __DIR__ . '/../DBConnect.php';
checkAuth('coordinator');

$user_id = $_SESSION['user_id'];

// Check if coordinator is approved
$check = getRow($db, "SELECT approved FROM coordinators WHERE coordinator_id = ?", "i", $user_id);
if (!$check || $check['approved'] == 0) {
    session_destroy();
    header("Location: /VMS2/coordinator/companyLogin.php?error=pending");
    exit();
}

$error = '';
$success = '';
$debug_info = [];

// Handle update event with DEBUG
if (isset($_POST['update'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $activity_id = (int)$_POST['activity_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $activity_date = $_POST['activity_date'];
    $capacity = (int)$_POST['capacity'];
    $status = trim($_POST['status']);
    $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    
    // DEBUG: Store all values
    $debug_info = [
        'activity_id' => $activity_id,
        'title' => $title,
        'status_received' => $status,
        'status_length' => strlen($status),
        'status_hex' => bin2hex($status),
        'start_time' => $start_time,
        'end_time' => $end_time,
        'capacity' => $capacity,
        'user_id' => $user_id
    ];
    
    // METHOD 1: Try direct SQL with string concatenation (FOR DEBUG ONLY)
    // This bypasses prepared statements to see if it's a binding issue
    $test_sql = "UPDATE activities SET 
        title = '$title', 
        description = '$description', 
        location = '$location', 
        activity_date = '$activity_date', 
        start_time = " . ($start_time ? "'$start_time'" : "NULL") . ", 
        end_time = " . ($end_time ? "'$end_time'" : "NULL") . ", 
        capacity = $capacity, 
        status = '$status' 
        WHERE activity_id = $activity_id AND coordinator_id = $user_id";
    
    $debug_info['test_sql'] = $test_sql;
    
    // Try direct query
    if ($db->query($test_sql)) {
        $debug_info['direct_query_success'] = true;
        $debug_info['affected_rows'] = $db->affected_rows;
        $success = "Event updated successfully!";
        // Refresh the edit event data
        $edit_event = getRow($db, "SELECT * FROM activities WHERE activity_id = ? AND coordinator_id = ?", "ii", $activity_id, $user_id);
    } else {
        $debug_info['direct_query_error'] = $db->error;
        $error = "Update failed: " . $db->error;
    }
}

// Handle delete event
if (isset($_POST['delete'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $activity_id = (int)$_POST['activity_id'];
    
    // Check if event has registered volunteers
    $check = getRow($db, "SELECT COUNT(*) as count FROM participation WHERE activity_id = ?", "i", $activity_id);
    
    if ($check['count'] > 0) {
        $error = "Cannot delete event with registered volunteers.";
    } else {
        $stmt = $db->prepare("DELETE FROM activities WHERE activity_id = ? AND coordinator_id = ?");
        $stmt->bind_param("ii", $activity_id, $user_id);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $success = "Event deleted successfully!";
                header("Location: modifyevent.php?deleted=1");
                exit();
            } else {
                $error = "Event not found or you don't have permission to delete it.";
            }
        } else {
            $error = "Delete failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get all events for this coordinator
$events = executeQuery($db,
    "SELECT a.*, (SELECT COUNT(*) FROM participation WHERE activity_id = a.activity_id) as registered
     FROM activities a 
     WHERE a.coordinator_id = ? 
     ORDER BY a.activity_date DESC",
    "i", $user_id
);

// Get event for editing
$edit_event = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_event = getRow($db, "SELECT * FROM activities WHERE activity_id = ? AND coordinator_id = ?", "ii", $edit_id, $user_id);
    if (!$edit_event) {
        $error = "Event not found or you don't have permission to edit it.";
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Events - Coordinator</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: #f4f7fc; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); border: none; border-radius: 0; }
        .container { background: white; border-radius: 15px; padding: 30px; margin-top: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .event-card { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #3498db; transition: transform 0.3s; }
        .event-card:hover { transform: translateX(5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .event-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .event-meta { color: #666; font-size: 13px; margin-bottom: 10px; }
        .event-meta i { width: 20px; color: #3498db; }
        .btn-edit { background: #3498db; color: white; border: none; padding: 6px 15px; border-radius: 20px; margin-right: 10px; }
        .btn-edit:hover { background: #2980b9; color: white; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 6px 15px; border-radius: 20px; }
        .btn-delete:hover { background: #c82333; color: white; }
        .btn-save { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 25px; width: 100%; }
        .btn-save:hover { background: #218838; color: white; }
        .edit-form { background: #f8f9fa; border-radius: 15px; padding: 25px; margin-top: 30px; border: 1px solid #e0e0e0; }
        .form-control { border: 2px solid #e0e0e0; border-radius: 8px; height: 40px; }
        .form-control:focus { border-color: #3498db; box-shadow: none; }
        .status-badge { padding: 4px 10px; border-radius: 15px; font-size: 12px; font-weight: normal; }
        .status-published { background: #28a745; color: white; }
        .status-draft { background: #6c757d; color: white; }
        .status-completed { background: #17a2b8; color: white; }
        .status-cancelled { background: #dc3545; color: white; }
        .debug-box { background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 20px 0; font-family: monospace; font-size: 12px; overflow-x: auto; }
        .debug-box pre { margin: 0; white-space: pre-wrap; }
        .alert-success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .alert-danger { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbarcompany.php'; ?>

<div class="container">
    <h2><i class="fa fa-edit" style="color: #3498db;"></i> Manage Events (DEBUG VERSION)</h2>
    <p class="text-muted">View, edit, or delete your created events</p>

    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
            <i class="fa fa-check-circle"></i> Event deleted successfully!
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
            <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
            <i class="fa fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <!-- Events List -->
    <h3>Your Events</h3>
    <?php if ($events && $events->num_rows > 0): ?>
        <?php while($event = $events->fetch_assoc()): ?>
            <div class="event-card">
                <div class="row">
                    <div class="col-md-8">
                        <div class="event-title">
                            <?php echo sanitize($event['title']); ?>
                            <span class="status-badge status-<?php echo $event['status']; ?> pull-right" style="margin-left: 10px;">
                                <?php echo ucfirst($event['status']); ?>
                            </span>
                        </div>
                        <div class="event-meta">
                            <i class="fa fa-calendar"></i> <?php echo date('F j, Y', strtotime($event['activity_date'])); ?>
                            <?php if ($event['start_time'] && $event['start_time'] != '00:00:00'): ?>
                                <i class="fa fa-clock-o" style="margin-left: 15px;"></i> <?php echo date('h:i A', strtotime($event['start_time'])); ?>
                                <?php if ($event['end_time'] && $event['end_time'] != '00:00:00'): ?>
                                    - <?php echo date('h:i A', strtotime($event['end_time'])); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                            <br>
                            <i class="fa fa-map-marker"></i> <?php echo sanitize($event['location']); ?><br>
                            <i class="fa fa-users"></i> Registered: <?php echo $event['registered']; ?> / <?php echo $event['capacity'] ?: 'Unlimited'; ?>
                        </div>
                        <?php if (!empty($event['description'])): ?>
                            <div class="event-meta">
                                <i class="fa fa-info-circle"></i> <?php echo substr(sanitize($event['description']), 0, 100); ?>...
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="?edit=<?php echo $event['activity_id']; ?>" class="btn btn-edit">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        
                        <?php if ($event['registered'] == 0): ?>
                            <form method="POST" style="display: inline;" 
                                  onsubmit="return confirm('Delete this event? This action cannot be undone.');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="activity_id" value="<?php echo $event['activity_id']; ?>">
                                <button type="submit" name="delete" class="btn btn-delete">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-delete" disabled title="Cannot delete event with registered volunteers">
                                <i class="fa fa-trash"></i> Delete
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="fa fa-info-circle"></i> You haven't created any events yet.
            <a href="addevent3.php">Create your first event!</a>
        </div>
    <?php endif; ?>
    
    <!-- Edit Form -->
    <?php if ($edit_event): ?>
        <div class="edit-form">
            <h3><i class="fa fa-pencil-square-o" style="color: #3498db;"></i> Edit Event: <?php echo sanitize($edit_event['title']); ?></h3>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="activity_id" value="<?php echo $edit_event['activity_id']; ?>">
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Event Title *</label>
                            <input type="text" class="form-control" name="title" value="<?php echo sanitize($edit_event['title']); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="4"><?php echo sanitize($edit_event['description']); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Location *</label>
                            <input type="text" class="form-control" name="location" value="<?php echo sanitize($edit_event['location']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Event Date *</label>
                            <input type="date" class="form-control" name="activity_date" value="<?php echo $edit_event['activity_date']; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Start Time</label>
                            <input type="time" class="form-control" name="start_time" value="<?php echo $edit_event['start_time'] && $edit_event['start_time'] != '00:00:00' ? $edit_event['start_time'] : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>End Time</label>
                            <input type="time" class="form-control" name="end_time" value="<?php echo $edit_event['end_time'] && $edit_event['end_time'] != '00:00:00' ? $edit_event['end_time'] : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Capacity (0 for unlimited)</label>
                            <input type="number" class="form-control" name="capacity" value="<?php echo $edit_event['capacity']; ?>" min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control" name="status">
                                <option value="draft" <?php echo $edit_event['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $edit_event['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="in_progress" <?php echo $edit_event['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $edit_event['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $edit_event['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="archived" <?php echo $edit_event['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="update" class="btn btn-save">
                    <i class="fa fa-save"></i> Update Event
                </button>
                <a href="modifyevent.php" class="btn btn-default" style="margin-top: 10px; display: inline-block;">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>

</body>
</html>