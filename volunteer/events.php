<?php
// volunteer/events.php - WITH WORKING NOTIFICATION
require_once __DIR__ . '/../DBConnect.php';
checkAuth('volunteer');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

if (isset($_POST['join'])) {
    $activity_id = (int)$_POST['activity_id'];
    
    $existing = getRow($db, "SELECT * FROM participation WHERE volunteer_id = ? AND activity_id = ?", "ii", $user_id, $activity_id);
    
    if (!$existing) {
        insertData($db, "INSERT INTO participation (volunteer_id, activity_id) VALUES (?, ?)", "ii", $user_id, $activity_id);
        
        // ============================================
        // NOTIFICATION: Volunteer Joined Event (DIRECT INSERT)
        // ============================================
        $event_info = getRow($db, "SELECT title, coordinator_id FROM activities WHERE activity_id = ?", "i", $activity_id);
        if ($event_info) {
            $coord_id = $event_info['coordinator_id'];
            $event_title = addslashes($event_info['title']);
            $db->query("INSERT INTO notifications (user_id, title, message, type, created_at, is_read) 
                       VALUES ($coord_id, 'Volunteer Joined', 'Volunteer $full_name joined your event: $event_title', 'info', NOW(), 0)");
        }
        
        $success = "Successfully joined event!";
    } else {
        $error = "You already joined this event";
    }
}

$events = executeQuery($db,
    "SELECT a.*, u.full_name as coordinator_name,
            (SELECT COUNT(*) FROM participation WHERE activity_id = a.activity_id) as registered
     FROM activities a
     JOIN coordinators c ON a.coordinator_id = c.coordinator_id
     JOIN users u ON c.coordinator_id = u.user_id
     WHERE a.status = 'published' AND a.activity_date >= CURDATE()
     ORDER BY a.activity_date"
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Browse Events</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container" style="margin-top: 30px;">
    <h2>Available Events</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <?php if ($events && $events->num_rows > 0): ?>
            <?php while($event = $events->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h4><?php echo sanitize($event['title']); ?></h4>
                        </div>
                        <div class="panel-body">
                            <p><i class="fa fa-calendar"></i> <?php echo date('M d, Y', strtotime($event['activity_date'])); ?></p>
                            <p><i class="fa fa-map-marker"></i> <?php echo sanitize($event['location']); ?></p>
                            <p><i class="fa fa-users"></i> Registered: <?php echo $event['registered']; ?>/<?php echo $event['capacity'] ?: '∞'; ?></p>
                            <p><?php echo substr(sanitize($event['description']), 0, 100); ?>...</p>
                        </div>
                        <div class="panel-footer">
                            <form method="POST">
                                <input type="hidden" name="activity_id" value="<?php echo $event['activity_id']; ?>">
                                <button type="submit" name="join" class="btn btn-primary btn-block">Join Event</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-md-12">
                <div class="alert alert-info">No events available at this time.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>