<?php
// DBConnect.php - Database connection with security functions
session_start();

$host = 'localhost';
$port = 8889;        // MAMP MySQL port
$user = 'root';      // MAMP default user
$password = 'root';  // MAMP default password
$dbname = 'vms2';    // Database name as requested

// Create connection
$db = new mysqli($host, $user, $password, $dbname, $port);

// Check connection
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Set charset
$db->set_charset("utf8mb4");

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Execute prepared statement and return result
 */
function executeQuery($db, $sql, $types = "", ...$params) {
    try {
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    } catch (Exception $e) {
        error_log("Query Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get single row from database
 */
function getRow($db, $sql, $types = "", ...$params) {
    $result = executeQuery($db, $sql, $types, ...$params);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Insert data and return inserted ID
 */
function insertData($db, $sql, $types = "", ...$params) {
    try {
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $insert_id = $stmt->insert_id;
        $stmt->close();
        return $insert_id;
    } catch (Exception $e) {
        error_log("Insert Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log user activity
 */
function logActivity($db, $user_id, $action, $table_name, $record_id = null, $details = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $sql = "INSERT INTO activity_log (user_id, action, table_name, record_id, details, ip_address, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ississ", $user_id, $action, $table_name, $record_id, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

/**
 * Check authentication and role
 */
function checkAuth($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /VMS2/index.php");
        exit();
    }
    
    if ($required_role && $_SESSION['role'] !== $required_role) {
        header("Location: /VMS2/unauthorized.php");
        exit();
    }
    
    return true;
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Set timezone
date_default_timezone_set('Africa/Addis_Ababa');

// Set current user for triggers
if (isset($_SESSION['user_id'])) {
    $db->query("SET @current_user = {$_SESSION['user_id']}");
}

// ============================================
// NOTIFICATION FUNCTIONS
// ============================================

/**
 * Create a notification for a specific user
 */
function createNotification($db, $user_id, $title, $message, $type = 'info') {
    $sql = "INSERT INTO notifications (user_id, title, message, type, created_at, is_read) 
            VALUES (?, ?, ?, ?, NOW(), 0)";
    $stmt = $db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $title, $message, $type);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    return false;
}

/**
 * Create notification for all admins
 */
function notifyAllAdmins($db, $title, $message, $type = 'info') {
    $admins = executeQuery($db, "SELECT user_id FROM admins");
    if ($admins && $admins->num_rows > 0) {
        while($admin = $admins->fetch_assoc()) {
            createNotification($db, $admin['user_id'], $title, $message, $type);
        }
        return true;
    }
    return false;
}

/**
 * Get unread notification count for a user
 */
function getUnreadCount($db, $user_id) {
    $result = getRow($db, "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0", "i", $user_id);
    return $result['count'] ?? 0;
}

?>