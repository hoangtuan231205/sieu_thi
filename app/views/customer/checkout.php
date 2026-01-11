<?php

include __DIR__ . '/../layouts/header.php';
?>

<style>
/* ========== CHECKOUT PAGE STYLES ========== */

/* Main Layout */
.checkout-page {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    min-height: 80vh;
    padding: 0 0 60px; /* Removed top padding as breadcrumb has it */
}

/* FIX STICKY SIDEBAR: Ensure parent is visible */
body, html {
    overflow-y: auto !important;
}

.checkout-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 20px; /* Matched orders.php padding-top */
}

/* Breadcrumb */
/* Breadcrumb matches orders.php */
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
.breadcrumb-section span.separator {
    margin: 0 8px;
}
.breadcrumb-section span.current {
    color: #496C2C;
    font-weight: 500;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 24px;
}



/* Grid Layout */
.checkout-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 32px;
}
@media (min-width: 992px) {
    .checkout-grid {
        display: grid !important;
        grid-template-columns: 1.8fr 1fr !important;
        align-items: stretch !important;
        gap: 32px !important;
    }

    /* Right column wrapper needs full height for sticky to work */
    .checkout-right {
        height: 100% !important;
    }

    /* ========== ORDER SUMMARY (Desktop Sticky) ========== */
    .order-summary {
        position: -webkit-sticky !important;
        position: sticky !important;
        top: 20px !important;
        z-index: 100;
        height: fit-content !important;
        align-self: start !important;
    }
}

/* Section Header with Step */
.section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
}

.section-title {
    font-size: 20px;
    font-weight: 700;
    color: #291D51;
    margin: 0;
}

/* Card Style */
.checkout-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    padding: 28px;
}

/* Form Styles */
.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}
@media (min-width: 640px) {
    .form-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .form-grid .full-width {
        grid-column: span 2;
    }
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.form-label {
    font-size: 14px;
    font-weight: 600;
    color: #334155;
}
.form-label .required {
    color: #ef4444;
    margin-left: 2px;
}

.input-wrapper {
    position: relative;
}
.input-wrapper i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 16px;
}
.input-wrapper input,
.input-wrapper textarea {
    width: 100%;
    padding: 14px 14px 14px 44px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 14px;
    background: #f8fafc;
    transition: all 0.2s;
    color: #1e293b;
}
.input-wrapper input:focus,
.input-wrapper textarea:focus {
    outline: none;
    border-color: #496C2C;
    background: white;
    box-shadow: 0 0 0 3px rgba(73, 108, 44, 0.1);
}
.input-wrapper input::placeholder,
.input-wrapper textarea::placeholder {
    color: #94a3b8;
}
.input-wrapper textarea {
    padding-top: 14px;
    resize: none;
}
.input-wrapper.textarea-wrapper i {
    top: 18px;
    transform: none;
}

