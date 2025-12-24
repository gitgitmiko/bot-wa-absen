<?php
// Konfigurasi Database PostgreSQL
class Database {
    private $host;
    private $port;
    private $dbname;
    private $user;
    private $password;
    private $conn;

    public function __construct() {
        // Load dari .env atau gunakan default
        $this->host = getenv('DB_HOST') ?: '192.168.18.21';
        $this->port = getenv('DB_PORT') ?: '5432';
        $this->dbname = getenv('DB_NAME') ?: 'absensidb';
        $this->user = getenv('DB_USER') ?: 'admin';
        $this->password = getenv('DB_PASSWORD') ?: 'admindb';
    }

    public function connect() {
        if ($this->conn === null) {
            try {
                $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";
                $this->conn = new PDO($dsn, $this->user, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                error_log("Connection details: host={$this->host}, port={$this->port}, dbname={$this->dbname}, user={$this->user}");
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        return $this->conn;
    }

    public function getConnection() {
        return $this->connect();
    }
}

