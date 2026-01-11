<?php
/**
 * ADMIN - QUẢN LÝ GIAO HÀNG (DELIVERY MANAGEMENT)
 * Standardized UI - Theme Woodland
 * Reference: report_expiry.php style
 */

// Helper status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'dang_xu_ly': return 'status-badge warning';
        case 'dang_giao': return 'status-badge info';
        case 'da_giao': return 'status-badge success';
        case 'huy': return 'status-badge danger';
        default: return 'status-badge normal';
    }
}

// Helper status text
function getStatusLabel($status) {
    switch ($status) {
        case 'dang_xu_ly': return 'Đang xử lý';
        case 'dang_giao': return 'Đang giao';
        case 'da_giao': return 'Đã giao';
        case 'huy': return 'Trả hàng / Hủy đơn';
        default: return $status;
    }
}
?>
<?php include __DIR__ . '/../layouts/header.php'; ?>
<link rel="stylesheet" href="<?= asset('css/admin-modern.css') ?>">

<div class="admin-modern">
    <div class="admin-modern-container">
        <!-- Breadcrumb -->
        <div class="admin-breadcrumb">
            <a href="<?= BASE_URL ?>/admin/dashboard">Admin</a>
            <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
            <span class="current">Quản lý đơn hàng</span>
        </div>
        
        <!-- Management Tabs -->
        <?php include dirname(__DIR__) . '/components/management_tabs.php'; ?>
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Theo Dõi Vận Chuyển</h1>
                <p class="admin-page-subtitle">Quản lý trạng thái đơn hàng và điều phối giao nhận.</p>
            </div>
            <div class="admin-header-actions">

                <a href="<?= BASE_URL ?>/admin/exportOrders" class="btn-admin-primary">
                    <i class="fas fa-download"></i>
                    <span>Xuất báo cáo</span>
                </a>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="stat-cards-row">
            <!-- Chờ Xử Lý -->
            <div class="stat-card" style="border-left: 4px solid var(--admin-warning);">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Chờ Xử Lý</h4>
                        <p class="stat-card-value" style="color: var(--admin-warning);"><?= $status_stats['pending'] ?? 0 ?></p>
                    </div>
                    <div class="stat-card-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Đơn chờ xác nhận
                    </span>
                </div>
            </div>
            
            <!-- Đang Giao -->
            <div class="stat-card" style="border-left: 4px solid var(--admin-info);">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Đang Giao</h4>
                        <p class="stat-card-value" style="color: var(--admin-info);"><?= $status_stats['shipping'] ?? 0 ?></p>
                    </div>
                    <div class="stat-card-icon info">
                        <i class="fas fa-truck"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge info">
                        <i class="fas fa-truck-moving"></i>
                        Shipper đang giao
                    </span>
                </div>
            </div>
            
            <!-- Đã Giao -->
            <div class="stat-card" style="border-left: 4px solid var(--admin-success);">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Đã Giao</h4>
                        <p class="stat-card-value" style="color: var(--admin-success);"><?= $status_stats['delivered'] ?? 0 ?></p>
                    </div>
                    <div class="stat-card-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <?php 
                    $percentChange = $status_stats['percent_change'] ?? 0;
                    $isPositive = $percentChange >= 0;
                    ?>
                    <span style="font-size: 13px; color: var(--admin-text-muted); display: flex; align-items: center; gap: 4px;">
                        <i class="fas fa-arrow-<?= $isPositive ? 'up' : 'down' ?>" style="color: <?= $isPositive ? 'var(--admin-success)' : 'var(--admin-danger)' ?>;"></i>
                        <span style="color: <?= $isPositive ? 'var(--admin-success)' : 'var(--admin-danger)' ?>; font-weight: 500;"><?= $percentChange ?>%</span>
                        hôm nay
                    </span>
                </div>
            </div>
            
            <!-- Trả Hàng / Hủy -->
            <div class="stat-card" style="border-left: 4px solid var(--admin-danger);">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Trả / Hủy</h4>
                        <p class="stat-card-value" style="color: var(--admin-danger);"><?= $status_stats['cancelled'] ?? 0 ?></p>
                    </div>
                    <div class="stat-card-icon danger">
                        <i class="fas fa-undo-alt"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge danger">
                        <i class="fas fa-times-circle"></i>
                        Cần kiểm tra
                    </span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Danh sách đơn hàng</h3>
                
                <!-- Filter Form embedded in header for compactness -->
                <form action="" method="GET" style="display: flex; gap: 12px; align-items: center;">
                    <div style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--admin-text-light);"></i>
                        <input type="text" name="search" class="form-control" placeholder="Tìm đơn hàng..." 
                               value="<?= htmlspecialchars($filters['keyword']) ?>"
                               style="padding-left: 36px; padding-right: 12px; height: 36px; width: 250px;">
                    </div>
                    
                    <select name="status" class="form-select" onchange="this.form.submit()" style="height: 36px; min-width: 160px;">
                        <option value="">Tất cả trạng thái</option>
                        <option value="dang_xu_ly" <?= $filters['status'] == 'dang_xu_ly' ? 'selected' : '' ?>>Đang xử lý</option>
                        <option value="dang_giao" <?= $filters['status'] == 'dang_giao' ? 'selected' : '' ?>>Đang giao</option>
                        <option value="da_giao" <?= $filters['status'] == 'da_giao' ? 'selected' : '' ?>>Đã giao</option>
                        <option value="huy" <?= $filters['status'] == 'huy' ? 'selected' : '' ?>>Trả hàng / Hủy</option>
                    </select>
                </form>
            </div>
            
            <div class="admin-card-body no-padding">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAll"></th>
                            <th>Mã đơn hàng</th>
                            <th>Khách hàng</th>
                            <th>Chi tiết SP</th>
                            <th style="text-align: right;">Tổng tiền</th>
                            <th style="text-align: center;">Trạng thái</th>
                            <th style="text-align: center;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><input type="checkbox"></td>
                                <td>
                                    <a href="<?= BASE_URL ?>/admin/orderDetail/<?= $order['ID_dh'] ?>" style="font-weight: 600; color: var(--primary);">
                                        DH<?= date('Ymd', strtotime($order['Ngay_dat'])) ?><?= str_pad($order['ID_dh'], 2, '0', STR_PAD_LEFT) ?>
                                    </a>
                                    <div style="font-size: 12px; color: var(--admin-text-muted); margin-top: 4px;">
                                        <?= date('H:i - d/m/Y', strtotime($order['Ngay_dat'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 500; color: var(--text-dark); margin-bottom: 2px;">
                                        <?= htmlspecialchars($order['Ten_nguoi_nhan']) ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--admin-text-muted);">
                                        <i class="fas fa-phone-alt" style="font-size: 10px;"></i> <?= htmlspecialchars($order['Sdt_nguoi_nhan']) ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--admin-text-muted); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($order['Dia_chi_giao_hang']) ?>">
                                        <i class="fas fa-map-marker-alt" style="font-size: 10px;"></i> <?= htmlspecialchars($order['Dia_chi_giao_hang']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $itemCount = $order['Tong_so_thuc'] ?? 0;
                                    $imgName = $order['Hinh_anh_dai_dien'] ?? 'no-image.png';
                                    $firstItemImg = asset('img/products/' . $imgName);
                                    ?>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="<?= $firstItemImg ?>" 
                                             onerror="this.src='<?= asset('assets/img/products/no-image.png') ?>'"
                                             style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover; background: #f8fafc; border: 1px solid #e2e8f0;">
                                        <div>
                                            <div style="font-size: 13px; font-weight: 500;">Sản phẩm</div>
                                            <div style="font-size: 12px; color: var(--admin-text-muted);"><?= $itemCount ?> món</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: var(--text-dark);">
                                    <?= number_format($order['Thanh_tien'] ?? 0, 0, ',', '.') ?>đ
                                </td>
                                <td style="text-align: center;">
                                    <span class="<?= getStatusBadgeClass($order['Trang_thai']) ?>">
                                        <span class="dot"></span>
                                        <?= getStatusLabel($order['Trang_thai']) ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: flex; justify-content: center; gap: 8px;">
                                        <a href="<?= BASE_URL ?>/admin/orderDetail/<?= $order['ID_dh'] ?>" class="btn-icon" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="#" class="btn-icon" title="In hóa đơn">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 60px 20px;">
                                    <i class="fas fa-box-open" style="font-size: 48px; color: var(--admin-text-light); margin-bottom: 16px; display: block;"></i>
                                    <p style="color: var(--admin-text-muted);">Không tìm thấy đơn hàng nào</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
                <!-- Pagination -->
                <?php if (!empty($pagination) && isset($pagination['total']) && $pagination['total'] > 0): ?>
                <div class="admin-card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 13px; color: var(--admin-text-muted);">
                         Hiển thị <strong><?= $pagination['from'] ?? 1 ?></strong> đến <strong><?= $pagination['to'] ?? 0 ?></strong> 
                         trong tổng <strong><?= number_format($pagination['total']) ?></strong> đơn hàng
                    </div>
                    <div class="pagination">
                        <?php 
                        $queryParams = [];
                        if (!empty($filters['status'])) $queryParams['status'] = $filters['status'];
                        if (!empty($filters['keyword'])) $queryParams['search'] = $filters['keyword'];
                        if (!empty($filters['date_from'])) $queryParams['date_from'] = $filters['date_from'];
                        if (!empty($filters['date_to'])) $queryParams['date_to'] = $filters['date_to'];
                        ?>
                        
                        <!-- Previous -->
                        <a href="?page=<?= max(1, $pagination['current_page'] - 1) ?>&<?= http_build_query($queryParams) ?>" 
                           class="page-link <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        
                        <!-- Page Numbers -->
                        <?php 
                        $lastPage = $pagination['last_page'] ?? 1;
                        $currentPage = $pagination['current_page'] ?? 1;
                        
                        for ($i = 1; $i <= min($lastPage, 5); $i++): 
                        ?>
                            <a href="?page=<?= $i ?>&<?= http_build_query($queryParams) ?>" 
                               class="page-link <?= $i == $currentPage ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($lastPage > 5): ?>
                            <span class="page-link disabled">...</span>
                            <a href="?page=<?= $lastPage ?>&<?= http_build_query($queryParams) ?>" 
                               class="page-link <?= $lastPage == $currentPage ? 'active' : '' ?>">
                                <?= $lastPage ?>
                            </a>
                        <?php endif; ?>
                        
                        <!-- Next -->
                        <a href="?page=<?= min($lastPage, $pagination['current_page'] + 1) ?>&<?= http_build_query($queryParams) ?>" 
                           class="page-link <?= $pagination['current_page'] >= $lastPage ? 'disabled' : '' ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
