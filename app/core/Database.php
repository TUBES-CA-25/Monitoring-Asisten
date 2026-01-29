<?php
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $db_name = DB_NAME;

    private $dbh; // Database Handler
    private $stmt; // Statement

    public function __construct() {
        // Data Source Name
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name;
        
        $option = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $option);
        } catch(PDOException $e) {
            die("Koneksi Database Gagal: " . $e->getMessage());
        }
    }

    // --- PENTING UNTUK MODEL ANDA ---
    // Method ini dibutuhkan agar UserModel lama Anda tetap jalan
    public function getConnection() {
        return $this->dbh;
    }
    // --------------------------------

    // Menyiapkan query
    public function query($query) {
        $this->stmt = $this->dbh->prepare($query);
    }

    // Binding data (Mencegah SQL Injection)
    public function bind($param, $value, $type = null) {
        if(is_null($type)) {
            switch(true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute() {
        return $this->stmt->execute();
    }

    // Ambil banyak data
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil satu data
    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Hitung baris
    public function rowCount() {
        return $this->stmt->rowCount();
    }
}