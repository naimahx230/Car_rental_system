<?php
class Security {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->initSecurityTables();
    }
    
    // Initialize security tables if they don't exist
    private function initSecurityTables() {
        // Login attempts table
        $login_attempts_table = "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100),
            ip_address VARCHAR(45),
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_ip (ip_address)
        )";
        mysqli_query($this->conn, $login_attempts_table);
        
        // Security logs table
        $security_logs_table = "CREATE TABLE IF NOT EXISTS security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(100),
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )";
        mysqli_query($this->conn, $security_logs_table);
        
        // User sessions table
        $user_sessions_table = "CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_token VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (session_token),
            INDEX idx_active (is_active)
        )";
        mysqli_query($this->conn, $user_sessions_table);
        
        // Add security columns to users table if missing
        $alter_users = "ALTER TABLE users 
                        ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0,
                        ADD COLUMN IF NOT EXISTS account_locked_until TIMESTAMP NULL,
                        ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45) NULL,
                        ADD COLUMN IF NOT EXISTS last_login_time TIMESTAMP NULL";
        mysqli_query($this->conn, $alter_users);
    }
    
    // Sanitize input
    public function sanitize($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    // Validate email
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    // Validate phone number (Kenyan format)
    public function validatePhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        // Check for 07xx or 01xx or 2547xx
        return preg_match('/^(07|01|2547)\d{8}$/', $phone) || preg_match('/^(\+254|0)?7\d{8}$/', $phone);
    }
    
    // Validate password strength
    public function validatePasswordStrength($password) {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        return $errors;
    }
    
    // Check login attempts
    public function checkLoginAttempts($email, $ip) {
        $email = mysqli_real_escape_string($this->conn, $email);
        $ip = mysqli_real_escape_string($this->conn, $ip);
        
        $check = mysqli_query($this->conn, "SELECT COUNT(*) as attempts FROM login_attempts 
                    WHERE (email = '$email' OR ip_address = '$ip') 
                    AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $result = mysqli_fetch_assoc($check);
        
        if ($result && $result['attempts'] >= 5) {
            return false;
        }
        return true;
    }
    
    // Log login attempt
    public function logLoginAttempt($email, $ip) {
        $email = mysqli_real_escape_string($this->conn, $email);
        $ip = mysqli_real_escape_string($this->conn, $ip);
        mysqli_query($this->conn, "INSERT INTO login_attempts (email, ip_address) VALUES ('$email', '$ip')");
    }
    
    // Clear login attempts
    public function clearLoginAttempts($email) {
        $email = mysqli_real_escape_string($this->conn, $email);
        mysqli_query($this->conn, "DELETE FROM login_attempts WHERE email = '$email'");
    }
    
    // Generate CSRF token
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Verify CSRF token
    public function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
        return true;
    }
    
    // Generate secure random token
    public function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    // Log security event
    public function logSecurityEvent($user_id, $action, $details = null) {
        $user_id = $user_id ? (int)$user_id : 'NULL';
        $action = mysqli_real_escape_string($this->conn, $action);
        $details = $details ? mysqli_real_escape_string($this->conn, $details) : null;
        $ip = mysqli_real_escape_string($this->conn, $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $user_agent = mysqli_real_escape_string($this->conn, $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        
        $query = "INSERT INTO security_logs (user_id, action, details, ip_address, user_agent) 
                  VALUES ($user_id, '$action', " . ($details ? "'$details'" : "NULL") . ", '$ip', '$user_agent')";
        return mysqli_query($this->conn, $query);
    }
    
    // Check if account is locked
    public function isAccountLocked($email) {
        $email = mysqli_real_escape_string($this->conn, $email);
        $query = "SELECT account_locked_until FROM users WHERE email = '$email'";
        $result = mysqli_query($this->conn, $query);
        $user = mysqli_fetch_assoc($result);
        
        if ($user && $user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
            return true;
        }
        return false;
    }
    
    // Increment failed login attempts
    public function incrementFailedAttempts($email) {
        $email = mysqli_real_escape_string($this->conn, $email);
        mysqli_query($this->conn, "UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE email = '$email'");
        
        // Lock account after 10 failed attempts
        $check = mysqli_query($this->conn, "SELECT failed_login_attempts FROM users WHERE email = '$email'");
        $result = mysqli_fetch_assoc($check);
        if ($result && $result['failed_login_attempts'] >= 10) {
            $lock_until = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            mysqli_query($this->conn, "UPDATE users SET account_locked_until = '$lock_until' WHERE email = '$email'");
            $this->logSecurityEvent(0, 'account_locked', "Account locked for email: $email");
        }
    }
    
    // Reset failed login attempts
    public function resetFailedAttempts($email) {
        $email = mysqli_real_escape_string($this->conn, $email);
        mysqli_query($this->conn, "UPDATE users SET failed_login_attempts = 0, account_locked_until = NULL WHERE email = '$email'");
    }
    
    // Create user session
    public function createSession($user_id) {
        $session_token = $this->generateToken();
        $ip = mysqli_real_escape_string($this->conn, $_SERVER['REMOTE_ADDR']);
        $user_agent = mysqli_real_escape_string($this->conn, $_SERVER['HTTP_USER_AGENT']);
        $user_id = (int)$user_id;
        
        // Invalidate old sessions
        mysqli_query($this->conn, "UPDATE user_sessions SET is_active = FALSE WHERE user_id = $user_id");
        
        $query = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent) VALUES ($user_id, '$session_token', '$ip', '$user_agent')";
        mysqli_query($this->conn, $query);
        
        $_SESSION['session_token'] = $session_token;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['login_time'] = time();
        
        // Update last login info
        mysqli_query($this->conn, "UPDATE users SET last_login_ip = '$ip', last_login_time = NOW() WHERE id = $user_id");
        
        $this->logSecurityEvent($user_id, 'login_success', 'User logged in successfully');
        
        return $session_token;
    }
    
    // Validate session
    public function validateSession($user_id, $session_token) {
        $session_token = mysqli_real_escape_string($this->conn, $session_token);
        $user_id = (int)$user_id;
        
        $query = "SELECT id FROM user_sessions WHERE user_id = $user_id AND session_token = '$session_token' AND is_active = TRUE 
                  AND last_activity > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $result = mysqli_query($this->conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            // Update last activity
            mysqli_query($this->conn, "UPDATE user_sessions SET last_activity = NOW() WHERE session_token = '$session_token'");
            return true;
        }
        return false;
    }
    
    // Destroy session
    public function destroySession($user_id) {
        $user_id = (int)$user_id;
        mysqli_query($this->conn, "UPDATE user_sessions SET is_active = FALSE WHERE user_id = $user_id");
        $this->logSecurityEvent($user_id, 'logout', 'User logged out');
    }
    
    // Rate limiting
    public function checkRateLimit($key, $limit = 10, $window = 60) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $cache_key = "rate_limit_{$key}_{$ip}";
        
        if (!isset($_SESSION[$cache_key])) {
            $_SESSION[$cache_key] = ['count' => 1, 'first_request' => time()];
            return true;
        }
        
        $data = $_SESSION[$cache_key];
        if (time() - $data['first_request'] > $window) {
            $_SESSION[$cache_key] = ['count' => 1, 'first_request' => time()];
            return true;
        }
        
        if ($data['count'] >= $limit) {
            return false;
        }
        
        $_SESSION[$cache_key]['count']++;
        return true;
    }
    
    // Validate ID (ensure it's a positive integer)
    public function validateId($id) {
        return is_numeric($id) && $id > 0 && $id == (int)$id;
    }
    
    // XSS Prevention
    public function xssClean($data) {
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
?>