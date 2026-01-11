<?php
/**
 * =============================================================================
 * TRANG GIỎ HÀNG - CART PAGE
 * =============================================================================
 * 
 * Giao diện mới theo thiết kế modern với grid layout
 */
include __DIR__ . '/../layouts/header.php';
?>

<style>
/* ===== MAIN LAYOUT ===== */
.cart-page-wrapper {
    background: #f7f7f7;
    min-height: 80vh;
    padding-bottom: 60px;
}

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
    color: #7BC043;
}

.breadcrumb-section span {
    margin: 0 8px;
}

/* ===== CONTAINER ===== */
.cart-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 20px;
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 24px;
    align-items: start;
}

/* ===== PAGE HEADER ===== */
.cart-page-header {
    grid-column: 1 / -1;
}

.cart-page-title {
    font-size: 32px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.cart-item-count {
    font-size: 16px;
    color: #6b7280;
    font-weight: 400;
}

/* ===== CART ITEMS TABLE ===== */
.cart-items-section {
    background: white;
    border-radius: 16px;
    overflow: hidden;
}

.cart-table-header {
    display: grid;
    grid-template-columns: 40px 2fr 1fr 1fr 1fr 50px;
    padding: 16px 20px;
    background: #fafafa;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #f3f4f6;
    align-items: center;
}

.cart-item-row {
    display: grid;
    grid-template-columns: 40px 2fr 1fr 1fr 1fr 50px;
    padding: 20px;
    border-bottom: 1px solid #f3f4f6;
    align-items: center;
    gap: 16px;
}

.cart-item-row:last-child {
    border-bottom: none;
}

/* ===== ITEM INFO ===== */
.cart-item-info {
    display: flex;
    gap: 16px;
    align-items: center;
}

.cart-item-image {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    object-fit: cover;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
}

.cart-item-details {
    flex: 1;
}

.cart-item-name {
    font-size: 15px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 6px;
    line-height: 1.4;
}

.cart-item-unit {
    font-size: 13px;
    color: #9ca3af;
    margin-bottom: 8px;
}

.cart-item-stock {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 500;
}

.stock-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.stock-available {
    color: #496C2C;
}

.stock-available .stock-dot {
    background: #496C2C;
}

.stock-low {
    color: #f59e0b;
}

.stock-low .stock-dot {
    background: #f59e0b;
}

/* ===== ITEM PRICE ===== */
.cart-item-price-col {
    font-size: 15px;
    color: #6b7280;
}

.cart-item-price-col del {
    display: block;
    font-size: 13px;
    color: #d1d5db;
    margin-bottom: 4px;
}

.current-price {
    font-weight: 600;
    color: #1a1a1a;
}

/* ===== QUANTITY CONTROL ===== */
.qty-control-modern {
    display: flex;
    align-items: center;
    gap: 0;
    background: #f9fafb;
    border-radius: 10px;
    padding: 4px;
    width: fit-content;
}

.qty-btn-modern {
    width: 32px;
    height: 32px;
    border: none;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.qty-btn-modern:hover {
    background: #496C2C;
    color: white;
}

.qty-btn-modern:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.qty-input-modern {
    width: 48px;
    text-align: center;
    border: none;
    background: transparent;
    font-size: 14px;
    font-weight: 600;
    color: #1a1a1a;
    outline: none;
}

/* ===== ITEM TOTAL ===== */
.cart-item-total {
    font-size: 17px;
    font-weight: 700;
    color: #496C2C;
}

/* ===== REMOVE BUTTON ===== */
.btn-remove-item {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: #9ca3af;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-remove-item:hover {
    background: #fee2e2;
    color: #dc2626;
}

/* ===== CONTINUE SHOPPING ===== */
.continue-shopping-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #496C2C;
    text-decoration: none;
    font-size: 15px;
    font-weight: 600;
    margin: 20px;
    padding: 12px 0;
    transition: gap 0.2s;
}

.continue-shopping-link:hover {
    gap: 12px;
    color: #059669;
}

/* ===== ORDER SUMMARY ===== */
.order-summary-section {
    background: white;
    border-radius: 16px;
    padding: 24px;
    position: sticky;
    top: 20px;
}

.summary-title {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 20px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    font-size: 15px;
    color: #6b7280;
}

.summary-row strong {
    color: #1a1a1a;
    font-weight: 600;
}

.free-shipping-badge {
    color: #496C2C;
    font-weight: 600;
}


.total-section {
    padding-top: 20px;
    margin-top: 20px;
    border-top: 2px solid #f3f4f6;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.total-label {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a1a;
}

.total-amount {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
}

.total-note {
    font-size: 12px;
    color: #9ca3af;
    text-align: right;
}

.checkout-btn-modern {
    width: 100%;
    padding: 16px;
    background: #496C2C;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}

.checkout-btn-modern:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.checkout-btn-modern:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.secure-payment-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 16px;
    font-size: 13px;
    color: #496C2C;
}

.payment-methods-icons {
    display: flex;
    gap: 8px;
    margin-top: 16px;
    justify-content: center;
}

.payment-icon {
    width: 48px;
    height: 32px;
    background: #f3f4f6;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 600;
    color: #6b7280;
}

/* ===== EMPTY CART ===== */
.empty-cart-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 16px;
}

