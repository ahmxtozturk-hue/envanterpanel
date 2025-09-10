<?php
require_once __DIR__ . '/../../api/auth.php';

// Veritabanı bağlantısı
$host = 'localhost';
$db_name = 'gunesege_katalog';
$username = 'gunesege_admintabela';
$password = 'A22_2n34A1134i!';

try {
    $db = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Kullanıcı aktivite bilgilerini veritabanından al
    $online_users = [];
    $recent_logins = [];

    // Mevcut kullanıcı bilgilerini al
    $current_user_stmt = $db->prepare("SELECT username, is_online, last_activity FROM users WHERE username = :username");
    $current_user_stmt->execute([':username' => $_SESSION['username']]);
    $current_user = $current_user_stmt->fetch(PDO::FETCH_ASSOC);

    // Tüm kullanıcıları al
    $stmt = $db->query("SELECT username, last_activity, is_online FROM users ORDER BY is_online DESC, last_activity DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        if ($user['is_online']) {
            $online_users[] = $user;
        } else {
            $recent_logins[] = $user;
        }
    }

} catch (PDOException $e) {
    error_log("Veritabanı hatası: " . $e->getMessage());
    $online_users = [];
    $recent_logins = [];
    $current_user = ['username' => 'Kullanıcı', 'is_online' => false];
}
?>

