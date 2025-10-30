<?php
// Veritabanı bağlantı bilgileri
$host = "localhost";
$user = "root";     // XAMPP varsayılan kullanıcı adı
$pass = "";         // XAMPP varsayılan şifresi (boş)
$db = "msb_kutuphane"; // Sizin oluşturduğunuz veritabanı adı

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Bağlantı başarısız olursa tarayıcıda hata mesajı göster
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
?>