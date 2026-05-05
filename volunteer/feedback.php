<?php
// volunteer/feedback.php - WITH WORKING NOTIFICATION
require_once __DIR__ . '/../DBConnect.php';
checkAuth('volunteer');

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

if (isset($_POST['submit'])) {
    $activity_id = (int)$_POST['activity_id'];
    $rating = (int)$_POST['rating'];
    $comment = sanitize($_POST['comment']);
    
    insertData($db,
        "INSERT INTO feedback (activity_id, volunteer_id, rating, comment) VALUES (?, ?, ?, ?)",
        "iiis", $activity_id, $user_id, $rating, $comment
    );
    
    // ============================================
    // NOTIFICATION: New Feedback (DIRECT INSERT)
    // ============================================
    $event_info = getRow($db, "SELECT title, coordinator_id FROM activities WHERE activity_id = ?", "i", $activity_id);
    if ($event_info) {
        $coord_id = $event_info['coordinator_id'];
        $event_title = addslashes($event_info['title']);
        $db->query("INSERT INTO notifications (user_id, title, message, type, created_at, is_read) 
                   VALUES ($coord_id, 'New Feedback', 'Volunteer $full_name gave a $rating-star review for: $event_title', 'info', NOW(), 0)");
    }
    
    $success = "Feedback submitted!";
}

$events = executeQuery($db,
    "SELECT a.activity_id, a.title, a.activity_date
     FROM activities a
     JOIN participation p ON a.activity_id = p.activity_id
     LEFT JOIN feedback f ON a.activity_id = f.activity_id AND f.volunteer_id = p.volunteer_id
     WHERE p.volunteer_id = ? AND p.attendance_status = 'present' AND f.feedback_id IS NULL",
    "i", $user_id
);

$my_feedback = executeQuery($db,
    "SELECT f.*, a.title FROM feedback f JOIN activities a ON f.activity_id = a.activity_id WHERE f.volunteer_id = ? ORDER BY f.submitted_at DESC",
    "i", $user_id
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Feedback</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container" style="margin-top: 30px;">
    <h2>Event Feedback</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">Submit Feedback</div>
                <div class="panel-body">
                    <?php if ($events && $events->num_rows > 0): ?>
                        <form method="POST">
                            <div class="form-group">
                                <label>Select Event</label>
                                <select name="activity_id" class="form-control" required>
                                    <?php while($event = $events->fetch_assoc()): ?>
                                        <option value="<?php echo $event['activity_id']; ?>">
                                            <?php echo sanitize($event['title']); ?> (<?php echo date('M d, Y', strtotime($event['activity_date'])); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Rating</label>
                                <select name="rating" class="form-control" required>
                                    <option value="5">5 - Excellent</option>
                                    <option value="4">4 - Good</option>
                                    <option value="3">3 - Average</option>
                                    <option value="2">2 - Poor</option>
                                    <option value="1">1 - Very Poor</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Comment</label>
                                <textarea name="comment" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" name="submit" class="btn btn-primary">Submit Feedback</button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted">No events available for feedback. Attend events first!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">My Feedback History</div>
                <div class="panel-body">
                    <?php if ($my_feedback && $my_feedback->num_rows > 0): ?>
                        <ul class="list-group">
                            <?php while($fb = $my_feedback->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <strong><?php echo sanitize($fb['title']); ?></strong><br>
                                    <span class="label label-warning">Rating: <?php echo $fb['rating']; ?>/5</span>
                                    <p class="text-muted small"><?php echo nl2br(sanitize($fb['comment'])); ?></p>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No feedback submitted yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>