<!doctype html>
<html lang="en" 
  class="<?php echo htmlspecialchars($_COOKIE['theme_preset'] ?? 'preset-1'); ?>" 
  data-pc-preset="<?php echo htmlspecialchars($_COOKIE['theme_preset'] ?? 'preset-1'); ?>" 
  data-pc-sidebar-caption="true" 
  data-pc-direction="ltr" 
  dir="ltr" 
  data-pc-theme="<?php echo htmlspecialchars($_COOKIE['theme_mode'] ?? 'light'); ?>">
  <!-- [Head] start -->
  <head>
    <title>Envanter Listesi | Admin Panel</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Admin Paneli" />
    <meta name="keywords" content="admin panel" />
    <meta name="author" content="Güneş Egel" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="../../assets/images/favicon.svg" type="image/x-icon" />

     <!-- [Font] Family -->
     <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <!-- [phosphor Icons] -->
    <link rel="stylesheet" href="../../assets/fonts/phosphor/duotone/style.css" />
    <!-- [Tabler Icons] -->
    <link rel="stylesheet" href="../../assets/fonts/tabler-icons.min.css" />
    <!-- [Feather Icons] -->
    <link rel="stylesheet" href="../../assets/fonts/feather.css" />
    <!-- [Font Awesome Icons] -->
    <link rel="stylesheet" href="../../assets/fonts/fontawesome.css" />
    <!-- [Material Icons] -->
    <link rel="stylesheet" href="../../assets/fonts/material.css" />
    <!-- [Template CSS Files] -->
    <link rel="stylesheet" href="../../assets/css/style.css" id="main-style-link" />
    
    <!-- Choices.js CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/>

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">
    
    <style>
      [data-pc-theme="dark"] .swal2-popup {
        background: #2a2a3c;
        color: #e0e0e0 !important;
      }
      [data-pc-theme="dark"] .swal2-content,
      [data-pc-theme="dark"] .swal2-html-container {
        color: #e0e0e0 !important;
      }
      [data-pc-theme="light"] .swal2-popup {
        background: #ffffff;
        color: #333333 !important;
      }
      [data-pc-theme="light"] .swal2-content,
      [data-pc-theme="light"] .swal2-html-container {
        color: #333333 !important;
      }
      
      .product-image {
        max-width: 80px;
        max-height: 80px;
        object-fit: cover;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
      }
      
      .product-image:hover {
        transform: scale(1.05);
        border-color: var(--bs-primary);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      }

      /* Image Modal Styles */
      .image-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        animation: fadeIn 0.3s ease;
      }

      .image-modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
        border-radius: 10px;
        animation: zoomIn 0.3s ease;
      }

      .image-modal-close {
        position: absolute;
        top: 20px;
        right: 35px;
        color: #fff;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
      }

      .image-modal-close:hover {
        color: #ff6b6b;
      }

      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }

      @keyframes zoomIn {
        from { transform: translate(-50%, -50%) scale(0.5); }
        to { transform: translate(-50%, -50%) scale(1); }
      }

      /* Loading Animation */
      .loading-spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(0,0,0,.1);
        border-radius: 50%;
        border-top-color: var(--bs-primary);
        animation: spin 1s ease-in-out infinite;
      }

      @keyframes spin {
        to { transform: rotate(360deg); }
      }

      /* Enhanced Table Styles */
      .table-responsive {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
      }

      .table thead th {
        background: linear-gradient(135deg, var(--bs-primary), var(--bs-primary-dark));
        color: white;
        border: none;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 15px 10px;
      }

      .table tbody tr {
        transition: all 0.3s ease;
      }

      .table tbody tr:hover {
        background-color: var(--bs-light);
        transform: translateX(5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      }

      [data-pc-theme="dark"] .table tbody tr:hover {
        background-color: rgba(255,255,255,0.05);
      }

      .table tbody td {
        padding: 15px 10px;
        vertical-align: middle;
        border-bottom: 1px solid rgba(0,0,0,0.05);
      }

      /* Enhanced Buttons */
      .btn {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
      }

      .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
      }

      .btn:hover::before {
        left: 100%;
      }

      .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      }

      /* Card Enhancements */
      .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
      }

      .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
      }

      .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 15px 15px 0 0 !important;
        padding: 20px;
      }

      /* Pagination Enhancements */
      .pagination .page-link {
        border: none;
        margin: 0 2px;
        border-radius: 8px;
        color: var(--bs-primary);
        font-weight: 500;
        transition: all 0.3s ease;
      }

      .pagination .page-link:hover {
        background: var(--bs-primary);
        color: white;
        transform: translateY(-2px);
      }

      .pagination .page-item.active .page-link {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
        box-shadow: 0 5px 15px rgba(var(--bs-primary-rgb), 0.3);
      }

      /* Checkbox Enhancements */
      .form-check-input {
        border-radius: 4px;
        border: 2px solid #ddd;
        transition: all 0.3s ease;
      }

      .form-check-input:checked {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
        box-shadow: 0 0 10px rgba(var(--bs-primary-rgb), 0.5);
      }

      /* Search and Filter Section */
      .filter-section {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
      }

      [data-pc-theme="dark"] .filter-section {
        background: linear-gradient(135deg, #2c2c54 0%, #40407a 100%);
      }

      /* Badge Styles */
      .badge {
        font-size: 0.75rem;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
      }

      /* Mobile Responsive Styles */
      @media (max-width: 768px) {
        .card-header .d-flex {
          flex-direction: column;
          gap: 15px;
        }
        
        .filter-section .row {
          gap: 15px;
        }
        
        .filter-section .col-md-3,
        .filter-section .col-md-6 {
          width: 100%;
          max-width: 100%;
          flex: 0 0 100%;
        }
        
        .table-responsive {
          overflow-x: auto;
        }
        
        .table thead th,
        .table tbody td {
          padding: 10px 5px;
          font-size: 14px;
        }
        
        .product-image {
          max-width: 60px;
          max-height: 60px;
        }
        
        .btn-lg {
          padding: 8px 16px;
          font-size: 14px;
        }
        
        .card-body .row {
          flex-direction: column;
          gap: 15px;
        }
        
        .card-body .row .col-md-6 {
          width: 100%;
          text-align: center !important;
        }
        
        .pagination {
          flex-wrap: wrap;
          justify-content: center;
        }
        
        .pagination .page-item {
          margin-bottom: 5px;
        }
      }

      @media (max-width: 576px) {
        .page-header-title h5 {
          font-size: 18px;
        }
        
        .breadcrumb {
          font-size: 12px;
        }
        
        .card-header h5 {
          font-size: 16px;
        }
        
        .table thead th,
        .table tbody td {
          padding: 8px 3px;
          font-size: 12px;
        }
        
        .product-image {
          max-width: 40px;
          max-height: 40px;
        }
        
        .badge {
          font-size: 10px;
          padding: 4px 8px;
        }
        
        .btn {
          font-size: 12px;
          padding: 6px 12px;
        }
      }

      /* Filter input styles */
      .filter-input {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 8px 12px;
        width: 100%;
        transition: all 0.3s ease;
      }
      
      .filter-input:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
        outline: none;
      }
      
      [data-pc-theme="dark"] .filter-input {
        background-color: #2a2a3c;
        border-color: #444;
        color: #e0e0e0;
      }
    </style>

  </head>
  <!-- [Head] end -->
  <!-- [Body] Start -->

  <body>
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg fixed inset-0 bg-white dark:bg-themedark-cardbg z-[1034]">
      <div class="loader-track h-[5px] w-full inline-block absolute overflow-hidden top-0">
        <div class="loader-fill w-[300px] h-[5px] bg-primary-500 absolute top-0 left-0 animate-[hitZak_0.6s_ease-in-out_infinite_alternate]"></div>
      </div>
    </div>
    <!-- [ Pre-loader ] End -->
    
    <!-- [ Sidebar Menu ] start -->
    <nav class="pc-sidebar">
      <div class="navbar-wrapper">
        <div class="navbar-content h-[calc(100vh_-_74px)] py-2.5">
          <?php include '../../assets/menu.php'; renderMenu(); ?>
        </div>
      </div>
    </nav>
    <!-- [ Sidebar Menu ] end -->
    
    <!-- [ Header Topbar ] start -->
    <header class="pc-header">
      <div class="header-wrapper flex max-sm:px-[15px] px-[25px] grow">
        <div class="me-auto pc-mob-drp">
          <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
            <li class="pc-h-item pc-sidebar-collapse max-lg:hidden lg:inline-flex">
              <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="sidebar-hide">
                <i data-feather="menu"></i>
              </a>
            </li>
            <li class="pc-h-item pc-sidebar-popup lg:hidden">
              <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="mobile-collapse">
                <i data-feather="menu"></i>
              </a>
            </li>
          </ul>
        </div>
        <div class="ms-auto">
          <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
            <li class="dropdown pc-h-item">
              <a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button"
                aria-haspopup="false" aria-expanded="false">
                <i data-feather="sun"></i>
              </a>
              <div class="dropdown-menu dropdown-menu-end pc-h-dropdown">
                <a href="#!" class="dropdown-item" onclick="layout_change('dark')">
                  <i data-feather="moon"></i>
                  <span>Dark</span>
                </a>
                <a href="#!" class="dropdown-item" onclick="layout_change('light')">
                  <i data-feather="sun"></i>
                  <span>Light</span>
                </a>
                <a href="#!" class="dropdown-item" onclick="layout_change_default()">
                  <i data-feather="settings"></i>
                  <span>Default</span>
                </a>
              </div>
            </li>
            <li class="dropdown pc-h-item header-user-profile">
              <a class="pc-head-link dropdown-toggle arrow-none me-0" data-pc-toggle="dropdown" href="#" role="button"
                aria-haspopup="false" data-pc-auto-close="outside" aria-expanded="false">
                <i data-feather="user"></i>
              </a>
              <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown p-2 overflow-hidden">
                <div class="dropdown-header flex items-center justify-between py-4 px-5 bg-primary-500">
                  <div class="flex mb-1 items-center">
                    <div class="grow ms-3">
                      <h6 class="mb-1 text-white"><?php echo htmlspecialchars($current_user['username'] ?? 'Kullanıcı'); ?></h6>
                      <span class="text-white">Durum: <?php echo ($current_user['is_online'] ? 'Çevrimiçi' : 'Çevrimdışı'); ?></span>
                    </div>
                  </div>
                </div>
                <div class="dropdown-body py-4 px-5">
                  <div class="profile-notification-scroll position-relative" style="max-height: calc(100vh - 225px)">
                    <a href="https://sorgu.gunesegel.net/logout" class="dropdown-item">
                      <span>
                        <svg class="pc-icon text-muted me-2 inline-block">
                          <use xlink:href="#custom-logout-1-outline"></use>
                        </svg>
                        <span>Çıkış Yap</span>
                      </span>
                    </a>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </header>
    <!-- [ Header ] end -->

    <!-- [ Main Content ] start -->
    <div class="pc-container">
      <div class="pc-content">
        <div class="page-header">
          <div class="page-block">
            <div class="page-header-title">
              <h5 class="mb-0 font-medium">
                <i class="me-2 text-primary"></i>
                Envanter Listesi
              </h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="../">Home</a></li>
              <li class="breadcrumb-item"><a href="javascript: void(0)">Envanter Listesi</a></li>
              <li class="breadcrumb-item" aria-current="page">Liste</li>
            </ul>
          </div>
        </div>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
          <div class="col-span-12">
            <div class="card">
              <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">
                    <i class="fas fa-inventory me-2"></i>
                    Envanter Yönetimi
                  </h5>
                  <a href="../barkodekle" class="btn btn-light btn-lg">
                    <i class="fas fa-plus me-2"></i>Yeni Envanter Ekle
                  </a>
                </div>
              </div>
              <div class="card-body">
                <!-- Filter Section -->
                <div class="filter-section">
                  <div class="row align-items-center">
                    <div class="col-md-3">
                      <label for="pageLimit" class="form-label fw-bold">Sayfa Başı Göster:</label>
                      <select id="pageLimit" class="form-select form-select-lg">
                        <option value="20">20 Kayıt</option>
                        <option value="50">50 Kayıt</option>
                        <option value="100">100 Kayıt</option>
                        <option value="200">200 Kayıt</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <div class="d-flex justify-content-center">
                        <nav aria-label="Page navigation">
                          <ul class="pagination pagination-lg">
                            <!-- Pagination will be generated by JavaScript -->
                          </ul>
                        </nav>
                      </div>
                    </div>
                    <div class="col-md-3 text-end">
                      <div class="text-muted">
                        <span id="recordInfo">Toplam: <span class="loading-spinner"></span></span>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Filter Inputs -->
                  <div class="row mt-3">
                    <div class="col-md-2 mb-2">
                      <input type="text" class="filter-input" id="filterStokKodu" placeholder="Stok Kodu Ara...">
                    </div>
                    <div class="col-md-3 mb-2">
                      <input type="text" class="filter-input" id="filterEnvanterAdi" placeholder="Envanter Adı Ara...">
                    </div>
                    <div class="col-md-2 mb-2">
                      <select id="filterDepartman" class="filter-input" placeholder="Departman Seç..."></select>
                    </div>
                    <div class="col-md-2 mb-2">
                      <select id="filterKullanici" class="filter-input" placeholder="Kullanıcı Seç..."></select>
                    </div>
                    <div class="col-md-3 mb-2 d-flex gap-2">
                      <button id="clearFilters" class="btn btn-secondary w-50">
                        <i class="fas fa-times me-1"></i> Temizle
                      </button>
                      <button id="applyFilters" class="btn btn-primary w-50">
                        <i class="fas fa-filter me-1"></i> Uygula
                      </button>
                    </div>
                  </div>
                </div>
                
                <div class="table-responsive">
                  <table class="table table-hover" id="envanterTable">
                    <thead>
                      <tr>
                        <th width="50">
                          <div class="form-check">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                            <label class="form-check-label" for="selectAll"></label>
                          </div>
                        </th>
                        <th width="150" data-sort="stok_kodu" style="cursor: pointer;">
                          <i class="fas fa-barcode me-2"></i>Stok Kodu
                        </th>
                        <th width="300" data-sort="envanteradi" style="cursor: pointer;">
                          <i class="fas fa-tag me-2"></i>Envanter Adı
                        </th>
                        <th width="200" data-sort="departman" style="cursor: pointer;">
                          <i class="fas fa-building me-2"></i>Departman
                        </th>
                        <th width="150" data-sort="kullanici" style="cursor: pointer;">
                          <i class="fas fa-user me-2"></i>Kullanıcı
                        </th>
                        <th width="120" class="text-center">
                          <i class="fas fa-image me-2"></i>Resim
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td colspan="7" class="text-center py-5">
                          <div class="loading-spinner me-2"></div>
                          Envanterler yükleniyor...
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                
                <div class="row mt-4">
                  <div class="col-md-6">
                    <button id="topluSilBtn" class="btn btn-danger btn-lg">
                      <i class="fas fa-trash me-2"></i>Seçili Envanterleri Sil
                    </button>
                  </div>
                  <div class="col-md-6 text-end">
                    <button id="refreshBtn" class="btn btn-info btn-lg me-2">
                      <i class="fas fa-sync-alt me-2"></i>Yenile
                    </button>
                    <button id="exportBtn" class="btn btn-success btn-lg">
                      <i class="fas fa-download me-2"></i>Excel'e Aktar
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- [ Main Content ] end -->
      </div>
    </div>
    <!-- [ Main Content ] end -->

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
      <span class="image-modal-close">&times;</span>
      <img class="image-modal-content" id="modalImage">
    </div>
 
    <!-- Required Js -->
    <script src="../../assets/js/plugins/simplebar.min.js"></script>
    <script src="../../assets/js/plugins/popper.min.js"></script>
    <script src="../../assets/js/icon/custom-icon.js"></script>
    <script src="../../assets/js/plugins/feather.min.js"></script>
    <script src="../../assets/js/component.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/script.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
    
    <!-- SheetJS for Excel export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    
    <!-- Choices.js JS -->
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    <!-- Heartbeat Script -->
    <script src="https://katalog.gunesegel.net/dashboard/js/heartbeat.js"></script>

    <div class="floting-button fixed bottom-[50px] right-[30px] z-[1030]">
    </div>
    
    <script>
