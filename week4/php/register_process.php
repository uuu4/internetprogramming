<?php
session_start();
require_once 'db_connect.php'; // Veritabanı bağlantısını dahil et

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Alanların boş olup olmadığını kontrol et
    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        // Hata durumunda kayıt sayfasına yönlendir
        header('Location: register.html');
        exit;
    }

    // DİKKAT: GEÇİCİ OLARAK DÜZ METİN ŞİFRE KULLANILIYOR!
    $plain_password = $password;
    
    // Varsayılan Rol
    $role = 'user'; 

    try {
        // 1. Kullanıcı Adı veya E-posta Çakışması Kontrolü
        $stmt_check = $pdo->prepare("SELECT id FROM kullanicilar WHERE username = ? OR email = ?");
        $stmt_check->execute([$username, $email]);
        if ($stmt_check->fetch()) {
            // Hata durumunda kayıt sayfasına yönlendir
            header('Location: register.html');
            exit;
        }

        // 2. Yeni Kullanıcıyı Veritabanına Ekle
        $stmt = $pdo->prepare("INSERT INTO kullanicilar (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
        // Düz metin şifreyi kaydet
        $stmt->execute([$name, $username, $email, $plain_password, $role]);

        // Başarılı kayıt durumunda giriş sayfasına yönlendir
        header('Location: login.html');
        exit;

    } catch (PDOException $e) {
        // Veritabanı hatasında kayıt sayfasına yönlendir
        header('Location: register.html');
        exit;
    }
} else {
    header('Location: register.html');
    exit;
}
?>