/* Payment Method */
.payment-section {
    margin-top: 28px;
    padding-top: 28px;
    border-top: 1px solid #e2e8f0;
}
.payment-option {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    border: 2px solid #496C2C;
    border-radius: 12px;
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    cursor: pointer;
    position: relative;
}
.payment-option input[type="radio"] {
    width: 20px;
    height: 20px;
    accent-color: #496C2C;
}
.payment-icon {
    width: 48px;
    height: 48px;
    background: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.payment-icon i {
    font-size: 22px;
    color: #496C2C;
}
.payment-info h4 {
    font-size: 15px;
    font-weight: 700;
    color: #291D51;
    margin: 0 0 4px;
}
.payment-info p {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}

/* ========== ORDER SUMMARY (Mobile Default) ========== */
.order-summary {
    position: relative;
    /* Sticky moved to media query */
}
.summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.summary-title {
    font-size: 18px;
    font-weight: 700;
    color: #291D51;
    margin: 0;
}
.summary-count {
    font-size: 14px;
    color: #64748b;
    font-weight: 400;
}

/* Product Items */
.product-list {
    max-height: 420px;
    overflow-y: auto;
    margin-bottom: 20px;
    padding-right: 8px;
}
.product-list::-webkit-scrollbar {
    width: 4px;
}
.product-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.product-item {
    display: flex;
    gap: 14px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}
.product-item:last-child {
    border-bottom: none;
}
.product-thumb {
    position: relative;
    width: 60px;
    height: 60px;
    border-radius: 10px;
    overflow: hidden;
    background: white;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
}
.product-thumb img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 4px;
    transition: transform 0.3s;
}
.product-item:hover .product-thumb img {
    transform: scale(1.1);
}
.product-qty {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #496C2C;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 6px;
}
.product-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.product-name {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 4px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.product-unit {
    font-size: 12px;
    color: #94a3b8;
}

/* Quantity Control */
.qty-control-wrapper {
    display: inline-flex;
    align-items: center;
    background: #f1f5f9;
    border-radius: 20px;
    padding: 2px 4px;
    margin-top: 8px;
    width: fit-content;
}
.qty-btn {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: none;
    background: white;
    color: #7BC043;
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    transition: all 0.2s;
}
.qty-btn:hover {
    background: #496C2C;
    color: white;
}
.qty-btn:active {
    transform: scale(0.95);
}
.qty-value {
    width: 30px;
    text-align: center;
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
}

.product-price {
    font-size: 14px;
    font-weight: 700;
    color: #1e293b;
    text-align: right;
    white-space: nowrap;
}

/* Summary Totals */
.summary-divider {
    border-top: 2px dashed #e2e8f0;
    margin: 16px 0;
}
.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    font-size: 14px;
}
.summary-row span:first-child {
    color: #64748b;
}
.summary-row span:last-child {
    font-weight: 600;
    color: #1e293b;
}
.summary-total {
    border-top: 1px solid #e2e8f0;
    padding-top: 16px;
    margin-top: 8px;
}
.summary-total .total-label {
    font-size: 16px;
    font-weight: 700;
    color: #291D51;
}
.summary-total .total-value {
    font-size: 26px;
    font-weight: 800;
    color: #496C2C;
}
.vat-note {
    font-size: 11px;
    color: #94a3b8;
    text-align: right;
    margin-top: 4px;
}

/* Submit Button */
.btn-checkout {
    width: 100%;
    padding: 16px 24px;
    background: linear-gradient(135deg, #496C2C 0%, #3d5a25 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 14px rgba(73, 108, 44, 0.35);
    transition: all 0.2s;
    margin-top: 24px;
}
.btn-checkout:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(73, 108, 44, 0.45);
}
.btn-checkout:active {
    transform: translateY(0);
}
.btn-checkout i {
    font-size: 18px;
}

/* Terms */
.checkout-terms {
    text-align: center;
    font-size: 12px;
    color: #94a3b8;
    margin-top: 16px;
}
.checkout-terms a {
    color: #496C2C;
    text-decoration: none;
}
.checkout-terms a:hover {
    text-decoration: underline;
}

/* Security Badge */
.security-badge {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    border: 1px solid #bbf7d0;
    border-radius: 12px;
    padding: 16px;
    margin-top: 20px;
}
.security-badge i {
    color: #496C2C;
    font-size: 20px;
    margin-top: 2px;
}
.security-badge-text strong {
    display: block;
    font-size: 13px;
    color: #166534;
    margin-bottom: 4px;
}
.security-badge-text span {
    font-size: 12px;
    color: #4b5563;
    line-height: 1.5;
}
</style>

