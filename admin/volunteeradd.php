<?php
// admin/volunteeradd.php - Add Volunteer to Event
require_once __DIR__ . '/../DBConnect.php';
checkAuth('admin');

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$events = executeQuery($db,
    "SELECT activity_id, title, activity_date, capacity,
            (SELECT COUNT(*) FROM participation WHERE activity_id = a.activity_id) as registered
     FROM activities a WHERE status IN ('published','in_progress') ORDER BY activity_date DESC"
);

$volunteers = executeQuery($db,
    "SELECT u.user_id, u.full_name, v.student_id, v.department
     FROM users u JOIN volunteers v ON u.user_id = v.volunteer_id WHERE u.status = 'active' ORDER BY u.full_name"
);

if (isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $volunteer_id = (int)$_POST['volunteer_id'];
    $activity_id = (int)$_POST['activity_id'];
    
    if ($volunteer_id <= 0 || $activity_id <= 0) {
        $error = "Please select both volunteer and event";
    } else {
        $existing = getRow($db, "SELECT * FROM participation WHERE volunteer_id = ? AND activity_id = ?", "ii", $volunteer_id, $activity_id);
        
        if ($existing) {
            $error = "Volunteer already registered for this event";
        } else {
            $event = getRow($db, "SELECT capacity, (SELECT COUNT(*) FROM participation WHERE activity_id=?) as current FROM activities WHERE activity_id=?", "ii", $activity_id, $activity_id);
            
            if ($event && ($event['capacity'] == 0 || $event['current'] < $event['capacity'])) {
                $participation_id = insertData($db, "INSERT INTO participation (volunteer_id, activity_id) VALUES (?, ?)", "ii", $volunteer_id, $activity_id);
                
                if ($participation_id) {
                    logActivity($db, $user_id, 'ADD_VOLUNTEER', 'participation', $participation_id, "Added volunteer $volunteer_id to event $activity_id");
                    $success = "Volunteer added to event successfully!";
                } else {
                    $error = "Failed to add volunteer";
                }
            } else {
                $error = "Event has reached maximum capacity";
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Volunteer to Event</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        body { background: #f4f7fc; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .container { max-width: 600px; margin: 30px auto; background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="container">
    <h2>Add Volunteer to Event</h2>
    
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="form-group">
            <label>Select Volunteer</label>
            <select class="form-control" name="volunteer_id" required>
                <option value="">-- Choose Volunteer --</option>
                <?php while($vol = $volunteers->fetch_assoc()): ?>
                    <option value="<?php echo $vol['user_id']; ?>">
                        <?php echo sanitize($vol['full_name']); ?> (<?php echo $vol['student_id']; ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Select Event</label>
            <select class="form-control" name="activity_id" required>
                <option value="">-- Choose Event --</option>
                <?php while($event = $events->fetch_assoc()): 
                    $available = $event['capacity'] == 0 || $event['registered'] < $event['capacity'];
                ?>
                    <option value="<?php echo $event['activity_id']; ?>" <?php echo !$available ? 'disabled' : ''; ?>>
                        <?php echo sanitize($event['title']); ?> (<?php echo date('M d, Y', strtotime($event['activity_date'])); ?>) - <?php echo $event['registered']; ?>/<?php echo $event['capacity'] ?: '∞'; ?>
                        <?php echo !$available ? ' [FULL]' : ''; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <button type="submit" name="submit" class="btn btn-primary btn-block">Add Volunteer to Event</button>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>