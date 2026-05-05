<?php
// forgot_password_functions.php - Common functions for password reset
require_once __DIR__ . '/DBConnect.php';
require_once __DIR__ . '/email_config.php';

/**
 * Generate a random secure token
 */
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Send password reset email
 */
function sendResetEmail($db, $email, $token, $role) {
    $user = getRow($db, 
        "SELECT user_id, full_name, username FROM users WHERE email = ? AND role = ?", 
        "ss", $email, $role
    );
    
    if (!$user) {
        return false;
    }
    
    // Create reset link
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8888';
    $reset_link = $protocol . $host . "/VMS2/" . $role . "/reset_password.php?token=" . $token;
    
    $subject = "Password Reset Request - VMS Arsi University";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 20px auto; border-radius: 10px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
            .content { background: #ffffff; padding: 30px; }
            .button { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .button:hover { background: #5a6fd1; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .warning { color: #dc3545; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Password Reset Request</h1>
            </div>
            <div class='content'>
                <h2>Dear " . htmlspecialchars($user['full_name']) . ",</h2>
                
                <p>We received a request to reset your password for your " . ucfirst($role) . " account at the Volunteer Management System.</p>
                
                <p><strong>Username:</strong> " . htmlspecialchars($user['username']) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                
                <div style='text-align: center;'>
                    <a href='" . $reset_link . "' class='button'>Reset Password</a>
                </div>
                
                <p class='warning'>This link will expire in 1 hour.</p>
                
                <p>If you did not request a password reset, please ignore this email or contact the administrator.</p>
                
                <p>Best regards,<br>
                <strong>VMS Administration Team</strong><br>
                Arsi University</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " Arsi University - Volunteer Management System</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($db, $email, $subject, $message);
}

/**
 * Store reset token in database
 */
/**
 * Store reset token in database - DEBUG VERSION
 */
function storeResetToken($db, $user_id, $email, $token, $role) {
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    echo "<!-- DEBUG: Storing token - User: $user_id, Email: $email, Token: $token, Expires: $expires -->\n";
    
    // First, delete any existing tokens for this user
    $delete_result = executeQuery($db, "DELETE FROM password_resets WHERE user_id = ?", "i", $user_id);
    echo "<!-- DEBUG: Delete old tokens: " . ($delete_result ? "Success" : "Failed") . " -->\n";
    
    // Insert new token
    $sql = "INSERT INTO password_resets (user_id, email, token, role, expires_at) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("issss", $user_id, $email, $token, $role, $expires);
    
    if ($stmt->execute()) {
        $insert_id = $stmt->insert_id;
        echo "<!-- DEBUG: Token stored successfully with ID: $insert_id -->\n";
        
        // Verify it was stored
        $check = getRow($db, "SELECT * FROM password_resets WHERE token = ?", "s", $token);
        if ($check) {
            echo "<!-- DEBUG: Verification: Token found in database -->\n";
        } else {
            echo "<!-- DEBUG: Verification: Token NOT found in database after insert! -->\n";
        }
        
        $stmt->close();
        return $insert_id;
    } else {
        echo "<!-- DEBUG: Failed to store token: " . $stmt->error . " -->\n";
        $stmt->close();
        return false;
    }
}

/**
 * Validate reset token
 */
function validateResetToken($db, $token, $role) {
    $reset = getRow($db,
        "SELECT * FROM password_resets 
         WHERE token = ? AND role = ? AND used = 0 AND expires_at > NOW()",
        "ss", $token, $role
    );
    
    return $reset;
}

/**
 * Mark token as used - DEBUG VERSION
 */
function markTokenAsUsed($db, $token) {
    echo "<!-- DEBUG: Marking token as used: $token -->\n";
    
    $sql = "UPDATE password_resets SET used = 1 WHERE token = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $token);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        echo "<!-- DEBUG: Token update affected rows: $affected -->\n";
        
        if ($affected > 0) {
            $stmt->close();
            return true;
        }
    } else {
        echo "<!-- DEBUG: Token update failed: " . $stmt->error . " -->\n";
    }
    
    $stmt->close();
    return false;
}
/**
 * Update user password
 */
/**
 * Update user password - DEBUG VERSION
 */
function updateUserPassword($db, $user_id, $new_password) {
    echo "<!-- DEBUG: Starting password update for user: $user_id -->\n";
    
    // Check if user exists
    $user_check = getRow($db, "SELECT user_id FROM users WHERE user_id = ?", "i", $user_id);
    if (!$user_check) {
        echo "<!-- DEBUG: User $user_id does not exist! -->\n";
        return false;
    }
    echo "<!-- DEBUG: User $user_id exists -->\n";
    
    // Hash the new password
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    echo "<!-- DEBUG: Password hashed successfully -->\n";
    
    // Update the database using direct mysqli
    $sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("si", $hash, $user_id);
    
    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        echo "<!-- DEBUG: Update executed. Affected rows: $affected -->\n";
        
        if ($affected > 0) {
            // Verify the update
            $check = getRow($db, 
                "SELECT user_id FROM users WHERE user_id = ? AND password_hash = ?", 
                "is", $user_id, $hash
            );
            
            if ($check) {
                echo "<!-- DEBUG: Password update verified successfully -->\n";
                $stmt->close();
                return true;
            } else {
                echo "<!-- DEBUG: Password update could not be verified -->\n";
            }
        } else {
            echo "<!-- DEBUG: No rows affected. Password may be same as old? -->\n";
        }
    } else {
        echo "<!-- DEBUG: Execute failed: " . $stmt->error . " -->\n";
    }
    
    $stmt->close();
    return false;
}