<div class="checkout-page">
    
    <!-- Breadcrumb -->
    <div class="breadcrumb-section">
        <a href="<?= BASE_URL ?>/">Trang chủ</a>
        <span class="separator">›</span>
        <a href="<?= BASE_URL ?>/cart">Giỏ hàng</a>
        <span class="separator">›</span>
        <span class="current">Thanh toán</span>
    </div>
    
    <div class="checkout-container">
        <h1 class="page-title">Thanh toán</h1>
            
        <form method="POST" action="<?= BASE_URL ?>/checkout/process" id="checkoutForm" data-validate-form>
            <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
            <input type="hidden" name="selected_ids" value="<?= $selected_ids ?? '' ?>">
            
            <div class="checkout-grid">
                
                <!-- Left Column: Shipping & Payment -->
                <div class="checkout-left">
                    
                    <!-- Shipping Info -->
                    <div class="section-header">
                        <h2 class="section-title">Thông tin giao hàng</h2>
                    </div>
                    
                    <div class="checkout-card">
                        <div class="form-grid">
                            
                            <!-- Full Name -->
                            <div class="form-group">
                                <label class="form-label">
                                    Họ và tên <span class="required">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="fas fa-user"></i>
                                    <input type="text" 
                                           name="receiver_name" 
                                           data-validate="required|min:3|max:100"
                                           value="<?= htmlspecialchars($user['Ho_ten'] ?? '') ?>"
                                           placeholder="Nguyễn Văn A">
                                </div>
                            </div>
                            
                            <!-- Phone -->
                            <div class="form-group">
                                <label class="form-label">
                                    Số điện thoại <span class="required">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" 
                                           name="receiver_phone" 
                                           data-validate="required|phone"
                                           value="<?= htmlspecialchars($user['Sdt'] ?? '') ?>"
                                           placeholder="0912 xxx xxx">
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div class="form-group full-width">
                                <label class="form-label">Email</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-envelope"></i>
                                    <input type="email" 
                                           name="receiver_email" 
                                           data-validate="email"
                                           value="<?= htmlspecialchars($user['Email'] ?? '') ?>"
                                           placeholder="email@example.com">
                                </div>
                            </div>
                            
                            <!-- Address -->
                            <div class="form-group full-width">
                                <label class="form-label">
                                    Địa chỉ nhận hàng <span class="required">*</span>
                                </label>
                                <div class="input-wrapper">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <input type="text" 
                                           name="receiver_address" 
                                           data-validate="required|min:10|max:500"
                                           value="<?= htmlspecialchars($user['Dia_chi'] ?? '') ?>"
                                           placeholder="Số nhà, tên đường, phường/xã, quận/huyện...">
                                </div>
                            </div>
                            
                            <!-- Notes -->
                            <div class="form-group full-width">
                                <label class="form-label">Ghi chú đơn hàng (Tùy chọn)</label>
                                <div class="input-wrapper textarea-wrapper">
                                    <i class="fas fa-sticky-note"></i>
                                    <textarea name="note" 
                                              rows="3"
                                              data-validate="max:500"
                                              placeholder="Ví dụ: Giao hàng vào giờ hành chính..."></textarea>
                                </div>
                            </div>
                            
                        </div>
                        
                        <!-- Payment Method -->
                        <div class="payment-section">
                            <label class="form-label" style="margin-bottom: 12px;">Phương thức thanh toán</label>
                            
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="cod" checked>
                                <div class="payment-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="payment-info">
                                    <h4>Thanh toán khi nhận hàng (COD)</h4>
                                    <p>Thanh toán bằng tiền mặt khi nhận hàng</p>
                                </div>
                            </label>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Right Column: Order Summary -->
                <div class="checkout-right">
                    <div class="checkout-card order-summary">
                        
                        <div class="summary-header">
                            <h3 class="summary-title">Đơn hàng của bạn</h3>
                            <span class="summary-count">(<?= count($cart_items) ?> sản phẩm)</span>
                        </div>
                        
                        <!-- Product List -->
                        <div class="product-list">
                            <?php if (!empty($cart_items)): ?>
                                <?php foreach ($cart_items as $item): ?>
                                <!-- Product Item -->
                                <!-- Product Item -->
                                <div class="product-item" data-cart-id="<?= $item['ID_gio'] ?? '' ?>">
                                    <div style="position: relative; margin-right: 6px;">
                                        <div class="product-thumb">
                                            <?php 
                                            $imagePath = getProductImagePath($item['Hinh_anh'] ?? '');
                                            if (!empty($imagePath)): 
                                            ?>
                                                <img src="<?= asset('img/products/' . $imagePath) ?>" alt="<?= htmlspecialchars($item['Ten']) ?>">
                                            <?php else: ?>
                                                <img src="<?= asset('img/placeholder-product.png') ?>" alt="<?= htmlspecialchars($item['Ten']) ?>">
                                            <?php endif; ?>
                                        </div>
                                        <span class="product-qty" style="z-index: 10;">x<span class="badge-qty"><?= $item['So_luong'] ?></span></span>
                                    </div>
                                        
                                        <div class="product-info">
                                            <h4 class="product-name"><?= htmlspecialchars($item['Ten']) ?></h4>
                                            <span class="product-unit"><?= htmlspecialchars($item['Don_vi_tinh'] ?? '') ?></span>
                                            
                                            <div class="qty-control-wrapper">
                                                <button type="button" class="qty-btn minus" onclick="updateQuantity('<?= $item['ID_gio'] ?? 'direct' ?>', -1)">-</button>
                                                <span class="qty-value"><?= $item['So_luong'] ?></span>
                                                <button type="button" class="qty-btn plus" onclick="updateQuantity('<?= $item['ID_gio'] ?? 'direct' ?>', 1)">+</button>
                                            </div>
                                        </div>

                                    
                                    <div class="product-price" id="item-total-<?= $item['ID_gio'] ?? 'direct' ?>">
                                        <?= number_format($item['Gia_tien'] * $item['So_luong'], 0, ',', '.') ?>₫
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Totals -->
                        <div class="summary-divider"></div>
                        
                        <div class="summary-row">
                            <span>Tạm tính</span>
                            <span id="summary-subtotal"><?= number_format($cart_summary['subtotal'], 0, ',', '.') ?>₫</span>
                        </div>
                        <div class="summary-row">
                            <span>Phí vận chuyển</span>
                            <span id="summary-shipping"><?= ($cart_summary['shipping_fee'] == 0) ? 'Miễn phí' : number_format($cart_summary['shipping_fee'], 0, ',', '.') . '₫' ?></span>
                        </div>
                        
                        <div class="summary-row summary-total">
                            <span class="total-label">Tổng cộng</span>
                            <div style="text-align: right;">
                                <span class="total-value" id="summary-total"><?= number_format($cart_summary['total'], 0, ',', '.') ?>₫</span>
                                <p class="vat-note">(Đã bao gồm VAT)</p>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="btn-checkout">
                            ĐẶT HÀNG NGAY
                        </button>
                        
                        <p class="checkout-terms">
                            Bằng việc đặt hàng, bạn đồng ý với 
                            <a href="#">Điều khoản sử dụng</a> của chúng tôi.
                        </p>
                        
                        <!-- Security Badge -->
                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            <div class="security-badge-text">
                                <strong>Bảo mật & An toàn</strong>
                                <span>Thông tin của bạn được mã hóa và bảo vệ an toàn tuyệt đối.</span>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
            </div>
        </form>
        
    </div>
