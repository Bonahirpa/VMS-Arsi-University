<?php
// includes/navbarcompany.php - FULLY FUNCTIONAL
if (session_status() === PHP_SESSION_NONE) session_start();

// Get unread notification count
$unread_count = 0;
$notifications_list = [];
if (isset($db) && isset($_SESSION['user_id'])) {
    $result = getRow($db, "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE", "i", $_SESSION['user_id']);
    $unread_count = $result['count'] ?? 0;
    
    // Get recent notifications for dropdown
    $notif_result = executeQuery($db, 
        "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5",
        "i", $_SESSION['user_id']
    );
    if ($notif_result) {
        while($row = $notif_result->fetch_assoc()) {
            $notifications_list[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <style>
        .navbar {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            border: none;
            border-radius: 0;
            margin-bottom: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .navbar-brand {
            color: #fff !important;
            font-weight: 600;
            font-size: 20px;
            padding: 15px 20px;
        }
        .navbar-brand i {
            color: #f1c40f;
            margin-right: 10px;
        }
        .navbar-nav > li > a {
            color: #ecf0f1 !important;
            font-weight: 500;
            padding: 15px 15px;
            transition: all 0.3s;
        }
        .navbar-nav > li > a:hover,
        .navbar-nav > li > a:focus {
            background: rgba(255,255,255,0.15) !important;
            color: #fff !important;
        }
        .navbar-nav > li > a i {
            margin-right: 5px;
        }
        .navbar-nav > .active > a {
            background: rgba(255,255,255,0.2) !important;
            color: #fff !important;
            border-bottom: 3px solid #f1c40f;
        }
        .dropdown-menu {
            background: #34495e;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            border-radius: 8px;
            padding: 5px 0;
        }
        .dropdown-menu > li > a {
            color: #ecf0f1;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        .dropdown-menu > li > a:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        .dropdown-menu > li > a i {
            margin-right: 10px;
            width: 20px;
            color: #f1c40f;
        }
        .dropdown-header {
            color: #f1c40f;
            padding: 10px 20px;
            font-weight: 600;
            background: #2c3e50;
        }
        .divider {
            background: rgba(255,255,255,0.1);
        }
        .navbar-toggle {
            border-color: #f1c40f;
        }
        .navbar-toggle .icon-bar {
            background-color: #f1c40f;
        }
        .navbar-toggle:hover,
        .navbar-toggle:focus {
            background: rgba(255,255,255,0.1);
        }
        
        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background-color: #e74c3c;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: bold;
        }
        
        /* Notification Items in Dropdown */
        .notification-item {
            padding: 10px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            min-width: 280px;
        }
        .notification-item.unread {
            background-color: rgba(52, 152, 219, 0.3);
        }
        .notification-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 3px;
        }
        .notification-message {
            font-size: 11px;
            color: #bdc3c7;
            margin-bottom: 3px;
        }
        .notification-time {
            font-size: 10px;
            color: #7f8c8d;
        }
        
        /* Profile Image */
        .profile-img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 8px;
            border: 2px solid #f1c40f;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar-nav > li > a {
                padding: 10px 15px;
            }
            .notification-badge {
                top: 5px;
                right: 5px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-inverse">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#coordinatorNavbar">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/VMS2/index.php">
                    <i class="fa fa-building"></i> Coordinator Portal
                </a>
            </div>
            
            <div class="collapse navbar-collapse" id="coordinatorNavbar">
                <ul class="nav navbar-nav">
                    <!-- Dashboard -->
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'coordinator_dashboard.php' ? 'active' : ''; ?>">
                        <a href="/VMS2/coordinator/coordinator_dashboard.php">
                            <i class="fa fa-dashboard"></i> Dashboard
                        </a>
                    </li>
                    
                    <!-- Events Dropdown -->
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-calendar"></i> Events <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="/VMS2/coordinator/addevent3.php"><i class="fa fa-plus-circle"></i> Add Event</a></li>
                            <li><a href="/VMS2/coordinator/eventsallcompany.php"><i class="fa fa-list"></i> My Events</a></li>
                            <li><a href="/VMS2/coordinator/modifyevent.php"><i class="fa fa-edit"></i> Manage Events</a></li>
                        </ul>
                    </li>
                    
                    <!-- Attendance -->
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>">
                        <a href="/VMS2/coordinator/attendance.php">
                            <i class="fa fa-check-square-o"></i> Attendance
                        </a>
                    </li>
                    
                    <!-- Add Volunteer -->
                    <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'volunteeraddcompany.php' ? 'active' : ''; ?>">
                        <a href="/VMS2/coordinator/volunteeraddcompany.php">
                            <i class="fa fa-user-plus"></i> Add Volunteer
                        </a>
                    </li>
                </ul>
                
                <ul class="nav navbar-nav navbar-right">
                    <!-- Notifications Dropdown -->
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-bell"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu" style="min-width: 320px;">
                            <li class="dropdown-header">
                                <i class="fa fa-bell"></i> Notifications
                                <?php if ($unread_count > 0): ?>
                                    <span class="pull-right"><?php echo $unread_count; ?> unread</span>
                                <?php endif; ?>
                            </li>
                            <li class="divider"></li>
                            <?php if (count($notifications_list) > 0): ?>
                                <?php foreach ($notifications_list as $notif): ?>
                                    <li class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                                        <div class="notification-title">
                                            <?php echo htmlspecialchars($notif['title']); ?>
                                        </div>
                                        <div class="notification-message">
                                            <?php echo htmlspecialchars(substr($notif['message'], 0, 50)); ?>...
                                        </div>
                                        <div class="notification-time">
                                            <i class="fa fa-clock-o"></i> <?php echo date('M d, H:i', strtotime($notif['created_at'])); ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><a href="#" style="text-align: center;">No notifications</a></li>
                            <?php endif; ?>
                            <li class="divider"></li>
                            <li><a href="/VMS2/coordinator/notifications.php">
                                <i class="fa fa-eye"></i> View All Notifications
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Profile Dropdown -->
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <?php if (!empty($_SESSION['profile_pic'])): ?>
                                <img src="/VMS2/uploads/profiles/<?php echo $_SESSION['profile_pic']; ?>" class="profile-img" alt="Profile">
                            <?php else: ?>
                                <i class="fa fa-user-circle"></i>
                            <?php endif; ?>
                            <?php echo $_SESSION['full_name'] ?? 'Coordinator'; ?> <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="/VMS2/coordinator/CompanyProfile.php"><i class="fa fa-id-card"></i> My Profile</a></li>
                            <li><a href="/VMS2/coordinator/editcompany.php"><i class="fa fa-edit"></i> Edit Profile</a></li>
                            <li><a href="/VMS2/coordinator/update_passwordcompany.php"><i class="fa fa-key"></i> Change Password</a></li>
                            <li class="divider"></li>
                            <li><a href="/VMS2/logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <script>
        $(document).ready(function() {
            // Mark notification as read when clicked (optional)
            $('.notification-item').click(function() {
                // You can add AJAX here to mark as read
                console.log('Notification clicked');
            });
            
            // Active page highlighting
            var currentPage = window.location.pathname;
            $('.nav li a').each(function() {
                var link = $(this).attr('href');
                if (currentPage.indexOf(link) !== -1 && link !== '#') {
                    $(this).closest('li').addClass('active');
                }
            });
        });
    </script>
</body>
</html>