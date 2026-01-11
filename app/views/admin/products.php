<?php
/**
 * =============================================================================
 * ADMIN - QUẢN LÝ SẢN PHẨM (PRODUCT MANAGEMENT)
 * =============================================================================
 * 
 * View: admin/products.php
 * Giao diện quản lý sản phẩm - Standardized UI
 * Theme: #7BC043 (Lime Green) + #2D3657 (Navy)
 */

// Nhận dữ liệu từ controller
$products = $data['products'] ?? [];
$categories = $data['categories'] ?? [];
$filters = $data['filters'] ?? [];
$pagination = $data['pagination'] ?? [];
$csrf_token = $data['csrf_token'] ?? '';
$total_products = $data['total_products'] ?? 0;

// Helper: Render category options với tree structure
function renderCategoryOptions(array $cats, $selected = '') {
    foreach ($cats as $cat) {
        if (!empty($cat['children'])) {
            echo '<optgroup label="📁 ' . htmlspecialchars($cat['Ten_danh_muc']) . '">';
            foreach ($cat['children'] as $child) {
                $sel = ($selected !== '' && $selected == $child['ID_danh_muc']) ? 'selected' : '';
                echo '<option value="' . $child['ID_danh_muc'] . '" ' . $sel . '>';
                echo '&nbsp;&nbsp;&nbsp;📄 ' . htmlspecialchars($child['Ten_danh_muc']);
                echo '</option>';
            }
            echo '</optgroup>';
        } else {
            $sel = ($selected !== '' && $selected == $cat['ID_danh_muc']) ? 'selected' : '';
            echo '<option value="' . $cat['ID_danh_muc'] . '" ' . $sel . '>';
            echo '📦 ' . htmlspecialchars($cat['Ten_danh_muc']);
            echo '</option>';
        }
    }
}

// Helper: Get stock status mapping to admin-modern utility classes
function getStockStatus($quantity) {
    if ($quantity <= 0) {
        // status-badge alert-item.danger style
        return ['class' => 'status-badge danger', 'text' => 'Hết hàng'];
    }
    if ($quantity < 30) {
        return ['class' => 'status-badge warning', 'text' => 'Sắp hết'];
    }
    return ['class' => 'status-badge success', 'text' => 'Còn hàng'];
}
?>
<?php include __DIR__ . '/layouts/header.php'; ?>
<link rel="stylesheet" href="<?= asset('css/admin-modern.css') ?>">