</div>

<script>
// Định dạng tiền tệ
function formatCurrency(col) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(col).replace('₫', '') + '₫';
}

// Cập nhật số lượng
function updateQuantity(cartId, change) {
    // Nếu cartId là 'direct', dùng selector khác vì data-cart-id trống
    let itemRow;
    if (cartId === 'direct') {
        itemRow = document.querySelector(`.product-item[data-cart-id=""]`);
    } else {
        itemRow = document.querySelector(`.product-item[data-cart-id="${cartId}"]`);
    }
    
    const qtySpan = itemRow.querySelector('.qty-value');
    const badgeSpan = itemRow.querySelector('.badge-qty');
    const currentQty = parseInt(qtySpan.textContent);
    const newQty = currentQty + change;
    
    if (newQty < 1) return; // Tối thiểu 1

    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    const formData = new FormData();
    formData.append('quantity', newQty);
    formData.append('csrf_token', csrfToken);
    
    let url = '<?= BASE_URL ?>/checkout/updateDirectQuantity'; // Default for direct
    
    
    if (cartId !== 'direct') {
        formData.append('cart_id', cartId);
        // CRITICAL FIX: Use specific Checkout endpoint that respects selected_ids
        url = '<?= BASE_URL ?>/checkout/update-cart-quantity';
        
        // Pass selected_ids so backend calculates partial total correctly
        const selectedIdsInput = document.querySelector('input[name="selected_ids"]');
        if (selectedIdsInput) {
            formData.append('selected_ids', selectedIdsInput.value);
        }
    }
    
    // Vô hiệu hóa các nút
    const btns = itemRow.querySelectorAll('.qty-btn');
    btns.forEach(btn => btn.disabled = true);
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Cập nhật giao diện số lượng
            qtySpan.textContent = newQty;
            badgeSpan.textContent = newQty;
            
            // Cập nhật giá item
            const priceEl = document.getElementById(`item-total-${cartId}`);
            if (priceEl && data.item_total_formatted) {
                priceEl.textContent = data.item_total_formatted;
            }

            // Cập nhật tổng
            const subtotalEl = document.getElementById('summary-subtotal');
            const shippingEl = document.getElementById('summary-shipping');
            const totalEl = document.getElementById('summary-total');

            if (subtotalEl && data.subtotal_formatted) subtotalEl.textContent = data.subtotal_formatted;
            if (shippingEl && data.shipping_fee_formatted) shippingEl.textContent = data.shipping_fee_formatted;
            if (totalEl && data.total_formatted) totalEl.textContent = data.total_formatted;

        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Có lỗi kết nối, vui lòng thử lại');
    })
    .finally(() => {
        btns.forEach(btn => btn.disabled = false);
    });
}

