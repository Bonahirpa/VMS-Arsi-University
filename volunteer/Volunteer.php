<?php
// volunteer/Volunteer.php - Dashboard
require_once __DIR__ . '/../DBConnect.php';
checkAuth('volunteer');

$user_id = $_SESSION['user_id'];

$volunteer = getRow($db, 
    "SELECT u.*, v.* FROM users u JOIN volunteers v ON u.user_id = v.volunteer_id WHERE u.user_id = ?",
    "i", $user_id
);

$stats = [
    'joined' => getRow($db, "SELECT COUNT(*) as count FROM participation WHERE volunteer_id = ?", "i", $user_id)['count'],
    'attended' => getRow($db, "SELECT COUNT(*) as count FROM participation WHERE volunteer_id = ? AND attendance_status='present'", "i", $user_id)['count'],
    'hours' => getRow($db, "SELECT SUM(hours_earned) as total FROM participation WHERE volunteer_id = ?", "i", $user_id)['total'] ?? 0
];

$upcoming = executeQuery($db,
    "SELECT a.* FROM activities a JOIN participation p ON a.activity_id = p.activity_id 
     WHERE p.volunteer_id = ? AND a.activity_date >= CURDATE() ORDER BY a.activity_date",
    "i", $user_id
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Volunteer Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: #f4f7fc; }
        .dashboard { padding: 30px; }
        .stat-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #667eea; }
        .stat-number { font-size: 28px; font-weight: bold; color: #667eea; }
        .welcome-header { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .btn-custom { background: #667eea; color: white; margin-bottom: 10px; width: 100%; }
        .btn-custom:hover { background: #764ba2; color: white; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="dashboard container">
    <div class="welcome-header">
        <h2>Welcome, <?php echo sanitize($volunteer['full_name']); ?>!</h2>
        <p>Student ID: <?php echo $volunteer['student_id']; ?> | Department: <?php echo $volunteer['department'] ?: 'Not set'; ?></p>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['joined']; ?></div>
                <div>Events Joined</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['attended']; ?></div>
                <div>Events Attended</div>
            </div>
        </div>
        <!-- <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-number"></div>
                <div>Hours Contributed</div>
            </div>
        </div> -->
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">Upcoming Events</div>
                <div class="panel-body">
                    <?php if ($upcoming && $upcoming->num_rows > 0): ?>
                        <ul class="list-group">
                            <?php while($event = $upcoming->fetch_assoc()): ?>
                                <li class="list-group-item">
                                    <?php echo sanitize($event['title']); ?> - <?php echo date('M d, Y', strtotime($event['activity_date'])); ?>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No upcoming events. <a href="events.php">Browse events</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">Quick Actions</div>
                <div class="panel-body">
                    <a href="events.php" class="btn btn-custom">Browse Events</a>
                    <a href="joined_events.php" class="btn btn-custom">My Joined Events</a>
                    <a href="feedback.php" class="btn btn-custom">My Feedback</a>
                    <a href="edit.php" class="btn btn-custom">Edit Profile</a>
                    <a href="update_password.php" class="btn btn-custom">Change Password</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>