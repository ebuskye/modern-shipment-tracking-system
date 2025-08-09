<?php
/**
 * Database Configuration
 * Update these settings according to your database setup
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'shipment_tracking';
    private $username = 'root'; // Change to your database username
    private $password = ''; // Change to your database password
    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            die();
        }

        return $this->conn;
    }
}

// Global database connection function
function getDbConnection() {
    $database = new Database();
    return $database->connect();
}
?>