document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    
    // Kiểm tra các trường bắt buộc TRƯỚC khi hiện loading
    const nameField = this.querySelector('input[name="receiver_name"]');
    const phoneField = this.querySelector('input[name="receiver_phone"]');
    const addressField = this.querySelector('input[name="receiver_address"]');
    
    let isValid = true;
    let errorMessage = '';
    
    // Xóa các lỗi cũ
    this.querySelectorAll('.field-error').forEach(el => el.remove());
    this.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    
    // Kiểm tra Họ tên
    if (!nameField.value.trim() || nameField.value.trim().length < 3) {
        isValid = false;
        showFieldError(nameField, 'Vui lòng nhập họ tên (tối thiểu 3 ký tự)');
    }
    
    // Kiểm tra Số điện thoại
    const phonePattern = /^(0[3|5|7|8|9])+([0-9]{8})$/;
    if (!phoneField.value.trim()) {
        isValid = false;
        showFieldError(phoneField, 'Vui lòng nhập số điện thoại');
    } else if (!phonePattern.test(phoneField.value.trim().replace(/\s/g, ''))) {
        isValid = false;
        showFieldError(phoneField, 'Số điện thoại không hợp lệ');
    }
    
    // Kiểm tra Địa chỉ
    if (!addressField.value.trim() || addressField.value.trim().length < 10) {
        isValid = false;
        showFieldError(addressField, 'Vui lòng nhập địa chỉ đầy đủ (tối thiểu 10 ký tự)');
    }
    
    // Nếu không hợp lệ, dừng lại và không xoay loading
    if (!isValid) {
        e.preventDefault();
        // Cuộn đến lỗi đầu tiên
        const firstError = this.querySelector('.field-error');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return false;
    }
    
    // Chỉ hiện loading khi form hợp lệ
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
});

// Helper function hiển thị lỗi cho field
function showFieldError(field, message) {
    const wrapper = field.closest('.input-wrapper');
    field.classList.add('input-error');
    wrapper.style.borderColor = '#ef4444';
    
    // Thêm thông báo lỗi
    const errorEl = document.createElement('span');
    errorEl.className = 'field-error';
    errorEl.style.cssText = 'color: #ef4444; font-size: 12px; margin-top: 4px; display: block;';
    errorEl.textContent = message;
    field.closest('.form-group').appendChild(errorEl);
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>