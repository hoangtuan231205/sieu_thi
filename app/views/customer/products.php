<?php
/**
 * =============================================================================
 * DANH SÁCH SẢN PHẨM - NEW DESIGN
 * File: app/views/customer/products.php
 * =============================================================================
 */
include __DIR__ . '/../layouts/header.php';

// Lấy category hiện tại để hiển thị tên
$currentCategoryName = 'Sản phẩm';
if (!empty($filters['category_id']) && !empty($categories)) {
    foreach ($categories as $cat) {
        if ($cat['ID_danh_muc'] == $filters['category_id']) {
            $currentCategoryName = $cat['Ten_danh_muc'];
            break;
        }
        if (!empty($cat['children'])) {
            foreach ($cat['children'] as $child) {
                if ($child['ID_danh_muc'] == $filters['category_id']) {
                    $currentCategoryName = $child['Ten_danh_muc'];
                    break 2;
                }
            }
        }
    }
}
?>

<style>
/* ===== BREADCRUMB ===== */
.breadcrumb-nav {
    max-width: 1400px;
    margin: 0 auto;
    padding: 16px 20px;
    font-size: 14px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 8px;
}

.breadcrumb-nav a {
    color: #666;
    text-decoration: none;
}

.breadcrumb-nav a:hover {
    color: #5a8c6a;
}

/* ===== MAIN CONTAINER ===== */
.products-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    align-items: start;
}

/* ===== SIDEBAR FILTER ===== */
.sidebar-filter {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    position: sticky;
    top: 20px;
}

.filter-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    font-size: 18px;
    font-weight: 700;
    color: #333;
}

.filter-section {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid #f0f0f0;
}

.filter-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.filter-label {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 12px;
}

.price-range {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-bottom: 12px;
}

.price-input {
    flex: 1;
    width: 100%;
    min-width: 0;
    padding: 0.7rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.95rem;
    outline: none;
    box-sizing: border-box;
}

.price-input:focus {
    border-color: #5a8c6a;
}

.apply-btn {
    width: 100%;
    padding: 0.7rem 2rem;
    background: #496C2C;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.apply-btn:hover {
    background: #3a5623;
}

.filter-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    cursor: pointer;
}

.filter-option input[type="radio"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #5a8c6a;
}

.filter-option label {
    flex: 1;
    font-size: 14px;
    color: #555;
    cursor: pointer;
}

.filter-option.active label {
    color: #5a8c6a;
    font-weight: 600;
}

/* ===== PRODUCTS SECTION ===== */
.products-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
}

.products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f0f0f0;
}

.products-count {
    font-size: 16px;
    color: #666;
}

.products-count strong {
    color: #333;
    font-weight: 600;
}

.sort-dropdown {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: #666;
}

.sort-select {
    padding: 0.7rem 2rem 0.7rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.95rem;
    outline: none;
    cursor: pointer;
    background: white;
}

/* ===== PRODUCTS GRID ===== */
.products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 32px;
}

.product-card {
    background: white;
    border: 1px solid #f0f0f0;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
    cursor: pointer;
    position: relative;
}

.product-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-4px);
}

.product-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #ef4444;
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 700;
    z-index: 1;
}

.product-badge.new {
    background: #7BC043;
}

.product-image {
    width: 100%;
    aspect-ratio: 4/3;
    object-fit: contain;
    background: #f9fafb;
    padding: 1rem;
    transition: opacity 0.3s ease-in;
}

.product-image.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
    opacity: 0.6;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.product-image.loaded {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.product-info {
    padding: 1.2rem;
}

.product-name {
    font-size: 0.95rem;
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 40px;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 12px;
}

.stars {
    color: #fbbf24;
    font-size: 14px;
}

.rating-count {
    font-size: 12px;
    color: #999;
}

.product-price {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 12px;
}

.current-price {
    font-size: 18px;
    font-weight: 700;
    color: #333;
}

.old-price {
    font-size: 13px;
    color: #999;
    text-decoration: line-through;
}

/* Button Colors */
.btn-add-cart {
    background: var(--color-woodland);
    color: white;
}

.btn-add-cart:hover {
    background: #3a5623;
}

.btn-buy-now {
    background: var(--color-port-gore);
    color: white;
}

.btn-buy-now:hover {
    background: #1a0b36;
}

/* ===== PAGINATION ===== */
.pagination-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 32px;
}

.page-btn {
    width: 40px;
    height: 40px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #333;
}

.page-btn:hover {
    background: #f5f5f5;
    border-color: #496C2C;
}

.page-btn.active {
    background: #496C2C;
    color: white;
    border-color: #496C2C;
}

.page-btn.disabled {
    opacity: 0.3;
    cursor: not-allowed;
    pointer-events: none;
}

