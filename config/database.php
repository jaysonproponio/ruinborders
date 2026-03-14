

    <?php
// Database configuration
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Auto-detect environment and set database credentials
        if ($this->isLocalEnvironment()) {
            // Local development settings
            $this->host = 'localhost';
            $this->db_name = 'ruin_borders';
            $this->username = 'root';
            $this->password = '';
        } else {
            // Production settings (InfinityFree)
            // IMPORTANT: Use the EXACT host from the InfinityFree control panel (usually sqlXXX.epizy.com)
            $this->host = getenv('IF_DB_HOST') ?: 'sql311.byetcluster.com';
            $this->db_name = getenv('IF_DB_NAME') ?: 'if0_39936005_ruinborder_db';
            $this->username = getenv('IF_DB_USER') ?: 'if0_39936005';
            $this->password = getenv('IF_DB_PASS') ?: 'Tisoiigamay1124';
        }
    }

    private function isLocalEnvironment() {
        // Check if we're running locally
        $host = $_SERVER['HTTP_HOST'] ?? '';
        return strpos($host, 'localhost') !== false || 
               strpos($host, '127.0.0.1') !== false ||
               strpos($host, '::1') !== false;
    }

    public function getConnection() {
        $this->conn = null;
        
        if (!$this->isLocalEnvironment()) {
            try {
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => false,
                ];
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                // Ensure MySQL session uses Philippine timezone
                try { $this->conn->exec("SET time_zone = '+08:00'"); } catch (Throwable $e) {}
            } catch(PDOException $e) {
                echo "Connection error: " . $e->getMessage() . " (host=" . htmlspecialchars($this->host) . ", db=" . htmlspecialchars($this->db_name) . ")";
                return null;
            }
        } else {
            // Local connection
            try {
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => false,
                ];
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
                // Ensure MySQL session uses Philippine timezone
                try { $this->conn->exec("SET time_zone = '+08:00'"); } catch (Throwable $e) {}
            } catch(PDOException $e) {
                echo "Connection error: " . $e->getMessage();
            }
        }
        
        return $this->conn;
    }
}
?>
