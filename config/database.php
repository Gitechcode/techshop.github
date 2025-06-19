<?php
// Database configuration with better error handling
class Database {
    private $host = 'localhost';
    private $db_name = 'tech_shop';  // Correct database name
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            // Create connection with proper options
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch(PDOException $e) {
            // Log the error and show user-friendly message
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Show detailed error in development
            if (defined('DEVELOPMENT') && DEVELOPMENT) {
                die("Database Connection Error: " . $e->getMessage());
            } else {
                die("Database connection failed. Please try again later.");
            }
        }
        
        return $this->conn;
    }
    
    // Test connection method
    public function testConnection() {
        try {
            $this->getConnection();
            return true;
        } catch(Exception $e) {
            return false;
        }
    }
}

// Global database instance
$database = new Database();

try {
    $pdo = new PDO("mysql:host=localhost;dbname=tech_shop;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
