<?php
// admin/manage_events.php - DEBUG VERSION
require_once __DIR__ . '/../DBConnect.php';
checkAuth('admin');

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';
$debug_info = '';

// Handle Update Event - DEBUG VERSION
if (isset($_POST['update_event'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $activity_id = (int)$_POST['activity_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $activity_date = $_POST['activity_date'];
    $capacity = (int)$_POST['capacity'];
    $status_input = $_POST['status'];
    $coordinator_id = (int)$_POST['coordinator_id'];
    
    // DEBUG: Capture the exact status value
    $debug_info .= "Raw status input: '" . $status_input . "'\n";
    $debug_info .= "Status length: " . strlen($status_input) . "\n";
    $debug_info .= "Status HEX: " . bin2hex($status_input) . "\n";
    
    // Hardcode status for testing - temporarily bypass the form value
    // Change this to test different statuses
    $status = 'published'; // Force to 'published' for testing
    
    $debug_info .= "Using status: '" . $status . "'\n";
    
    // Use direct query with hardcoded status for testing
    $sql = "UPDATE activities SET 
        title = '$title',
        description = '$description',
        location = '$location',
        activity_date = '$activity_date',
        capacity = $capacity,
        status = '$status',
        coordinator_id = $coordinator_id
        WHERE activity_id = $activity_id";
    
    $debug_info .= "SQL: " . $sql . "\n";
    
    if ($db->query($sql)) {
        logActivity($db, $user_id, 'UPDATE_EVENT', 'activities', $activity_id, "Admin updated event: $title");
        
        if ($coordinator_id > 0) {
            $db->query("INSERT INTO notifications (user_id, title, message, type, created_at, is_read) 
                       VALUES ($coordinator_id, 'Event Updated', 'Admin has updated your event: $title', 'warning', NOW(), 0)");
        }
        
        $message = "Event updated successfully!";
        echo "<script>setTimeout(function(){ window.location.href = 'manage_events.php?updated=1'; }, 1500);</script>";
    } else {
        $error = "Update failed: " . $db->error;
        $debug_info .= "Error: " . $db->error . "\n";
    }
}

// Handle Delete Event
if (isset($_POST['delete_event'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $activity_id = (int)$_POST['activity_id'];
    
    $check = getRow($db, "SELECT COUNT(*) as count FROM participation WHERE activity_id = ?", "i", $activity_id);
    
    if ($check['count'] > 0) {
        $error = "Cannot delete event with registered volunteers. Cancel it instead.";
    } else {
        $stmt = $db->prepare("DELETE FROM activities WHERE activity_id = ?");
        $stmt->bind_param("i", $activity_id);
        if ($stmt->execute()) {
            logActivity($db, $user_id, 'DELETE_EVENT', 'activities', $activity_id, "Admin deleted event");
            $message = "Event deleted successfully!";
            echo "<script>setTimeout(function(){ window.location.href = 'manage_events.php?deleted=1'; }, 1000);</script>";
        } else {
            $error = "Delete failed: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get all coordinators for dropdown
$coordinators = executeQuery($db,
    "SELECT u.user_id, u.full_name FROM users u 
     JOIN coordinators c ON u.user_id = c.coordinator_id 
     WHERE u.status = 'active'"
);

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$coordinator_filter = isset($_GET['coordinator']) ? (int)$_GET['coordinator'] : 0;

// Build query for events
$sql = "SELECT a.*, a.coordinator_id, u.full_name as coordinator_name,
        (SELECT COUNT(*) FROM participation WHERE activity_id = a.activity_id) as registered,
        (SELECT COUNT(*) FROM feedback WHERE activity_id = a.activity_id) as feedback_count,
        (SELECT ROUND(AVG(rating),1) FROM feedback WHERE activity_id = a.activity_id) as avg_rating
        FROM activities a
        JOIN coordinators c ON a.coordinator_id = c.coordinator_id
        JOIN users u ON c.coordinator_id = u.user_id
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (a.title LIKE ? OR a.description LIKE ? OR a.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

if (!empty($status_filter)) {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($coordinator_filter > 0) {
    $sql .= " AND a.coordinator_id = ?";
    $params[] = $coordinator_filter;
    $types .= "i";
}

$sql .= " ORDER BY a.activity_date DESC";

$events = executeQuery($db, $sql, $types, ...$params);

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Events - Admin</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <style>
        body { background: #f4f7fc; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 0; }
        .container { background: white; border-radius: 15px; padding: 30px; margin-top: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .filter-section { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .table thead th { padding: 12px; }
        .btn-action { padding: 5px 10px; margin: 2px; }
        .alert { border-radius: 10px; }
        .modal-content { border-radius: 15px; }
        .form-control { border-radius: 8px; border: 2px solid #e0e0e0; height: 40px; }
        .form-control:focus { border-color: #667eea; box-shadow: none; }
        textarea.form-control { height: auto; }
        .debug-box { background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 20px 0; font-family: monospace; font-size: 12px; overflow-x: auto; white-space: pre-wrap; }
        .btn-save { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 10px 20px; border-radius: 25px; width: 100%; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="container">
    <h2><i class="fa fa-calendar" style="color: #667eea;"></i> Manage All Events (DEBUG VERSION)</h2>
    <p class="text-muted">View, edit, and manage all events in the system</p>
    
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
            <i class="fa fa-check-circle"></i> Event updated successfully!
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
            <i class="fa fa-check-circle"></i> Event deleted successfully!
        </div>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="filter-section">
        <form method="GET" class="form-inline">
            <div class="form-group" style="margin-right: 10px;">
                <input type="text" class="form-control" name="search" placeholder="Search events..." value="<?php echo htmlspecialchars($search); ?>" style="width:250px;">
            </div>
            <div class="form-group" style="margin-right: 10px;">
                <select class="form-control" name="status">
                    <option value="">All Status</option>
                    <option value="draft" <?php echo $status_filter=='draft'?'selected':''; ?>>Draft</option>
                    <option value="published" <?php echo $status_filter=='published'?'selected':''; ?>>Published</option>
                    <option value="in_progress" <?php echo $status_filter=='in_progress'?'selected':''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status_filter=='completed'?'selected':''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter=='cancelled'?'selected':''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="form-group" style="margin-right: 10px;">
                <select class="form-control" name="coordinator">
                    <option value="0">All Coordinators</option>
                    <?php if ($coordinators && $coordinators->num_rows > 0): ?>
                        <?php while($coord = $coordinators->fetch_assoc()): ?>
                            <option value="<?php echo $coord['user_id']; ?>" <?php echo $coordinator_filter==$coord['user_id']?'selected':''; ?>>
                                <?php echo htmlspecialchars($coord['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
            <a href="manage_events.php" class="btn btn-default"><i class="fa fa-refresh"></i> Clear</a>
            <a href="addevent.php" class="btn btn-success pull-right"><i class="fa fa-plus"></i> Add New Event</a>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Coordinator</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Rating</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($events && $events->num_rows > 0): ?>
                    <?php while($event = $events->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $event['activity_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                        <td><?php echo date('M d, Y', strtotime($event['activity_date'])); ?></td>
                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                        <td><?php echo htmlspecialchars($event['coordinator_name']); ?></td>
                        <td>
                            <span class="label label-<?php 
                                echo $event['status']=='published'?'success':
                                    ($event['status']=='completed'?'info':
                                    ($event['status']=='cancelled'?'danger':
                                    ($event['status']=='in_progress'?'warning':'default'))); 
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $event['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo $event['registered']; ?> / <?php echo $event['capacity'] ?: '∞'; ?></td>
                        <td><?php echo $event['avg_rating'] ? $event['avg_rating'].'/5' : 'N/A'; ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="editEvent(<?php echo htmlspecialchars(json_encode($event)); ?>)">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                            
                            <?php if ($event['registered'] == 0): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this event?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="activity_id" value="<?php echo $event['activity_id']; ?>">
                                    <button type="submit" name="delete_event" class="btn btn-danger btn-sm">
                                        <i class="fa fa-trash"></i> Delete
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-danger btn-sm" disabled><i class="fa fa-trash"></i> Delete</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center">No events found</td</tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" id="editEventForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="activity_id" id="edit_event_id">
                <input type="hidden" name="update_event" value="1">
                
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Event</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Event Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" id="edit_title" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Description</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="location" id="edit_location" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Event Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="activity_date" id="edit_date" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Capacity</label>
                                <input type="number" class="form-control" name="capacity" id="edit_capacity" min="0">
                                <small class="text-muted">0 = Unlimited</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="status" id="edit_status">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Assign Coordinator</label>
                                <select class="form-control" name="coordinator_id" id="edit_coordinator_id">
                                    <option value="">Select Coordinator</option>
                                    <?php 
                                    $coordinators2 = executeQuery($db,
                                        "SELECT u.user_id, u.full_name FROM users u 
                                         JOIN coordinators c ON u.user_id = c.coordinator_id 
                                         WHERE u.status = 'active'"
                                    );
                                    if ($coordinators2): while($coord = $coordinators2->fetch_assoc()): ?>
                                        <option value="<?php echo $coord['user_id']; ?>">
                                            <?php echo htmlspecialchars($coord['full_name']); ?>
                                        </option>
                                    <?php endwhile; endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                    <button type="submit" class="btn btn-primary btn-save"><i class="fa fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editEvent(event) {
    document.getElementById('edit_event_id').value = event.activity_id;
    document.getElementById('edit_title').value = event.title;
    document.getElementById('edit_description').value = event.description || '';
    document.getElementById('edit_location').value = event.location;
    document.getElementById('edit_date').value = event.activity_date;
    document.getElementById('edit_capacity').value = event.capacity;
    document.getElementById('edit_status').value = event.status;
    
    var coordSelect = document.getElementById('edit_coordinator_id');
    if (coordSelect && event.coordinator_id) {
        for(var i = 0; i < coordSelect.options.length; i++) {
            if(coordSelect.options[i].value == event.coordinator_id) {
                coordSelect.selectedIndex = i;
                break;
            }
        }
    }
    
    $('#editEventModal').modal('show');
}

$(document).ready(function() {
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>