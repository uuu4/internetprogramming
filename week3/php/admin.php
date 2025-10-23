<?php
session_start();
require_once 'db_connect.php';

// 1. GÜVENLİK KONTROLÜ
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit;
}

$userName = $_SESSION['name'] ?? 'Admin';
$message = null; // Kullanıcıya gösterilecek mesaj [type, text]

// 2. PHP İŞLEM MANTIĞI (POST İSTEKLERİ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    try {
        switch ($_POST['action']) {
            
            // --- KİTAP EKLEME ---
            case 'add_book':
                $title = trim($_POST['bookTitle']);
                $author = trim($_POST['bookAuthor']);
                $year = trim($_POST['bookYear']);
                $category = trim($_POST['bookCategory']);

                // Sunucu taraflı basit doğrulama
                if (empty($title) || empty($author) || empty($year)) {
                    $message = ['type' => 'error', 'text' => 'Başlık, Yazar ve Yıl alanları zorunludur.'];
                    break;
                }

                $stmt = $pdo->prepare("INSERT INTO kitaplar (title, author, year, category) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $author, $year, $category]);
                $message = ['type' => 'success', 'text' => 'Kitap başarıyla eklendi!'];
                break;

            // --- KİTAP SİLME ---
            case 'delete_book':
                if (isset($_POST['book_id'])) {
                    $book_id = $_POST['book_id'];
                    // Önce rezervasyonları sil (Foreign Key)
                    $pdo->prepare("DELETE FROM rezervasyonlar WHERE book_id = ?")->execute([$book_id]);
                    // Sonra kitabı sil
                    $pdo->prepare("DELETE FROM kitaplar WHERE id = ?")->execute([$book_id]);
                    $message = ['type' => 'success', 'text' => 'Kitap ve ilgili rezervasyonlar başarıyla silindi.'];
                }
                break;

            // --- KULLANICI SİLME ---
            case 'delete_user':
                if (isset($_POST['user_id'])) {
                    $user_id = $_POST['user_id'];
                     // Kullanıcıya ait rezervasyonları sil
                    $pdo->prepare("DELETE FROM rezervasyonlar WHERE user_id = ?")->execute([$user_id]);
                    // Kullanıcıyı sil (Güvenlik için role != 'admin' kontrolü SQL'de de var)
                    $pdo->prepare("DELETE FROM kullanicilar WHERE id = ? AND role != 'admin'")->execute([$user_id]);
                    $message = ['type' => 'success', 'text' => 'Kullanıcı ve ilgili rezervasyonları başarıyla silindi.'];
                }
                break;
        }
    } catch (PDOException $e) {
        // Genel Hata Yakalama
        $message = ['type' => 'error', 'text' => 'Bir veritabanı hatası oluştu: ' . htmlspecialchars($e->getMessage())];
    }
}

