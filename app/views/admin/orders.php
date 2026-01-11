<?php
/**
 * ADMIN - QUẢN LÝ ĐƠN HÀNG
 * Thiết kế giao diện hiện đại - Theme Xanh lá/Xanh đậm
 */
?>
<?php include __DIR__ . '/layouts/header.php'; ?>
<link rel="stylesheet" href="<?= asset('css/admin-modern.css') ?>">

<div class="admin-modern">
    <div class="admin-modern-container">
        <!-- Breadcrumb -->
        <div class="admin-breadcrumb">
            <a href="<?= BASE_URL ?>/">Trang chủ</a>
            <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
            <span class="current">Quản lý vận chuyển</span>
        </div>
        
        <?php include __DIR__ . '/components/management_tabs.php'; ?>
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Quản lý vận chuyển</h1>
                <p class="admin-page-subtitle">Theo dõi và xử lý các đơn hàng từ khách hàng</p>
            </div>
            <div class="admin-header-actions">
                <a href="<?= BASE_URL ?>/admin/export-orders" class="btn-admin-secondary">
                    <i class="fas fa-download"></i>
                    <span>Xuất Excel</span>
                </a>
            </div>
        </div>
        
        <!-- Orders Content -->
        <div class="admin-content-card">
            <div style="padding: 60px; text-align: center; color: #94a3b8;">
                <i class="fas fa-shopping-cart" style="font-size: 48px; margin-bottom: 16px;"></i>
                <h3>Trang quản lý đơn hàng</h3>
                <p>Đang phát triển...</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>