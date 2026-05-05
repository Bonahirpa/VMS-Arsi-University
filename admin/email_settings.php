<?php
// admin/email_settings.php - Configure Email
require_once __DIR__ . '/../DBConnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: /VMS2/admin/admin_login.php");
    exit();
}

$message = '';
$error = '';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Handle form submission
if (isset($_POST['save_settings'])) {
    $settings = [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => '587',
        'smtp_username' => $_POST['smtp_username'] ?? '',
        'smtp_password' => $_POST['smtp_password'] ?? '',
        'from_email' => $_POST['from_email'] ?? '',
        'from_name' => $_POST['from_name'] ?? 'VMS - Arsi University',
        'email_enabled' => isset($_POST['email_enabled']) ? '1' : '0',
        'smtp_encryption' => 'tls'
    ];
    
    foreach ($settings as $key => $value) {
        executeQuery($db, 
            "INSERT INTO email_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            "ss", $key, $value
        );
    }
    
    $message = "Email settings saved successfully!";
}

// Handle test email
if (isset($_POST['send_test'])) {
    require_once __DIR__ . '/../email_config.php';
    $test_email = $_POST['test_email'] ?? '';
    
    if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $subject = "Test Email from VMS";
        $body = "<h2>Test Email</h2><p>If you receive this, your email configuration is working!</p>";
        
        if (sendEmail($db, $test_email, $subject, $body)) {
            $message = "Test email sent successfully!";
        } else {
            $error = "Failed to send test email. Check your settings.";
        }
    } else {
        $error = "Invalid email address.";
    }
}

// Get current settings
$settings = [];
$result = executeQuery($db, "SELECT setting_key, setting_value FROM email_settings");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Email Settings</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        body { background: #f4f7fc; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .container { background: white; border-radius: 15px; padding: 30px; margin-top: 30px; max-width: 600px; }
        .info-box { background: #e3f2fd; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-control { height: 45px; border: 2px solid #e0e0e0; border-radius: 8px; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/navbaradmin.php'; ?>

<div class="container">
    <h2><i class="fa fa-envelope"></i> Email Configuration</h2>
    
    <div class="info-box">
        <h4>How to get Gmail App Password:</h4>
        <ol>
            <li>Go to your <a href="https://myaccount.google.com/security" target="_blank">Google Account Security</a></li>
            <li>Enable <strong>2-Step Verification</strong></li>
            <li>Go to <strong>App Passwords</strong> (under "Signing in to Google")</li>
            <li>Select: App = <strong>Mail</strong>, Device = <strong>Other</strong> (name it "VMS")</li>
            <li>Copy the 16-character password</li>
        </ol>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        
        <div class="checkbox">
            <label>
                <input type="checkbox" name="email_enabled" <?php echo ($settings['email_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                <strong>Enable Email Notifications</strong>
            </label>
        </div>
        
        <div class="form-group">
            <label>Gmail Address</label>
            <input type="email" class="form-control" name="smtp_username" 
                   value="<?php echo htmlspecialchars($settings['smtp_username'] ?? ''); ?>" 
                   placeholder="your.email@gmail.com" required>
        </div>
        
        <div class="form-group">
            <label>Gmail App Password</label>
            <input type="password" class="form-control" name="smtp_password" 
                   value="<?php echo htmlspecialchars($settings['smtp_password'] ?? ''); ?>" 
                   placeholder="16-character app password" required>
            <small class="text-muted">This is NOT your regular Gmail password</small>
        </div>
        
        <div class="form-group">
            <label>From Email</label>
            <input type="email" class="form-control" name="from_email" 
                   value="<?php echo htmlspecialchars($settings['from_email'] ?? $settings['smtp_username'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label>From Name</label>
            <input type="text" class="form-control" name="from_name" 
                   value="<?php echo htmlspecialchars($settings['from_name'] ?? 'VMS - Arsi University'); ?>" required>
        </div>
        
        <button type="submit" name="save_settings" class="btn btn-primary btn-block">Save Settings</button>
    </form>
    
    <hr>
    
    <h3>Test Email</h3>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="form-group">
            <label>Send test email to:</label>
            <div class="input-group">
                <input type="email" class="form-control" name="test_email" placeholder="your@email.com" required>
                <span class="input-group-btn">
                    <button type="submit" name="send_test" class="btn btn-info">Send Test</button>
                </span>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>