/* ===== EMPTY STATE ===== */
.empty-products {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-products i {
    font-size: 60px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-products h4 {
    margin-bottom: 8px;
    color: #666;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .products-container {
        grid-template-columns: 1fr;
    }

    .sidebar-filter {
        position: static;
    }

    .products-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .product-info {
        padding: 12px;
    }

    .products-header {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .product-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Breadcrumb -->
<div class="breadcrumb-nav">
    <a href="<?= BASE_URL ?>/">🏠 Trang chủ</a>
    <span>›</span>
    <a href="<?= BASE_URL ?>/products">Sản phẩm</a>
    <?php if ($currentCategoryName !== 'Sản phẩm'): ?>
    <span>›</span>
    <span><?= htmlspecialchars($currentCategoryName) ?></span>
    <?php endif; ?>
</div>

<!-- Main Content -->
<div class="products-container">
    <!-- Sidebar Filter -->
    <aside class="sidebar-filter">
        <div class="filter-header">
            🎚️ Lọc theo giá
        </div>

        <form method="GET" action="<?= BASE_URL ?>/products" class="filter-section">
            <input type="hidden" name="category" value="<?= $filters['category_id'] ?? '' ?>">
            <input type="hidden" name="sort" value="<?= $filters['sort'] ?? '' ?>">
            
            <div class="filter-label">Khoảng giá tùy chỉnh</div>
            <div class="price-range">
                <input type="number" name="min_price" class="price-input" placeholder="Từ (đ)" 
                       value="<?= $filters['min_price'] ?? '' ?>">
                <span>-</span>
                <input type="number" name="max_price" class="price-input" placeholder="Đến (đ)" 
                       value="<?= $filters['max_price'] ?? '' ?>">
            </div>
            <button type="submit" class="apply-btn">▼ Áp dụng</button>
        </form>

        <div class="filter-section">
            <div class="filter-label">Chọn nhanh</div>
            <?php
            $priceRanges = [
                ['min' => 0, 'max' => 50000, 'label' => 'Dưới 50.000đ'],
                ['min' => 50000, 'max' => 100000, 'label' => '50.000đ - 100.000đ'],
                ['min' => 100000, 'max' => 200000, 'label' => '100.000đ - 200.000đ'],
                ['min' => 200000, 'max' => 500000, 'label' => '200.000đ - 500.000đ'],
                ['min' => 500000, 'max' => 0, 'label' => 'Trên 500.000đ'],
            ];
            
            $currentMin = $filters['min_price'] ?? 0;
            $currentMax = $filters['max_price'] ?? 0;
            
            foreach ($priceRanges as $i => $range):
                $isActive = false;
                if ($range['min'] == 0 && $currentMax == $range['max'] && $currentMin == 0) {
                    $isActive = true;
                } elseif ($range['max'] == 0 && $currentMin == $range['min'] && $currentMax == 0) {
                    $isActive = true;
                } elseif ($currentMin == $range['min'] && $currentMax == $range['max']) {
                    $isActive = true;
                }
                
                $url = BASE_URL . '/products?';
                if ($range['min'] > 0) $url .= 'min_price=' . $range['min'] . '&';
                if ($range['max'] > 0) $url .= 'max_price=' . $range['max'] . '&';
                if (!empty($filters['category_id'])) $url .= 'category=' . $filters['category_id'] . '&';
                if (!empty($filters['sort'])) $url .= 'sort=' . $filters['sort'] . '&';
                $url = rtrim($url, '&');
            ?>
            <a href="<?= $url ?>" class="filter-option <?= $isActive ? 'active' : '' ?>">
                <input type="radio" name="price_quick" id="price<?= $i ?>" <?= $isActive ? 'checked' : '' ?>>
                <label for="price<?= $i ?>"><?= $range['label'] ?></label>
            </a>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- Products Section -->
    <div class="products-section">
        <div class="products-header">
            <div class="products-count">
                Hiển thị <strong><?= count($products ?? []) ?></strong> trong tổng số <strong><?= $pagination['total'] ?? 0 ?></strong> sản phẩm
            </div>
        </div>

        <?php if (!empty($products)): ?>
        <!-- Products Grid -->
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card-standard">
                <!-- Product Image -->
                <div class="product-image-wrapper">
                    <?php 
                    $imagePath = getProductImagePath($product['Hinh_anh'] ?? '');
                    if (!empty($imagePath)): 
                    ?>
                        <a href="<?= BASE_URL ?>/products/detail/<?= $product['ID_sp'] ?>">
                            <img src="<?= asset('img/products/' . $imagePath) ?>" 
                                 alt="<?= htmlspecialchars($product['Ten']) ?>">
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/products/detail/<?= $product['ID_sp'] ?>">
                            <img src="<?= asset('img/placeholder-product.png') ?>" 
                                 alt="<?= htmlspecialchars($product['Ten']) ?>">
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Product Info -->
                <div class="product-info">
                    <a href="<?= BASE_URL ?>/products/detail/<?= $product['ID_sp'] ?>" style="text-decoration: none;">
                        <h3 class="product-name"><?= htmlspecialchars($product['Ten']) ?></h3>
                    </a>
                    
                    <!-- Rating -->
                    <div class="product-rating">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <span class="rating-value">(4.5)</span>
                    </div>
                    
                    <!-- Price -->
                    <div class="product-price">
                        <?= number_format($product['Gia_tien'], 0, ',', '.') ?>đ
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="product-actions">
                        <?php if ($product['So_luong_ton'] > 0): ?>
                            <button class="btn-add" onclick="addToCart(<?= $product['ID_sp'] ?>)">
                                <i class="fas fa-cart-plus"></i> Thêm
                            </button>
                            <button class="btn-buy" onclick="buyNow(<?= $product['ID_sp'] ?>)">
                                Mua ngay
                            </button>
                        <?php else: ?>
                            <button class="btn-add" style="grid-column: span 2; background: #9ca3af; cursor: not-allowed;" disabled>
                                <i class="fas fa-ban"></i> Hết hàng
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="pagination-container">
            <a href="<?= $pagination['current_page'] > 1 ? BASE_URL . '/products?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] - 1])) : '#' ?>" 
               class="page-btn <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">‹</a>
            
            <?php for ($i = 1; $i <= min(5, $pagination['total_pages']); $i++): ?>
            <a href="<?= BASE_URL ?>/products?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>" 
               class="page-btn <?= $pagination['current_page'] == $i ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($pagination['total_pages'] > 5): ?>
            <span style="color: #999;">...</span>
            <a href="<?= BASE_URL ?>/products?<?= http_build_query(array_merge($filters, ['page' => $pagination['total_pages']])) ?>" 
               class="page-btn"><?= $pagination['total_pages'] ?></a>
            <?php endif; ?>
            
            <a href="<?= $pagination['current_page'] < $pagination['total_pages'] ? BASE_URL . '/products?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] + 1])) : '#' ?>" 
               class="page-btn <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>">›</a>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Empty State -->
        <div class="empty-products">
            <i class="fas fa-box-open"></i>
            <h4>Không tìm thấy sản phẩm</h4>
            <p>Thử thay đổi bộ lọc hoặc tìm kiếm khác</p>
            <a href="<?= BASE_URL ?>/products" class="btn-product btn-add-cart" style="display: inline-block; margin-top: 16px; padding: 12px 24px;">
                Xem tất cả sản phẩm
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Go to product detail
function goToProduct(productId) {
    window.location.href = '<?= BASE_URL ?>/products/detail/' + productId;
}

// Apply sort
function applySort(sortValue) {
    const url = new URL(window.location.href);
    if (sortValue) {
        url.searchParams.set('sort', sortValue);
    } else {
        url.searchParams.delete('sort');
    }
    url.searchParams.delete('page'); // Reset page when sorting
    window.location.href = url.toString();
}

// Add to cart
function addToCart(productId, quantity = 1) {
    const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content || '';
    
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    formData.append('csrf_token', csrfToken);
    
    fetch('<?= BASE_URL ?>/cart/add', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Đã thêm sản phẩm vào giỏ hàng!', 'success');
            updateCartCount();
        } else {
            showNotification(data.message || 'Không thể thêm sản phẩm', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
    });
}

// Buy Now
function buyNow(productId, quantity = 1) {
    const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content || '';
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= BASE_URL ?>/cart/buy-now';
    
    const productInput = document.createElement('input');
    productInput.type = 'hidden';
    productInput.name = 'product_id';
    productInput.value = productId;
    form.appendChild(productInput);
    
    const qtyInput = document.createElement('input');
    qtyInput.type = 'hidden';
    qtyInput.name = 'quantity';
    qtyInput.value = quantity;
    form.appendChild(qtyInput);
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = csrfToken;
    form.appendChild(csrfInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Update cart count
function updateCartCount() {
    fetch('<?= BASE_URL ?>/cart/count', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        const badge = document.querySelector('.cart-badge');
        if (badge && data.count !== undefined) {
            badge.textContent = data.count;
        }
    });
}

// Show notification
function showNotification(message, type = 'info') {
    const existing = document.querySelector('.notification-toast');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = 'notification-toast';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#496C2C' : type === 'error' ? '#ef4444' : '#1a252f'};
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 2500);
}

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Quick filter clicks - Fixed: filter-option IS the anchor tag, not containing one
document.querySelectorAll('.filter-option').forEach(option => {
    option.addEventListener('click', function(e) {
        // Prevent radio button from stopping navigation
        e.preventDefault();
        
        // Since .filter-option IS the <a> tag, use its href directly
        const href = this.getAttribute('href');
        if (href) {
            window.location.href = href;
        }
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
