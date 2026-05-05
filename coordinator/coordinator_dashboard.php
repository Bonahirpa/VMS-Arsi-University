<?php
// coordinator/coordinator_dashboard.php
require_once __DIR__ . '/../DBConnect.php';
checkAuth('coordinator');

// Check if coordinator is approved
$user_id = $_SESSION['user_id'];
$check = getRow($db, "SELECT approved FROM coordinators WHERE coordinator_id = ?", "i", $user_id);
if (!$check || $check['approved'] == 0) {
    session_destroy();
    header("Location: /VMS2/coordinator/companyLogin.php?error=pending");
    exit();
}

$stats = [
    'events' => getRow($db, "SELECT COUNT(*) as count FROM activities WHERE coordinator_id = ?", "i", $user_id)['count'],
    'published' => getRow($db, "SELECT COUNT(*) as count FROM activities WHERE coordinator_id = ? AND status='published'", "i", $user_id)['count'],
    'volunteers' => getRow($db, "SELECT COUNT(DISTINCT volunteer_id) as count FROM participation p JOIN activities a ON p.activity_id = a.activity_id WHERE a.coordinator_id = ?", "i", $user_id)['count']
];

$recent = executeQuery($db,
    "SELECT * FROM activities WHERE coordinator_id = ? ORDER BY created_at DESC LIMIT 5",
    "i", $user_id
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Coordinator Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>

<?php include __DIR__ . '/../includes/navbarcompany.php'; ?>

<div class="container" style="margin-top: 30px;">
    <h2>Coordinator Dashboard</h2>
    
    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">Total Events</div>
                <div class="panel-body text-center"><h3><?php echo $stats['events']; ?></h3></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-success">
                <div class="panel-heading">Published</div>
                <div class="panel-body text-center"><h3><?php echo $stats['published']; ?></h3></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="panel panel-info">
                <div class="panel-heading">Volunteers</div>
                <div class="panel-body text-center"><h3><?php echo $stats['volunteers']; ?></h3></div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-default">
                <div class="panel-heading">Recent Events</div>
                <table class="table">
                    <thead>
                        <tr><th>Title</th><th>Date</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php while($event = $recent->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo sanitize($event['title']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($event['activity_date'])); ?></td>
                            <td><?php echo ucfirst($event['status']); ?></td>
                            <td>
                                <a href="modifyevent.php?edit=<?php echo $event['activity_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="attendance.php?activity_id=<?php echo $event['activity_id']; ?>" class="btn btn-sm btn-success">Attendance</a>
                            </td>
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
                    <a href="addevent3.php" class="btn btn-primary btn-block">Add New Event</a>
                    <a href="attendance.php" class="btn btn-success btn-block">Mark Attendance</a>
                    <a href="volunteeraddcompany.php" class="btn btn-info btn-block">Add Volunteer to Event</a>
                    <a href="eventsallcompany.php" class="btn btn-default btn-block">My Events</a>
                    <a href="CompanyProfile.php" class="btn btn-warning btn-block">My Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>