<!DOCTYPE html>
<html>
<head>
    <title>Öğrenci Ekle</title>
    <style>
        /* Genel Stil: Siyah Arkaplan, Beyaz Yazı, Ortada */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #1a1a1a;
            color: #f0f0f0;
            display: flex;
            flex-direction: column;
            align-items: center; /* Ortala */
            min-height: 100vh;
        }
        h2 {
            color: #00e676;
            margin-bottom: 20px;
        }
        form {
            width: 100%;
            max-width: 400px; /* Form genişliğini sınırla */
            padding: 30px;
            background-color: #2c2c2c;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
            color: #00e676; /* Yeşil Label Rengi */
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            box-sizing: border-box;
            border: 1px solid #444;
            background-color: #383838; /* Input arkaplanı */
            color: #f0f0f0;
            border-radius: 5px;
        }
        input[type="submit"] {
            background-color: #00e676; /* Yeşil Kaydet Butonu */
            color: #1a1a1a;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #00c853;
        }
        .geri-btn {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #9e9e9e; /* Açık gri geri butonu */
            transition: color 0.3s;
        }
        .geri-btn:hover {
            color: #f0f0f0;
        }
    </style>
</head>
<body>
    <h2>Yeni Öğrenci Ekle</h2>

    <form action="index.php?controller=ogrenci&action=ekle" method="POST">
        <label for="ad">Ad:</label>
        <input type="text" id="ad" name="ad" required>

        <label for="soyad">Soyad:</label>
        <input type="text" id="soyad" name="soyad" required>

        <label for="ogrenci_no">Öğrenci No:</label>
        <input type="text" id="ogrenci_no" name="ogrenci_no" required>

        <input type="submit" value="Kaydet">
    </form>
    <a href="index.php" class="geri-btn">⬅️ Geri Dön</a>
</body>
</html>