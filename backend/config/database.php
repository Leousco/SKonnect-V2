<?php
class Database {
    private $dsn = "pgsql:host=aws-0-ap-southeast-1.pooler.supabase.com;port=5432;dbname=postgres";
    private $username = "postgres.qexsehfkcwkvhijovhvn";
    private $password = "sbit3f3rdyrstudents"; 
    public $conn;

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