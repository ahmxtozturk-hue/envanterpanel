<?php
session_start();

require_once __DIR__ . '/../../api/auth.php';

header('Content-Type: application/json');

// Kullanıcının giriş yapıp yapmadığını kontrol et
if (!isset($_SESSION['loggedin'])) {
    http_response_code(401); // Yetkisiz erişim
    echo json_encode([
        'success' => false,
        'message' => 'Giriş yapılmamış. Lütfen oturum açın.'
    ]);
    exit();
}

// Veritabanı bağlantısı
$host = 'localhost';
$db_name = 'gunesege_katalog';
$username = 'gunesege_admintabela';
$password = 'A22_2n34A1134i!';

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Node.js API'sine GET isteği gönder
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    
    // Query parametrelerini oluştur
    $queryParams = http_build_query([
        'secret_key' => '98234983242345jjjhhy',
        'page' => $page,
        'limit' => $limit
    ]);
    
    $url = 'http://82.153.241.79:9013/api/urunler?' . $queryParams;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('CURL hatası: ' . curl_error($ch));
    }
    
    curl_close($ch);

    // Node.js'den gelen yanıtı direkt ilet
    http_response_code($httpCode);
    echo $response;

} catch (PDOException $e) {
    error_log("Veritabanı hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Veritabanı hatası oluştu'
    ]);
} catch (Exception $e) {
    error_log("Genel hata: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu: ' . $e->getMessage()
    ]);
}
?>
