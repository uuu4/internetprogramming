<?php
/*
 * VERİTABANI BAĞLANTI DOSYASI (db_connect.php)
 * * Bu betik, PHP Data Objects (PDO) eklentisini kullanarak 
 * MySQL veritabanına güvenli bir bağlantı oluşturur.
 * Bu dosya, projenizdeki diğer PHP dosyaları tarafından 'require_once' 
 * ile çağrılmak üzere tasarlanmıştır.
 */

// 1. Veritabanı Bağlantı Bilgileri
$host = "localhost";        // Sunucu adı (genellikle localhost)
$port = "3306";             // MySQL port numarası (varsayılan)
$db   = "msb_kutuphane";    // Bağlanılacak veritabanının adı
$user = "root";             // Veritabanı kullanıcı adı
$pass = "";                 // Veritabanı şifresi (XAMPP'ta varsayılan olarak boştur)
$charset = "utf8mb4";       // Karakter seti (Emoji ve modern karakterler için 'utf8' yerine 'utf8mb4' tercih edilir)

// 2. DSN (Data Source Name) Oluşturma
// DSN, PDO'ya hangi veritabanı sürücüsünü (mysql) ve 
// bağlantı detaylarını (host, dbname, charset) bildiren bir dizgedir.
$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

// 3. PDO Bağlantı Seçenekleri (Opsiyonel ama önerilir)
$options = [
    // Hata modunu 'Exception' (İstisna) olarak ayarla.
    // Bu sayede veritabanı hatalarını 'try-catch' bloğu ile yakalayabiliriz.
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    
    // Sorgu sonuçlarını varsayılan olarak 'ilişkisel dizi' (sütun adı => değer) 
    // formatında almamızı sağlar.
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    
    // Hazırlanan sorguların (prepared statements) öykünmesini (emulation) kapatır.
    // Bu, sorguların doğrudan veritabanı sunucusu tarafından hazırlanmasını 
    // sağlayarak SQL enjeksiyonuna karşı güvenliği artırır.
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 4. Bağlantıyı Kurma
try {
    // $pdo adında yeni bir PDO nesnesi oluşturarak bağlantıyı deniyoruz.
    $pdo = new PDO($dsn, $user, $pass, $options);
    
} catch (PDOException $e) {
    // Eğer 'try' bloğunda bir hata (istisna) oluşursa, 
    // 'catch' bloğu çalışır, hatayı yakalar ve betiği sonlandırır.
    // Kullanıcıya teknik detayı göstermek yerine (güvenlik açığı olabilir),
    // genellikle daha genel bir hata mesajı vermek daha iyidir.
    // Ancak geliştirme aşamasında $e->getMessage() kullanmak faydalıdır.
    
    // die() fonksiyonu betiği o noktada durdurur.
    die("Veritabanı bağlantısı kurulamadı: " . $e->getMessage());
}

// Bu noktadan sonra, eğer betik 'die()' ile durmadıysa,
// $pdo değişkeni başarılı bir veritabanı bağlantısı içerir 
// ve diğer dosyalarda sorgu yapmak için kullanılabilir.
?>