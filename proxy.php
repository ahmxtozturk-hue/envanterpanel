<?php
header('Content-Type: application/json');

// Veritabanı bağlantısı bilgileri (index.php'den alındı)
$host = 'localhost';
$db_name = 'gunesege_katalog';
$username = 'gunesege_admintabela';
$password = 'A22_2n34A1134i!';

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Gelen parametreleri al
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $filters = isset($_GET['filters']) ? json_decode($_GET['filters'], true) : [];
    $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'stok_kodu'; // Varsayılan sıralama
    $sortOrder = isset($_GET['sortOrder']) && in_array(strtoupper($_GET['sortOrder']), ['ASC', 'DESC']) ? $_GET['sortOrder'] : 'ASC';

    // Güvenlik: İzin verilen sıralama sütunları
    $allowedSortColumns = ['stok_kodu', 'envanteradi', 'departman', 'kullanici'];
    if (!in_array($sortBy, $allowedSortColumns)) {
        $sortBy = 'stok_kodu'; // Geçersiz sütun gelirse varsayılana dön
    }

    $offset = ($page - 1) * $limit;

    // WHERE koşulunu oluştur
    $whereClauses = [];
    $params = [];
    if (!empty($filters)) {
        if (!empty($filters['stok_kodu'])) {
            $whereClauses[] = "stok_kodu LIKE :stok_kodu";
            $params[':stok_kodu'] = '%' . $filters['stok_kodu'] . '%';
        }
        if (!empty($filters['envanteradi'])) {
            $whereClauses[] = "envanteradi LIKE :envanteradi";
            $params[':envanteradi'] = '%' . $filters['envanteradi'] . '%';
        }
        if (!empty($filters['departman'])) {
            $whereClauses[] = "departman LIKE :departman";
            $params[':departman'] = '%' . $filters['departman'] . '%';
        }
        if (!empty($filters['kullanici'])) {
            $whereClauses[] = "kullanici LIKE :kullanici";
            $params[':kullanici'] = '%' . $filters['kullanici'] . '%';
        }
    }

    $whereSql = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    // Toplam kayıt sayısını al
    $totalStmt = $db->prepare("SELECT COUNT(*) FROM envanter " . $whereSql);
    $totalStmt->execute($params);
    $totalRecords = $totalStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $limit);

    // Envanter verilerini al
    $query = "SELECT stok_kodu, envanteradi, departman, kullanici, resim FROM envanter "
           . $whereSql
           . " ORDER BY " . $sortBy . " " . $sortOrder
           . " LIMIT " . $limit . " OFFSET " . $offset;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Yanıtı oluştur
    $responseData = [
        'products' => $products,
        'total' => $totalRecords,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'per_page' => $limit
    ];

    // Sadece ön yüz tarafından açıkça istendiğinde filtre seçeneklerini gönder
    if (isset($_GET['getFilters']) && $_GET['getFilters'] === 'true') {
        $departmanStmt = $db->query("SELECT DISTINCT departman FROM envanter WHERE departman IS NOT NULL AND departman != '' ORDER BY departman ASC");
        $responseData['filterOptions']['departman'] = $departmanStmt->fetchAll(PDO::FETCH_COLUMN);

        $kullaniciStmt = $db->query("SELECT DISTINCT kullanici FROM envanter WHERE kullanici IS NOT NULL AND kullanici != '' ORDER BY kullanici ASC");
        $responseData['filterOptions']['kullanici'] = $kullaniciStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $response = [
        'success' => true,
        'data' => $responseData
    ];

} catch (PDOException $e) {
    // Hata durumunda yanıt
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ];
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => 'Genel hata: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?>
