<?php
/**
 * =============================================================================
 * ADMIN - CHI TIẾT ĐƠN HÀNG
 * =============================================================================
 * 
 * View: admin/order_detail.php
 * Hiển thị chi tiết đơn hàng, sản phẩm, và timeline trạng thái
 */

// Helper status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'dang_xu_ly': return 'status-pending';
        case 'dang_giao': return 'status-shipping';
        case 'da_giao': return 'status-delivered';
        case 'huy': return 'status-cancelled';
        default: return '';
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

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #DH<?= date('Ymd', strtotime($order['Ngay_dat'])) ?><?= str_pad($order['ID_dh'], 2, '0', STR_PAD_LEFT) ?> - Admin</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= asset('css/refactored-ui.css') ?>?v=<?= time() ?>">
</head>
<body>

<style>
/* Custom Styles for Detail Page */
.detail-page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 20px;
}

/* Sticky breadcrumb navigation */
.breadcrumb-nav {
    background: var(--primary-500);
    padding: 12px 0;
    position: sticky;
    top: 0;
    z-index: 999;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.breadcrumb-nav .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.breadcrumb {
    margin: 0;
    padding: 0;
    background: transparent;
    display: flex;
    align-items: center;
    gap: 8px;
    color: white;
}

.breadcrumb-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,0.8);
    font-size: 14px;
}

.breadcrumb-item a {
    color: white;
    text-decoration: none;
    transition: opacity 0.2s;
}

.breadcrumb-item a:hover {
    opacity: 0.8;
}

.breadcrumb-item.active {
    color: white;
    font-weight: 500;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: rgba(255,255,255,0.6);
    font-size: 18px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    color: #6c757d;
    text-decoration: none;
    font-weight: 500;
    margin-bottom: 20px;
    transition: all 0.2s;
}

.back-link:hover {
    color: var(--primary-color);
    transform: translateX(-3px);
}

.order-header-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    padding: 25px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-title h1 {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
    color: #2c3e50;
}

.order-meta {
    color: #6c757d;
    margin-top: 5px;
    font-size: 14px;
}

.status-badge-lg {
    padding: 8px 16px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.status-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
.status-shipping { background: #cce5ff; color: #004085; border: 1px solid #b8daff; }
.status-delivered { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.status-cancelled { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 25px;
}

.card-custom {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    padding: 25px;
    margin-bottom: 25px;
    border: 1px solid #eee;
}

.card-title-custom {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f1f2f6;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Product List */
.product-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px dashed #eee;
}

.product-item:last-child {
    border-bottom: none;
}

.product-img {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #eee;
}

.product-info {
    flex: 1;
}

.product-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 4px;
    font-size: 14px;
}

.product-meta {
    font-size: 13px;
    color: #6c757d;
}

.product-price {
    font-weight: 600;
    color: #2c3e50;
}

/* Summary Row */
.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    font-size: 14px;
    color: #6c757d;
}

.summary-row.total {
    border-top: 2px dashed #eee;
    padding-top: 15px;
    margin-top: 10px;
    color: #2c3e50;
    font-weight: 700;
    font-size: 18px;
}

.total-value {
    color: var(--primary-color);
}

/* Customer Info */
.info-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.info-icon {
    width: 20px;
    color: #adb5bd;
    margin-top: 3px;
}

.info-content div:first-child {
    font-size: 12px;
    text-transform: uppercase;
    color: #adb5bd;
    font-weight: 600;
    margin-bottom: 3px;
}

.info-content div:last-child {
    font-size: 15px;
    color: #2c3e50;
    font-weight: 500;
}

/* Timeline */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 7px;
    top: 5px;
    bottom: 0;
    width: 2px;
    background: #f1f2f6;
}

.timeline-item {
    position: relative;
    padding-bottom: 25px;
}

.timeline-dot {
    position: absolute;
    left: -30px;
    top: 5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: white;
    border: 3px solid #ddd;
    z-index: 1;
}

