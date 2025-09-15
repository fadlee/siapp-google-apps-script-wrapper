<?php
/**
 * Authentication Class for SiApp Control Panel
 */

class AuthManager
{
    private static $config;
    
    /**
     * Initialize authentication with config
     */
    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!self::$config) {
            self::$config = require_once __DIR__ . '/auth.php';
        }
    }
    
    /**
     * Authenticate user with username and password
     */
    public static function login($username, $password, $remember = false)
    {
        self::init();
        
        if ($username === self::$config['username'] && $password === self::$config['password']) {
            $_SESSION['authenticated'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            
            if ($remember) {
                $expire = time() + self::$config['remember_duration'];
                setcookie('siapp_remember', hash('sha256', $username . self::$config['password']), $expire, '/');
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated()
    {
        self::init();
        
        // Check session authentication
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            // Check session timeout
            if (isset($_SESSION['last_activity']) && 
                (time() - $_SESSION['last_activity'] > self::$config['session_timeout'])) {
                self::logout();
                return false;
            }
            
            $_SESSION['last_activity'] = time();
            return true;
        }
        
        // Check remember cookie
        if (isset($_COOKIE['siapp_remember'])) {
            $expectedHash = hash('sha256', self::$config['username'] . self::$config['password']);
            if ($_COOKIE['siapp_remember'] === $expectedHash) {
                // Auto-login from cookie
                $_SESSION['authenticated'] = true;
                $_SESSION['username'] = self::$config['username'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Logout user and destroy session
     */
    public static function logout()
    {
        self::init();
        
        // Clear session
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Clear remember cookie
        setcookie('siapp_remember', '', time() - 3600, '/');
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Require authentication or redirect to login
     */
    public static function requireAuth()
    {
        if (!self::isAuthenticated()) {
            $currentUrl = $_SERVER['REQUEST_URI'];
            $loginUrl = '/cp/login.php';
            
            // Add redirect parameter if not already on login page
            if ($currentUrl !== $loginUrl) {
                $loginUrl .= '?redirect=' . urlencode($currentUrl);
            }
            
            header('Location: ' . $loginUrl);
            exit();
        }
    }
    
    /**
     * Get current authenticated username
     */
    public static function getUsername()
    {
        self::init();
        return isset($_SESSION['username']) ? $_SESSION['username'] : null;
    }
    
    /**
     * Get time since login
     */
    public static function getLoginDuration()
    {
        self::init();
        if (isset($_SESSION['login_time'])) {
            return time() - $_SESSION['login_time'];
        }
        return 0;
    }
}