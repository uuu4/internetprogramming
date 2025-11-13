<?php
// app/controllers/OgrenciController.php

// Gerekli dosyaları dahil et
require_once 'app/models/DB.php';
require_once 'app/models/Ogrenci.php';

class OgrenciController {

    private $ogrenci;

    public function __construct() {
        // Veritabanı bağlantısını oluştur
        $database = new DB();
        $db = $database->getConnection();
        // Model nesnesini Controller'a bağla
        $this->ogrenci = new Ogrenci($db);
    }

    // Tüm öğrencileri listeler (READ)
    public function index() {
        $stmt = $this->ogrenci->listele();
        $ogrenciler = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // View'a verileri gönder
        require_once 'app/views/ogrenci_listesi.php';
    }

    // Yeni öğrenci ekleme formunu gösterir veya ekleme işlemini yapar (CREATE)
    public function ekle() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // POST verilerini al ve modele ata
            $this->ogrenci->ad = $_POST['ad'];
            $this->ogrenci->soyad = $_POST['soyad'];
            $this->ogrenci->ogrenci_no = $_POST['ogrenci_no'];

            if ($this->ogrenci->ekle()) {
                // Başarılıysa Listeleme sayfasına yönlendir
                header("Location: index.php?controller=ogrenci&action=index");
                exit;
            } else {
                echo "<div style='color:red;'>Öğrenci eklenirken bir hata oluştu veya öğrenci numarası zaten mevcut.</div>";
            }
        }

        // Formu göster
        require_once 'app/views/ogrenci_ekle.php';
    }

    // Öğrenci düzenleme formunu gösterir veya düzenleme işlemini yapar (UPDATE)
    public function duzenle() {
        // ID'yi al
        $this->ogrenci->id = isset($_GET['id']) ? $_GET['id'] : die('HATA: ID bulunamadı.');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // POST verilerini al ve modele ata
            $this->ogrenci->ad = $_POST['ad'];
            $this->ogrenci->soyad = $_POST['soyad'];
            $this->ogrenci->ogrenci_no = $_POST['ogrenci_no'];

            if ($this->ogrenci->duzenle()) {
                // Başarılıysa Listeleme sayfasına yönlendir
                header("Location: index.php?controller=ogrenci&action=index");
                exit;
            } else {
                echo "<div style='color:red;'>Öğrenci güncellenirken bir hata oluştu.</div>";
            }
        }

        // Mevcut öğrenci bilgilerini oku ve formu göster
        if ($this->ogrenci->oku()) {
            require_once 'app/views/ogrenci_duzenle.php';
        } else {
            echo "<div style='color:red;'>Öğrenci bulunamadı.</div>";
        }
    }

    // Öğrenciyi silme işlemini yapar (DELETE)
    public function sil() {
        // ID'yi al
        $this->ogrenci->id = isset($_GET['id']) ? $_GET['id'] : die('HATA: ID bulunamadı.');

        if ($this->ogrenci->sil()) {
            // Başarılıysa Listeleme sayfasına yönlendir
            header("Location: index.php?controller=ogrenci&action=index");
            exit;
        } else {
            echo "<div style='color:red;'>Öğrenci silinirken bir hata oluştu.</div>";
        }
    }
}
?>