<?php
// email_config.php - COMPLETE WORKING VERSION
require_once __DIR__ . '/DBConnect.php';

// Load PHPMailer - FIXED PATHS
$phpmailer_loaded = false;

// Check multiple possible paths
$possible_paths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/vendor/PHPMailer/src/PHPMailer.php',
    __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php'
];

foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        if (basename($path) == 'autoload.php') {
            require_once $path;
            $phpmailer_loaded = true;
            break;
        } else {
            require_once dirname($path) . '/PHPMailer.php';
            require_once dirname($path) . '/SMTP.php';
            require_once dirname($path) . '/Exception.php';
            $phpmailer_loaded = true;
            break;
        }
    }
}

/**
 * Get email settings from database
 */
function getEmailSettings($db) {
    $settings = [];
    $result = executeQuery($db, "SELECT setting_key, setting_value FROM email_settings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    // Default settings
    $defaults = [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => '587',
        'smtp_username' => '',
        'smtp_password' => '',
        'from_email' => 'noreply@vms.arsi.edu.et',
        'from_name' => 'VMS - Arsi University',
        'email_enabled' => '0',
        'smtp_encryption' => 'tls'
    ];
    
    foreach ($defaults as $key => $value) {
        if (!isset($settings[$key])) {
            $settings[$key] = $value;
        }
    }
    
    return $settings;
}

/**
 * Send email using PHPMailer
 */
function sendEmail($db, $to, $subject, $message, $from = null, $fromName = null) {
    global $phpmailer_loaded;
    
    $settings = getEmailSettings($db);
    
    // Check if email is enabled
    if (!isset($settings['email_enabled']) || $settings['email_enabled'] != '1') {
        error_log("Email sending is disabled");
        return false;
    }
    
    // Check if PHPMailer is loaded
    if (!$phpmailer_loaded || !class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer not loaded");
        return false;
    }
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['smtp_username'];
        $mail->Password   = $settings['smtp_password'];
        $mail->SMTPSecure = $settings['smtp_encryption'];
        $mail->Port       = $settings['smtp_port'];
        
        // Timeout settings
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;
        
        // Disable SSL verification for local testing
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Recipients
        $fromEmail = $from ?? $settings['from_email'];
        $fromName = $fromName ?? $settings['from_name'];
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->addReplyTo($settings['from_email'], $settings['from_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $message));
        
        $mail->send();
        error_log("Email sent successfully to: $to");
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send approval email to coordinator
 */
function sendApprovalEmail($db, $coordinator_id, $coordinator_name, $coordinator_email) {
    $subject = "Your Coordinator Account Has Been Approved - VMS Arsi University";
    
    // Get base URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8888';
    $base_url = $protocol . $host . '/VMS2';
    $login_url = $base_url . '/coordinator/companyLogin.php';
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 20px auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; background: #f9f9f9; }
            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { background: #eee; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Account Approved! ✅</h1>
            </div>
            <div class='content'>
                <h2>Dear " . htmlspecialchars($coordinator_name) . ",</h2>
                
                <p>Congratulations! Your coordinator account for the <strong>Volunteer Management System</strong> at Arsi University has been approved.</p>
                
                <p><strong>Your login details:</strong></p>
                <ul>
                    <li><strong>Username:</strong> " . htmlspecialchars($coordinator_name) . "</li>
                    <li><strong>Email:</strong> " . htmlspecialchars($coordinator_email) . "</li>
                </ul>
                
                <p>You can now:</p>
                <ul>
                    <li>✓ Create and manage volunteer events</li>
                    <li>✓ Track volunteer attendance</li>
                    <li>✓ Add volunteers to events</li>
                    <li>✓ View feedback from volunteers</li>
                </ul>
                
                <div style='text-align: center;'>
                    <a href='" . $login_url . "' class='button'>Login to Your Account</a>
                </div>
                
                <p>If you have any questions, please contact the system administrator.</p>
                
                <p>Best regards,<br>
                <strong>VMS Administration Team</strong><br>
                Arsi University</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " Arsi University</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($db, $coordinator_email, $subject, $message);
}
?>