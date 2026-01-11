<?php
/**
 * =============================================================================
 * TRANG ĐƠN HÀNG CỦA TÔI - CUSTOMER ORDERS
 * =============================================================================
 * 
 * Giao diện theo thiết kế mới - Card layout với tabs
 */
include __DIR__ . '/../layouts/header.php';

$user = Session::get('user');
?>

<style>
/* ===== BREADCRUMB ===== */
.breadcrumb-section {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px 20px 0;
    font-size: 14px;
    color: #6b7280;
}

.breadcrumb-section a {
    color: #6b7280;
    text-decoration: none;
    transition: color 0.2s;
}

.breadcrumb-section a:hover {
    color: #496C2C;
}

.breadcrumb-section span {
    margin: 0 8px;
}

/* ===== MAIN CONTAINER ===== */
.orders-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 20px 48px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 24px;
}

/* ===== TABS ===== */
.order-tabs {
    display: flex;
    gap: 0;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 24px;
    background: white;
    border-radius: 12px 12px 0 0;
    padding: 0 20px;
    overflow-x: auto;
}

.order-tab {
    padding: 16px 24px;
    font-size: 14px;
    color: #666;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    white-space: nowrap;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
}

.order-tab:hover {
    color: #496C2C;
}

.order-tab.active {
    color: #496C2C;
    border-bottom-color: #496C2C;
    font-weight: 600;
}

.tab-count {
    color: #f59e0b;
    margin-left: 4px;
}

/* ===== ORDER CARD ===== */
.order-card {
    background: white;
    border-radius: 12px;
    margin-bottom: 16px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    transition: box-shadow 0.2s;
}

.order-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

/* Order Header */
.order-card-header {
    padding: 16px 20px;
    background: #fafafa;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}

.order-meta-group {
    display: flex;
    gap: 32px;
    flex-wrap: wrap;
}

.order-meta-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.order-meta-label {
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.order-meta-value {
    font-size: 13px;
    color: #1a1a1a;
    font-weight: 600;
}

/* Status Badge */
.order-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
}

.status-dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: currentColor;
}

.status-pending {
    background: #fef3c7;
    color: #d97706;
}

.status-shipping {
    background: #dbeafe;
    color: #2563eb;
}

.status-delivered {
    background: #d1fae5;
    color: #059669;
}

.status-cancelled {
    background: #fee2e2;
    color: #dc2626;
}

/* Order Body */
.order-card-body {
    padding: 20px;
}

.order-product-row {
    display: flex;
    gap: 16px;
    align-items: center;
}

.order-product-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    background: #f5f5f5;
    border: 1px solid #e5e7eb;
    flex-shrink: 0;
}

.order-product-info {
    flex: 1;
    min-width: 0;
}

