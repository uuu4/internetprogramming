<?php
/*
 * ÇIKIŞ İŞLEM BETİĞİ (logout.php)
 * * Aktif kullanıcı oturumunu güvenli bir şekilde sonlandırır 
 * ve kullanıcıyı ana sayfaya yönlendirir.
 */

// 1. Oturumu Başlat
// session_destroy() gibi oturum fonksiyonlarını kullanabilmek için
// mevcut oturumun önce başlatılması (veya devam ettirilmesi) gerekir.
session_start();

// 2. Oturum Değişkenlerini Temizle
// $_SESSION dizisi içindeki tüm verileri (user_id, role, name vb.)
// anında kaldırır (boş bir dizi haline getirir).
session_unset();

// 3. Oturumu Sonlandır
// Sunucu tarafındaki oturum dosyasını siler ve oturum kimliğini (session ID)
// geçersiz kılar. Bu, oturumun tamamen yok edilmesini sağlar.
session_destroy();

// 4. Ana Sayfaya Yönlendir
// Kullanıcıyı güvenli bir şekilde çıkış yaptıktan sonra
// ana sayfaya (veya giriş sayfasına) yönlendirir.
header('Location: index.html');

// 5. Betiği Durdur
// header() yönlendirmesinden sonra 'exit' veya 'die' kullanmak
// kritik bir güvenlik adımıdır. Bu, yönlendirmeden sonra 
// alttaki hiçbir kodun (varsa bile) çalışmamasını garanti eder.
exit;
?>