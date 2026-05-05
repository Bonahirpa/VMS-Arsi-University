<?php
// volunteer/joined_events.php
require_once __DIR__ . '/../DBConnect.php';
checkAuth('volunteer');

$user_id = $_SESSION['user_id'];

if (isset($_POST['leave'])) {
    $activity_id = (int)$_POST['activity_id'];
    executeQuery($db, "DELETE FROM participation WHERE volunteer_id = ? AND activity_id = ?", "ii", $user_id, $activity_id);
    $success = "Left event successfully";
}

$events = executeQuery($db,
    "SELECT a.*, p.attendance_status, p.joined_at
     FROM activities a
     JOIN participation p ON a.activity_id = p.activity_id
     WHERE p.volunteer_id = ?
     ORDER BY a.activity_date DESC",
    "i", $user_id
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Joined Events</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container" style="margin-top: 30px;">
    <h2>My Joined Events</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Event</th>
                <th>Date</th>
                <th>Location</th>
                <th>Status</th>
                <!-- <th>Hours</th> -->
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($events && $events->num_rows > 0): ?>
                <?php while($event = $events->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo sanitize($event['title']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($event['activity_date'])); ?></td>
                        <td><?php echo sanitize($event['location']); ?></td>
                        <td>
                            <span class="label label-<?php echo $event['attendance_status'] == 'present' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($event['attendance_status']); ?>
                            </span>
                        </td>
                        <!-- <td></td> -->
                        <td>
                            <?php if (strtotime($event['activity_date']) > time()): ?>
                                <form method="POST">
                                    <input type="hidden" name="activity_id" value="<?php echo $event['activity_id']; ?>">
                                    <button type="submit" name="leave" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Leave this event?')">Leave</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="6" class="text-center">No joined events</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>