.empty-cart-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.empty-cart-title {
    font-size: 24px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.empty-cart-text {
    font-size: 14px;
    color: #9ca3af;
    margin-bottom: 24px;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .cart-container {
        grid-template-columns: 1fr;
    }

    .order-summary-section {
        position: static;
    }
}

@media (max-width: 768px) {
    .cart-table-header {
        display: none;
    }

    .cart-item-row {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .cart-item-info {
        width: 100%;
    }

    .cart-item-total {
        text-align: left;
    }
}

@media (max-width: 480px) {
    .cart-page-title {
        font-size: 24px;
    }

    .cart-item-image {
        width: 64px;
        height: 64px;
    }
}

.btn-continue-shopping {
    width: auto;
    display: inline-flex;
    padding: 16px 40px;
}
</style>

<div class="cart-page-wrapper">
    <!-- Breadcrumb -->
    <div class="breadcrumb-section">
        <a href="<?= BASE_URL ?>/">Trang chủ</a>
        <span>›</span>
        <span>Giỏ hàng</span>
    </div>

    <!-- Main Content -->
    <div class="cart-container">
        <!-- Page Header -->
        <div class="cart-page-header">
            <h1 class="cart-page-title">
                Giỏ hàng của bạn 
                <span class="cart-item-count">(<?= count($cart_items ?? []) ?> sản phẩm)</span>
            </h1>
        </div>

        <?php if (!empty($cart_items)): ?>
        <!-- Cart Items -->
        <div class="cart-items-section">
            <!-- Table Header -->
            <div class="cart-table-header">
                <div><input type="checkbox" id="selectAll" checked onchange="toggleAll(this)" style="width:18px;height:18px;cursor:pointer;"></div>
                <div>Sản phẩm</div>
                <div>Đơn giá</div>
                <div>Số lượng</div>
                <div>Tạm tính</div>
                <div></div>
            </div>

            <!-- Cart Items -->
            <?php foreach ($cart_items as $item): ?>
            <div class="cart-item-row" data-cart-id="<?= $item['ID_gio'] ?>">
                <div style="display:flex;justify-content:center;">
                    <input type="checkbox" class="item-checkbox" 
                           data-id="<?= $item['ID_gio'] ?>" 
                           data-price="<?= $item['Gia_tien'] ?>"
                           checked 
                           onchange="calculateSummary()"
                           style="width:18px;height:18px;cursor:pointer;">
                </div>
                <div class="cart-item-info">
                    <img src="<?= asset('img/products/' . ($item['Hinh_anh'] ?? 'placeholder-product.png')) ?>" 
                         class="cart-item-image" 
                         alt="<?= htmlspecialchars($item['Ten']) ?>">
                    <div class="cart-item-details">
                        <div class="cart-item-name"><?= htmlspecialchars($item['Ten']) ?></div>
                        <div class="cart-item-unit"><?= htmlspecialchars($item['Don_vi_tinh'] ?? 'Sản phẩm') ?></div>
                        <div class="cart-item-stock <?= ($item['So_luong_ton'] > 10) ? 'stock-available' : 'stock-low' ?>">
                            <span class="stock-dot"></span>
                            <?= ($item['So_luong_ton'] > 10) ? 'Còn hàng' : 'Sắp hết (' . $item['So_luong_ton'] . ')' ?>
                        </div>
                    </div>
                </div>

                <div class="cart-item-price-col">
                    <span class="current-price"><?= number_format($item['Gia_tien'], 0, ',', '.') ?>đ</span>
                </div>

                <div class="qty-control-modern">
                    <button class="qty-btn-modern" onclick="updateQty(<?= $item['ID_gio'] ?>, -1)">−</button>
                    <input type="text" 
                           class="qty-input-modern" 
                           id="qty-<?= $item['ID_gio'] ?>" 
                           value="<?= $item['So_luong'] ?>" 
                           data-max="<?= $item['So_luong_ton'] ?>"
                           readonly>
                    <button class="qty-btn-modern" onclick="updateQty(<?= $item['ID_gio'] ?>, 1)">+</button>
                </div>

                <div class="cart-item-total" id="total-<?= $item['ID_gio'] ?>" data-price="<?= $item['Gia_tien'] ?>">
                    <?= number_format($item['Gia_tien'] * $item['So_luong'], 0, ',', '.') ?>đ
                </div>

                <button class="btn-remove-item" onclick="removeItem(<?= $item['ID_gio'] ?>)" title="Xóa">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <?php endforeach; ?>

            <!-- Continue Shopping Link -->
            <a href="<?= BASE_URL ?>/products" class="continue-shopping-link">
                ← Tiếp tục mua sắm
            </a>
        </div>

        <!-- Order Summary -->
        <div class="order-summary-section">
            <h2 class="summary-title">Tóm tắt đơn hàng</h2>

            <div class="summary-row">
                <span>Tổng tiền hàng</span>
                <strong id="subtotal"><?= number_format($subtotal ?? 0, 0, ',', '.') ?>đ</strong>
            </div>

            <div class="summary-row">
                <span>Phí vận chuyển</span>
                <span id="shipping" class="<?= ($shipping_fee ?? 20000) == 0 ? 'free-shipping-badge' : '' ?>">
                    <?= ($shipping_fee ?? 20000) == 0 ? 'Miễn phí' : number_format($shipping_fee ?? 20000, 0, ',', '.') . 'đ' ?>
                </span>
            </div>

            <div class="summary-row">
                <span style="font-size: 13px; color: #9ca3af;">
                    Miễn phí vận chuyển cho đơn từ 150.000đ
                </span>
            </div>

            <div class="total-section">
                <div class="total-row">
                    <span class="total-label">Tổng cộng</span>
                    <div>
                        <div class="total-amount" id="total-amount"><?= number_format($total ?? 0, 0, ',', '.') ?>đ</div>
                        <div class="total-note">Đã bao gồm VAT</div>
                    </div>
                </div>
            </div>

            <button class="checkout-btn-modern" id="btn-checkout" onclick="proceedToCheckout()">
                Thanh toán ngay →
            </button>

            <div class="secure-payment-note">
                <i class="fas fa-shield-alt"></i>
                Thanh toán an toàn 100%
            </div>

            <div class="payment-methods-icons">
                <div class="payment-icon">VISA</div>
                <div class="payment-icon">MC</div>
                <div class="payment-icon">JCB</div>
                <div class="payment-icon">COD</div>
            </div>
        </div>

        <?php else: ?>
        <!-- Empty Cart State -->
        <div class="empty-cart-state">
            <div class="empty-cart-icon">🛒</div>
            <h3 class="empty-cart-title">Giỏ hàng trống</h3>
            <p class="empty-cart-text">Bạn chưa có sản phẩm nào trong giỏ hàng. Hãy khám phá ngay!</p>
            <a href="<?= BASE_URL ?>/products" class="checkout-btn-modern btn-continue-shopping">
                <i class="fas fa-shopping-bag"></i>
                Tiếp tục mua sắm
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Cập nhật số lượng
function updateQty(cartId, change) {
    const input = document.getElementById(`qty-${cartId}`);
    let currentQty = parseInt(input.value);
    let newQty = currentQty + change;
    const maxQty = parseInt(input.dataset.max);

    if (newQty < 1) newQty = 1;
    if (newQty > maxQty) newQty = maxQty;

    input.value = newQty;

    // Lấy CSRF token
    const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content || '';

    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('quantity', newQty);
    formData.append('csrf_token', csrfToken);

    fetch('<?= BASE_URL ?>/cart/update', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Cập nhật tổng item
            const totalElement = document.getElementById(`total-${cartId}`);
            const price = parseFloat(totalElement.dataset.price);
            const newTotal = price * newQty;
            totalElement.textContent = new Intl.NumberFormat('vi-VN').format(newTotal) + 'đ';

            // Tính lại tổng
            calculateSummary();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
            location.reload();
        }
    })
    .catch(err => {
        console.error('Update error:', err);
        alert('Không thể cập nhật. Vui lòng thử lại.');
    });
}