<div class="admin-modern">
    <div class="admin-modern-container">
        <!-- Breadcrumb -->
        <div class="admin-breadcrumb">
            <a href="<?= BASE_URL ?>/">Trang chủ</a>
            <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
            <span class="current">Quản lý sản phẩm</span>
        </div>

        <?php include __DIR__ . '/components/warehouse_tabs.php'; ?>

        <!-- Page Header with Actions -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Quản lý Sản phẩm</h1>
                <p class="admin-page-subtitle">Tổng cộng: <strong><?= number_format($total_products) ?></strong> sản phẩm</p>
            </div>
            <div class="admin-header-actions">
                <a href="<?= BASE_URL ?>/admin/export-products" class="btn-admin-secondary">
                    <i class="fas fa-file-download"></i>
                    <span>Export Excel</span>
                </a>
                <button onclick="openAddModal()" class="btn-admin-primary">
                    <i class="fas fa-plus"></i>
                    <span>Thêm sản phẩm</span>
                </button>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="admin-card mb-4" style="margin-bottom: 24px;">
            <div class="admin-card-body">
                <form method="GET" action="<?= BASE_URL ?>/admin/products" class="admin-filter-bar" style="flex-wrap: wrap; gap: 16px;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label>Tìm kiếm</label>
                        <input 
                            type="text" 
                            name="search" 
                            class="form-control" 
                            placeholder="Tìm theo tên, MSP..."
                            value="<?= htmlspecialchars($filters['keyword'] ?? '') ?>"
                        >
                    </div>
                    
                    <div class="form-group" style="min-width: 200px;">
                        <label>Danh mục</label>
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">Tất cả danh mục</option>
                            <?php renderCategoryOptions($categories, $filters['category_id'] ?? ''); ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="min-width: 150px;">
                        <label>Trạng thái</label>
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Tất cả trạng thái</option>
                            <option value="active" <?= (($filters['status'] ?? '') == 'active') ? 'selected' : '' ?>>Đang bán</option>
                            <option value="inactive" <?= (($filters['status'] ?? '') == 'inactive') ? 'selected' : '' ?>>Ngừng bán</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 8px; align-items: flex-end;">
                        <button type="submit" class="btn-admin-primary">
                            <i class="fas fa-filter"></i> Lọc
                        </button>
                        <a href="<?= BASE_URL ?>/admin/products" class="btn-admin-secondary">Xóa</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="admin-card">
            <div class="admin-card-body no-padding">
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">
                                    <input type="checkbox" id="selectAll" style="cursor: pointer;">
                                </th>
                                <th style="width: 70px;">Ảnh</th>
                                <th style="min-width: 200px;">Tên sản phẩm & MSP</th>
                                <th>Danh mục</th>
                                <th style="text-align: right;">Giá bán</th>
                                <th style="text-align: center;">Tồn kho</th>
                                <th>Trạng thái</th>
                                <th style="text-align: center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 60px 20px;">
                                        <i class="fas fa-box-open" style="font-size: 48px; color: var(--admin-text-light); margin-bottom: 16px; display: block;"></i>
                                        <p style="color: var(--admin-text-muted);">Không có sản phẩm nào</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <?php $stockStatus = getStockStatus($product['So_luong_ton']); ?>
                                    <tr>
                                        <td style="text-align: center;">
                                            <input type="checkbox" class="product-checkbox" value="<?= $product['ID_sp'] ?>">
                                        </td>
                                        <td>
                                            <?php if ($product['Hinh_anh']): ?>
                                                <img src="<?= asset('img/products/' . $product['Hinh_anh']) ?>" 
                                                     alt="<?= htmlspecialchars($product['Ten']) ?>"
                                                     style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover; background: #f8fafc; border: 1px solid #e2e8f0;"
                                                     onerror="this.src='<?= asset('img/placeholder-product.png') ?>'">
                                            <?php else: ?>
                                                <div style="width: 44px; height: 44px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center;">📦</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-weight: 500; color: var(--text-dark); margin-bottom: 2px;">
                                                <?= htmlspecialchars($product['Ten']) ?>
                                            </div>
                                            <div style="font-size: 12px; color: var(--admin-text-muted);">
                                                MSP: <?= htmlspecialchars($product['Ma_hien_thi'] ?? 'N/A') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 12px; padding: 2px 8px; background: #f1f5f9; border-radius: 12px; color: #475569;">
                                                <?= htmlspecialchars($product['Ten_danh_muc'] ?? 'Chưa phân loại') ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right; font-weight: 600; color: var(--text-dark);">
                                            <?= number_format($product['Gia_tien']) ?>₫
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="<?= $stockStatus['class'] ?>">
                                                <span class="dot"></span>
                                                <?= $product['So_luong_ton'] ?> - <?= $stockStatus['text'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($product['Trang_thai'] == 'active'): ?>
                                                <span class="status-badge success">Đang bán</span>
                                            <?php else: ?>
                                                <span class="status-badge normal">Ngừng bán</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; justify-content: center; gap: 8px;">
                                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($product)) ?>)" 
                                                        class="btn-icon" title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="openDeleteModal(<?= $product['ID_sp'] ?>, '<?= htmlspecialchars($product['Ten']) ?>', '<?= htmlspecialchars($product['Ma_hien_thi']) ?>')" 
                                                        class="btn-icon" style="color: var(--admin-danger);" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (!empty($pagination) && isset($pagination['total']) && $pagination['total'] > 0): ?>
                <div class="admin-card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 13px; color: var(--admin-text-muted);">
                        Hiển thị <strong><?= $pagination['from'] ?? 1 ?></strong> đến <strong><?= $pagination['to'] ?? 0 ?></strong> 
                        trong tổng <strong><?= number_format($pagination['total']) ?></strong> sản phẩm
                    </div>
                    <div class="pagination">
                        <?php 
                        $queryParams = [];
                        if (!empty($filters['category_id'])) $queryParams['category'] = $filters['category_id'];
                        if (!empty($filters['keyword'])) $queryParams['search'] = $filters['keyword'];
                        if (!empty($filters['status'])) $queryParams['status'] = $filters['status'];
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
</div>


