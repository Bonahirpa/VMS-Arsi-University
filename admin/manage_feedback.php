<?php
// admin/manage_feedback.php - Admin manages all feedback
require_once __DIR__ . '/../DBConnect.php';
checkAuth('admin');

$user_id = $_SESSION['user_id'];
$message = '';

// Handle Delete Feedback
if (isset($_POST['delete_feedback'])) {
    $feedback_id = (int)$_POST['feedback_id'];
    executeQuery($db, "DELETE FROM feedback WHERE feedback_id = ?", "i", $feedback_id);
    logActivity($db, $user_id, 'DELETE_FEEDBACK', 'feedback', $feedback_id, "Admin deleted feedback");
    $message = "Feedback deleted successfully!";
}

// Get all feedback with details
$feedback = executeQuery($db,
    "SELECT f.*, a.title as event_title, u.full_name as volunteer_name, v.student_id,
            coord.full_name as coordinator_name
     FROM feedback f
     JOIN activities a ON f.activity_id = a.activity_id
     JOIN volunteers v ON f.volunteer_id = v.volunteer_id
     JOIN users u ON v.volunteer_id = u.user_id
     JOIN coordinators c ON a.coordinator_id = c.coordinator_id
     JOIN users coord ON c.coordinator_id = coord.user_id
     ORDER BY f.submitted_at DESC"
);

$stats = [
    'total' => getRow($db, "SELECT COUNT(*) as count FROM feedback")['count'],
    'avg_rating' => getRow($db, "SELECT ROUND(AVG(rating),1) as avg FROM feedback")['avg'] ?? 0,
    '5star' => getRow($db, "SELECT COUNT(*) as count FROM feedback WHERE rating=5")['count'],
    '4star' => getRow($db, "SELECT COUNT(*) as count FROM feedback WHERE rating=4")['count'],
    '3star' => getRow($db, "SELECT COUNT(*) as count FROM feedback WHERE rating=3")['count'],
    '2star' => getRow($db, "SELECT COUNT(*) as count FROM feedback WHERE rating=2")['count'],
    '1star' => getRow($db, "SELECT COUNT(*) as count FROM feedback WHERE rating=1")['count'],
];

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Feedback - Admin</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: #f4f7fc; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .container { background: white; border-radius: 15px; padding: 30px; margin-top: 30px; }
        .stats-card { background: #f8f9fa; border-radius: 10px; padding: 20px; text-align: center; border-left: 4px solid #667eea; margin-bottom: 20px; }
        .stats-number { font-size: 28px; font-weight: bold; color: #667eea; }
        .feedback-item { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #667eea; }
        .rating { color: #ffc107; font-size: 18px; }
        .delete-btn { float: right; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="container">
    <h2><i class="fa fa-star" style="color: #667eea;"></i> Manage Feedback</h2>
    
    <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
    
    <!-- Statistics -->
    <div class="row">
        <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?php echo $stats['total']; ?></div><div>Total Feedback</div></div></div>
        <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?php echo $stats['avg_rating']; ?></div><div>Average Rating</div></div></div>
        <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?php echo $stats['5star']; ?></div><div>5-Star</div></div></div>
        <div class="col-md-3"><div class="stats-card"><div class="stats-number"><?php echo $stats['1star']; ?></div><div>1-Star</div></div></div>
    </div>
    
    <!-- Rating Distribution -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">Rating Distribution</div>
                <div class="panel-body">
                    <?php for($i=5; $i>=1; $i--): 
                        $count = $stats[$i.'star'];
                        $percentage = $stats['total'] > 0 ? round(($count / $stats['total']) * 100) : 0;
                    ?>
                    <div class="row" style="margin-bottom: 10px;">
                        <div class="col-md-1"><?php echo $i; ?> <i class="fa fa-star" style="color:#ffc107;"></i></div>
                        <div class="col-md-8">
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar progress-bar-warning" style="width: <?php echo $percentage; ?>%;"><?php echo $percentage; ?>%</div>
                            </div>
                        </div>
                        <div class="col-md-3"><?php echo $count; ?> reviews</div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Feedback List -->
    <h3>All Feedback</h3>
    <?php if ($feedback && $feedback->num_rows > 0): while($fb = $feedback->fetch_assoc()): ?>
        <div class="feedback-item">
            <form method="POST" class="delete-btn" onsubmit="return confirm('Delete this feedback?');">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="feedback_id" value="<?php echo $fb['feedback_id']; ?>">
                <button type="submit" name="delete_feedback" class="btn btn-danger btn-sm">
                    <i class="fa fa-trash"></i> Delete
                </button>
            </form>
            
            <h4><?php echo sanitize($fb['event_title']); ?></h4>
            <div class="rating">
                <?php for($i=1; $i<=5; $i++): ?>
                    <i class="fa fa-star<?php echo $i <= $fb['rating'] ? '' : '-o'; ?>"></i>
                <?php endfor; ?>
                (<?php echo $fb['rating']; ?>/5)
            </div>
            
            <p><strong>Volunteer:</strong> <?php echo sanitize($fb['volunteer_name']); ?> (<?php echo $fb['student_id']; ?>)</p>
            <p><strong>Coordinator:</strong> <?php echo sanitize($fb['coordinator_name']); ?></p>
            <p><strong>Comment:</strong> <?php echo nl2br(sanitize($fb['comment'])); ?></p>
            <p><small>Submitted: <?php echo date('M d, Y H:i', strtotime($fb['submitted_at'])); ?></small></p>
        </div>
    <?php endwhile; else: ?>
        <p class="text-center">No feedback yet</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>