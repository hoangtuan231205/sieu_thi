<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base_url" content="<?= BASE_URL ?>">
    <meta name="csrf_token" content="<?= Session::getCsrfToken() ?>">
    <title><?= $page_title ?? 'FreshMart - Siêu thị thực phẩm tươi sống' ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS - LOAD ORDER MATTERS! -->
    <link rel="stylesheet" href="<?= asset('css/refactored-ui.css') ?>?v=1.0.7">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=1.0.7">
</head>
<body>

<!-- ============================================================================
     TOP BAR - Thông tin giao hàng và hotline
     ============================================================================ -->
<div class="top-bar">
    <div class="container">
        <div class="row align-items-center">
            <!-- Bên trái: Thông tin giao hàng -->
            <div class="col-md-6 col-12 text-center text-md-start">
                <i class="fas fa-map-marker-alt me-2"></i>
                <span>Giao hàng nhanh 2h - Miễn phí từ 150.000đ</span>
            </div>
            
            <!-- Bên phải: Hotline -->
            <div class="col-md-6 col-12 text-center text-md-end">
                <i class="fas fa-phone-alt me-2"></i>
                <span>Hotline: <strong>1900-xxxx</strong></span>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================================
     MAIN HEADER - Logo, Search, Icons
     ============================================================================ -->
