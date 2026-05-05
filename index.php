<?php
// index.php - Homepage with Background Image
require_once "DBConnect.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Volunteer Management System - Arsi University</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
<style>
    .hero-section {
        background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                    url('/VMS2/images/Volunteering-thumb.jpg');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        color: white;
        padding: 120px 0;
        text-align: center;
        position: relative;
    }
    
    /* Animated overlay effect */
    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle, transparent 30%, rgba(0,0,0,0.3) 100%);
        pointer-events: none;
    }
    
    .hero-title {
        font-size: 48px;
        font-weight: bold;
        margin-bottom: 20px;
        animation: fadeInDown 1s;
        position: relative;
        z-index: 2;
    }
    
    .hero-subtitle {
        font-size: 20px;
        margin-bottom: 40px;
        opacity: 0.95;
        animation: fadeInUp 1s;
        position: relative;
        z-index: 2;
    }
    
    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .cta-button {
        display: inline-block;
        padding: 15px 40px;
        margin: 10px;
        font-size: 18px;
        font-weight: 600;
        color: #667eea;
        background: white;
        border: none;
        border-radius: 50px;
        text-decoration: none;
        transition: all 0.3s;
        position: relative;
        z-index: 2;
    }
    
    .cta-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        text-decoration: none;
        color: #667eea;
    }
    
    .cta-button-outline {
        background: transparent;
        color: white;
        border: 2px solid white;
    }
    
    .cta-button-outline:hover {
        background: white;
        color: #667eea;
    }
    
    .stats-section {
        padding: 60px 0;
        background: white;
    }
    
    .stat-card {
        text-align: center;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        transition: transform 0.3s;
        background: white;
    }
    
    .stat-card:hover {
        transform: translateY(-10px);
    }
    
    .stat-icon {
        font-size: 48px;
        color: #667eea;
        margin-bottom: 20px;
    }
    
    .stat-number {
        font-size: 36px;
        font-weight: bold;
        color: #333;
    }
    
    .stat-label {
        font-size: 18px;
        color: #666;
    }
    
    .features-section {
        padding: 60px 0;
        background: #f8f9fa;
    }
    
    .feature-card {
        text-align: center;
        padding: 30px;
        margin-bottom: 30px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }
    
    .feature-icon {
        font-size: 40px;
        color: #667eea;
        margin-bottom: 20px;
    }
    
    .feature-title {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 15px;
        color: #333;
    }
    
    .feature-description {
        color: #666;
    }
    
    @media (max-width: 768px) {
        .hero-title { font-size: 32px; }
        .hero-subtitle { font-size: 16px; }
        .stat-card { margin-bottom: 20px; }
    }
</style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<!-- Hero Section with Background Image -->
<section class="hero-section">
    <div class="container">
        <h1 class="hero-title">Volunteer Management System</h1>
        <p class="hero-subtitle">Empowering students to make a difference through community service at Arsi University</p>
        
        <?php if(!isset($_SESSION['user_id'])): ?>
            <div>
                <a href="/VMS2/volunteer/VolunteerRegistration.php" class="cta-button">
                    <i class="fa fa-user-plus"></i> Join as Volunteer
                </a>
                <a href="/VMS2/coordinator/companySignup.php" class="cta-button cta-button-outline">
                    <i class="fa fa-building"></i> Register as Coordinator
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section">
    <div class="container">
        <div class="row">
            <?php
            $total_volunteers = getRow($db, "SELECT COUNT(*) as count FROM volunteers")['count'] ?? 0;
            $total_activities = getRow($db, "SELECT COUNT(*) as count FROM activities WHERE status = 'published'")['count'] ?? 0;
            $active_events = getRow($db, "SELECT COUNT(*) as count FROM activities WHERE activity_date >= CURDATE() AND status = 'published'")['count'] ?? 0;
            ?>
            
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa fa-users"></i></div>
                    <div class="stat-number"><?php echo number_format($total_volunteers); ?></div>
                    <div class="stat-label">Active Volunteers</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa fa-calendar-check-o"></i></div>
                    <div class="stat-number"><?php echo number_format($total_activities); ?></div>
                    <div class="stat-label">Total Events</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa fa-bullhorn"></i></div>
                    <div class="stat-number"><?php echo number_format($active_events); ?></div>
                    <div class="stat-label">Active Events</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <h2 class="text-center" style="margin-bottom: 50px; font-weight: bold;">Key Features</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa fa-hand-pointer-o"></i></div>
                    <h3 class="feature-title">Easy Registration</h3>
                    <p class="feature-description">Register using your Arsi University ID and start participating immediately.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa fa-calendar-plus-o"></i></div>
                    <h3 class="feature-title">Event Management</h3>
                    <p class="feature-description">Coordinators can create, update, and manage volunteer activities with ease.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fa fa-check-square-o"></i></div>
                    <h3 class="feature-title">Attendance Tracking</h3>
                    <p class="feature-description">Digital attendance recording and participation history for all volunteers.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Upcoming Events -->
<section style="padding: 60px 0; background: white;">
    <div class="container">
        <h2 class="text-center" style="margin-bottom: 50px; font-weight: bold;">Upcoming Events</h2>
        <div class="row">
            <?php
            $events = executeQuery($db, 
                "SELECT a.*, u.full_name as coordinator_name 
                 FROM activities a 
                 JOIN coordinators c ON a.coordinator_id = c.coordinator_id
                 JOIN users u ON c.coordinator_id = u.user_id
                 WHERE a.activity_date >= CURDATE() AND a.status = 'published'
                 ORDER BY a.activity_date ASC LIMIT 6"
            );
            
            if ($events && $events->num_rows > 0):
                while($event = $events->fetch_assoc()):
            ?>
            <div class="col-md-4 col-sm-6">
                <div class="feature-card" style="text-align: left;">
                    <h4 style="color: #667eea;"><i class="fa fa-calendar"></i> <?php echo date('M d, Y', strtotime($event['activity_date'])); ?></h4>
                    <h3 style="font-size: 20px; font-weight: bold;"><?php echo sanitize($event['title']); ?></h3>
                    <p><i class="fa fa-map-marker"></i> <?php echo sanitize($event['location']); ?></p>
                    <a href="/VMS2/volunteer/Login.php" class="btn btn-primary btn-sm">Login to Join</a>
                </div>
            </div>
            <?php 
                endwhile;
            endif; 
            ?>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>

</body>
</html>