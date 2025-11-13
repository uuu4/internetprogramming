<!DOCTYPE html>
<html>
<head>
    <title>Öğrenci Listesi</title>
    <style>
        /* Genel Stil: Siyah Arkaplan, Beyaz Yazı */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #1a1a1a; /* Koyu Siyah Arkaplan */
            color: #f0f0f0; /* Açık Gri/Beyaz Yazı */
            display: flex;
            flex-direction: column;
            align-items: center; /* Her şeyi ortaya hizala */
        }
        h2 {
            color: #00e676; /* Parlak Yeşil Başlık */
            margin-bottom: 25px;
        }

        /* Container ve Tablo Stili */
        .container {
            width: 80%;
            max-width: 1000px;
            background-color: #2c2c2c; /* Koyu Gri Container */
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }
        table {
            width: 100%;
            border-collapse: separate; /* Köşeleri yuvarlamak için */
            border-spacing: 0;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #444; /* Daha ince, koyu ayırıcı */
        }
        th {
            background-color: #00a040; /* Koyu Yeşil Başlık Arkaplanı */
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #383838; /* Çift satır arkaplanı */
        }
        tr:hover {
            background-color: #4a4a4a; /* Hover efekti */
        }
        
        /* Buton Stilleri */
        .ekle-btn, .duzenle-btn, .sil-btn {
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
            transition: background-color 0.3s;
            margin: 2px;
        }
        .ekle-btn {
            background-color: #00e676; /* Ana Yeşil */
            color: #1a1a1a;
            margin-bottom: 20px;
        }
        .ekle-btn:hover {
            background-color: #00c853;
        }
        .duzenle-btn {
            background-color: #2196F3; /* Mavi (Aksiyon için farklı bir renk) */
            color: white;
        }
        .duzenle-btn:hover {
            background-color: #0d8ce8;
        }
        .sil-btn {
            background-color: #ff3d00; /* Kırmızı (Tehlike için farklı bir renk) */
            color: white;
        }
        .sil-btn:hover {
            background-color: #e63900;
        }
        p {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Öğrenci Yönetim Sistemi</h2>
        <a href="index.php?controller=ogrenci&action=ekle" class="ekle-btn">➕ Yeni Öğrenci Ekle</a>

        <?php if (count($ogrenciler) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad</th>
                    <th>Soyad</th>
                    <th>Öğrenci No</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ogrenciler as $ogrenci): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ogrenci['id']); ?></td>
                    <td><?php echo htmlspecialchars($ogrenci['ad']); ?></td>
                    <td><?php echo htmlspecialchars($ogrenci['soyad']); ?></td>
                    <td><?php echo htmlspecialchars($ogrenci['ogrenci_no']); ?></td>
                    <td>
                        <a href="index.php?controller=ogrenci&action=duzenle&id=<?php echo htmlspecialchars($ogrenci['id']); ?>" class="duzenle-btn">✏️ Düzenle</a>
                        <a href="index.php?controller=ogrenci&action=sil&id=<?php echo htmlspecialchars($ogrenci['id']); ?>" class="sil-btn" onclick="return confirm('Bu öğrenciyi silmek istediğinizden emin misiniz?');">🗑️ Sil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>Henüz kayıtlı öğrenci bulunmamaktadır.</p>
        <?php endif; ?>
    </div>
</body>
</html>