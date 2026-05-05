<?php
// admin/feedbackadmin.php - View All Feedback
require_once __DIR__ . '/../DBConnect.php';
checkAuth('admin');

$feedback = executeQuery($db,
    "SELECT f.*, a.title as event_title, u.full_name as volunteer_name, v.student_id
     FROM feedback f
     JOIN activities a ON f.activity_id = a.activity_id
     JOIN volunteers v ON f.volunteer_id = v.volunteer_id
     JOIN users u ON v.volunteer_id = u.user_id
     ORDER BY f.submitted_at DESC"
);

$stats = [
    'total' => getRow($db, "SELECT COUNT(*) as count FROM feedback")['count'],
    'avg' => getRow($db, "SELECT ROUND(AVG(rating),1) as avg FROM feedback")['avg'] ?? 0,
    '5star' => getRow($db, "SELECT COUNT(*) as count FROM feedback WHERE rating=5")['count'],
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Feedback Management</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: #f4f7fc; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 0; }
        .container { background: white; border-radius: 15px; padding: 30px; margin-top: 30px; }
        .stats-card { background: #f8f9fa; border-radius: 10px; padding: 20px; text-align: center; border-left: 4px solid #667eea; }
        .stats-number { font-size: 24px; font-weight: bold; color: #667eea; }
        .feedback-item { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; border-left: 4px solid #667eea; }
        .rating { color: #ffc107; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="container">
    <h2>Feedback Management</h2>
    
    <div class="row">
        <div class="col-md-4"><div class="stats-card"><div class="stats-number"><?php echo $stats['total']; ?></div><div>Total Feedback</div></div></div>
        <div class="col-md-4"><div class="stats-card"><div class="stats-number"><?php echo $stats['avg']; ?></div><div>Average Rating</div></div></div>
        <div class="col-md-4"><div class="stats-card"><div class="stats-number"><?php echo $stats['5star']; ?></div><div>5-Star Reviews</div></div></div>
    </div>
    
    <?php if ($feedback && $feedback->num_rows > 0): while($fb = $feedback->fetch_assoc()): ?>
        <div class="feedback-item">
            <h4><?php echo sanitize($fb['event_title']); ?></h4>
            <div class="rating">
                <?php for($i=1; $i<=5; $i++): ?>
                    <i class="fa fa-star<?php echo $i <= $fb['rating'] ? '' : '-o'; ?>"></i>
                <?php endfor; ?>
                (<?php echo $fb['rating']; ?>/5)
            </div>
            <p><strong>From:</strong> <?php echo sanitize($fb['volunteer_name']); ?> (<?php echo $fb['student_id']; ?>)</p>
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