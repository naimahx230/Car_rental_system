<?php
// includes/session_helper.php - Session management helper

class SessionManager {
    
    // Start customer session
    public static function startCustomerSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        session_name('URBAN_WHEELS_CUSTOMER');
        session_start();
    }
    
    // Start admin session
    public static function startAdminSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        session_name('URBAN_WHEELS_ADMIN');
        session_start();
    }
    
    // Destroy customer session
    public static function destroyCustomerSession() {
        // Store current session name
        $current_name = session_name();
        
        session_name('URBAN_WHEELS_CUSTOMER');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        
        // Restore original session name if needed
        if (!empty($current_name) && $current_name !== 'URBAN_WHEELS_CUSTOMER') {
            session_name($current_name);
        }
    }
    
    // Destroy admin session
    public static function destroyAdminSession() {
        // Store current session name
        $current_name = session_name();
        
        session_name('URBAN_WHEELS_ADMIN');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        
        // Restore original session name if needed
        if (!empty($current_name) && $current_name !== 'URBAN_WHEELS_ADMIN') {
            session_name($current_name);
        }
    }
    
    // Destroy both sessions
    public static function destroyAllSessions() {
        // Destroy customer session
        session_name('URBAN_WHEELS_CUSTOMER');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        
        // Destroy admin session
        session_name('URBAN_WHEELS_ADMIN');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
    }
    
    // Check if customer is logged in
    public static function isCustomerLoggedIn() {
        session_name('URBAN_WHEELS_CUSTOMER');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer';
    }
    
    // Check if admin is logged in
    public static function isAdminLoggedIn() {
        session_name('URBAN_WHEELS_ADMIN');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    // Get customer session data
    public static function getCustomerSession() {
        session_name('URBAN_WHEELS_CUSTOMER');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION;
    }
    
    // Get admin session data
    public static function getAdminSession() {
        session_name('URBAN_WHEELS_ADMIN');
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION;
    }
}
?>