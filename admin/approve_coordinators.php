<?php
// admin/approve_coordinators.php - WITH EMAIL WORKING
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../DBConnect.php';
require_once __DIR__ . '/../email_config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: /VMS2/admin/admin_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle Approve/Reject
if (isset($_POST['action'])) {
    $coordinator_id = isset($_POST['coordinator_id']) ? (int)$_POST['coordinator_id'] : 0;
    $action = $_POST['action'] ?? '';
    $rejection_reason = $_POST['rejection_reason'] ?? '';
    
    if ($coordinator_id > 0) {
        // Get coordinator details
        $coord = getRow($db, 
            "SELECT u.user_id, u.full_name, u.email, u.username, c.college 
             FROM users u 
             JOIN coordinators c ON u.user_id = c.coordinator_id 
             WHERE u.user_id = ?",
            "i", $coordinator_id
        );
        
        if ($coord) {
            if ($action == 'approve') {
                $db->begin_transaction();
                try {
                    // Update coordinator as approved
                    executeQuery($db, 
                        "UPDATE coordinators SET approved = 1, approved_by = ?, approved_at = NOW(), email_sent = 1 WHERE coordinator_id = ?",
                        "ii", $user_id, $coordinator_id
                    );
                    
                    // Update user status to active
                    executeQuery($db, "UPDATE users SET status = 'active' WHERE user_id = ?", "i", $coordinator_id);
                    
                    // ============================================
                    // IN-APP NOTIFICATION
                    // ============================================
                    $db->query("INSERT INTO notifications (user_id, title, message, type, created_at, is_read) 
                               VALUES ($coordinator_id, 'Account Approved', 'Your coordinator account has been approved! You can now login and create events.', 'success', NOW(), 0)");
                    
                    // ============================================
                    // EMAIL NOTIFICATION
                    // ============================================
                    $email_sent = sendApprovalEmail($db, $coordinator_id, $coord['full_name'], $coord['email']);
                    
                    if ($email_sent) {
                        $message = "Coordinator approved successfully! Email notification sent.";
                    } else {
                        $message = "Coordinator approved successfully! (Email failed - check email settings)";
                    }
                    
                    $db->commit();
                    
                } catch (Exception $e) {
                    $db->rollback();
                    $error = "Error: " . $e->getMessage();
                }
                
            } elseif ($action == 'reject') {
                // Delete coordinator
                executeQuery($db, "DELETE FROM users WHERE user_id = ?", "i", $coordinator_id);
                $message = "Coordinator rejected and removed.";
            }
        } else {
            $error = "Coordinator not found.";
        }
    } else {
        $error = "Invalid coordinator ID.";
    }
}

// Get pending coordinators
$pending_result = executeQuery($db,
    "SELECT u.user_id, u.username, u.full_name, u.email, u.created_at,
            c.college, c.department, c.phone
     FROM users u
     JOIN coordinators c ON u.user_id = c.coordinator_id
     WHERE c.approved = 0 AND u.role = 'coordinator'
     ORDER BY u.created_at ASC"
);

$pending = ($pending_result && $pending_result->num_rows > 0) ? $pending_result : null;

// Get approved coordinators
$approved_result = executeQuery($db,
    "SELECT u.user_id, u.username, u.full_name, u.email, u.created_at,
            c.college, c.department, c.phone, c.approved_at, c.email_sent,
            adm.full_name as approved_by_name
     FROM users u
     JOIN coordinators c ON u.user_id = c.coordinator_id
     LEFT JOIN users adm ON c.approved_by = adm.user_id
     WHERE c.approved = 1 AND u.role = 'coordinator'
     ORDER BY c.approved_at DESC"
);

$approved = ($approved_result && $approved_result->num_rows > 0) ? $approved_result : null;

$pending_count = $pending ? $pending->num_rows : 0;
$approved_count = $approved ? $approved->num_rows : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Approve Coordinators - VMS Admin</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <style>
        body { background: #f4f7fc; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 0; }
        .container { background: white; border-radius: 15px; padding: 30px; margin-top: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .pending-card { background: #fff3cd; border-left: 5px solid #ffc107; padding: 20px; margin-bottom: 20px; border-radius: 10px; }
        .btn-approve { background: #28a745; color: white; margin-right: 10px; }
        .btn-reject { background: #dc3545; color: white; }
        .stats-badge { font-size: 16px; padding: 10px; border-radius: 20px; }
        .modal-content { border-radius: 15px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="container">
    <h2><i class="fa fa-user-check" style="color: #667eea;"></i> Approve Coordinators</h2>
    <p class="lead">Review and approve new coordinator registrations</p>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
            <i class="fa fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
            <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- Pending Approvals -->
    <h3>
        Pending Approvals 
        <span class="badge" style="background: #ffc107; color: #333; font-size: 16px;">
            <?php echo $pending_count; ?>
        </span>
    </h3>
    
    <?php if ($pending && $pending_count > 0): ?>
        <?php while($coord = $pending->fetch_assoc()): ?>
            <div class="pending-card">
                <div class="row">
                    <div class="col-md-8">
                        <h4><i class="fa fa-user"></i> <?php echo htmlspecialchars($coord['full_name'] ?? 'Unknown'); ?></h4>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($coord['username'] ?? 'N/A'); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($coord['email'] ?? 'N/A'); ?></p>
                        <p><strong>College:</strong> <?php echo htmlspecialchars($coord['college'] ?? 'N/A'); ?></p>
                        <p><strong>Department:</strong> <?php echo htmlspecialchars($coord['department'] ?: 'N/A'); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($coord['phone'] ?: 'N/A'); ?></p>
                        <p><strong>Registered:</strong> <?php echo isset($coord['created_at']) ? date('M d, Y h:i A', strtotime($coord['created_at'])) : 'N/A'; ?></p>
                    </div>
                    <div class="col-md-4 text-right">
                        <button class="btn btn-approve" onclick="approveCoordinator(<?php echo $coord['user_id']; ?>, '<?php echo htmlspecialchars($coord['full_name']); ?>')">
                            <i class="fa fa-check"></i> Approve
                        </button>
                        <button class="btn btn-reject" onclick="rejectCoordinator(<?php echo $coord['user_id']; ?>, '<?php echo htmlspecialchars($coord['full_name']); ?>')">
                            <i class="fa fa-times"></i> Reject
                        </button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No pending coordinator approvals.
        </div>
    <?php endif; ?>
    
    <hr>
    
    <!-- Approved Coordinators -->
    <h3>
        Approved Coordinators 
        <span class="badge" style="background: #28a745; color: white; font-size: 16px;">
            <?php echo $approved_count; ?>
        </span>
    </h3>
    
    <?php if ($approved && $approved_count > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>College</th>
                        <th>Approved By</th>
                        <th>Approved At</th>
                        <th>Email Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($coord = $approved->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($coord['full_name'] ?? 'N/A'); ?>
                                                <td><?php echo htmlspecialchars($coord['username'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($coord['email'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($coord['college'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($coord['approved_by_name'] ?: 'System'); ?></td>
                        <td><?php echo isset($coord['approved_at']) ? date('M d, Y H:i', strtotime($coord['approved_at'])) : 'N/A'; ?></td>
                        <td>
                            <?php if ($coord['email_sent'] ?? 0): ?>
                                <span class="label label-success">Sent</span>
                            <?php else: ?>
                                <span class="label label-warning">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted">No approved coordinators yet.</p>
    <?php endif; ?>
</div>

<!-- Approve Confirmation Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="coordinator_id" id="approve_id">
                <input type="hidden" name="action" value="approve">
                
                <div class="modal-header bg-success">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Confirm Approval</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve <strong id="approve_name"></strong>?</p>
                    <p>They will receive an email notification and be able to login immediately.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Yes, Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="coordinator_id" id="reject_id">
                <input type="hidden" name="action" value="reject">
                
                <div class="modal-header bg-danger">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Reject Coordinator</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject <strong id="reject_name"></strong>?</p>
                    <p>This will permanently delete their registration.</p>
                    
                    <div class="form-group">
                        <label>Rejection Reason (Optional):</label>
                        <textarea class="form-control" name="rejection_reason" rows="3" placeholder="Enter reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approveCoordinator(id, name) {
    document.getElementById('approve_id').value = id;
    document.getElementById('approve_name').textContent = name;
    $('#approveModal').modal('show');
}

function rejectCoordinator(id, name) {
    document.getElementById('reject_id').value = id;
    document.getElementById('reject_name').textContent = name;
    $('#rejectModal').modal('show');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>