<!-- Import Modal -->
<div id="importModal" class="modal-overlay hidden">
    <div class="admin-card modal-content" style="max-width: 500px;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Import sản phẩm từ Excel</h3>
            <button class="btn-icon" onclick="closeImportModal()"><i class="fas fa-times"></i></button>
        </div>
        <form action="<?= BASE_URL ?>/admin/import-products" method="POST" enctype="multipart/form-data" class="admin-card-body">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <div class="form-group">
                <label>Chọn file Excel (.xlsx, .xls)</label>
                <input type="file" name="file" class="form-control" accept=".xlsx, .xls" required>
                <small style="display: block; margin-top: 8px; color: var(--admin-text-muted);">
                    Tải file mẫu <a href="<?= BASE_URL ?>/admin/download-sample-import" style="color: var(--admin-primary);">tại đây</a>
                </small>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn-admin-secondary" onclick="closeImportModal()">Hủy</button>
                <button type="submit" class="btn-admin-primary">Upload</button>
            </div>
        </form>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="productModal" class="modal-overlay hidden">
    <div class="admin-card modal-content" style="max-width: 800px;">
        <div class="admin-card-header">
            <h3 class="admin-card-title" id="modalTitle">Thêm Sản phẩm mới</h3>
            <button class="btn-icon" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <form id="productForm" action="<?= BASE_URL ?>/admin/product-save" method="POST" enctype="multipart/form-data" class="admin-card-body">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="id" id="productId">
            
            <div style="display: grid; grid-template-columns: 1fr 200px; gap: 24px;">
                <div style="display: grid; gap: 16px;">
                    <div class="form-group">
                        <label>Tên sản phẩm <span style="color: var(--admin-danger);">*</span></label>
                        <input type="text" name="ten" id="productName" class="form-control" required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label>Danh mục <span style="color: var(--admin-danger);">*</span></label>
                            <select name="danh_muc_id" id="productCategory" class="form-select" required>
                                <option value="">-- Chọn danh mục --</option>
                                <?php renderCategoryOptions($categories); ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Mã hiển thị (SKU)</label>
                            <input type="text" name="ma_hien_thi" id="productSku" class="form-control" placeholder="Tự động nếu để trống">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label>Giá bán (VNĐ) <span style="color: var(--admin-danger);">*</span></label>
                            <input type="number" name="gia_tien" id="productPrice" class="form-control" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Giá nhập (VNĐ)</label>
                            <input type="number" name="gia_nhap" id="productCost" class="form-control" min="0">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label>Số lượng tồn</label>
                            <input type="number" name="so_luong" id="productStock" class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label>Đơn vị tính</label>
                            <input type="text" name="don_vi" id="productUnit" class="form-control" placeholder="Cái, Hộp, Kg...">
                        </div>
                    </div>

                    <div class="form-group">
                         <label>Trạng thái</label>
                         <select name="trang_thai" id="productStatus" class="form-select">
                             <option value="active">Đang bán</option>
                             <option value="inactive">Ngừng bán</option>
                         </select>
                    </div>
                </div>
                
                <div>
                    <div class="form-group">
                        <label>Ảnh đại diện</label>
                        <div style="border: 2px dashed #e2e8f0; border-radius: 8px; padding: 16px; text-align: center; cursor: pointer; position: relative;" onclick="document.getElementById('productImage').click()">
                            <input type="file" name="hinh_anh" id="productImage" class="hidden" accept="image/*" onchange="previewImage(this)">
                            <img id="imagePreview" src="<?= asset('img/placeholder-product.png') ?>" style="width: 100%; height: 150px; object-fit: contain; margin-bottom: 8px;">
                            <span style="font-size: 13px; color: var(--admin-primary); font-weight: 500;">Chọn ảnh</span>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-top: 16px;">
                        <label>Mô tả ngắn</label>
                        <textarea name="mo_ta" id="productDesc" class="form-control" rows="4"></textarea>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--admin-border);">
                <button type="button" class="btn-admin-secondary" onclick="closeModal()">Hủy</button>
                <button type="submit" class="btn-admin-primary">Lưu sản phẩm</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-overlay hidden">
    <div class="admin-card modal-content" style="max-width: 400px;">
        <div class="admin-card-body" style="text-align: center; padding: 32px 24px;">
            <div style="width: 64px; height: 64px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 8px;">Xác nhận xóa?</h3>
            <p style="color: #6b7280; margin-bottom: 24px;">
                Bạn có chắc chắn muốn xóa sản phẩm <strong id="deleteProductName"></strong>?<br>
                Hành động này không thể hoàn tác.
            </p>
            
            <form action="<?= BASE_URL ?>/admin/product-delete" method="POST" style="display: flex; gap: 12px; justify-content: center;">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="id" id="deleteProductId">
                <button type="button" class="btn-admin-secondary" onclick="closeDeleteModal()">Hủy</button>
                <button type="submit" class="btn-admin-primary" style="background: var(--admin-danger);">Xóa ngay</button>
            </form>
        </div>
    </div>
</div>

<script>
// Modal Functions
function openImportModal() {
    document.getElementById('importModal').classList.remove('hidden');
}

function closeImportModal() {
    document.getElementById('importModal').classList.add('hidden');
}

function openAddModal() {
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('modalTitle').textContent = 'Thêm Sản phẩm mới';
    document.getElementById('imagePreview').src = '<?= asset('img/placeholder-product.png') ?>';
    document.getElementById('productModal').classList.remove('hidden');
}

function openEditModal(product) {
    document.getElementById('productId').value = product.ID_sp;
    document.getElementById('productName').value = product.Ten;
    document.getElementById('productCategory').value = product.ID_danh_muc;
    document.getElementById('productSku').value = product.Ma_hien_thi;
    document.getElementById('productPrice').value = product.Gia_tien;
    document.getElementById('productCost').value = product.Gia_nhap || '';
    document.getElementById('productStock').value = product.So_luong_ton;
    document.getElementById('productUnit').value = product.Don_vi_tinh;
    document.getElementById('productDesc').value = product.Mo_ta_sp || product.Mo_ta || '';
    document.getElementById('productStatus').value = product.Trang_thai;
    
    if (product.Hinh_anh) {
        document.getElementById('imagePreview').src = '<?= asset('img/products/') ?>' + product.Hinh_anh;
    } else {
        document.getElementById('imagePreview').src = '<?= asset('img/placeholder-product.png') ?>';
    }
    
    document.getElementById('modalTitle').textContent = 'Cập nhật Sản phẩm';
    document.getElementById('productModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('productModal').classList.add('hidden');
}

function openDeleteModal(id, name) {
    document.getElementById('deleteProductId').value = id;
    document.getElementById('deleteProductName').textContent = name;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Image Preview
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.add('hidden');
    }
}

// Select All Checkbox
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>