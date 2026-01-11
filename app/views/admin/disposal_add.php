<?php
/**
 * ADMIN - TẠO PHIẾU HỦY
 * Thiết kế giao diện hiện đại - Theme Woodland
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
            <a href="<?= BASE_URL ?>/admin/disposals">Phiếu hủy</a>
            <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
            <span class="current">Tạo mới</span>
        </div>
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Tạo phiếu hủy mới</h1>
                <p class="admin-page-subtitle">Lập phiếu hủy hàng hư hỏng, hết hạn hoặc điều chỉnh kiểm kê</p>
            </div>
        </div>
        
        <?php if (Session::hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= Session::getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="disposalForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-9">
                    <!-- Info Card -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">Thông tin phiếu</h3>
                        </div>
                        <div class="admin-card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Loại phiếu <span class="text-danger">*</span></label>
                                    <?php $prefillType = !empty($prefill_product) ? 'het_han' : ''; ?>
                                    <select class="form-select" name="loai_phieu" required style="border-radius: 8px; padding: 10px 14px;">
                                        <option value="huy" <?= $prefillType === '' ? 'selected' : '' ?>>Hủy bỏ</option>
                                        <option value="hong">Hư hỏng</option>
                                        <option value="het_han" <?= $prefillType === 'het_han' ? 'selected' : '' ?>>Hết hạn</option>
                                        <option value="dieu_chinh">Điều chỉnh kiểm kê</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Ngày hủy <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="ngay_huy" value="<?= date('Y-m-d') ?>" required style="border-radius: 8px; padding: 10px 14px;">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Lý do <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="ly_do" rows="3" placeholder="Mô tả chi tiết lý do hủy hàng..." required style="border-radius: 8px; padding: 12px 14px;"><?= htmlspecialchars($prefill_reason ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Products Card -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h3 class="admin-card-title">Sản phẩm hủy</h3>
                        </div>
                        <div class="admin-card-body">
                            <!-- Search Box -->
                            <div class="admin-search-box" style="margin-bottom: 20px; max-width: 100%; border: none; box-shadow: none; background: #f8fafc;">
                                <i class="fas fa-search"></i>
                                <input type="text" id="productSearch" placeholder="Tìm sản phẩm theo tên hoặc mã..." style="background: transparent; border: none;">
                                <div id="productSuggestions" style="position: absolute; width: 100%; z-index: 100; background: white; border-radius: 10px; margin-top: 4px; max-height: 300px; overflow-y: auto; display: none; box-shadow: 0 10px 40px rgba(0,0,0,0.15);"></div>
                            </div>
                            
                            <!-- Items Table -->
                            <table class="admin-table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 35%;">Sản phẩm</th>
                                        <th style="width: 20%;">Lô nhập</th>
                                        <th style="width: 12%;">Số lượng</th>
                                        <th style="width: 13%;">Giá nhập</th>
                                        <th style="width: 15%;">Thành tiền</th>
                                        <th style="width: 5%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr id="noItemRow">
                                        <td colspan="6" style="text-align: center; padding: 40px 20px;">
                                            <i class="fas fa-box-open" style="font-size: 36px; color: var(--admin-text-light); display: block; margin-bottom: 12px;"></i>
                                            <p style="color: var(--admin-text-muted); margin: 0;">Tìm và thêm sản phẩm ở trên</p>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot style="background: #f8fafc;">
                                    <tr>
                                        <td colspan="4" style="text-align: right; font-weight: 600;">TỔNG GIÁ TRỊ HỦY:</td>
                                        <td id="totalValue" style="font-weight: 700; color: var(--admin-danger); font-size: 18px;">0đ</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="col-lg-3">
                    <div class="admin-card" style="position: sticky; top: 20px;">
                        <div class="admin-card-body">
                            <!-- Info Alert -->
                            <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 10px; padding: 16px; margin-bottom: 20px;">
                                <div style="display: flex; gap: 12px;">
                                    <i class="fas fa-info-circle" style="color: #3b82f6; margin-top: 2px;"></i>
                                    <div>
                                        <p style="font-size: 14px; font-weight: 500; color: #1e40af; margin: 0 0 4px 0;">Lưu ý quan trọng</p>
                                        <p style="font-size: 13px; color: #3b82f6; margin: 0;">Phiếu sẽ ở trạng thái <strong>Chờ duyệt</strong>. Kho chỉ bị trừ sau khi Admin duyệt phiếu.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Summary -->
                            <div class="summary-box" style="margin-bottom: 20px;">
                                <div class="summary-row">
                                    <span>Số sản phẩm</span>
                                    <span id="itemCount">0</span>
                                </div>
                                <div class="summary-row">
                                    <span>Tổng số lượng</span>
                                    <span id="totalQty">0</span>
                                </div>
                                <div class="summary-row">
                                    <span style="color: var(--admin-danger);">Giá trị hủy</span>
                                    <span id="summaryTotal" style="color: var(--admin-danger);">0đ</span>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <button type="submit" class="btn-admin-primary" style="width: 100%; justify-content: center; padding: 14px;">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Tạo phiếu hủy</span>
                                </button>
                                <a href="<?= BASE_URL ?>/admin/disposals" class="btn-admin-secondary" style="width: 100%; justify-content: center; padding: 14px;">
                                    <span>Hủy bỏ</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
let itemIndex = 0;
const searchInput = document.getElementById('productSearch');
const suggestions = document.getElementById('productSuggestions');
let searchTimeout;

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 2) { suggestions.style.display = 'none'; return; }
    
    searchTimeout = setTimeout(() => {
        fetch(baseUrl + '/admin/search-product-for-disposal?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            if (data.length === 0) {
                suggestions.innerHTML = '<div style="padding: 16px; color: var(--admin-text-muted); text-align: center;">Không tìm thấy sản phẩm</div>';
            } else {
                suggestions.innerHTML = data.map(p => `
                    <div onclick='selectProduct(${JSON.stringify(p)})' style="padding: 12px 16px; cursor: pointer; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                        <div>
                            <div style="font-weight: 600; color: var(--admin-primary);">${p.Ma_hien_thi}</div>
                            <div style="font-size: 13px; color: var(--admin-text);">${p.Ten}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 12px; color: var(--admin-text-muted);">Tồn kho</div>
                            <div style="font-weight: 600;">${p.So_luong_ton}</div>
                        </div>
                    </div>
                `).join('');
            }
            suggestions.style.display = 'block';
        });
    }, 300);
});

document.addEventListener('click', e => {
    if (!searchInput.contains(e.target) && !suggestions.contains(e.target)) {
        suggestions.style.display = 'none';
    }
});

function selectProduct(product) {
    suggestions.style.display = 'none';
    searchInput.value = '';
    fetch(baseUrl + '/admin/get-product-batches?product_id=' + product.ID_sp)
    .then(r => r.json())
    .then(batches => addProductRow(product, batches));
}

function addProductRow(product, batches, prefillQty = 1) {
    document.getElementById('noItemRow')?.remove();
    
    // Determine price: from batches or from product
    let defaultPrice = product.Gia_nhap || 0;
    if (batches.length > 0 && batches[0].Don_gia_nhap) {
        defaultPrice = batches[0].Don_gia_nhap;
    }
    
    const batchOptions = batches.length > 0 
        ? batches.map(b => `<option value="${b.ID_chi_tiet_nhap}" data-price="${b.Don_gia_nhap}">${b.Ma_phieu_nhap} (${b.So_luong_con})</option>`).join('')
        : '<option value="">Không có lô</option>';
    
    const row = document.createElement('tr');
    row.id = `row_${itemIndex}`;
    row.innerHTML = `
        <td>
            <div style="font-weight: 600; color: var(--admin-primary);">${product.Ma_hien_thi}</div>
            <div style="font-size: 13px; color: var(--admin-text-muted);">${product.Ten}</div>
            <input type="hidden" name="items[${itemIndex}][ID_sp]" value="${product.ID_sp}">
            <input type="hidden" name="items[${itemIndex}][Ten_sp]" value="${product.Ten}">
            <input type="hidden" name="items[${itemIndex}][Gia_nhap]" value="${defaultPrice}" id="price_${itemIndex}">
        </td>
        <td><select class="form-select form-select-sm" name="items[${itemIndex}][ID_lo_nhap]" onchange="updateBatchPrice(${itemIndex}, this)" style="border-radius: 6px; min-width: 180px; font-weight: 500;">${batchOptions}</select></td>
        <td><input type="number" class="form-control form-control-sm" name="items[${itemIndex}][So_luong]" min="1" value="${prefillQty}" onchange="updateRowTotal(${itemIndex})" required style="border-radius: 6px; text-align: center;"></td>
        <td id="priceDisplay_${itemIndex}">${Number(defaultPrice).toLocaleString()}đ</td>
        <td id="rowTotal_${itemIndex}" style="font-weight: 600;">${Number(defaultPrice * prefillQty).toLocaleString()}đ</td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(${itemIndex})" style="border-radius: 6px;"><i class="fas fa-trash"></i></button></td>
    `;
    document.getElementById('itemsBody').appendChild(row);
    itemIndex++;
    updateSummary();
}

function updateBatchPrice(idx, select) {
    const option = select.options[select.selectedIndex];
    if (option.value) {
        const price = parseFloat(option.dataset.price) || 0;
        document.getElementById(`price_${idx}`).value = price;
        document.getElementById(`priceDisplay_${idx}`).textContent = price.toLocaleString() + 'đ';
        updateRowTotal(idx);
    }
}

function updateRowTotal(idx) {
    const price = parseFloat(document.getElementById(`price_${idx}`).value) || 0;
    const qty = parseInt(document.querySelector(`input[name="items[${idx}][So_luong]"]`).value) || 0;
    document.getElementById(`rowTotal_${idx}`).textContent = (price * qty).toLocaleString() + 'đ';
    updateSummary();
}

function updateSummary() {
    let total = 0, count = 0, qty = 0;
    document.querySelectorAll('[id^="rowTotal_"]').forEach(el => {
        total += parseInt(el.textContent.replace(/\D/g, '')) || 0;
        count++;
    });
    document.querySelectorAll('input[name$="[So_luong]"]').forEach(el => {
        qty += parseInt(el.value) || 0;
    });
    document.getElementById('totalValue').textContent = total.toLocaleString() + 'đ';
    document.getElementById('summaryTotal').textContent = total.toLocaleString() + 'đ';
    document.getElementById('itemCount').textContent = count;
    document.getElementById('totalQty').textContent = qty;
}

function removeRow(idx) {
    document.getElementById(`row_${idx}`)?.remove();
    updateSummary();
    if (document.getElementById('itemsBody').children.length === 0) {
        document.getElementById('itemsBody').innerHTML = `<tr id="noItemRow"><td colspan="6" style="text-align: center; padding: 40px 20px;"><i class="fas fa-box-open" style="font-size: 36px; color: var(--admin-text-light); display: block; margin-bottom: 12px;"></i><p style="color: var(--admin-text-muted); margin: 0;">Tìm và thêm sản phẩm ở trên</p></td></tr>`;
    }
}

document.getElementById('disposalForm').addEventListener('submit', function(e) {
    if (document.querySelectorAll('#itemsBody tr:not(#noItemRow)').length === 0) {
        e.preventDefault();
        showNotification('Vui lòng thêm ít nhất 1 sản phẩm', 'error');
    }
});

// Auto-add product from prefill data (từ trang cảnh báo hết hạn)
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($prefill_product)): ?>
    // Add prefilled product automatically (từ trang cảnh báo hết hạn)
    const product = {
        ID_sp: <?= (int)$prefill_product['ID_sp'] ?>,
        Ma_hien_thi: '<?= addslashes($prefill_product['Ma_hien_thi'] ?? '') ?>',
        Ten: '<?= addslashes($prefill_product['Ten'] ?? '') ?>',
        Don_vi_tinh: '<?= addslashes($prefill_product['Don_vi_tinh'] ?? '') ?>',
        So_luong_ton: <?= (int)($prefill_product['So_luong_ton'] ?? 0) ?>,
        Gia_nhap: <?= (int)($prefill_price ?? 0) ?>
    };
    
    // Create batches array with prefilled batch info
    const prefillBatches = [];
    <?php if (!empty($prefill_batch_code)): ?>
    prefillBatches.push({
        ID_chi_tiet_nhap: <?= (int)($prefill_batch_id ?? 0) ?>,
        Ma_phieu_nhap: '<?= addslashes($prefill_batch_code ?? '') ?>',
        So_luong_con: <?= (int)$prefill_quantity ?>,
        Don_gia_nhap: <?= (int)($prefill_price ?? 0) ?>
    });
    <?php endif; ?>
    
    selectedProduct = product;
    addProductRow(product, prefillBatches, <?= (int)$prefill_quantity ?>);
    
    // Show success notification
    showNotification('Đã tự động thêm sản phẩm "' + product.Ten + '" - Số lượng: <?= (int)$prefill_quantity ?>', 'success');
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>
