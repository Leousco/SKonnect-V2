<?php
require_once __DIR__ . '/../../vendor/autoload.php';

if (!isset($_ENV['DB_HOST']) && getenv('DB_HOST') === false) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
    $dotenv->load();
}

class Database {
    private $dsn;
    private $username;
    private $password;
    public  $conn;

    public function __construct() {
        $host   = trim($_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '');
        $port   = trim($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '');
        $dbname = trim($_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '');

        $this->dsn      = "pgsql:host={$host};port={$port};dbname={$dbname}";
        $this->username = trim($_ENV['DB_USER'] ?? getenv('DB_USER') ?: '');
        $this->password = trim($_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '');
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                $this->dsn,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "message" => "Database connection failed: " . $exception->getMessage()
            ]);
            exit;
        }
        return $this->conn;
    }
}
?>