.order-product-name {
    font-size: 14px;
    color: #1a1a1a;
    font-weight: 500;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.order-product-quantity {
    font-size: 13px;
    color: #666;
}

.order-product-price {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.order-price-label {
    font-size: 12px;
    color: #999;
}

.order-price-value {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
}

/* Order Footer */
.order-card-footer {
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}

.order-btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-detail {
    background: white;
    color: #666;
    border: 1px solid #e0e0e0;
}

.btn-detail:hover {
    background: #f5f5f5;
    color: #333;
}

.btn-reorder {
    background: #496C2C;
    color: white;
    border: 1px solid #496C2C;
}

.btn-reorder:hover {
    background: #059669;
}

.btn-track {
    background: #3b82f6;
    color: white;
}

.btn-track:hover {
    background: #2563eb;
    color: white;
}

.btn-cancel {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.btn-cancel:hover {
    background: #fecaca;
    color: #b91c1c;
}

/* Tracking Modal Custom Styling */
.tracking-item {
    display: flex;
    gap: 16px;
    padding-bottom: 24px;
    position: relative;
}

.tracking-item::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 20px;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.tracking-item:last-child::before {
    display: none;
}

.tracking-icon {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #d1d5db;
    border: 4px solid white;
    z-index: 1;
    margin-top: 4px;
}

.tracking-item.active .tracking-icon {
    background: #496C2C;
    box-shadow: 0 0 0 4px #e8f0e3;
}

.tracking-info h5 {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 4px;
    color: #1a1a1a;
}

.tracking-info p {
    font-size: 12px;
    color: #6b7280;
    margin: 0;
}

.tracking-date {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 4px;
}

/* ===== PAGINATION ===== */
.orders-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 32px;
}

.page-btn {
    min-width: 36px;
    height: 36px;
    padding: 0 12px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    color: #666;
    border: 1px solid #e0e0e0;
    background: white;
    transition: all 0.2s;
    text-decoration: none;
}

.page-btn:hover {
    background: #f5f5f5;
    color: #333;
}

.page-btn.active {
    background: #496C2C;
    color: white;
    border-color: #496C2C;
    font-weight: 600;
}

.page-btn.disabled {
    opacity: 0.4;
    cursor: not-allowed;
    pointer-events: none;
}

.page-ellipsis {
    color: #999;
    padding: 0 4px;
}

/* ===== EMPTY STATE ===== */
.empty-orders {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 12px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.empty-title {
    font-size: 18px;
    font-weight: 600;
    color: #666;
    margin-bottom: 8px;
}

.empty-text {
    font-size: 14px;
    color: #999;
    margin-bottom: 24px;
}

.btn-shop {
    background: #496C2C;
    color: white;
    padding: 12px 32px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-shop:hover {
    background: #059669;
    color: white;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .order-meta-group {
        gap: 16px;
    }
    
    .order-card-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .order-card-footer {
        flex-direction: column;
    }
    
    .order-btn {
        width: 100%;
        justify-content: center;
    }
    
    .order-tabs {
        padding: 0 12px;
    }
    
    .order-tab {
        padding: 16px 16px;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 22px;
    }
    
    .order-product-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .order-product-price {
        align-items: flex-start;
        width: 100%;
    }
}
</style>

<!-- Breadcrumb -->
<div class="breadcrumb-section">
    <a href="<?= BASE_URL ?>/">Trang chủ</a>
    <span>›</span>
    <span>Tài khoản</span>
    <span>›</span>
    <span>Đơn hàng của tôi</span>
</div>

<!-- Main Content -->
<div class="orders-container">
    <h1 class="page-title">Đơn hàng của tôi</h1>

    <!-- Tabs -->
    <div class="order-tabs">
        <a href="<?= BASE_URL ?>/orders" class="order-tab <?= empty($filters['status']) ? 'active' : '' ?>">
            Tất cả
        </a>
        <a href="<?= BASE_URL ?>/orders?status=dang_xu_ly" class="order-tab <?= ($filters['status'] ?? '') == 'dang_xu_ly' ? 'active' : '' ?>">
            Đang xử lý
            <?php if(($status_counts['dang_xu_ly']??0) > 0): ?>
                <span class="tab-count"><?= $status_counts['dang_xu_ly'] ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/orders?status=dang_giao" class="order-tab <?= ($filters['status'] ?? '') == 'dang_giao' ? 'active' : '' ?>">
            Đang giao
            <?php if(($status_counts['dang_giao']??0) > 0): ?>
                <span class="tab-count"><?= $status_counts['dang_giao'] ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/orders?status=da_giao" class="order-tab <?= ($filters['status'] ?? '') == 'da_giao' ? 'active' : '' ?>">
            Đã giao
            <?php if(($status_counts['da_giao']??0) > 0): ?>
                <span class="tab-count"><?= $status_counts['da_giao'] ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/orders?status=huy" class="order-tab <?= ($filters['status'] ?? '') == 'huy' ? 'active' : '' ?>">
            Trả hàng/Hủy đơn
            <?php if(($status_counts['huy']??0) > 0): ?>
                <span class="tab-count"><?= $status_counts['huy'] ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Order List -->
    <?php if (!empty($orders)): ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <!-- Order Header -->
                <div class="order-card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="order-meta-info">
                                <div class="order-meta-label">MÃ ĐƠN HÀNG</div>
                                <div class="order-meta-value">DH<?= date('Ymd', strtotime($order['Ngay_dat'])) ?><?= str_pad($order['ID_dh'], 2, '0', STR_PAD_LEFT) ?></div>
                            </div>
                        <div class="order-meta-item">
                            <div class="order-meta-label">Ngày đặt</div>
                            <div class="order-meta-value"><?= date('d/m/Y H:i', strtotime($order['Ngay_dat'])) ?></div>
                        </div>
                    </div>
                    <div>
                        <?php
                        $statusClass = 'status-pending';
                        $statusText = 'Đang xử lý';
                        
                        switch ($order['Trang_thai']) {
                            case 'dang_xu_ly':
                                $statusClass = 'status-pending';
                                $statusText = 'Đang xử lý';
                                break;
                            case 'dang_giao':
                                $statusClass = 'status-shipping';
                                $statusText = 'Đang giao';
                                break;
                            case 'da_giao':
                                $statusClass = 'status-delivered';
                                $statusText = 'Đã giao';
                                break;
                            case 'huy':
                                $statusClass = 'status-cancelled';
                                $statusText = 'Trả hàng/Hủy đơn';
                                break;
                        }
                        ?>
                        <span class="order-status-badge <?= $statusClass ?>">
                            <span class="status-dot"></span>
                            <?= $statusText ?>
                        </span>
                    </div>
                </div>

                <!-- Order Body -->
                <div class="order-card-body">
                    <?php if(!empty($order['details'])): ?>
                        <?php $firstItem = $order['details'][0]; ?>
                        <div class="order-product-row">
                            <img src="<?= asset('img/products/' . ($firstItem['Hinh_anh'] ?: 'placeholder.png')) ?>" 
                                 onerror="this.src='<?= asset('img/placeholder-product.png') ?>'"
                                 alt="<?= htmlspecialchars($firstItem['Ten_sp']) ?>"
                                 class="order-product-image">
                            <div class="order-product-info">
                                <div class="order-product-name">
                                    <?php 
                                    $productNames = array_map(function($item) { 
                                        return $item['Ten_sp']; 
                                    }, $order['details']);
                                    echo htmlspecialchars(implode(', ', array_slice($productNames, 0, 3)));
                                    if(count($productNames) > 3) echo '...';
                                    ?>
                                </div>
                                <div class="order-product-quantity"><?= count($order['details']) ?> sản phẩm</div>
                            </div>
                            <div class="order-product-price">
                                <div class="order-price-label">Tổng tiền</div>
                                <div class="order-price-value"><?= number_format($order['Thanh_tien'], 0, ',', '.') ?>đ</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Order Footer -->
                <div class="order-card-footer">
                    <!-- Hủy đơn: Chỉ cho phép khi đang xử lý -->
                    <?php if($order['Trang_thai'] == 'dang_xu_ly'): ?>
                        <button class="order-btn btn-cancel" onclick="confirmCancelOrder(<?= $order['ID_dh'] ?>)">
                            <i class="fas fa-times"></i> Hủy đơn
                        </button>
                    <?php endif; ?>

                    <!-- Theo dõi đơn / Thông tin đơn -->
                    <button class="order-btn btn-track" onclick="trackOrder(<?= $order['ID_dh'] ?>)">
                        <i class="fas fa-search-location"></i> Theo dõi đơn
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="orders-pagination">
            <a href="<?= $pagination['current_page'] > 1 ? '?page=' . ($pagination['current_page'] - 1) . (!empty($filters['status']) ? '&status='.$filters['status'] : '') : '#' ?>" 
               class="page-btn <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">‹</a>
            
            <a href="?page=1<?= !empty($filters['status']) ? '&status='.$filters['status'] : '' ?>" 
               class="page-btn <?= $pagination['current_page'] == 1 ? 'active' : '' ?>">1</a>
            
            <?php if($pagination['current_page'] > 3): ?>
                <span class="page-ellipsis">...</span>
            <?php endif; ?>
            
            <?php 
            $start = max(2, $pagination['current_page'] - 1);
            $end = min($pagination['total_pages'] - 1, $pagination['current_page'] + 1);
            for ($i = $start; $i <= $end; $i++): 
            ?>
                <a href="?page=<?= $i ?><?= !empty($filters['status']) ? '&status='.$filters['status'] : '' ?>" 
                   class="page-btn <?= $i == $pagination['current_page'] ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if($pagination['current_page'] < $pagination['total_pages'] - 2): ?>
                <span class="page-ellipsis">...</span>
            <?php endif; ?>
            
            <?php if($pagination['total_pages'] > 1): ?>
                <a href="?page=<?= $pagination['total_pages'] ?><?= !empty($filters['status']) ? '&status='.$filters['status'] : '' ?>" 
                   class="page-btn <?= $pagination['current_page'] == $pagination['total_pages'] ? 'active' : '' ?>"><?= $pagination['total_pages'] ?></a>
            <?php endif; ?>
            
            <a href="<?= $pagination['current_page'] < $pagination['total_pages'] ? '?page=' . ($pagination['current_page'] + 1) . (!empty($filters['status']) ? '&status='.$filters['status'] : '') : '#' ?>" 
               class="page-btn <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>">›</a>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Empty State -->
        <div class="empty-orders">
            <div class="empty-icon">📦</div>
            <h3 class="empty-title">Chưa có đơn hàng nào</h3>
            <p class="empty-text">Bạn chưa có đơn hàng nào. Hãy khám phá và mua sắm ngay!</p>
            <a href="<?= BASE_URL ?>/products" class="btn-shop">
                Tiếp tục mua sắm
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Theo dõi đơn -->
<div class="modal fade" id="trackingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thông tin theo dõi đơn hàng #<span id="trackOrderId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="trackingContent" class="py-2">
                    <!-- Dynamic Content -->
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="viewFullDetail" class="btn btn-outline-success">Xem chi tiết đầy đủ</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Xác nhận hủy -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Xác nhận hủy đơn hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                <p>Bạn có chắc chắn muốn hủy đơn hàng <strong>#<span id="cancelOrderIdText"></span></strong>?</p>
                <p class="text-muted small">Quy trình này không thể hoàn tác sau khi thực hiện.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Không, giữ lại</button>
                <button type="button" id="confirmCancelBtn" class="btn btn-danger">Đồng ý hủy đơn</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentCancelOrderId = null;

function confirmCancelOrder(orderId) {
    currentCancelOrderId = orderId;
    document.getElementById('cancelOrderIdText').textContent = orderId;
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}

document.getElementById('confirmCancelBtn').addEventListener('click', function() {
    if (!currentCancelOrderId) return;
    
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    
    fetch('<?= BASE_URL ?>/orders/cancel/' + currentCancelOrderId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf_token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Hủy đơn hàng thất bại');
            btn.disabled = false;
            btn.textContent = 'Đồng ý hủy đơn';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra, vui lòng thử lại');
        btn.disabled = false;
        btn.textContent = 'Đồng ý hủy đơn';
    });
});

function trackOrder(orderId) {
    document.getElementById('trackOrderId').textContent = orderId;
    document.getElementById('trackingContent').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-2 text-muted">Đang tải thông tin...</p></div>';
    document.getElementById('viewFullDetail').href = '<?= BASE_URL ?>/orders/detail/' + orderId;
    
    const modal = new bootstrap.Modal(document.getElementById('trackingModal'));
    modal.show();
    
    fetch('<?= BASE_URL ?>/orders/track/' + orderId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let html = '<div class="tracking-list">';
            data.timeline.forEach((item, index) => {
                const isActive = item.completed ? 'active' : '';
                html += `
                    <div class="tracking-item ${isActive}">
                        <div class="tracking-icon"></div>
                        <div class="tracking-info">
                            <h5>${item.label}</h5>
                            <div class="tracking-date">${item.date || 'Chưa cập nhật'}</div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            document.getElementById('trackingContent').innerHTML = html;
        } else {
            document.getElementById('trackingContent').innerHTML = '<div class="alert alert-warning">Không lấy được thông tin theo dõi.</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('trackingContent').innerHTML = '<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu.</div>';
    });
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