// Bật/tắt tất cả checkbox
function toggleAll(master) {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    calculateSummary();
}

// Tính tổng chỉ dựa trên các item đã chọn
function calculateSummary() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    let subtotal = 0;
    let selectedCount = 0;

    checkboxes.forEach(cb => {
        const row = cb.closest('.cart-item-row');
        const totalEl = row.querySelector('.cart-item-total');
        const price = parseFloat(totalEl.dataset.price);
        const qty = parseInt(row.querySelector('.qty-input-modern').value);
        subtotal += price * qty;
        selectedCount++;
    });

    // Cập nhật tổng phụ
    document.getElementById('subtotal').textContent = new Intl.NumberFormat('vi-VN').format(subtotal) + 'đ';

    // Tính phí vận chuyển
    const shipping = (subtotal >= 150000 || subtotal === 0) ? 0 : 20000;
    const shippingEl = document.getElementById('shipping');
    if (subtotal === 0) {
        shippingEl.textContent = '0đ';
        shippingEl.classList.remove('free-shipping-badge');
    } else if (shipping === 0) {
        shippingEl.textContent = 'Miễn phí';
        shippingEl.classList.add('free-shipping-badge');
    } else {
        shippingEl.textContent = new Intl.NumberFormat('vi-VN').format(shipping) + 'đ';
        shippingEl.classList.remove('free-shipping-badge');
    }

    // Cập nhật tổng cộng
    const total = subtotal + shipping;
    document.getElementById('total-amount').textContent = new Intl.NumberFormat('vi-VN').format(total) + 'đ';
    
    // Cập nhật trạng thái nút thanh toán
    const checkoutBtn = document.getElementById('btn-checkout');
    if (checkoutBtn) {
        if (selectedCount === 0) {
            checkoutBtn.disabled = true;
            checkoutBtn.style.opacity = '0.5';
            checkoutBtn.style.cursor = 'not-allowed';
        } else {
            checkoutBtn.disabled = false;
            checkoutBtn.style.opacity = '1';
            checkoutBtn.style.cursor = 'pointer';
        }
    }
    
    // Đồng bộ checkbox Chọn tất cả
    const allCheckboxes = document.querySelectorAll('.item-checkbox');
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.checked = (checkboxes.length === allCheckboxes.length && allCheckboxes.length > 0);
    }
}