.timeline-item.active .timeline-dot {
    border-color: var(--primary-color);
    background: var(--primary-color);
}

.timeline-content h4 {
    margin: 0 0 5px 0;
    font-size: 15px;
    font-weight: 600;
}

.timeline-content p {
    margin: 0;
    font-size: 13px;
    color: #6c757d;
}

.timeline-date {
    font-size: 12px;
    color: #adb5bd;
    margin-top: 3px;
}

</style>

<!-- Sticky Breadcrumb Navigation -->
<div class="breadcrumb-nav">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= BASE_URL ?>/admin/dashboard">
                        <i class="fas fa-home"></i> Trang chủ
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= BASE_URL ?>/admin/orders">Quản lý giao hàng</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    Chi tiết đơn hàng
                </li>
            </ol>
        </nav>
    </div>
</div>

<div class="detail-page-container">
    <a href="<?= BASE_URL ?>/admin/orders" class="back-link">
        <i class="fas fa-arrow-left me-2"></i> Quay lại danh sách
    </a>

    <!-- Header -->
    <div class="order-header-card">
        <div class="order-title">
            <h1>Đơn hàng #DH<?= date('Ymd', strtotime($order['Ngay_dat'])) ?><?= str_pad($order['ID_dh'], 2, '0', STR_PAD_LEFT) ?></h1>
            <div class="order-meta">
                Đặt lúc: <?= date('H:i - d/m/Y', strtotime($order['Ngay_dat'])) ?>
            </div>
        </div>
        
        <div class="order-actions">
            <!-- Status Badge -->
            <span class="status-badge-lg <?= getStatusBadgeClass($order['Trang_thai']) ?>">
                <?php if($order['Trang_thai'] == 'dang_xu_ly'): ?><i class="fas fa-clock"></i>
                <?php elseif($order['Trang_thai'] == 'dang_giao'): ?><i class="fas fa-shipping-fast"></i>
                <?php elseif($order['Trang_thai'] == 'da_giao'): ?><i class="fas fa-check-circle"></i>
                <?php else: ?><i class="fas fa-times-circle"></i><?php endif; ?>
                
                <?= getStatusLabel($order['Trang_thai']) ?>
            </span>
        </div>
    </div>

    <div class="content-grid">
        <!-- Left Column: Products -->
        <div class="left-col">
            <div class="card-custom">
                <div class="card-title-custom">
                    <i class="fas fa-shopping-basket text-success"></i> Danh sách sản phẩm
                </div>
                
                <?php if(!empty($order_details)): ?>
                    <?php foreach($order_details as $item): ?>
                    <div class="product-item">
                        <img src="<?= asset('img/products/' . ($item['Hinh_anh'] ?? 'no-image.png')) ?>" 
                             alt="<?= htmlspecialchars($item['Ten_sp']) ?>" 
                             class="product-img"
                             onerror="this.src='<?= asset('img/products/no-image.png') ?>'">
                             
                        <div class="product-info">
                            <div class="product-name"><?= htmlspecialchars($item['Ten_sp']) ?></div>
                            <div class="product-meta">x<?= $item['So_luong'] ?></div>
                        </div>
                        
                        <div class="product-price">
                            <?= number_format($item['Thanh_tien'], 0, ',', '.') ?>đ
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-3">Không có thông tin sản phẩm</p>
                <?php endif; ?>
                
                <!-- Financial Summary -->
                <div class="mt-4 pt-3 border-top">
                    <div class="summary-row">
                        <span>Tạm tính</span>
                        <span><?= number_format(($order['Tong_tien'] ?? 0), 0, ',', '.') ?>đ</span>
                    </div>
                    <div class="summary-row">
                        <span>Phí vận chuyển</span>
                        <span><?= number_format(($order['Phi_van_chuyen'] ?? 20000), 0, ',', '.') ?>đ</span>
                    </div>
                    <div class="summary-row total">
                        <span>Tổng cộng</span>
                        <span class="total-value"><?= number_format(($order['Thanh_tien'] ?? 0), 0, ',', '.') ?>đ</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Customer & Timeline -->
        <div class="right-col">
            <!-- Update Status Card -->
            <div class="card-custom">
                <div class="card-title-custom">
                    <i class="fas fa-cog text-secondary"></i> Cập nhật trạng thái
                </div>
                <div class="form-group mb-3">
                    <select id="orderStatus" class="form-select">
                        <option value="dang_xu_ly" <?= $order['Trang_thai']=='dang_xu_ly'?'selected':'' ?>>Đang xử lý</option>
                        <option value="dang_giao" <?= $order['Trang_thai']=='dang_giao'?'selected':'' ?>>Đang giao hàng</option>
                        <option value="da_giao" <?= $order['Trang_thai']=='da_giao'?'selected':'' ?>>Đã giao thành công</option>
                        <option value="huy" <?= $order['Trang_thai']=='huy'?'selected':'' ?>>Hủy đơn hàng</option>
                    </select>
                </div>
                <button class="btn btn-primary w-100" onclick="updateStatus()">
                    <i class="fas fa-save me-2"></i> Cập nhật
                </button>
            </div>

            <!-- Customer Info -->
            <div class="card-custom">
                <div class="card-title-custom">
                    <i class="fas fa-user text-primary"></i> Thông tin khách hàng
                </div>
                
                <div class="info-row">
                    <div class="info-icon"><i class="fas fa-user-circle"></i></div>
                    <div class="info-content">
                        <div>Người nhận</div>
                        <div><?= htmlspecialchars($order['Ten_nguoi_nhan']) ?></div>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                    <div class="info-content">
                        <div>Số điện thoại</div>
                        <div><?= htmlspecialchars($order['Sdt_nguoi_nhan']) ?></div>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="info-content">
                        <div>Địa chỉ giao hàng</div>
                        <div><?= htmlspecialchars($order['Dia_chi_giao_hang']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card-custom">
                <div class="card-title-custom">
                    <i class="fas fa-history text-warning"></i> Lịch sử đơn hàng
                </div>
                
                <div class="timeline">
                    <!-- Example timeline logic, adjust based on actual data if available -->
                    <div class="timeline-item active">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <h4>Đặt hàng thành công</h4>
                            <p class="timeline-date"><?= date('H:i - d/m/Y', strtotime($order['Ngay_dat'])) ?></p>
                        </div>
                    </div>
                    
                    <?php if($order['Trang_thai'] != 'cho_xac_nhan' && $order['Trang_thai'] != 'dang_xu_ly'): ?>
                    <div class="timeline-item active">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <h4>Đã xác nhận</h4>
                            <p class="timeline-date">Admin đã xác nhận đơn</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($order['Trang_thai'] == 'da_giao'): ?>
                    <div class="timeline-item active">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <h4>Giao hàng thành công</h4>
                            <p class="timeline-date">Đơn hàng đã được giao</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const baseUrl = '<?= BASE_URL ?>';
    const orderId = <?= $order['ID_dh'] ?>;

    function updateStatus() {
        const newStatus = document.getElementById('orderStatus').value;
        const btn = document.querySelector('button[onclick="updateStatus()"]');
        const originalText = btn.innerHTML;
        
        if (newStatus === 'huy' && !confirm('Bạn có chắc chắn muốn hủy đơn hàng này? Hành động này sẽ hoàn lại tồn kho.')) {
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('status', newStatus);
        formData.append('csrf_token', '<?= Session::getCsrfToken() ?>');

        fetch(baseUrl + '/admin/order-update-status', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cập nhật trạng thái thành công!');
                location.reload();
            } else {
                alert(data.message || 'Có lỗi xảy ra');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi kết nối đến máy chủ');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

</body>
</html>
