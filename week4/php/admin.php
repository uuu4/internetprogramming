<?php
session_start();
require_once 'db_connect.php';

// Güvenlik Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html');
    exit;
}

$userName = $_SESSION['name'] ?? 'Admin';
$current_action = 'add'; // Varsayılan: Kitap Ekleme modu
$edit_book = null; // Düzenlenecek kitap verisi

// --- PHP İşlem Mantığı ---

$message = ''; // Kullanıcıya gösterilecek mesaj

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- KİTAP İŞLEMLERİ ---
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // 1. KİTAP EKLEME
        if ($action === 'add_book') {
            $title = trim($_POST['bookTitle']);
            $author = trim($_POST['bookAuthor']);
            $year = trim($_POST['bookYear']);
            $category = trim($_POST['bookCategory']);

            try {
                $stmt = $pdo->prepare("INSERT INTO kitaplar (title, author, year, category) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $author, $year, $category]);
                $message = "<div style='color:green; padding: 10px; background: #e6ffe6; border-radius: 8px; margin-bottom: 15px;'>Kitap başarıyla eklendi!</div>";
            } catch (PDOException $e) {
                $message = "<div style='color:red; padding: 10px; background: #ffe6e6; border-radius: 8px; margin-bottom: 15px;'>Kitap eklenirken hata oluştu: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
        
        // 2. KİTAP GÜNCELLEME
        if ($action === 'update_book') {
            $book_id = $_POST['book_id'];
            $title = trim($_POST['bookTitle']);
            $author = trim($_POST['bookAuthor']);
            $year = trim($_POST['bookYear']);
            $category = trim($_POST['bookCategory']);

            try {
                $stmt = $pdo->prepare("UPDATE kitaplar SET title = ?, author = ?, year = ?, category = ? WHERE id = ?");
                $stmt->execute([$title, $author, $year, $category, $book_id]);
                $message = "<div style='color:green; padding: 10px; background: #e6ffe6; border-radius: 8px; margin-bottom: 15px;'>Kitap başarıyla güncellendi!</div>";
            } catch (PDOException $e) {
                $message = "<div style='color:red; padding: 10px; background: #ffe6e6; border-radius: 8px; margin-bottom: 15px;'>Kitap güncellenirken hata oluştu: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }

        // 3. KİTAP SİLME
        if ($action === 'delete_book' && isset($_POST['book_id'])) {
            $book_id = $_POST['book_id'];
            try {
                $pdo->prepare("DELETE FROM rezervasyonlar WHERE book_id = ?")->execute([$book_id]);
                $pdo->prepare("DELETE FROM kitaplar WHERE id = ?")->execute([$book_id]);
                $message = "<div style='color:green; padding: 10px; background: #e6ffe6; border-radius: 8px; margin-bottom: 15px;'>Kitap başarıyla silindi (ve ilgili rezervasyonlar kaldırıldı).</div>";
            } catch (PDOException $e) {
                $message = "<div style='color:red; padding: 10px; background: #ffe6e6; border-radius: 8px; margin-bottom: 15px;'>Silme hatası: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }

        // --- KULLANICI İŞLEMLERİ ---
        
        // 4. KULLANICI EKLEME (Admin'in yeni standart kullanıcı eklemesi)
        if ($action === 'add_user_by_admin') {
            $name = trim($_POST['userName']);
            $username = trim($_POST['userUsername']);
            $email = trim($_POST['userEmail']);
            $password = $_POST['userPassword'];
            $role = $_POST['userRole'] ?? 'user';

            // DİKKAT: GEÇİCİ OLARAK DÜZ METİN ŞİFRE KULLANILIYOR!
            // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $plain_password = $password; 

            try {
                $stmt_check = $pdo->prepare("SELECT id FROM kullanicilar WHERE username = ? OR email = ?");
                $stmt_check->execute([$username, $email]);
                if ($stmt_check->fetch()) {
                    $message = "<div style='color:red; padding: 10px; background: #ffe6e6; border-radius: 8px; margin-bottom: 15px;'>Hata: Bu kullanıcı adı veya e-posta zaten kayıtlı.</div>";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO kullanicilar (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
                    // Düz metin şifreyi kaydet
                    $stmt->execute([$name, $username, $email, $plain_password, $role]); 
                    $message = "<div style='color:green; padding: 10px; background: #e6ffe6; border-radius: 8px; margin-bottom: 15px;'>Kullanıcı başarıyla eklendi!</div>";
                }
            } catch (PDOException $e) {
                $message = "<div style='color:red; padding: 10px; background: #ffe6e6; border-radius: 8px; margin-bottom: 15px;'>Kullanıcı ekleme hatası: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }

        // 5. KULLANICI SİLME
        if ($action === 'delete_user' && isset($_POST['user_id'])) {
            $user_id = $_POST['user_id'];
            try {
                // Kullanıcıya ait rezervasyonları sil
                $pdo->prepare("DELETE FROM rezervasyonlar WHERE user_id = ?")->execute([$user_id]);
                // Kullanıcıyı sil (Admin'in kendini veya başka admin'i silmesini engellemek için role kontrolü)
                $pdo->prepare("DELETE FROM kullanicilar WHERE id = ? AND role != 'admin' AND id != ?")->execute([$user_id, $_SESSION['user_id']]);
                $message = "<div style='color:green; padding: 10px; background: #e6ffe6; border-radius: 8px; margin-bottom: 15px;'>Kullanıcı başarıyla silindi (ve ilgili rezervasyonlar kaldırıldı).</div>";
            } catch (PDOException $e) {
                $message = "<div style='color:red; padding: 10px; background: #ffe6e6; border-radius: 8px; margin-bottom: 15px;'>Silme hatası: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
}

// --- GET İŞLEMLERİ (Kitap Düzenleme Modu) ---
if (isset($_GET['action']) && $_GET['action'] === 'edit_book' && isset($_GET['id'])) {
    $book_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM kitaplar WHERE id = ?");
    $stmt->execute([$book_id]);
    $edit_book = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($edit_book) {
        $current_action = 'edit'; // Düzenleme modunu ayarla
    }
}

// --- VERİ ÇEKME FONKSİYONLARI ---

// Kitap Listesini Çek
$kitaplar = $pdo->query("SELECT * FROM kitaplar ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Kullanıcı Listesini Çek
$kullanicilar = $pdo->query("SELECT id, name, username, email, role FROM kullanicilar ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Genel Bakış Verileri
$totalBooks = count($kitaplar);
$totalUsers = count($kullanicilar);
$totalReservations = $pdo->query("SELECT COUNT(*) FROM rezervasyonlar")->fetchColumn();
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

    main {
      flex: 1;
    }
    
    /* İçerik Bölümü Gizleme/Gönderme */
    .dashboard-content-section {
        display: none;
    }
    .dashboard-content-section.active {
        display: block;
    }
    .ops button { margin-top: 5px; } /* Butonlar arası boşluk */
    
    /* Kitap/Kullanıcı Yönetimi Bölümü için 2 Kartlı Yeni Flex Düzen */
    .management-container {
        display: flex;
        gap: 20px;
        align-items: flex-start; /* Kartlar üstten hizalansın */
    }
    .form-card, .list-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        padding: 18px;
        margin-bottom: 20px;
        flex: 1; /* Her kart eşit yer kaplasın */
    }
    .form-card {
        max-width: 40%; /* Form kartı biraz daha dar olsun */
        min-width: 350px;
    }
    .list-card {
        flex: 1;
        overflow-x: auto;
    }
    .panel-header h3 { margin: 0 0 6px 0; color: #001f3f; }
    .panel-header .muted { color: #666; margin: 0 0 10px 0; }
    .panel-form { display:flex; flex-direction:column; gap:10px; }
    .panel-form label { font-weight:600; color:#003049; font-size:0.95rem; }
    .panel-form input, .panel-form textarea, .panel-form select { padding:10px; border-radius:8px; border:1px solid #ddd; font-size:0.95rem; }
    
    .edit-mode-label {
        background-color: #ffcc00;
        color: #333;
        padding: 5px 10px;
        border-radius: 8px;
        font-weight: 700;
        text-align: center;
        margin-bottom: 10px;
        display: block;
    }
    
    /* Responsive Düzenleme */
    @media(max-width:1000px) {
      .management-container { flex-direction:column; }
      .form-card { max-width: 100%; min-width: auto; }
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
        <li data-content="user-management">Kullanıcı Yönetimi</li>
      </ul>

      </aside>

    <section class="admin-main">
      <div class="dashboard-header">
        <h2>Yönetici Paneli</h2>
        </div>
      
      <?php if ($message): echo $message; endif; ?>

      <div id="overview" class="dashboard-content-section active">
        <div class="cards">
            <div class="card">
              <h4>Toplam Kitap</h4>
              <p id="totalBooks"><?php echo $totalBooks; ?></p>
            </div>
            <div class="card">
              <h4>Toplam Kullanıcı</h4>
              <p id="totalUsers"><?php echo $totalUsers; ?></p>
            </div>
            <div class="card">
              <h4>Aktif Rezervasyon</h4>
              <p id="totalReservations"><?php echo $totalReservations; ?></p>
            </div>
          </div>
      </div>
      
      <div id="book-management" class="dashboard-content-section">
          <div class="management-container">
              <div class="form-card">
                  <div class="panel-header">
                      <?php if ($current_action === 'edit'): ?>
                          <span class="edit-mode-label">Kitap Düzenleme Modu (ID: <?php echo $edit_book['id']; ?>)</span>
                      <?php else: ?>
                          <h3>Yeni Kitap Ekle</h3>
                          <p class="muted">Kütüphaneye yeni bir kaynak ekleyin.</p>
                      <?php endif; ?>
                  </div>
                  
                  <form id="bookForm" class="panel-form" method="POST" action="admin.php#book-management">
                      <input type="hidden" name="action" value="<?php echo $current_action === 'edit' ? 'update_book' : 'add_book'; ?>">
                      <?php if ($current_action === 'edit'): ?>
                          <input type="hidden" name="book_id" value="<?php echo $edit_book['id']; ?>">
                      <?php endif; ?>
                      
                      <label>Başlık
                          <input type="text" name="bookTitle" placeholder="Kitap başlığı" required value="<?php echo $edit_book ? htmlspecialchars($edit_book['title']) : ''; ?>" />
                      </label>
                      <label>Yazar
                          <input type="text" name="bookAuthor" placeholder="Yazar adı" required value="<?php echo $edit_book ? htmlspecialchars($edit_book['author']) : ''; ?>" />
                      </label>
                      <label>Yayın Yılı
                          <input type="number" name="bookYear" placeholder="2024" min="1000" max="2100" value="<?php echo $edit_book ? htmlspecialchars($edit_book['year']) : ''; ?>" />
                      </label>
                      <label>Kategori
                          <input type="text" name="bookCategory" placeholder="Kategori (örn. Bilgisayar)" value="<?php echo $edit_book ? htmlspecialchars($edit_book['category']) : ''; ?>" />
                      </label>
                      <div class="form-row">
                          <button type="submit" class="btn"><?php echo $current_action === 'edit' ? 'Güncelle' : 'Ekle'; ?></button>
                          <?php if ($current_action === 'edit'): ?>
                              <a href="admin.php#book-management" class="btn ghost small" style="text-decoration: none;">İptal</a>
                          <?php endif; ?>
                      </div>
                  </form>
              </div>

              <div class="list-card">
                  <div class="panel-header">
                      <h3>Kitap Listesi (<?php echo $totalBooks; ?> Adet)</h3>
                      <p class="muted">Mevcut tüm kitapların listesi.</p>
                  </div>
                  <input id="bookSearch" placeholder="Kitap ara..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 12px;" /> 
                  <div class="table-wrap">
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
                                      <a href="admin.php?action=edit_book&id=<?php echo $kitap['id']; ?>#book-management" class="btn ghost small">Düzenle</a>
                                      <form method="POST" style="display:inline;" onsubmit="return confirm('Bu kitabı silmek istediğinizden emin misiniz?');">
                                          <input type="hidden" name="action" value="delete_book">
                                          <input type="hidden" name="book_id" value="<?php echo $kitap['id']; ?>">
                                          <button type="submit" class="btn danger small">Sil</button>
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
          <div class="management-container">
              <div class="form-card">
                  <div class="panel-header">
                      <h3>Yeni Kullanıcı Ekle</h3>
                      <p class="muted">Sisteme manuel olarak yeni kullanıcı tanımlayın.</p>
                  </div>
                  <form id="addUserForm" class="panel-form" method="POST">
                      <input type="hidden" name="action" value="add_user_by_admin">
                      <label>Ad Soyad
                          <input type="text" name="userName" placeholder="Ad Soyad" required />
                      </label>
                      <label>Kullanıcı Adı
                          <input type="text" name="userUsername" placeholder="Kullanıcı Adı" required />
                      </label>
                      <label>E-Posta
                          <input type="email" name="userEmail" placeholder="E-Posta" required />
                      </label>
                      <label>Şifre
                          <input type="password" name="userPassword" placeholder="Geçici Şifre" required />
                      </label>
                      <label>Rol
                          <select name="userRole">
                              <option value="user">Standart Kullanıcı</option>
                              <option value="admin">Yönetici</option>
                          </select>
                      </label>
                      <div class="form-row">
                          <button type="submit" class="btn">Kullanıcı Ekle</button>
                      </div>
                  </form>
              </div>
              
              <div class="list-card">
                  <div class="panel-header">
                      <h3>Kayıtlı Kullanıcılar (<?php echo $totalUsers; ?> Adet)</h3>
                      <p class="muted">Tüm standart kullanıcıları ve yöneticileri görüntüleyin.</p>
                  </div>
                  <input id="userSearch" placeholder="Kullanıcı ara (isim, kullanıcı adı veya eposta)" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 12px;" />
                  <div class="table-wrap">
                      <table id="usersTable" class="table">
                          <thead>
                              <tr><th>Ad</th><th>Kullanıcı Adı</th><th>E-Posta</th><th>Rol</th><th>İşlemler</th></tr>
                          </thead>
                          <tbody>
                              <?php foreach ($kullanicilar as $kullanici): ?>
                              <tr>
                                  <td><?php echo htmlspecialchars($kullanici['name']); ?></td>
                                  <td><?php echo htmlspecialchars($kullanici['username']); ?></td>
                                  <td><?php echo htmlspecialchars($kullanici['email']); ?></td>
                                  <td><?php echo htmlspecialchars($kullanici['role']); ?></td>
                                  <td class="ops">
                                      <?php if ($kullanici['role'] !== 'admin' || $kullanici['id'] !== $_SESSION['user_id']): ?>
                                      <form method="POST" style="display:inline;" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">
                                          <input type="hidden" name="action" value="delete_user">
                                          <input type="hidden" name="user_id" value="<?php echo $kullanici['id']; ?>">
                                          <button type="submit" class="btn danger small del-user">Sil</button>
                                      </form>
                                      <?php else: ?>
                                          <span class="muted">Yönetici</span>
                                      <?php endif; ?>
                                  </td>
                              </tr>
                              <?php endforeach; ?>
                          </tbody>
                      </table>
                  </div>
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

    // Sayfa URL'sindeki fragment'ı kontrol et ve menüyü ayarla
    const setActiveMenuFromHash = () => {
        const hash = window.location.hash.replace('#', '');
        if (hash) {
            const targetMenuItem = $(`[data-content="${hash}"]`);
            const targetSection = $(`#${hash}`);
            
            if (targetMenuItem && targetSection) {
                $$('#admin-menu li').forEach(li => li.classList.remove('active'));
                targetMenuItem.classList.add('active');
                
                $$('.dashboard-content-section').forEach(section => section.classList.remove('active'));
                targetSection.classList.add('active');
            }
        } else {
            $('#admin-menu li:first-child').classList.add('active');
            $('#overview').classList.add('active');
        }
    }

    // Arama Simülasyonu (Kitaplar)
    $('#bookSearch').addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const rows = $$('#booksTable tbody tr');
        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(query) ? '' : 'none';
        });
    });

    // Arama Simülasyonu (Kullanıcılar)
    $('#userSearch').addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const rows = $$('#usersTable tbody tr');
        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(query) ? '' : 'none';
        });
    });

    // Sayfa Yüklendikten sonra menüyü ayarla
    document.addEventListener('DOMContentLoaded', setActiveMenuFromHash);
    window.addEventListener('hashchange', setActiveMenuFromHash); // Geri tuşu desteği için

  </script>
</body>
</html>