<?php
// admin/eventsalladmin.php - View All Events
require_once __DIR__ . '/../DBConnect.php';
checkAuth('admin');

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT a.*, u.full_name as coordinator_name,
               (SELECT COUNT(*) FROM participation WHERE activity_id = a.activity_id) as registered,
               (SELECT COUNT(*) FROM feedback WHERE activity_id = a.activity_id) as feedback_count,
               (SELECT ROUND(AVG(rating),1) FROM feedback WHERE activity_id = a.activity_id) as avg_rating
        FROM activities a
        JOIN coordinators c ON a.coordinator_id = c.coordinator_id
        JOIN users u ON c.coordinator_id = u.user_id
        WHERE 1=1";

$params = [];
$types = "";

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

$sql .= " ORDER BY a.created_at DESC";

$events = executeQuery($db, $sql, $types, ...$params);

$stats = [
    'total' => getRow($db, "SELECT COUNT(*) as count FROM activities")['count'],
    'published' => getRow($db, "SELECT COUNT(*) as count FROM activities WHERE status='published'")['count'],
    'completed' => getRow($db, "SELECT COUNT(*) as count FROM activities WHERE status='completed'")['count'],
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Events</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { background: #f4f7fc; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 0; }
        .container { background: white; border-radius: 15px; padding: 30px; margin-top: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .stats-card { background: #f8f9fa; border-radius: 10px; padding: 20px; text-align: center; border-left: 4px solid #667eea; }
        .stats-number { font-size: 24px; font-weight: bold; color: #667eea; }
        .filter-section { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="container">
    <h2>All Events</h2>
    
    <div class="row">
        <div class="col-md-4"><div class="stats-card"><div class="stats-number"><?php echo $stats['total']; ?></div><div>Total Events</div></div></div>
        <div class="col-md-4"><div class="stats-card" style="border-left-color:#28a745;"><div class="stats-number"><?php echo $stats['published']; ?></div><div>Published</div></div></div>
        <div class="col-md-4"><div class="stats-card" style="border-left-color:#17a2b8;"><div class="stats-number"><?php echo $stats['completed']; ?></div><div>Completed</div></div></div>
    </div>
    
    <div class="filter-section">
        <form method="GET" class="form-inline">
            <input type="text" class="form-control" name="search" placeholder="Search events..." value="<?php echo $search; ?>" style="width:300px;">
            <select class="form-control" name="status">
                <option value="">All Status</option>
                <option value="draft" <?php echo $status=='draft'?'selected':''; ?>>Draft</option>
                <option value="published" <?php echo $status=='published'?'selected':''; ?>>Published</option>
                <option value="completed" <?php echo $status=='completed'?'selected':''; ?>>Completed</option>
            </select>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="eventsalladmin.php" class="btn btn-default">Clear</a>
        </form>
    </div>
    
    <table class="table table-bordered">
        <thead>
            <tr><th>ID</th><th>Title</th><th>Date</th><th>Location</th><th>Coordinator</th><th>Status</th><th>Registered</th><th>Rating</th></tr>
        </thead>
        <tbody>
            <?php if ($events && $events->num_rows > 0): while($event = $events->fetch_assoc()): ?>
            <tr>
                <td><?php echo $event['activity_id']; ?></td>
                <td><?php echo sanitize($event['title']); ?></td>
                <td><?php echo date('M d, Y', strtotime($event['activity_date'])); ?></td>
                <td><?php echo sanitize($event['location']); ?></td>
                <td><?php echo sanitize($event['coordinator_name']); ?></td>
                <td><span class="label label-<?php echo $event['status']=='published'?'success':'default'; ?>"><?php echo ucfirst($event['status']); ?></span></td>
                <td><?php echo $event['registered']; ?></td>
                <td><?php echo $event['avg_rating'] ? $event['avg_rating'].'/5' : 'N/A'; ?></td>
                <!-- <td>
                    <a href="/VMS2/coordinator/modifyevent.php?edit=" class="btn btn-sm btn-primary">Edit</a>
                </td> -->
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="9" class="text-center">No events found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>