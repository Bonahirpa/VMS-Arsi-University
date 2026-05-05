<?php
// admin/notifications.php - View all notifications for admin
require_once __DIR__ . '/../DBConnect.php';
checkAuth('admin');

$user_id = $_SESSION['user_id'];

// Mark all as read when viewing
executeQuery($db, "UPDATE notifications SET is_read = 1 WHERE user_id = ?", "i", $user_id);

// Get all notifications
$notifications = executeQuery($db,
    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC",
    "i", $user_id
);

$unread_count = getRow($db, "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0", "i", $user_id)['count'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Notifications - Admin</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: #f4f7fc; }
        .navbar { background: linear-gradient(135deg, #1e1e2f 0%, #2d2d44 100%); }
        .container { background: white; border-radius: 15px; padding: 30px; margin-top: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .notification-item { padding: 15px; border-bottom: 1px solid #eee; transition: background 0.3s; }
        .notification-item:hover { background: #f8f9fa; }
        .notification-unread { background: #e3f2fd; border-left: 4px solid #2196f3; }
        .notification-read { background: white; }
        .notification-title { font-weight: bold; color: #333; }
        .notification-message { color: #666; margin: 5px 0; }
        .notification-time { color: #999; font-size: 12px; }
        .btn-mark-read { background: #667eea; color: white; border: none; padding: 5px 15px; border-radius: 20px; font-size: 12px; }
        .btn-mark-read:hover { background: #5a6fd1; color: white; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3><i class="fa fa-bell"></i> My Notifications</h3>
                    <?php if ($unread_count > 0): ?>
                        <span class="label label-info"><?php echo $unread_count; ?> unread</span>
                    <?php endif; ?>
                </div>
                <div class="panel-body">
                    <?php if ($notifications && $notifications->num_rows > 0): ?>
                        <?php while($notif = $notifications->fetch_assoc()): ?>
                            <div class="notification-item <?php echo $notif['is_read'] ? 'notification-read' : 'notification-unread'; ?>">
                                <div class="notification-title">
                                    <?php echo htmlspecialchars($notif['title']); ?>
                                    <?php if (!$notif['is_read']): ?>
                                        <span class="label label-danger">New</span>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                                <div class="notification-time">
                                    <i class="fa fa-clock-o"></i> <?php echo date('F j, Y \a\t g:i A', strtotime($notif['created_at'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center" style="padding: 40px;">
                            <i class="fa fa-bell-slash" style="font-size: 48px; color: #ccc;"></i>
                            <p class="text-muted" style="margin-top: 10px;">No notifications yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>