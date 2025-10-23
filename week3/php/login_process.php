<?php
/*
 * LOGIN İŞLEM BETİĞİ (login_process.php)
 * * Bu betik, 'login.html' formundan gönderilen POST verilerini işler,
 * kullanıcıyı doğrular ve rolüne göre ilgili panele yönlendirir.
 */

session_start();
require_once 'db_connect.php';

// --- 1. Guard Clause: İstek Metodu Kontrolü ---
// Betiğin sadece POST isteği ile çalışmasını garanti altına al.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html'); // POST değilse, forma geri yolla
    exit;
}

// --- 2. Veri Alma ve Temel Doğrulama ---
$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;

// Girdilerin boş olup olmadığını kontrol et
if (empty($username) || empty($password)) {
    $_SESSION['error'] = "Lütfen kullanıcı adı ve şifre alanlarını doldurun.";
    header('Location: login.html');
    exit;
}

try {
    // --- 3. Kullanıcıyı Bulma ---
    $stmt = $pdo->prepare("SELECT id, username, password, role, name FROM kullanicilar WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- 4. Guard Clause: Kullanıcı Kontrolü ---
    // Kullanıcı veritabanında bulunamadıysa...
    if (!$user) {
        $_SESSION['error'] = "Hata: Kullanıcı adı bulunamadı.";
        header('Location: login.html');
        exit;
    }

    // --- 5. Guard Clause: Şifre Kontrolü (GÜVENLİ YÖNTEM) ---
    // Gelen düz metin şifreyi ($password), veritabanındaki hash'lenmiş şifre ($user['password']) ile doğrula.
    // NOT: Kayıt olurken şifrenin password_hash() ile kaydedilmiş olması gerekir!
    if (!password_verify($password, $user['password'])) {
        $_SESSION['error'] = "Hata: Şifreniz yanlış.";
        header('Location: login.html');
        exit;
    }

    // --- 6. BAŞARILI GİRİŞ ---
    // Eğer kod bu noktaya ulaştıysa, hem kullanıcı adı hem de şifre doğrudur.
    
    // Varsa eski hata oturumunu temizle
    unset($_SESSION['error']);
    
    // Oturum (Session) değişkenlerini ayarla
    // Not: Güvenlik için şifreyi session'a asla kaydetme!
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name']; // 'Hoş geldiniz' mesajı için

    // --- 7. Rol Bazlı Yönlendirme ---
    if ($user['role'] === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: kullanici.php');
    }
    exit; // Yönlendirmeden sonra betiği durdur

} catch (PDOException $e) {
    // Veritabanı ile ilgili bir hata oluşursa (örn. bağlantı koptu)
    // Geliştirme aşamasında hatayı loglamak iyi bir fikirdir: error_log($e->getMessage());
    $_SESSION['error'] = "Sistemsel bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
    header('Location: login.html');
    exit;
}
?>