<?php
session_start();
require_once 'db_connect.php';

// 1. GÜVENLİK VE OTURUM KONTROLÜ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

// Oturumdan gelen değişkenler
$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'] ?? 'Üye';
$userEmail = $_SESSION['email'] ?? 'bilgi@msb.edu.tr';

// Kullanıcıya gösterilecek mesaj [type, text]
$message = null;

// 2. PHP İŞLEM MANTIĞI (POST İSTEKLERİ)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    try {
        switch ($_POST['action']) {
            
            // --- REZERVASYON EKLEME ---
            case 'reserve_book':
                if (isset($_POST['book_id'])) {
                    $book_id = $_POST['book_id'];
                    $stmt = $pdo->prepare("INSERT INTO rezervasyonlar (user_id, book_id) VALUES (?, ?)");
                    $stmt->execute([$userId, $book_id]);
                    $message = ['type' => 'success', 'text' => 'Kitap başarıyla rezerve edildi!'];
                }
                break;
            
            // --- REZERVASYON İPTAL ETME ---
            case 'cancel_reservation':
                if (isset($_POST['reservation_id'])) {
                    $reservation_id = $_POST['reservation_id'];
                    // Güvenlik: Kullanıcının SADECE kendi rezervasyonunu iptal edebilmesini sağla
                    $stmt = $pdo->prepare("DELETE FROM rezervasyonlar WHERE id = ? AND user_id = ?");
                    $stmt->execute([$reservation_id, $userId]);
                    $message = ['type' => 'success', 'text' => 'Rezervasyon başarıyla iptal edildi.'];
                }
                break;
        }
    } catch (PDOException $e) {
        // Hata yakalama
        if ($e->getCode() == 23000) { // Unique key (PK/FK) ihlali
             $message = ['type' => 'error', 'text' => 'Hata: Bu kitabı zaten rezerve ettiniz.'];
        } else {
             $message = ['type' => 'error', 'text' => 'Rezervasyon hatası: ' . htmlspecialchars($e->getMessage())];
        }
    }
}

