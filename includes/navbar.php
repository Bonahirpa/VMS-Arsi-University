<?php
// includes/navbar.php - COMPLETE FIXED VERSION (Public Dropdowns Working)
if (session_status() === PHP_SESSION_NONE) session_start();

// Get unread notification count for volunteer (only when logged in)
$unread_count = 0;
$notifications_list = [];
if (isset($db) && isset($_SESSION['user_id']) && $_SESSION['role'] == 'volunteer') {
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #ffd700;
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
            border-bottom: 3px solid #ffd700;
        }
        .dropdown-menu {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            border-radius: 8px;
            padding: 5px 0;
        }
        .dropdown-menu > li > a {
            color: #ecf0f1 !important;
            padding: 10px 20px;
            transition: all 0.3s;
        }
        .dropdown-menu > li > a:hover {
            background: rgba(255,255,255,0.2) !important;
            color: #fff !important;
        }
        .dropdown-menu > li > a i {
            margin-right: 10px;
            width: 20px;
            color: #ffd700;
        }
        .dropdown-header {
            color: #ffd700;
            padding: 10px 20px;
            font-weight: 600;
            background: rgba(0,0,0,0.1);
        }
        .divider {
            background: rgba(255,255,255,0.1);
        }
        .navbar-toggle {
            border-color: #ffd700;
        }
        .navbar-toggle .icon-bar {
            background-color: #ffd700;
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
            background-color: rgba(255, 215, 0, 0.2);
        }
        .notification-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 3px;
            color: #fff;
        }
        .notification-message {
            font-size: 11px;
            color: #e0e0e0;
            margin-bottom: 3px;
        }
        .notification-time {
            font-size: 10px;
            color: #bdc3c7;
        }
        
        /* Profile Image */
        .profile-img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 8px;
            border: 2px solid #ffd700;
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
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#volunteerNavbar">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="/VMS2/index.php">
                    <i class="fa fa-handshake-o"></i> VMS - Arsi University
                </a>
            </div>
            
            <div class="collapse navbar-collapse" id="volunteerNavbar">
                <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'volunteer'): ?>
                    <!-- ========== LOGGED IN VOLUNTEER MENU ========== -->
                    <ul class="nav navbar-nav">
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'Volunteer.php' ? 'active' : ''; ?>">
                            <a href="/VMS2/volunteer/Volunteer.php"><i class="fa fa-dashboard"></i> Dashboard</a>
                        </li>
                        
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-calendar"></i> Events <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="/VMS2/volunteer/events.php"><i class="fa fa-calendar-plus-o"></i> Browse Events</a></li>
                                <li><a href="/VMS2/volunteer/joined_events.php"><i class="fa fa-list"></i> My Joined Events</a></li>
                            </ul>
                        </li>
                        
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                            <a href="/VMS2/volunteer/feedback.php"><i class="fa fa-star"></i> Feedback</a>
                        </li>
                    </ul>
                    
                    <ul class="nav navbar-nav navbar-right">
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-bell"></i>
                                <?php if ($unread_count > 0): ?>
                                    <span class="notification-badge"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu" style="min-width: 320px;">
                                <li class="dropdown-header"><i class="fa fa-bell"></i> Notifications</li>
                                <li class="divider"></li>
                                <?php if (count($notifications_list) > 0): ?>
                                    <?php foreach ($notifications_list as $notif): ?>
                                        <li class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                                            <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                            <div class="notification-message"><?php echo htmlspecialchars(substr($notif['message'], 0, 50)); ?>...</div>
                                            <div class="notification-time"><i class="fa fa-clock-o"></i> <?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><a href="javascript:void(0)" style="text-align: center;">No notifications</a></li>
                                <?php endif; ?>
                                <li class="divider"></li>
                                <li><a href="/VMS2/volunteer/notifications.php"><i class="fa fa-eye"></i> View All</a></li>
                            </ul>
                        </li>
                        
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown">
                                <?php if (!empty($_SESSION['profile_pic'])): ?>
                                    <img src="/VMS2/uploads/profiles/<?php echo $_SESSION['profile_pic']; ?>" class="profile-img" alt="Profile">
                                <?php else: ?>
                                    <i class="fa fa-user-circle"></i>
                                <?php endif; ?>
                                <?php echo $_SESSION['full_name'] ?? 'Volunteer'; ?> <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="/VMS2/volunteer/Volunteer.php"><i class="fa fa-id-card"></i> My Profile</a></li>
                                <li><a href="/VMS2/volunteer/edit.php"><i class="fa fa-edit"></i> Edit Profile</a></li>
                                <li><a href="/VMS2/volunteer/update_password.php"><i class="fa fa-key"></i> Change Password</a></li>
                                <li class="divider"></li>
                                <li><a href="/VMS2/logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
                            </ul>
                        </li>
                        
                    </ul>
                    
                <?php elseif(isset($_SESSION['user_id']) && $_SESSION['role'] == 'coordinator'): ?>
                    <!-- ========== LOGGED IN COORDINATOR MENU ========== -->
                    <ul class="nav navbar-nav">
                        <li><a href="/VMS2/coordinator/coordinator_dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown">Events <span class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <li><a href="/VMS2/coordinator/addevent3.php"><i class="fa fa-plus-circle"></i> Add Event</a></li>
                                <li><a href="/VMS2/coordinator/eventsallcompany.php"><i class="fa fa-list"></i> My Events</a></li>
                                <li><a href="/VMS2/coordinator/modifyevent.php"><i class="fa fa-edit"></i> Manage Events</a></li>
                            </ul>
                        </li>
                        <li><a href="/VMS2/coordinator/attendance.php"><i class="fa fa-check-square-o"></i> Attendance</a></li>
                        <li><a href="/VMS2/coordinator/volunteeraddcompany.php"><i class="fa fa-user-plus"></i> Add Volunteer</a></li>
                    </ul>
                    <ul class="nav navbar-nav navbar-right">
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-user"></i> <?php echo $_SESSION['full_name'] ?? 'Coordinator'; ?> <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="/VMS2/coordinator/CompanyProfile.php">Profile</a></li>
                                <li><a href="/VMS2/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                    
                <?php elseif(isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin'): ?>
                    <!-- ========== LOGGED IN ADMIN MENU ========== -->
                    <ul class="nav navbar-nav">
                        <li><a href="/VMS2/admin/admin_dashboard.php"><i class="fa fa-shield"></i> Admin Panel</a></li>
                    </ul>
                    <ul class="nav navbar-nav navbar-right">
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-user"></i> <?php echo $_SESSION['full_name'] ?? 'Admin'; ?> <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="/VMS2/admin/AdminProfile.php">Profile</a></li>
                                <li><a href="/VMS2/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                    
                <?php else: ?>
                    <!-- ========== PUBLIC MENU (NOT LOGGED IN) - WORKING DROPDOWNS ========== -->
                    <ul class="nav navbar-nav navbar-right">
                        <!-- Login Dropdown -->
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown" role="button">
                                <i class="fa fa-sign-in"></i> Login <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="/VMS2/volunteer/Login.php"><i class="fa fa-graduation-cap"></i> Volunteer Login</a></li>
                                <li><a href="/VMS2/coordinator/companyLogin.php"><i class="fa fa-building"></i> Coordinator Login</a></li>
                                <li><a href="/VMS2/admin/admin_login.php"><i class="fa fa-shield"></i> Admin Login</a></li>
                            </ul>
                        </li>
                        
                        <!-- Register Dropdown -->
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown" role="button">
                                <i class="fa fa-user-plus"></i> Register <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="/VMS2/volunteer/VolunteerRegistration.php"><i class="fa fa-graduation-cap"></i> Volunteer Sign Up</a></li>
                                <li><a href="/VMS2/coordinator/companySignup.php"><i class="fa fa-building"></i> Coordinator Sign Up</a></li>
                            </ul>
                        </li>
                        
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <script>
        $(document).ready(function() {
            // Manually initialize dropdowns to ensure they work
            $('.dropdown-toggle').dropdown();
            
            // Alternative: Handle dropdown clicks manually if needed
            $('.dropdown-toggle').on('click', function(e) {
                var $this = $(this);
                var $parent = $this.parent();
                
                // Close other dropdowns
                $('.dropdown').not($parent).removeClass('open');
                $('.dropdown-menu').not($this.next('.dropdown-menu')).hide();
                
                // Toggle current dropdown
                $parent.toggleClass('open');
                $this.next('.dropdown-menu').toggle();
                
                e.preventDefault();
                return false;
            });
            
            // Close dropdowns when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.dropdown').length) {
                    $('.dropdown').removeClass('open');
                    $('.dropdown-menu').hide();
                }
            });
            
            // Active page highlighting
            var currentPage = window.location.pathname;
            $('.nav li a').each(function() {
                var link = $(this).attr('href');
                if (link && currentPage.indexOf(link) !== -1 && link !== 'javascript:void(0)') {
                    $(this).closest('li').addClass('active');
                }
            });
        });
    </script>
</body>
</html>