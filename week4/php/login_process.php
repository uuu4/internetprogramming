<?php
session_start(); // Oturumu başlat
require_once 'db_connect.php'; // Veritabanı bağlantısını dahil et

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = $_POST['username']; // Kullanıcı adı veya e-posta
    $password = $_POST['password'];

    // SQL Sorgusu: Kullanıcı adına veya e-postaya göre kaydı çek
    $stmt = $pdo->prepare("SELECT id, username, password, role, name, email FROM kullanicilar WHERE username = :user_input OR email = :user_input");
    $stmt->execute(['user_input' => $username_or_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // DÜZ METİN ŞİFRE KONTROLÜ (Geçici)
        if ($password === $user['password']) { 
            // Başarılı giriş: Oturum değişkenlerini ayarla
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];

            // Rol bazlı yönlendirme
            if ($user['role'] === 'admin') {
                header('Location: admin.php');
                exit;
            } else {
                header('Location: kullanici.php');
                exit;
            }
        } 
        // Hatalı şifrede hata mesajı session'a atılmıyor, direkt yönlendiriliyor.
    } 
    // Kullanıcı bulunamadığında da hata mesajı session'a atılmıyor.
}

// Başarısız giriş durumunda tekrar login sayfasına yönlendir
header('Location: login.html');
exit;
?>