<header class="main-header">
    <div class="container">
        <div class="row align-items-center py-3">
            
            <!-- Logo -->
            <div class="col-lg-2 col-md-3 col-6">
                <a href="<?= BASE_URL ?>" class="logo-link">
                    <div class="logo">
                        <div class="logo-icon">FM</div>
                        <span class="logo-text">FreshMart</span>
                    </div>
                </a>
            </div>
            
            <!-- Search Bar -->
            <div class="col-lg-6 col-md-5 col-12 order-3 order-md-2 mt-3 mt-md-0">
                <form action="<?= BASE_URL ?>/products" method="GET" class="search-form">
                    <div class="search-wrapper">
                        <input 
                            type="text" 
                            name="keyword" 
                            class="form-control search-input" 
                            placeholder="Tìm kiếm sản phẩm..."
                            value="<?= $_GET['keyword'] ?? '' ?>"
                        >
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- User Actions -->
            <div class="col-lg-4 col-md-4 col-6 order-2 order-md-3">
                <div class="header-actions">
                    
                    <!-- User Account Area -->
                    <?php if (Session::isLoggedIn()): ?>
                        <!-- POS Icon (Admin only) - Outside dropdown for horizontal layout -->
                        <?php if (Session::isAdmin()): ?>
                            <a href="<?= BASE_URL ?>/admin" class="action-btn admin-btn" title="Quản trị">
                                <i class="fas fa-tachometer-alt"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/pos" class="action-btn pos-btn" title="Bán hàng tại quầy">
                                <i class="fas fa-cash-register"></i>
                            </a>
                        <?php endif; ?>
                        
                        <!-- Logged In - User Menu -->
                        <div class="user-menu dropdown user-dropdown">
                            <!-- Click to show dropdown menu -->
                            <a href="<?= BASE_URL ?>/user/profile" class="action-btn" title="Tài khoản">
                                <i class="fas fa-user-circle"></i>
                                <span class="user-name"><?= htmlspecialchars(Session::getUserName() ?? 'User') ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/profile">
                                    <i class="fas fa-user me-2"></i>Thông tin tài khoản
                                </a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/orders">
                                    <i class="fas fa-receipt me-2"></i>Đơn hàng của tôi
                                </a></li>
                                
                                <?php if (Session::isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-primary" href="<?= BASE_URL ?>/admin/products">
                                    <i class="fas fa-box me-2"></i>Quản lý sản phẩm
                                </a></li>
                                <li><a class="dropdown-item text-primary" href="<?= BASE_URL ?>/admin/orders">
                                    <i class="fas fa-truck me-2"></i>Quản lý giao hàng
                                </a></li>
                                <?php endif; ?>

                                <?php if (Session::isWarehouse()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-success" href="<?= BASE_URL ?>/warehouse">
                                    <i class="fas fa-warehouse me-2"></i>Quản lý kho
                                </a></li>
                                <?php endif; ?>

                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout">
                                    <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                </a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <!-- Not Logged In - Login/Register Buttons -->
                        <div class="auth-buttons">
                            <a href="<?= BASE_URL ?>/auth/login" class="action-btn" title="Đăng nhập">
                                <i class="fas fa-user"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/auth/register" class="btn btn-sm btn-woodland" style="margin-left: 8px;">
                                <i class="fas fa-user-plus me-1"></i>Đăng ký
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    
                    <!-- Icon Giỏ hàng -->
                    <?php
                    // Đảm bảo số lượng luôn chính xác "mọi lúc mọi nơi"
                    if (Session::isLoggedIn()) {
                        $currentCount = Session::getCartCount();
                        // Nếu session = 0, thử đồng bộ lại 1 lần từ DB để chắc chắn
                        if ($currentCount <= 0) {
                            $currentCount = Session::syncCartCount();
                        }
                    } else {
                        $currentCount = 0;
                    }
                    ?>
                    <a href="<?= BASE_URL ?>/cart" class="action-btn cart-btn" title="Giỏ hàng">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-badge"><?= $currentCount ?></span>
                    </a>
                    
                </div>
            </div>
            
        </div>
    </div>
</header>

<!-- NAVIGATION MENU - Cập nhật theo database -->
<?php
// Tự động lấy danh mục nếu controller chưa truyền
if (!isset($categories)) {
    require_once __DIR__ . '/../../models/Category.php';
    $catModel = new Category();
    $categories = $catModel->getCategoriesTree();
}

// Map icon theo ID danh mục (Vì DB chưa có cột icon)
$categoryIcons = [
    1 => 'fas fa-glass-whiskey', // Sữa
    2 => 'fas fa-carrot',        // Rau củ
    3 => 'fas fa-pump-soap',     // Hóa phẩm
    4 => 'fas fa-tooth',         // Chăm sóc cá nhân
    5 => 'fas fa-drumstick-bite',// Thịt hải sản
    6 => 'fas fa-cookie-bite',   // Bánh kẹo
    7 => 'fas fa-wine-bottle',   // Đồ uống
    8 => 'fas fa-pepper-hot',    // Gia vị
    9 => 'fas fa-utensils',      // Gạo, mỳ
    10 => 'fas fa-glass-whiskey', 
    15 => 'fas fa-apple-alt',    // Trái cây
];

// Default icon fallback
$defaultIcon = 'fas fa-shopping-basket';
?>
<nav class="main-nav" id="mainNav">
    <div class="container">
        <div class="nav-wrapper">
            
            <!-- Main Menu - Horizontal Pills Style -->
            <ul class="nav-menu">
                <!-- Trang Chủ - Active -->
                <li class="nav-item active">
                    <a href="<?= BASE_URL ?>" class="nav-link active">Trang Chủ</a>
                </li>
                <?php foreach ($categories as $cat): ?>
                    <?php 
                        $hasChildren = !empty($cat['children']); 
                        $icon = $categoryIcons[$cat['ID_danh_muc']] ?? $defaultIcon;
                    ?>
                    <li class="nav-item <?= $hasChildren ? 'has-dropdown' : '' ?>">
                        <a href="<?= BASE_URL ?>/products?category=<?= $cat['ID_danh_muc'] ?>" class="nav-link">
                            <!-- Icon (Optional: user claims images wrong, fixing icons helps) -->
                            <!-- <i class="<?= $icon ?> me-1"></i> --> 
                            <?= htmlspecialchars($cat['Ten_danh_muc']) ?>
                            <?php if ($hasChildren): ?>
                                <i class="fas fa-chevron-down ms-1"></i>
                            <?php endif; ?>
                        </a>
                        
                        <?php if ($hasChildren): ?>
                        <div class="dropdown-menu">
                            <?php foreach ($cat['children'] as $child): ?>
                            <?php $childIcon = $categoryIcons[$child['ID_danh_muc']] ?? 'fas fa-circle'; ?>
                            <a href="<?= BASE_URL ?>/products?category=<?= $child['ID_danh_muc'] ?>" class="dropdown-item">
                                <i class="<?= $childIcon ?> me-2" style="font-size: 0.8em;"></i><?= htmlspecialchars($child['Ten_danh_muc']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content Start -->
<main class="main-content">