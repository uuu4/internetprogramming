<?php
// app/models/Ogrenci.php

class Ogrenci {
    private $conn;
    private $table_name = "ogrenci";

    public $id;
    public $ad;
    public $soyad;
    public $ogrenci_no;

    // DB bağlantısını alır
    public function __construct($db){
        $this->conn = $db;
    }

    // Tüm öğrencileri listele
    public function listele(){
        $query = "SELECT id, ad, soyad, ogrenci_no FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Yeni öğrenci kaydet
    public function ekle(){
        $query = "INSERT INTO " . $this->table_name . " SET ad=:ad, soyad=:soyad, ogrenci_no=:ogrenci_no";
        $stmt = $this->conn->prepare($query);

        // Güvenlik için verileri temizle
        $this->ad = htmlspecialchars(strip_tags($this->ad));
        $this->soyad = htmlspecialchars(strip_tags($this->soyad));
        $this->ogrenci_no = htmlspecialchars(strip_tags($this->ogrenci_no));

        // Parametreleri bağla
        $stmt->bindParam(":ad", $this->ad);
        $stmt->bindParam(":soyad", $this->soyad);
        $stmt->bindParam(":ogrenci_no", $this->ogrenci_no);

        if($stmt->execute()){
            return true;
        }
        return false;
    }

    // Tek bir öğrenciyi ID ile oku
    public function oku(){
        $query = "SELECT ad, soyad, ogrenci_no FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare( $query );
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->ad = $row['ad'];
            $this->soyad = $row['soyad'];
            $this->ogrenci_no = $row['ogrenci_no'];
            return true;
        }
        return false;
    }

    // Öğrenciyi güncelle
    public function duzenle(){
        $query = "UPDATE " . $this->table_name . " SET ad = :ad, soyad = :soyad, ogrenci_no = :ogrenci_no WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        // Güvenlik için verileri temizle
        $this->ad = htmlspecialchars(strip_tags($this->ad));
        $this->soyad = htmlspecialchars(strip_tags($this->soyad));
        $this->ogrenci_no = htmlspecialchars(strip_tags($this->ogrenci_no));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Parametreleri bağla
        $stmt->bindParam(':ad', $this->ad);
        $stmt->bindParam(':soyad', $this->soyad);
        $stmt->bindParam(':ogrenci_no', $this->ogrenci_no);
        $stmt->bindParam(':id', $this->id);

        if($stmt->execute()){
            return true;
        }
        return false;
    }

    // Öğrenciyi sil
    public function sil(){
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        // Güvenlik
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(1, $this->id);

        if($stmt->execute()){
            return true;
        }
        return false;
    }
}
?>