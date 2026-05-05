<?php
// admin/admin_dashboard.php
require_once __DIR__ . '/../DBConnect.php';
checkAuth('admin');

$stats = [
    'users' => getRow($db, "SELECT COUNT(*) as count FROM users")['count'],
    'volunteers' => getRow($db, "SELECT COUNT(*) as count FROM volunteers")['count'],
    'coordinators' => getRow($db, "SELECT COUNT(*) as count FROM coordinators")['count'],
    'activities' => getRow($db, "SELECT COUNT(*) as count FROM activities")['count'],
    'feedback' => getRow($db, "SELECT COUNT(*) as count FROM feedback")['count']
];

$recent_logs = executeQuery($db,
    "SELECT l.*, u.username FROM activity_log l LEFT JOIN users u ON l.user_id = u.user_id ORDER BY l.created_at DESC LIMIT 10"
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body>
<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="container" style="margin-top: 30px;">
    <h2>Admin Dashboard</h2>
    
    <div class="row">
        <div class="col-md-3"><div class="panel panel-primary"><div class="panel-heading">Users</div><div class="panel-body text-center"><h3><?php echo $stats['users']; ?></h3></div></div></div>
        <div class="col-md-3"><div class="panel panel-success"><div class="panel-heading">Volunteers</div><div class="panel-body text-center"><h3><?php echo $stats['volunteers']; ?></h3></div></div></div>
        <div class="col-md-3"><div class="panel panel-info"><div class="panel-heading">Coordinators</div><div class="panel-body text-center"><h3><?php echo $stats['coordinators']; ?></h3></div></div></div>
        <div class="col-md-3"><div class="panel panel-warning"><div class="panel-heading">Activities</div><div class="panel-body text-center"><h3><?php echo $stats['activities']; ?></h3></div></div></div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">Recent Activity Log</div>
                <table class="table">
                    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
                    <tbody>
                        <?php while($log = $recent_logs->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></td>
                            <td><?php echo $log['username'] ?? 'System'; ?></td>
                            <td><?php echo $log['action']; ?></td>
                            <td><?php echo $log['details']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">Quick Actions</div>
                <div class="panel-body">
                    <a href="addevent.php" class="btn btn-primary btn-block">Add Event</a>
                    <a href="volunteeradd.php" class="btn btn-success btn-block">Add Volunteer to Event</a>
                    <a href="feedbackadmin.php" class="btn btn-info btn-block">View Feedback</a>
                    <a href="activity_log.php" class="btn btn-warning btn-block">Full Activity Log</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>