// Xóa item
function removeItem(cartId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;

    const csrfToken = document.querySelector('meta[name="csrf_token"]')?.content || '';
    const formData = new FormData();
    formData.append('cart_id', cartId);
    formData.append('csrf_token', csrfToken);

    fetch('<?= BASE_URL ?>/cart/remove', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = document.querySelector(`.cart-item-row[data-cart-id="${cartId}"]`);
            if (row) {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    row.remove();
                    calculateSummary();

                    // Kiểm tra giỏ hàng có trống không
                    if (document.querySelectorAll('.cart-item-row').length === 0) {
                        location.reload();
                    }
                }, 300);
            }
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(err => {
        console.error('Remove error:', err);
        alert('Không thể xóa. Vui lòng thử lại.');
    });
}

// Chuyển đến thanh toán - chỉ các item đã chọn
function proceedToCheckout() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Vui lòng chọn ít nhất một sản phẩm để thanh toán');
        return;
    }

    const ids = Array.from(checkboxes).map(cb => cb.dataset.id).join(',');
    window.location.href = '<?= BASE_URL ?>/checkout?items=' + ids;
}

// Áp dụng mã giảm giá
document.querySelector('.apply-btn')?.addEventListener('click', function() {
    const code = document.querySelector('.discount-input').value.trim();
    if (code) {
        alert('Mã giảm giá sẽ được áp dụng ở bước thanh toán.');
    } else {
        alert('Vui lòng nhập mã giảm giá');
    }
});

// Tính toán ban đầu
document.addEventListener('DOMContentLoaded', calculateSummary);
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
