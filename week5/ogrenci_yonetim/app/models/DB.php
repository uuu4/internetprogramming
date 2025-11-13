<?php
// app/models/DB.php

class DB {
    private $host = 'localhost';
    private $db_name = 'ogrenci_db'; // Oluşturduğun VT adını buraya yaz
    private $username = 'root'; // XAMPP varsayılan kullanıcı adı
    private $password = ''; // XAMPP varsayılan şifresi

    public $conn;

    // Veritabanı bağlantısını kurar
    public function __construct() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->db_name};charset=utf8",
                                  $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Bağlantı Hatası: " . $exception->getMessage();
            die();
        }
    }

    // PDO bağlantı nesnesini döndürür
    public function getConnection(){
        return $this->conn;
    }
}
?>