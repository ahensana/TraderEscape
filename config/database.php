<?php
/**
 * Database Configuration for TraderEscape
 * Centralized database connection settings
 */

// Debug mode (set to true for development, false for production)
define('DEBUG_MODE', true);

// Application timezone (set to your local timezone)
define('APP_TIMEZONE', 'Asia/Kolkata'); // Change this to your timezone

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'traderescape_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Connection options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

// Database connection class
class Database {
    private static $instance = null;
    private $connection;
    private $connected = false;
    
    private function __construct() {
        $this->connect();
    }
    
    // Singleton pattern to ensure only one connection
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Establish database connection
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
            $this->connected = true;
            
            // Set timezone to UTC for consistency
            $this->connection->exec("SET time_zone = '+00:00'");
            
        } catch (PDOException $e) {
            $this->connected = false;
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
    
    // Get the PDO connection
    public function getConnection() {
        if (!$this->connected || !$this->connection) {
            $this->connect();
        }
        return $this->connection;
    }
    
    // Check if connected
    public function isConnected() {
        return $this->connected;
    }
    
    // Test the connection
    public function testConnection() {
        try {
            if (!$this->connection) {
                return false;
            }
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Close the connection
    public function closeConnection() {
        $this->connection = null;
        $this->connected = false;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {}
}

// Helper function to get database connection
function getDB() {
    return Database::getInstance()->getConnection();
}

// Helper function to check database status
function isDatabaseConnected() {
    try {
        $db = Database::getInstance();
        if (!$db->isConnected()) {
            return false;
        }
        return $db->testConnection();
    } catch (Exception $e) {
        error_log("Database connection check failed: " . $e->getMessage());
        return false;
    }
}

// Error handler for database errors
function handleDatabaseError($e, $context = '') {
    $errorMessage = "Database error";
    if ($context) {
        $errorMessage .= " in " . $context;
    }
    
    // Log the actual error for debugging
    error_log("Database Error: " . $e->getMessage() . " in " . $context);
    
    // Return user-friendly message
    return [
        'success' => false,
        'message' => $errorMessage,
        'debug' => DEBUG_MODE ? $e->getMessage() : null
    ];
}

// Initialize database connection
try {
    $db = Database::getInstance();
    // Connection will be established lazily when needed
} catch (Exception $e) {
    error_log("Database initialization failed: " . $e->getMessage());
    // Don't throw here - let individual pages handle connection failures gracefully
}
?>
