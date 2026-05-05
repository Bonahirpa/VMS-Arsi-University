<?php
// admin/activity_log.php
require_once __DIR__ . '/../DBConnect.php';
checkAuth('admin');

$logs = executeQuery($db,
    "SELECT l.*, u.username, u.full_name FROM activity_log l 
     LEFT JOIN users u ON l.user_id = u.user_id 
     ORDER BY l.created_at DESC"
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Activity Log</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body>
<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="container" style="margin-top: 30px;">
    <h2>System Activity Log</h2>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Date/Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Table</th>
                <th>Record ID</th>
                <th>Details</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php while($log = $logs->fetch_assoc()): ?>
            <tr>
                <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                <td><?php echo $log['username'] ?? 'System'; ?></td>
                <td><?php echo $log['action']; ?></td>
                <td><?php echo $log['table_name']; ?></td>
                <td><?php echo $log['record_id']; ?></td>
                <td><?php echo $log['details']; ?></td>
                <td><?php echo $log['ip_address']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>