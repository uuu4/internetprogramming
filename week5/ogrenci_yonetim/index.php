<?php
// index.php - Uygulamanın giriş noktası ve basit yönlendirici (Router)

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Varsayılan Controller ve Action
$controller_name = 'ogrenci';
$action_name = 'index';

// URL'den Controller ve Action'ı al
if (isset($_GET['controller'])) {
    $controller_name = strtolower($_GET['controller']);
}
if (isset($_GET['action'])) {
    $action_name = strtolower($_GET['action']);
}

// Controller dosyasını dahil et
$controller_file = 'app/controllers/' . ucfirst($controller_name) . 'Controller.php';

if (file_exists($controller_file)) {
    require_once $controller_file;
    $controller_class = ucfirst($controller_name) . 'Controller';

    // Controller sınıfını oluştur
    if (class_exists($controller_class)) {
        $controller = new $controller_class();

        // Action metodunu çağır
        if (method_exists($controller, $action_name)) {
            $controller->$action_name();
        } else {
            // 404 Action bulunamadı
            header("HTTP/1.0 404 Not Found");
            echo "Hata: Controller'da **{$action_name}** metodu bulunamadı.";
        }
    } else {
        // 404 Class bulunamadı
        header("HTTP/1.0 404 Not Found");
        echo "Hata: **{$controller_class}** sınıfı bulunamadı.";
    }
} else {
    // 404 Controller dosyası bulunamadı
    header("HTTP/1.0 404 Not Found");
    echo "Hata: **{$controller_file}** dosyası bulunamadı.";
}

?>