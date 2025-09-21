<?php
// Veritabanı bağlantı ayarları
define('DB_HOST', 'localhost:3306');
define('DB_USER', 'root');
define('DB_PASS', ''); // MySQL şifrenizi buraya yazın
define('DB_NAME', 'kurumsal');

// Mail ayarları - Gmail SMTP için
define('MAIL_FROM_EMAIL', 'nzm.burak02@gmail.com'); // Gmail adresiniz
define('MAIL_FROM_NAME', 'Kurumsal İstekler Sistemi');
define('MAIL_SMTP_HOST', 'smtp.gmail.com'); // Gmail SMTP
define('MAIL_SMTP_PORT', 587); // Gmail port
define('MAIL_SMTP_USERNAME', 'nzm.burak02@gmail.com'); // Gmail adresiniz
define('MAIL_SMTP_PASSWORD', 'qhvb dlmv vcbi qxsw'); // Gmail uygulama şifreniz
define('MAIL_USE_SMTP', true); // Gmail için true

// Veritabanı bağlantısı
function getDBConnection() {
    // Debug bilgisi
    error_log("Veritabanına bağlanmaya çalışılıyor: " . DB_HOST . " - " . DB_NAME);
    
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Bağlantı hatası kontrolü
    if ($mysqli->connect_error) {
        error_log("Veritabanı bağlantı hatası: " . $mysqli->connect_error);
        error_log("Host: " . DB_HOST . ", User: " . DB_USER . ", Database: " . DB_NAME);
        return null;
    }
    
    // UTF-8 karakter seti ayarla
    $mysqli->set_charset("utf8mb4");
    
    error_log("Veritabanı bağlantısı başarılı");
    return $mysqli;
}

// Bağlantıyı kapat
function closeDBConnection($mysqli) {
    if ($mysqli) {
        $mysqli->close();
    }
}

// Güvenli sorgu çalıştırma
function executeQuery($mysqli, $sql, $params = []) {
    if (empty($params)) {
        $result = $mysqli->query($sql);
        if (!$result) {
            error_log("Sorgu hatası: " . $mysqli->error);
            return false;
        }
        return $result;
    } else {
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Prepared statement hatası: " . $mysqli->error);
            return false;
        }
        
        // Parametre tiplerini belirle
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }
        
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Execute hatası: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        // SELECT sorguları için result döndür, diğerleri için true
        if (stripos(trim($sql), 'SELECT') === 0) {
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        } else {
            $stmt->close();
            return true;
        }
    }
}

// Tek satır getir
function fetchRow($mysqli, $sql, $params = []) {
    $result = executeQuery($mysqli, $sql, $params);
    if ($result) {
        return $result->fetch_assoc();
    }
    return false;
}

// Tüm satırları getir
function fetchAll($mysqli, $sql, $params = []) {
    $result = executeQuery($mysqli, $sql, $params);
    if ($result) {
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    return false;
}

// Etkilenen satır sayısını getir
function getAffectedRows($mysqli) {
    return $mysqli->affected_rows;
}

// Son eklenen ID'yi getir
function getLastInsertId($mysqli) {
    return $mysqli->insert_id;
}
function getTeachers($mysqli) {
    $sql = "SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM teachers ORDER BY first_name ASC";
    $result = $mysqli->query($sql);

    if (!$result) {
        die(json_encode(['success' => false, 'message' => 'Sorgu hatası: ' . $mysqli->error]));
    }

    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = $row;
    }

    return $teachers;
}

try {
    // PDO ile bağlantı oluştur
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Hata ayarları
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch modu
            PDO::ATTR_EMULATE_PREPARES => false, // Gerçek prepared statements kullan
        ]
    );
} catch (PDOException $e) {
    // Bağlantı hatası durumunda scripti durdur ve mesaj göster
    die("Veritabanı bağlantısı kurulamadı: " . $e->getMessage());
}



?>
