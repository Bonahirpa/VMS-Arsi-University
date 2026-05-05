<?php
// coordinator/attendance.php - WITH WORKING NOTIFICATION
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

if (isset($_POST['save'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    foreach ($_POST['attendance'] as $participation_id => $status) {
        // Get volunteer ID before updating
        $part = getRow($db, "SELECT volunteer_id FROM participation WHERE participation_id = ?", "i", $participation_id);
        
        executeQuery($db,
            "UPDATE participation SET attendance_status = ? WHERE participation_id = ?",
            "si", $status, $participation_id
        );
        
        // ============================================
        // NOTIFICATION: Attendance Marked (DIRECT INSERT)
        // ============================================
        if ($part) {
            $volunteer_id = $part['volunteer_id'];
            if ($status == 'present') {
                $db->query("INSERT INTO notifications (user_id, title, message, type, created_at, is_read) 
                           VALUES ($volunteer_id, 'Attendance Marked', 'Your attendance has been marked as PRESENT', 'success', NOW(), 0)");
            } elseif ($status == 'absent') {
                $db->query("INSERT INTO notifications (user_id, title, message, type, created_at, is_read) 
                           VALUES ($volunteer_id, 'Attendance Marked', 'Your attendance has been marked as ABSENT', 'danger', NOW(), 0)");
            }
        }
    }
    $success = "Attendance saved!";
}

$events = executeQuery($db,
    "SELECT activity_id, title, activity_date FROM activities 
     WHERE coordinator_id = ? ORDER BY activity_date DESC",
    "i", $user_id
);

$participants = null;
if (isset($_GET['activity_id'])) {
    $aid = (int)$_GET['activity_id'];
    $participants = executeQuery($db,
        "SELECT p.*, u.full_name, v.student_id 
         FROM participation p
         JOIN volunteers v ON p.volunteer_id = v.volunteer_id
         JOIN users u ON v.volunteer_id = u.user_id
         WHERE p.activity_id = ?",
        "i", $aid
    );
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mark Attendance</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
</head>
<body>

<?php include __DIR__ . '/../includes/navbarcompany.php'; ?>

<div class="container" style="margin-top: 30px;">
    <h2>Mark Attendance</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="GET" class="form-inline" style="margin-bottom: 20px;">
        <select name="activity_id" class="form-control" style="width: 300px;">
            <option value="">Select Event</option>
            <?php while($event = $events->fetch_assoc()): ?>
                <option value="<?php echo $event['activity_id']; ?>" 
                    <?php echo ($_GET['activity_id'] ?? '') == $event['activity_id'] ? 'selected' : ''; ?>>
                    <?php echo sanitize($event['title']); ?> (<?php echo date('M d, Y', strtotime($event['activity_date'])); ?>)
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="btn btn-primary">Load Volunteers</button>
    </form>
    
    <?php if ($participants && $participants->num_rows > 0): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($p = $participants->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $p['student_id']; ?></td>
                            <td><?php echo sanitize($p['full_name']); ?></td>
                            <td>
                                <select name="attendance[<?php echo $p['participation_id']; ?>]" class="form-control">
                                    <option value="pending" <?php echo $p['attendance_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="present" <?php echo $p['attendance_status'] == 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent" <?php echo $p['attendance_status'] == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="excused" <?php echo $p['attendance_status'] == 'excused' ? 'selected' : ''; ?>>Excused</option>
                                </select>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <button type="submit" name="save" class="btn btn-primary">Save Attendance</button>
        </form>
    <?php elseif(isset($_GET['activity_id'])): ?>
        <div class="alert alert-info">No volunteers have joined this event yet.</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>