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

// Sadece POST isteklerine izin ver
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'Sadece POST istekleri kabul edilir'
    ]);
    exit();
}

// Gelen veriyi al
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['urunler']) || !is_array($input['urunler'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz ürün listesi'
    ]);
    exit();
}

// Node.js API'sine silme isteği gönder
$apiResponse = sendDeleteRequestToNodeAPI($input['urunler']);

// API yanıtına göre cevap dön
if (!empty($apiResponse['success'])) {
    echo json_encode([
        'success' => true,
        'message' => 'Ürünler başarıyla silindi',
        'api_response' => $apiResponse
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Node.js API hatası: ' . ($apiResponse['message'] ?? 'Bilinmeyen hata'),
        'api_response' => $apiResponse
    ]);
}

/**
 * Node.js API'sine silme isteği gönderir
 */
function sendDeleteRequestToNodeAPI($urunKodlari) {
    $data = [
        'secret_key' => '98234983242345jjjhhy',
        'urunler' => $urunKodlari
    ];
    
    $url = 'http://82.153.241.79:9013/api/urunler/sil';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log("API silme hatası: " . curl_error($ch));
        return [
            'success' => false,
            'message' => 'API iletişim hatası: ' . curl_error($ch)
        ];
    }
    
    curl_close($ch);
    
    return json_decode($response, true) ?? [
        'success' => false,
        'message' => 'API yanıtı işlenemedi'
    ];
}
?>