// 3. VERİ ÇEKME (SAYFA GÖRÜNÜMÜ İÇİN)
try {
    // Kitap Listesi ve Sayısı
    $kitaplar = $pdo->query("SELECT * FROM kitaplar ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $totalBooks = count($kitaplar);

    // Kullanıcı Listesi (Adminler hariç) ve Sayısı
    $kullanicilar = $pdo->query("SELECT id, name, email, role FROM kullanicilar WHERE role != 'admin' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $totalUsers = count($kullanicilar);

    // Toplam Rezervasyon Sayısı
    $totalReservations = $pdo->query("SELECT COUNT(*) FROM rezervasyonlar")->fetchColumn();

} catch (PDOException $e) {
    // Veri çekme başarısız olursa sayfa çalışamaz.
    die("Veritabanı bağlantı hatası: " . htmlspecialchars($e->getMessage()));
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin Paneli | MSB Library</title>
  <link rel="stylesheet" href="genel.css" />
  <style>
    /* Footer fix for this page */
    body {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }
    main { flex: 1; }
    
    /* İçerik Bölümü Gizleme/Gönderme */
    .dashboard-content-section { display: none; }
    .dashboard-content-section.active { display: block; }
    .ops button { margin-top: 5px; }

    /* Refactor: Mesaj stilleri */
    .message {
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-weight: 500;
    }
    .message.success {
        color: #155724;
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
    }
    .message.error {
        color: #721c24;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
    }
  </style>
</head>
<body>
  
  <header class="navbar">
    <div class="logo">
      <img src="logo.png" alt="MSB Library Logo" />
      <h1>MSB Library</h1>
    </div>
    <nav>
      <ul class="nav-links">
        <li><a href="index.html">Ana Sayfa</a></li>
        <li><a href="hakkimizda.html">Hakkımızda</a></li>
        <li><a href="misyon-vizyon.html">Misyon & Vizyon</a></li>
        <li><a href="iletisim.html">İletişim</a></li>
        <li><a href="logout.php" class="active">Çıkış Yap</a></li> 
      </ul>
    </nav>
  </header>

  <main class="admin-container">
    <aside class="admin-sidebar">
      <div class="admin-profile">
        <img src="logo.png" alt="admin" />
        <div>
          <h3>Hoş Geldiniz!</h3>
          <p><?php echo htmlspecialchars($userName); ?></p>
        </div>
      </div>

      <ul id="admin-menu" class="admin-menu">
        <li class="active" data-content="overview">Panoya Genel Bakış</li>
        <li data-content="book-management">Kitap Yönetimi</li>
        <li data-content="user-management">Kullanıcılar</li>
        <li data-content="announcements">Duyurular</li> 
        <li data-content="settings">Ayarlar</li>
      </ul>

      </aside>

    <section class="admin-main">
      <div class="dashboard-header">
        <h2>Yönetici Paneli</h2>
      </div>
      
      <?php if ($message): ?>
        <div class="message <?php echo htmlspecialchars($message['type']); ?>">
            <?php echo htmlspecialchars($message['text']); ?>
        </div>
      <?php endif; ?>


      <div id="overview" class="dashboard-content-section active">
        <div class="cards">
            <div class="card">
              <h4>Toplam Kitap</h4>
              <p id="totalBooks"><?php echo $totalBooks; ?></p>
            </div>
            <div class="card">
              <h4>Toplam Kullanıcı (Yönetici Hariç)</h4>
              <p id="totalUsers"><?php echo $totalUsers; ?></p>
            </div>
            <div class="card">
              <h4>Aktif Rezervasyon</h4>
              <p id="totalReservations"><?php echo $totalReservations; ?></p>
            </div>
          </div>
      </div>
      
      <div id="book-management" class="dashboard-content-section">
          <div class="panel">
            <div class="panel-header">
              <h3>Kitap Yönetimi</h3>
              <p class="muted">Yeni kitap ekleyin, düzenleyin veya silin.</p>
            </div>
    
            <div class="panel-body two-col">
              <form id="bookForm" class="panel-form" method="POST">
                <h4>Yeni Kitap Ekle</h4>
                <input type="hidden" name="action" value="add_book">
                <label>Başlık
                  <input type="text" name="bookTitle" placeholder="Kitap başlığı" required />
                </label>
                <label>Yazar
                  <input type="text" name="bookAuthor" placeholder="Yazar adı" required />
                </label>
                <label>Yayın Yılı
                  <input type="number" name="bookYear" placeholder="2024" min="1000" max="2100" required />
                </label>
                <label>Kategori
                  <input type="text" name="bookCategory" placeholder="Kategori (örn. Bilgisayar)" />
                </label>
                <div class="form-row">
                  <button type="submit" class="btn">Ekle</button>
                </div>
              </form>
    
              <div class="table-wrap">
                <h4>Kitap Listesi (<?php echo $totalBooks; ?> Adet)</h4>
                <input id="bookSearch" placeholder="Kitap ara..." /> 
                <table id="booksTable" class="table">
                  <thead>
                    <tr><th>Başlık</th><th>Yazar</th><th>Yıl</th><th>Kategori</th><th>İşlemler</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($kitaplar as $kitap): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($kitap['title']); ?></td>
                      <td><?php echo htmlspecialchars($kitap['author']); ?></td>
                      <td><?php echo htmlspecialchars($kitap['year']); ?></td>
                      <td><?php echo htmlspecialchars($kitap['category']); ?></td>
                      <td class="ops">
                        <button data-id="<?php echo $kitap['id']; ?>" class="btn ghost small edit-book">Düzenle (Sim)</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Bu kitabı silmek istediğinizden emin misiniz? (İlgili rezervasyonlar da silinecektir)');">
                            <input type="hidden" name="action" value="delete_book">
                            <input type="hidden" name="book_id" value="<?php echo $kitap['id']; ?>">
                            <button type="submit" class="btn danger small del-book">Sil</button>
                        </form>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
      </div>

      <div id="user-management" class="dashboard-content-section">
          <div class="panel">
            <div class="panel-header">
              <h3>Kullanıcı Yönetimi</h3>
              <p class="muted">Kayıtlı kullanıcıları görüntüleyin ve silin.</p>
            </div>
    
            <div class="panel-body">
              <div class="users-controls">
                <input id="userSearch" placeholder="Kullanıcı ara (isim veya eposta)" />
              </div>
    
              <table id="usersTable" class="table">
                <thead>
                  <tr><th>Ad</th><th>E-Posta</th><th>Rol</th><th>İşlemler</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($kullanicilar as $kullanici): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($kullanici['name']); ?></td>
                      <td><?php echo htmlspecialchars($kullanici['email']); ?></td>
                      <td><?php echo htmlspecialchars($kullanici['role']); ?></td>
                      <td class="ops">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz? (İlgili rezervasyonlar da silinecektir)');">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?php echo $kullanici['id']; ?>">
                            <button type="submit" class="btn danger small del-user">Sil</C>
                        </form>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
      </div>
      
      <div id="announcements" class="dashboard-content-section">
          <div class="panel">
            <div class="panel-header">
              <h3>Duyurular</h3>
              <p class="muted">Yeni duyuru ekleyin, düzenleyin veya yayınlayın.</p>
            </div>
    
            <div class="panel-body">
                <p class="muted">Duyuru yönetimi dinamikleştirilmelidir.</p>
            </div>
          </div>
      </div>

      <div id="settings" class="dashboard-content-section">
          <div class="panel">
            <div class="panel-header">
              <h3>Sistem Ayarları</h3>
              <p class="muted">Kütüphane sisteminin temel ayarlarını yönetin.</p>
            </div>
            <div class="panel-body">
              <p class="muted">Ayarlar bölümü dinamikleştirilmelidir.</p>
            </div>
          </div>
      </div>
    </section>
  </main>

  <footer>
    <p>© 2025 MSB Library | İstanbul Medeniyet Üniversitesi</p>
  </footer>

  <script>
    const $ = (s) => document.querySelector(s);
    const $$ = (s) => document.querySelectorAll(s);

    const adminMenu = $('#admin-menu');

    // MENÜ GEÇİŞ İŞLEVİ
    adminMenu.addEventListener('click', (e) => {
        const menuItem = e.target.closest('li');
        if (!menuItem) return;

        const targetContentId = menuItem.dataset.content;
        
        $$('#admin-menu li').forEach(li => li.classList.remove('active'));
        menuItem.classList.add('active');

        $$('.dashboard-content-section').forEach(section => section.classList.remove('active'));
        const targetSection = $(`#${targetContentId}`);
        if (targetSection) {
            targetSection.classList.add('active');
        }
    });

    // Basit JS Kitap Düzenleme Simülasyonu
    $('#booksTable').addEventListener('click', (ev) => {
        if (ev.target.matches('.edit-book')) {
            const bookId = ev.target.dataset.id;
            alert(`Kitap ID ${bookId} için düzenleme formu açılmalı veya AJAX ile güncelleme yapılmalıdır.`);
        }
    });

    // Arama Fonksiyonu (Kitap)
    $('#bookSearch').addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const rows = $$('#booksTable tbody tr');
        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(query) ? '' : 'none';
        });
    });

    // Arama Fonksiyonu (Kullanıcı) - EKLENDİ
    $('#userSearch').addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const rows = $$('#usersTable tbody tr');
        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(query) ? '' : 'none';
        });
    });

    // Sayfa Yüklenince aktif menüyü ayarla (kaldırıldı, CSS ile halledildi)
    // İlk öğeler zaten HTML'de "active" sınıfına sahip.
  </script>
</body>
</html>