// 3. VERİ ÇEKME FONKSİYONLARI (SAYFA GÖRÜNÜMÜ)
try {
    // Kitap Kataloğu (GÜVENLİK: $userId parametre ile bağlandı)
    $stmt_kitaplar = $pdo->prepare("
        SELECT 
            k.id, k.title, k.author, k.category,
            EXISTS(SELECT 1 FROM rezervasyonlar r WHERE r.book_id = k.id AND r.user_id = ?) as is_reserved_by_user
        FROM kitaplar k
        ORDER BY k.id DESC
    ");
    $stmt_kitaplar->execute([$userId]);
    $kitaplar = $stmt_kitaplar->fetchAll(PDO::FETCH_ASSOC);

    // Kullanıcının Rezervasyonları (GÜVENLİK: $userId parametre ile bağlandı)
    $stmt_rezervasyonlar = $pdo->prepare("
        SELECT 
            r.id, r.reservation_date, k.title, k.author
        FROM rezervasyonlar r
        JOIN kitaplar k ON r.book_id = k.id
        WHERE r.user_id = ?
        ORDER BY r.reservation_date DESC
    ");
    $stmt_rezervasyonlar->execute([$userId]);
    $rezervasyonlar = $stmt_rezervasyonlar->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Veri çekme başarısız olursa sayfa çalışamaz
    die("Veritabanı okuma hatası: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Kullanıcı Paneli | MSB Library</title>
  <link rel="stylesheet" href="genel.css" />
  <style>
    /* Sadece bu sayfaya özgü stil ayarları */
    .user-container {
        display: flex;
        gap: 30px;
        padding: 30px;
        max-width: 1200px;
        margin: 30px auto;
    }
    .user-main { flex: 1; }
    .panel {
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        padding: 18px;
        margin-bottom: 20px;
    }
    .panel-header h3 { margin: 0 0 6px 0; color: #001f3f; }
    .panel-header .muted { color: #666; margin: 0 0 10px 0; }
    .table-wrap { overflow: auto; }
    .table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    .table thead th { text-align: left; font-size: 0.9rem; padding: 8px 6px; color: #003049; }
    .table tbody td { padding: 10px 6px; border-top: 1px solid #f1f1f1; font-size: 0.95rem; vertical-align: middle; }
    .ops { display: flex; gap: 8px; }
    
    /* Kitap durumu etiketleri */
    .status-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; }
    .available { background-color: #e6ffed; color: #007a33; }
    .reserved { background-color: #fff3e0; color: #e65100; }
    
    /* Panel Göster/Gizle */
    .content-section { display: none; }
    .content-section.active { display: block; }

    /* Profil Ayarları Formu */
    .profile-form .form-group { margin-bottom: 15px; }
    .profile-form label { display: block; font-weight: 600; margin-bottom: 5px; color: #001f3f; }
    .profile-form input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }

    /* Footer fix */
    body { display: flex; flex-direction: column; min-height: 100vh; }
    main { flex: 1; }

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
    
    /* Refactor: Devre dışı buton stili (inline style yerine) */
    .btn:disabled,
    button:disabled {
        background-color: #ccc;
        color: #666;
        cursor: not-allowed;
        opacity: 0.7;
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

  <main class="user-container">

    <aside class="admin-sidebar"> 
      <div class="admin-profile">
        <img src="logo.png" alt="user profile" />
        <div>
          <h3>Hoş Geldiniz!</h3>
          <p id="userName"><?php echo htmlspecialchars($userName); ?></p>
        </div>
      </div>

      <ul id="user-menu" class="admin-menu">
        <li class="active" data-content="catalog">Kitap Kataloğu</li>
        <li data-content="reservations">Rezervasyonlarım</li>
        <li data-content="profile">Profil Ayarları</li>
      </ul>

      </aside>

    <section class="user-main">
      
      <?php if ($message): ?>
        <div class="message <?php echo htmlspecialchars($message['type']); ?>">
            <?php echo htmlspecialchars($message['text']); ?>
        </div>
      <?php endif; ?>


      <div id="catalog" class="content-section active">
          <div class="panel">
            <div class="panel-header">
              <h3>Kütüphane Kataloğu</h3>
              <p class="muted">Aşağıdaki listeden kitapları inceleyebilir ve rezerve edebilirsiniz.</p>
            </div>
    
            <div class="panel-body">
              <input id="bookSearch" placeholder="Kitap veya yazar ara..." style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 12px;"/>
              
              <div class="table-wrap">
                <table id="catalogTable" class="table">
                  <thead>
                    <tr><th>Başlık</th><th>Yazar</th><th>Kategori</th><th>Durum</th><th>İşlemler</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($kitaplar as $kitap): 
                        $isReserved = (bool) $kitap['is_reserved_by_user'];
                        $statusClass = $isReserved ? 'reserved' : 'available';
                        $statusText = $isReserved ? 'Rezerve Edildi' : 'Mevcut';
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($kitap['title']); ?></td>
                      <td><?php echo htmlspecialchars($kitap['author']); ?></td>
                      <td><?php echo htmlspecialchars($kitap['category']); ?></td>
                      <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                      <td class="ops">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="reserve_book">
                            <input type="hidden" name="book_id" value="<?php echo $kitap['id']; ?>">
                            <button type="submit" 
                                class="btn small" 
                                <?php echo $isReserved ? 'disabled' : ''; ?>
                            >
                                <?php echo $isReserved ? 'Rezerve Edildi' : 'Rezerve Et'; ?>
                            </button>
                        </form>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
              </div>
            </div>
          </div>
    
          <div class="panel">
            <div class="panel-header">
              <h3>Önemli Duyurular</h3>
              <p class="muted">Kütüphane ile ilgili güncel bilgileri buradan takip edebilirsiniz.</p>
            </div>
            <div class="panel-body ann-list">
              <ul id="announcements">
                 <li>Duyurular veritabanından çekilmelidir.</li>
                 </ul>
            </div>
          </div>
      </div>

      <div id="reservations" class="content-section">
        <div class="panel">
            <div class="panel-header">
                <h3>Rezervasyon Listem</h3>
                <p class="muted">Rezerve ettiğiniz kitapların listesi ve son teslim tarihleri.</p>
            </div>
            <div class="panel-body">
                <div class="table-wrap">
                    <table id="reservationsTable" class="table">
                      <thead>
                        <tr><th>Kitap Adı</th><th>Yazar</th><th>Rezervasyon Tarihi</th><th>İşlemler</th></tr>
                      </thead>
                      <tbody>
                        <?php if (empty($rezervasyonlar)): ?>
                            <tr><td colspan="4" style="text-align:center;">Henüz rezerve ettiğiniz bir kitap bulunmamaktadır.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rezervasyonlar as $rez): ?>
                            <tr>
                              <td><?php echo htmlspecialchars($rez['title']); ?></td>
                              <td><?php echo htmlspecialchars($rez['author']); ?></td>
                              <td><?php echo date("d.m.Y", strtotime($rez['reservation_date'])); ?></td>
                              <td class="ops">
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bu rezervasyonu iptal etmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="action" value="cancel_reservation">
                                    <input type="hidden" name="reservation_id" value="<?php echo $rez['id']; ?>">
                                    <button type="submit" class="btn danger small">İptal Et</button>
                                </form>
                              </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                </div>
            </div>
        </div>
      </div>

      <div id="profile" class="content-section">
        <div class="panel">
            <div class="panel-header">
                <h3>Kullanıcı Bilgileri ve Şifre Güncelleme</h3>
                <p class="muted">Bilgilerinizi güncelleyebilir veya şifrenizi değiştirebilirsiniz.</p>
            </div>
            <div class="panel-body">
                <form class="profile-form">
                    <h4>Kişisel Bilgiler</h4>
                    <div class="form-group">
                        <label for="profileName">Ad Soyad</label>
                        <input type="text" id="profileName" value="<?php echo htmlspecialchars($userName); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="profileEmail">E-Posta</label>
                        <input type="email" id="profileEmail" value="<?php echo htmlspecialchars($userEmail); ?>" disabled>
                    </div>

                    <h4>Şifre Güncelleme</h4>
                    <p class="muted">Şifre güncelleme işlevi dinamikleştirilmelidir.</p>
                </form>
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

    const userMenu = $('#user-menu');

    // Menü Geçişi
    userMenu.addEventListener('click', (e) => {
        const menuItem = e.target.closest('li');
        if (!menuItem) return;

        const targetContentId = menuItem.dataset.content;