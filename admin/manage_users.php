<?php
// admin/manage_users.php - Manage Volunteers & Coordinators
require_once __DIR__ . '/../DBConnect.php';
checkAuth('admin');

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle Delete User
if (isset($_POST['delete_user'])) {
    $delete_id = (int)$_POST['user_id'];
    $role = sanitize($_POST['role']);
    
    // Check if user has any participations (for volunteers)
    if ($role == 'volunteer') {
        $check = getRow($db, "SELECT COUNT(*) as count FROM participation WHERE volunteer_id = ?", "i", $delete_id);
        if ($check['count'] > 0) {
            $error = "Cannot delete volunteer with event participation history.";
        } else {
            executeQuery($db, "DELETE FROM users WHERE user_id = ?", "i", $delete_id);
            logActivity($db, $user_id, 'DELETE_USER', 'users', $delete_id, "Deleted volunteer account");
            $message = "Volunteer deleted successfully!";
        }
    } elseif ($role == 'coordinator') {
        // Check if coordinator has events
        $check = getRow($db, "SELECT COUNT(*) as count FROM activities WHERE coordinator_id = ?", "i", $delete_id);
        if ($check['count'] > 0) {
            $error = "Cannot delete coordinator with existing events. Reassign events first.";
        } else {
            executeQuery($db, "DELETE FROM users WHERE user_id = ?", "i", $delete_id);
            logActivity($db, $user_id, 'DELETE_USER', 'users', $delete_id, "Deleted coordinator account");
            $message = "Coordinator deleted successfully!";
        }
    }
}

// Handle Update User
if (isset($_POST['update_user'])) {
    $update_id = (int)$_POST['user_id'];
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $status = sanitize($_POST['status']);
    
    executeQuery($db, "UPDATE users SET full_name = ?, email = ?, status = ? WHERE user_id = ?", "sssi", $full_name, $email, $status, $update_id);
    logActivity($db, $user_id, 'UPDATE_USER', 'users', $update_id, "Updated user account");
    $message = "User updated successfully!";
}

// Handle Reset Password
if (isset($_POST['reset_password'])) {
    $reset_id = (int)$_POST['user_id'];
    $default_password = 'Password@123';
    $hash = password_hash($default_password, PASSWORD_DEFAULT);
    
    executeQuery($db, "UPDATE users SET password_hash = ? WHERE user_id = ?", "si", $hash, $reset_id);
    logActivity($db, $user_id, 'RESET_PASSWORD', 'users', $reset_id, "Reset user password");
    $message = "Password reset to: Password@123";
}

// Get filter parameters
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT u.*, 
        CASE 
            WHEN u.role = 'volunteer' THEN (SELECT student_id FROM volunteers WHERE volunteer_id = u.user_id)
            WHEN u.role = 'coordinator' THEN (SELECT college FROM coordinators WHERE coordinator_id = u.user_id)
            ELSE 'N/A'
        END as extra_info,
        CASE 
            WHEN u.role = 'volunteer' THEN (SELECT department FROM volunteers WHERE volunteer_id = u.user_id)
            WHEN u.role = 'coordinator' THEN (SELECT department FROM coordinators WHERE coordinator_id = u.user_id)
            ELSE 'N/A'
        END as extra_info2
        FROM users u
        WHERE u.role IN ('volunteer', 'coordinator')";

$params = [];
$types = "";

if (!empty($role_filter)) {
    $sql .= " AND u.role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $sql .= " AND u.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}

$sql .= " ORDER BY u.created_at DESC";

$users = executeQuery($db, $sql, $types, ...$params);

