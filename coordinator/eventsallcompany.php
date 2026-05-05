<?php
// coordinator/eventsallcompany.php
require_once __DIR__ . '/../DBConnect.php';
checkAuth('coordinator');

$user_id = $_SESSION['user_id'];

// Check if coordinator is approved
$check = getRow($db, "SELECT approved FROM coordinators WHERE coordinator_id = ?", "i", $user_id);
if (!$check || $check['approved'] == 0) {
    session_destroy();
    header("Location: /VMS2/coordinator/companyLogin.php?error=pending");
    exit();
}

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT a.*,
               (SELECT COUNT(*) FROM participation WHERE activity_id = a.activity_id) as registered,
               (SELECT COUNT(*) FROM feedback WHERE activity_id = a.activity_id) as feedback_count
        FROM activities a
        WHERE a.coordinator_id = ?";

$params = [$user_id];
$types = "i";

if (!empty($search)) {
    $sql .= " AND (a.title LIKE ? OR a.location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if (!empty($status)) {
    $sql .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " ORDER BY a.activity_date DESC";

$events = executeQuery($db, $sql, $types, ...$params);

$stats = [
    'total' => getRow($db, "SELECT COUNT(*) as count FROM activities WHERE coordinator_id = ?", "i", $user_id)['count'],
    'published' => getRow($db, "SELECT COUNT(*) as count FROM activities WHERE coordinator_id = ? AND status='published'", "i", $user_id)['count'],
    'completed' => getRow($db, "SELECT COUNT(*) as count FROM activities WHERE coordinator_id = ? AND status='completed'", "i", $user_id)['count'],
];

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Events</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: #f4f7fc; }
        .navbar { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); }
        .container { background: white; border-radius: 15px; padding: 30px; margin-top: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .stats-card { background: #f8f9fa; border-radius: 10px; padding: 20px; text-align: center; border-left: 4px solid #3498db; margin-bottom: 20px; }
        .stats-number { font-size: 28px; font-weight: bold; color: #3498db; }
        .filter-section { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .table thead { background: #3498db; color: white; }
        .btn-action { padding: 5px 10px; margin: 2px; border-radius: 5px; font-size: 12px; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbarcompany.php'; ?>

<div class="container">
    <h2><i class="fa fa-calendar" style="color: #3498db;"></i> My Events</h2>
    
    <!-- Statistics -->
    <div class="row">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-number"><?php echo $stats['total']; ?></div>
                <div>Total Events</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card" style="border-left-color: #28a745;">
                <div class="stats-number"><?php echo $stats['published']; ?></div>
                <div>Published</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card" style="border-left-color: #17a2b8;">
                <div class="stats-number"><?php echo $stats['completed']; ?></div>
                <div>Completed</div>
            </div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="form-inline">
            <div class="form-group" style="margin-right: 10px;">
                <input type="text" class="form-control" name="search" placeholder="Search events..." value="<?php echo $search; ?>" style="width: 250px;">
            </div>
            <div class="form-group" style="margin-right: 10px;">
                <select class="form-control" name="status">
                    <option value="">All Status</option>
                    <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo $status == 'published' ? 'selected' : ''; ?>>Published</option>
                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="eventsallcompany.php" class="btn btn-default">Clear</a>
            <a href="addevent3.php" class="btn btn-success pull-right">+ Create New Event</a>
        </form>
    </div>
    
    <!-- Events Table -->
    <?php if ($events && $events->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Feedback</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($event = $events->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo sanitize($event['title']); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($event['activity_date'])); ?></td>
                            <td><?php echo sanitize($event['location']); ?></td>
                            <td>
                                <span class="label label-<?php 
                                    echo $event['status'] == 'published' ? 'success' : 
                                        ($event['status'] == 'completed' ? 'info' : 
                                        ($event['status'] == 'cancelled' ? 'danger' : 'default')); 
                                ?>">
                                    <?php echo ucfirst($event['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $event['registered']; ?>/<?php echo $event['capacity'] ?: '∞'; ?></td>
                            <td><?php echo $event['feedback_count']; ?> reviews</td>
                            <td>
                                <a href="modifyevent.php?edit=<?php echo $event['activity_id']; ?>" class="btn btn-primary btn-action" title="Edit">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="attendance.php?activity_id=<?php echo $event['activity_id']; ?>" class="btn btn-success btn-action" title="Mark Attendance">
                                    <i class="fa fa-check-square-o"></i>
                                </a>
                                <a href="volunteeraddcompany.php?event_id=<?php echo $event['activity_id']; ?>" class="btn btn-info btn-action" title="Add Volunteer">
                                    <i class="fa fa-user-plus"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="fa fa-info-circle"></i> No events found. 
            <a href="addevent3.php">Create your first event!</a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>