document.addEventListener('DOMContentLoaded', function() {
    feather.replace();
    
    let currentPage = 1;
    let currentLimit = 20;
    let currentFilters = {
        stok_kodu: '',
        envanteradi: '',
        departman: '',
        kullanici: ''
    };
    let currentSortBy = 'stok_kodu';
    let currentSortOrder = 'asc';
    
    let departmanChoices, kullaniciChoices;
    let filtersInitialized = false;

    // Ana veri yükleme fonksiyonu
    function loadInventory(page = 1, limit = 20, filters = {}, sortBy, sortOrder) {
        currentPage = page;
        currentLimit = limit;
        if (Object.keys(filters).length > 0) {
            currentFilters = filters;
        }
        if (sortBy) currentSortBy = sortBy;
        if (sortOrder) currentSortOrder = sortOrder;

        $('#envanterTable tbody').html(`
            <tr>
                <td colspan="6" class="text-center py-5">
                    <div class="loading-spinner me-2"></div>
                    Envanterler yükleniyor...
                </td>
            </tr>
        `);
        
        $.ajax({
            url: 'proxy.php',
            type: 'GET',
            data: {
                page: currentPage,
                limit: currentLimit,
                filters: JSON.stringify(currentFilters),
                sortBy: currentSortBy,
                sortOrder: currentSortOrder,
                getFilters: !filtersInitialized
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    if (response.data.filterOptions && !filtersInitialized) {
                        initializeFilterDropdowns(response.data.filterOptions);
                        filtersInitialized = true;
                    }
                    renderInventory(response.data);
                    renderPagination(response.data);
                    updateRecordInfo(response.data);
                } else {
                    $('#envanterTable tbody').html(`
                        <tr>
                            <td colspan="6" class="text-center text-danger py-5">
                                <i class="fas fa-exclamation-triangle fa-3x mb-3 text-warning"></i><br>
                                Envanterler yüklenirken bir hata oluştu: ${response.message || ''}
                            </td>
                        </tr>
                    `);
                }
            },
            error: function() {
                $('#envanterTable tbody').html(`
                    <tr>
                        <td colspan="6" class="text-center text-danger py-5">
                            <i class="fas fa-wifi fa-3x mb-3 text-danger"></i><br>
                            Sunucu bağlantı hatası
                        </td>
                    </tr>
                `);
            }
        });
    }

    // Choices.js filtre menülerini başlatan fonksiyon
    function initializeFilterDropdowns(options) {
        const choiceConfig = {
            searchEnabled: true,
            itemSelectText: 'Seç',
            removeItemButton: true,
            placeholder: true,
            allowHTML: false
        };

        // Departman
        const departmanEl = document.getElementById('filterDepartman');
        departmanChoices = new Choices(departmanEl, { ...choiceConfig, placeholderValue: 'Departman Seç...' });
        const departmanOptions = options.departman.map(val => ({ value: val, label: val }));
        departmanChoices.setChoices([ { value: '', label: 'Tüm Departmanlar' }, ...departmanOptions ], 'value', 'label', false);

        // Kullanıcı
        const kullaniciEl = document.getElementById('filterKullanici');
        kullaniciChoices = new Choices(kullaniciEl, { ...choiceConfig, placeholderValue: 'Kullanıcı Seç...' });
        const kullaniciOptions = options.kullanici.map(val => ({ value: val, label: val }));
        kullaniciChoices.setChoices([ { value: '', label: 'Tüm Kullanıcılar' }, ...kullaniciOptions ], 'value', 'label', false);
    }
    
    // Envanteri tabloya render et
    function renderInventory(data) {
        let html = '';
        if (data.products && data.products.length > 0) {
            data.products.forEach(item => {
                html += `
                <tr>
                    <td>
                        <div class="form-check"><input type="checkbox" class="form-check-input envanter-checkbox" value="${escapeHtml(item.stok_kodu)}"></div>
                    </td>
                    <td><span class="badge bg-primary-subtle text-primary">${escapeHtml(item.stok_kodu)}</span></td>
                    <td><div class="fw-bold">${escapeHtml(item.envanteradi)}</div></td>
                    <td><small class="text-muted">${escapeHtml(item.departman)}</small></td>
                    <td><span class="badge bg-info-subtle text-info">${escapeHtml(item.kullanici)}</span></td>
                    <td class="text-center">
                        ${item.resim ? 
                            `<img src="https://katalog.gunesegel.net/yonetim/envanterler/${escapeHtml(item.resim)}" class="product-image" alt="Envanter Resmi" onclick="openImageModal('https://katalog.gunesegel.net/yonetim/envanterler/${escapeHtml(item.resim)}')">` :
                            `<span class="badge bg-secondary">Resim Yok</span>`}
                    </td>
                </tr>`;
            });
        } else {
            html = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="fas fa-box-open fa-3x mb-3 text-muted"></i><br>
                        <span class="text-muted">Filtre kriterlerine uygun envanter bulunamadı</span>
                    </td>
                </tr>
            `;
        }
        $('#envanterTable tbody').html(html);
    }
    
    function updateRecordInfo(data) {
        const total = data.total || 0;
        const page = data.current_page || 1;
        const limit = data.per_page || 20;
        const startRecord = total > 0 ? ((page - 1) * limit) + 1 : 0;
        const endRecord = Math.min(page * limit, total);
        $('#recordInfo').html(`Toplam: <strong>${total}</strong> kayıt (${startRecord}-${endRecord} arası gösteriliyor)`);
    }
    
    function renderPagination(data) {
        let paginationHtml = '';
        const totalPages = data.total_pages || 1;
        const page = data.current_page || 1;
        if (totalPages <= 1) {
            $('.pagination').html('');
            return;
        }
        if (page > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadInventory(${page - 1}, currentLimit)" aria-label="Previous"><i class="fas fa-chevron-left"></i></a></li>`;
        }
        let startPage = Math.max(1, page - 2);
        let endPage = Math.min(totalPages, page + 2);
        if (startPage > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadInventory(1, currentLimit)">1</a></li>`;
            if (startPage > 2) paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `<li class="page-item ${i === page ? 'active' : ''}"><a class="page-link" href="#" onclick="loadInventory(${i}, currentLimit)">${i}</a></li>`;
        }
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadInventory(${totalPages}, currentLimit)">${totalPages}</a></li>`;
        }
        if (page < totalPages) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadInventory(${page + 1}, currentLimit)" aria-label="Next"><i class="fas fa-chevron-right"></i></a></li>`;
        }
        $('.pagination').html(paginationHtml);
        updateSortIcons();
    }

    function updateSortIcons() {
        $('#envanterTable thead th .sort-icon').remove();
        const th = $(`#envanterTable thead th[data-sort="${currentSortBy}"]`);
        if (th.length) {
            let iconClass = currentSortOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
            th.append(`&nbsp;<span class="sort-icon fas ${iconClass}" style="cursor: pointer;"></span>`);
        }
    }
    
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
    
    window.openImageModal = function(imageSrc) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        modal.style.display = "block";
        modalImg.src = imageSrc;
        modal.onclick = (event) => { if (event.target === modal) closeImageModal(); };
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeImageModal(); });
    }
    
    window.closeImageModal = function() {
        document.getElementById('imageModal').style.display = "none";
    }
    
    $('#pageLimit').change(function() {
        loadInventory(1, $(this).val());
    });
    
    $(document).on('click', '#envanterTable thead th[data-sort]', function() {
        const newSortBy = $(this).data('sort');
        if (!newSortBy) return;
        const newSortOrder = newSortBy === currentSortBy && currentSortOrder === 'asc' ? 'desc' : 'asc';
        loadInventory(1, currentLimit, {}, newSortBy, newSortOrder);
    });

    $('#selectAll').change(function() {
        $('.envanter-checkbox').prop('checked', $(this).prop('checked'));
        updateDeleteButton();
    });
    
    $(document).on('change', '.envanter-checkbox', function() {
        updateDeleteButton();
        const total = $('.envanter-checkbox').length;
        const checked = $('.envanter-checkbox:checked').length;
        $('#selectAll').prop('indeterminate', checked > 0 && checked < total);
        $('#selectAll').prop('checked', checked === total);
    });
    
    function updateDeleteButton() {
        const selected = $('.envanter-checkbox:checked').length;
        $('#topluSilBtn').prop('disabled', selected === 0);
        if (selected > 0) {
            $('#topluSilBtn').html(`<i class="fas fa-trash me-2"></i>Seçili ${selected} Envanteri Sil`);
        } else {
            $('#topluSilBtn').html('<i class="fas fa-trash me-2"></i>Seçili Envanterleri Sil');
        }
    }
    
    $('#topluSilBtn').click(function() {
        const selected = $('.envanter-checkbox:checked').map((_, el) => $(el).val()).get();
        if (selected.length === 0) {
            Swal.fire('Uyarı!', 'Lütfen silmek istediğiniz envanterleri seçiniz.', 'warning');
            return;
        }
        
        Swal.fire({
            title: 'Emin misiniz?',
            html: `<strong>${selected.length}</strong> envanter silinecek.<br><span class="text-danger">Bu işlem geri alınamaz!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash me-2"></i>Evet, sil!',
            cancelButtonText: '<i class="fas fa-times me-2"></i>İptal',
            reverseButtons: true,
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Siliniyor...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                $.ajax({
                    url: 'sil.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ urunler: selected }),
                    success: (response) => {
                        Swal.fire(response.success ? 'Başarılı!' : 'Hata!', response.message, response.success ? 'success' : 'error');
                        if (response.success) {
                            loadInventory(currentPage, currentLimit);
                            $('#selectAll').prop('checked', false).prop('indeterminate', false);
                        }
                    },
                    error: () => Swal.fire('Bağlantı Hatası!', 'İşlem sırasında bir hata oluştu.', 'error')
                });
            }
        });
    });
    
    $('#refreshBtn').click(() => loadInventory(currentPage, currentLimit, currentFilters));
    
    $('#exportBtn').click(function() {
        Swal.fire({ title: 'Excel Dosyası Hazırlanıyor', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.ajax({
            url: 'proxy.php',
            type: 'GET',
            data: { page: 1, limit: 10000, filters: JSON.stringify(currentFilters), sortBy: currentSortBy, sortOrder: currentSortOrder },
            dataType: 'json',
            success: (response) => {
                if (response.success && response.data.products) {
                    const data = response.data.products.map(item => ({
                        'Stok Kodu': item.stok_kodu, 'Envanter Adı': item.envanteradi, 'Departman': item.departman,
                        'Kullanıcı': item.kullanici, 'Resim': item.resim ? `https://katalog.gunesegel.net/yonetim/envanterler/${item.resim}` : 'Yok'
                    }));
                    const ws = XLSX.utils.json_to_sheet(data);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, 'Envanter Listesi');
                    XLSX.writeFile(wb, `envanter-listesi-${new Date().toISOString().slice(0,10)}.xlsx`);
                    Swal.close();
                } else {
                    Swal.fire('Hata!', 'Excel dosyası oluşturulurken bir hata oluştu.', 'error');
                }
            },
            error: () => Swal.fire('Bağlantı Hatası!', 'Veriler alınırken bir hata oluştu.', 'error')
        });
    });
    
    $('#applyFilters').click(function() {
        const filters = {
            stok_kodu: $('#filterStokKodu').val().trim(),
            envanteradi: $('#filterEnvanterAdi').val().trim(),
            departman: departmanChoices ? departmanChoices.getValue(true) || '' : '',
            kullanici: kullaniciChoices ? kullaniciChoices.getValue(true) || '' : ''
        };
        loadInventory(1, currentLimit, filters);
    });
    
    $('#clearFilters').click(function() {
        $('#filterStokKodu').val('');
        $('#filterEnvanterAdi').val('');
        if (departmanChoices) departmanChoices.setValue([]);
        if (kullaniciChoices) kullaniciChoices.setValue([]);
        
        loadInventory(1, currentLimit, { stok_kodu: '', envanteradi: '', departman: '', kullanici: '' });
    });
    
    $('.filter-input').keypress((e) => { if (e.which === 13) $('#applyFilters').click(); });
    
    document.querySelector('.image-modal-close').onclick = () => closeImageModal();
    
    const urlParams = new URLSearchParams(window.location.search);
    const initialPage = parseInt(urlParams.get('page')) || 1;
    let initialLimit = parseInt(urlParams.get('limit')) || 20;
    const validLimits = Array.from(document.querySelectorAll('#pageLimit option')).map(opt => opt.value);
    if (!validLimits.includes(String(initialLimit))) initialLimit = 20;
    $('#pageLimit').val(initialLimit);
    
    loadInventory(initialPage, initialLimit);
    updateDeleteButton();
    window.loadInventory = loadInventory;
});

$(document).ready(function() {
    $('html').css('scroll-behavior', 'smooth');
});
    </script>
  </body>
  <!-- [Body] end -->
</html>