// Get statistics
$stats = [
    'total_volunteers' => getRow($db, "SELECT COUNT(*) as count FROM volunteers")['count'],
    'active_volunteers' => getRow($db, "SELECT COUNT(*) as count FROM users WHERE role='volunteer' AND status='active'")['count'],
    'total_coordinators' => getRow($db, "SELECT COUNT(*) as count FROM coordinators")['count'],
    'active_coordinators' => getRow($db, "SELECT COUNT(*) as count FROM users WHERE role='coordinator' AND status='active'")['count'],
];

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Users - Admin</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <style>
        body { background: #f4f7fc; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 0; }
        .container { background: white; border-radius: 15px; padding: 30px; margin-top: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .stats-card { background: #f8f9fa; border-radius: 10px; padding: 20px; text-align: center; border-left: 4px solid #667eea; margin-bottom: 20px; }
        .stats-number { font-size: 28px; font-weight: bold; color: #667eea; }
        .filter-section { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .table { background: white; }
        .table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-action { padding: 5px 10px; margin: 2px; border-radius: 5px; font-size: 12px; }
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; font-weight: bold; }
        .status-suspended { color: orange; font-weight: bold; }
        .modal-content { border-radius: 15px; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="container">
    <h2><i class="fa fa-users" style="color: #667eea;"></i> Manage Users</h2>
    <p class="text-muted">Manage volunteers and coordinators - Edit, Delete, Reset Passwords</p>
    
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
            <i class="fa fa-check-circle"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
            <a href="#" class="close" data-dismiss="alert">&times;</a>
            <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="stats-card">
                <div class="stats-number"><?php echo $stats['total_volunteers']; ?></div>
                <div>Total Volunteers</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stats-card" style="border-left-color: #28a745;">
                <div class="stats-number"><?php echo $stats['active_volunteers']; ?></div>
                <div>Active Volunteers</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stats-card" style="border-left-color: #17a2b8;">
                <div class="stats-number"><?php echo $stats['total_coordinators']; ?></div>
                <div>Total Coordinators</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stats-card" style="border-left-color: #28a745;">
                <div class="stats-number"><?php echo $stats['active_coordinators']; ?></div>
                <div>Active Coordinators</div>
            </div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="form-inline">
            <div class="form-group" style="margin-right: 10px;">
                <input type="text" class="form-control" name="search" placeholder="Search by name, email..." value="<?php echo $search; ?>" style="width: 250px;">
            </div>
            <div class="form-group" style="margin-right: 10px;">
                <select class="form-control" name="role">
                    <option value="">All Roles</option>
                    <option value="volunteer" <?php echo $role_filter == 'volunteer' ? 'selected' : ''; ?>>Volunteers</option>
                    <option value="coordinator" <?php echo $role_filter == 'coordinator' ? 'selected' : ''; ?>>Coordinators</option>
                </select>
            </div>
            <div class="form-group" style="margin-right: 10px;">
                <select class="form-control" name="status">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
            <a href="manage_users.php" class="btn btn-default">Clear</a>
        </form>
    </div>
    
    <!-- Users Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>College/Student ID</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users && $users->num_rows > 0): while($user = $users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $user['user_id']; ?></td>
                    <td><?php echo sanitize($user['username']); ?></td>
                    <td><?php echo sanitize($user['full_name']); ?></td>
                    <td><?php echo sanitize($user['email']); ?></td>
                    <td>
                        <span class="label label-<?php echo $user['role'] == 'volunteer' ? 'success' : 'primary'; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </td>
                    <td><?php echo sanitize($user['extra_info']); ?></td>
                    <td><?php echo sanitize($user['extra_info2'] ?: 'N/A'); ?></td>
                    <td>
                        <span class="status-<?php echo $user['status']; ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <!-- Edit Button -->
                        <button class="btn btn-primary btn-action" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                            <i class="fa fa-edit"></i>
                        </button>
                        
                        <!-- Reset Password Button -->
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Reset password to default?');">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                            <button type="submit" name="reset_password" class="btn btn-warning btn-action" title="Reset Password">
                                <i class="fa fa-key"></i>
                            </button>
                        </form>
                        
                        <!-- Delete Button -->
                        <?php if ($user['role'] == 'volunteer'): ?>
                            <button class="btn btn-danger btn-action" onclick="confirmDelete(<?php echo $user['user_id']; ?>, '<?php echo $user['role']; ?>', '<?php echo addslashes($user['full_name']); ?>')">
                                <i class="fa fa-trash"></i>
                            </button>
                        <?php else: ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this coordinator? This will fail if they have events.');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <input type="hidden" name="role" value="<?php echo $user['role']; ?>">
                                <button type="submit" name="delete_user" class="btn btn-danger btn-action">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="10" class="text-center">No users found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Edit User</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status" id="edit_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" class="form-control" id="edit_role" readonly disabled>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Confirm Delete</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="delete_user_name"></strong>?</p>
                <p class="text-danger"><i class="fa fa-warning"></i> This action cannot be undone!</p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <input type="hidden" name="role" id="delete_user_role">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_user" class="btn btn-danger">Delete Permanently</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.user_id;
    document.getElementById('edit_full_name').value = user.full_name;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_status').value = user.status;
    document.getElementById('edit_role').value = user.role;
    $('#editUserModal').modal('show');
}

function confirmDelete(userId, role, userName) {
    document.getElementById('delete_user_id').value = userId;
    document.getElementById('delete_user_role').value = role;
    document.getElementById('delete_user_name').textContent = userName;
    $('#deleteConfirmModal').modal('show');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>