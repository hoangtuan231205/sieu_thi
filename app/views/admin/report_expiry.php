<?php
/**
 * ADMIN - CẢNH BÁO HẾT HẠN
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
            <span class="current">Cảnh báo hết hạn</span>
        </div>
        
        <!-- Warehouse Tabs Navigation -->
        <?php include __DIR__ . '/components/warehouse_tabs.php'; ?>
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Cảnh báo Hết hạn</h1>
                <p class="admin-page-subtitle">Theo dõi sản phẩm sắp hết hạn sử dụng để xử lý kịp thời</p>
            </div>
            <?php if (!empty($expiring_batches)): ?>
            <div class="admin-header-actions">
                <a href="<?= BASE_URL ?>/admin/disposal-add" class="btn-admin-primary" style="background: var(--admin-danger);">
                    <i class="fas fa-trash-alt"></i>
                    <span>Tạo phiếu hủy</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Stat Cards -->
        <div class="stat-cards-row">
            <!-- Đã hết hạn -->
            <div class="stat-card" style="border-left: 4px solid var(--admin-danger);">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Đã hết hạn</h4>
                        <p class="stat-card-value" style="color: var(--admin-danger);"><?= $stats['expired'] ?? 0 ?></p>
                    </div>
                    <div class="stat-card-icon danger">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge danger">
                        <i class="fas fa-times"></i>
                        Cần hủy ngay
                    </span>
                </div>
            </div>
            
            <!-- Trong 7 ngày -->
            <div class="stat-card" style="border-left: 4px solid var(--admin-warning);">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Trong 7 ngày</h4>
                        <p class="stat-card-value" style="color: var(--admin-warning);"><?= $stats['in_7_days'] ?? 0 ?></p>
                    </div>
                    <div class="stat-card-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Khẩn cấp
                    </span>
                </div>
            </div>
            
            <!-- Trong 30 ngày -->
            <div class="stat-card" style="border-left: 4px solid var(--admin-info);">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Trong 30 ngày</h4>
                        <p class="stat-card-value" style="color: var(--admin-info);"><?= $stats['in_30_days'] ?? 0 ?></p>
                    </div>
                    <div class="stat-card-icon info">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span style="font-size: 13px; color: var(--admin-text-muted);">Cần theo dõi</span>
                </div>
            </div>
            
            <!-- Giá trị tồn -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Giá trị tồn kho</h4>
                        <p class="stat-card-value"><?= number_format($stats['total_value'] ?? 0, 0, ',', '.') ?>đ</p>
                    </div>
                    <div class="stat-card-icon primary">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span style="font-size: 13px; color: var(--admin-text-muted);">Hàng sắp hết hạn</span>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Main Table -->
            <div class="col-lg-8">
                <!-- Filter Bar -->
                <form method="GET" class="admin-filter-bar">
                    <div class="form-group">
                        <label>Hiển thị</label>
                        <select class="form-select" name="days">
                            <option value="7" <?= ($days ?? 30) == 7 ? 'selected' : '' ?>>Trong 7 ngày</option>
                            <option value="14" <?= ($days ?? 30) == 14 ? 'selected' : '' ?>>Trong 14 ngày</option>
                            <option value="30" <?= ($days ?? 30) == 30 ? 'selected' : '' ?>>Trong 30 ngày</option>
                            <option value="0" <?= ($days ?? 30) == 0 ? 'selected' : '' ?>>Tất cả</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Danh mục</label>
                        <select class="form-select" name="category">
                            <option value="">Tất cả</option>
                            <?php foreach ($categories ?? [] as $cat): ?>
                                <option value="<?= $cat['ID_danh_muc'] ?>" <?= ($category_id ?? '') == $cat['ID_danh_muc'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['Ten_danh_muc']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-admin-secondary">
                        <i class="fas fa-filter"></i>
                        <span>Lọc</span>
                    </button>
                </form>
                
                <!-- Batches Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Danh sách lô hàng</h3>
                        <span style="font-size: 13px; color: var(--admin-text-muted);">
                            <?= count($expiring_batches ?? []) ?> lô
                        </span>
                    </div>
                    <div class="admin-card-body no-padding">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Lô nhập</th>
                                    <th style="text-align: center;">Còn lại</th>
                                    <th style="text-align: right;">Giá trị</th>
                                    <th style="text-align: center;">HSD</th>
                                    <th style="text-align: center;">Mức</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($expiring_batches)): ?>
                                    <?php foreach ($expiring_batches as $batch): ?>
                                        <?php
                                        $level = $batch['Muc_canh_bao'] ?? 'BINH_THUONG';
                                        $levelClass = ['DA_HET_HAN' => 'danger', 'TRONG_7_NGAY' => 'warning', 'TRONG_30_NGAY' => 'processing'][$level] ?? '';
                                        $levelText = ['DA_HET_HAN' => 'Hết hạn', 'TRONG_7_NGAY' => '7 ngày', 'TRONG_30_NGAY' => '30 ngày'][$level] ?? '';
                                        ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    <img src="<?= asset('img/products/' . ($batch['Hinh_anh'] ?: 'placeholder.png')) ?>" 
                                                         onerror="this.src='<?= asset('img/placeholder-product.png') ?>'" 
                                                         style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover; background: #f8fafc;">
                                                    <div>
                                                        <div style="font-weight: 500;"><?= htmlspecialchars($batch['Ten_SP']) ?></div>
                                                        <div style="font-size: 12px; color: var(--admin-text-muted);"><?= htmlspecialchars($batch['Ma_SP']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="type-badge huy"><?= htmlspecialchars($batch['Ma_phieu_nhap']) ?></span>
                                                <div style="font-size: 12px; color: var(--admin-text-muted); margin-top: 4px;">
                                                    <?= date('d/m/Y', strtotime($batch['Ngay_nhap'])) ?>
                                                </div>
                                            </td>
                                            <td style="text-align: center; font-weight: 600;"><?= number_format($batch['So_luong_con']) ?></td>
                                            <td style="text-align: right; font-weight: 600;"><?= number_format($batch['Gia_tri_ton'], 0, ',', '.') ?>đ</td>
                                            <td style="text-align: center; <?= $level == 'DA_HET_HAN' ? 'color: var(--admin-danger); font-weight: 600;' : '' ?>">
                                                <?= date('d/m/Y', strtotime($batch['Ngay_het_han'])) ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="status-badge <?= $levelClass ?>">
                                                    <span class="dot"></span>
                                                    <?= $levelText ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 60px 20px;">
                                            <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                                                <i class="fas fa-check-circle" style="font-size: 36px; color: var(--admin-success);"></i>
                                            </div>
                                            <h4 style="font-size: 18px; font-weight: 600; margin: 0 0 8px 0;">Tuyệt vời!</h4>
                                            <p style="color: var(--admin-text-muted); margin: 0;">Không có lô hàng nào sắp hết hạn</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar Alerts -->
            <div class="col-lg-4">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Cảnh báo tồn kho</h3>
                        <?php $urgentCount = ($stats['expired'] ?? 0) + ($stats['in_7_days'] ?? 0); ?>
                        <?php if ($urgentCount > 0): ?>
                        <span style="padding: 4px 10px; background: rgba(239, 68, 68, 0.1); color: var(--admin-danger); font-size: 12px; font-weight: 600; border-radius: 20px;">
                            <?= $urgentCount ?> Khẩn cấp
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="admin-card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php if (!empty($expiring_batches)): ?>
                            <?php 
                            $shown = 0;
                            foreach ($expiring_batches as $batch): 
                                if ($shown >= 10) break;
                                $level = $batch['Muc_canh_bao'] ?? 'BINH_THUONG';
                                $alertClass = $level == 'DA_HET_HAN' ? 'critical' : ($level == 'TRONG_7_NGAY' ? 'warning' : 'normal');
                                $shown++;
                            ?>
                            <div class="alert-card <?= $alertClass ?>">
                                <img src="<?= asset('img/products/' . ($batch['Hinh_anh'] ?: 'placeholder.png')) ?>" 
                                     onerror="this.src='<?= asset('img/placeholder-product.png') ?>'" 
                                     class="alert-card-img">
                                <div class="alert-card-info">
                                    <h5><?= htmlspecialchars($batch['Ten_SP']) ?></h5>
                                    <?php if ($level == 'DA_HET_HAN'): ?>
                                        <p class="critical">Đã hết hạn!</p>
                                    <?php elseif ($level == 'TRONG_7_NGAY'): ?>
                                        <p class="warning">Còn <?= $batch['So_ngay_con'] ?> ngày</p>
                                    <?php else: ?>
                                        <p class="muted">Còn <?= $batch['So_ngay_con'] ?> ngày</p>
                                    <?php endif; ?>
                                </div>
                                <?php if ($level == 'DA_HET_HAN'): ?>
                                    <?php 
                                    $price = ($batch['So_luong_con'] > 0) ? round($batch['Gia_tri_ton'] / $batch['So_luong_con']) : 0;
                                    $params = http_build_query([
                                        'product_id' => $batch['ID_sp'],
                                        'batch_id' => $batch['ID_lo_hang'] ?? '',
                                        'batch_code' => $batch['Ma_phieu_nhap'] ?? '',
                                        'quantity' => $batch['So_luong_con'],
                                        'price' => $price,
                                        'reason' => 'Sản phẩm đã hết hạn sử dụng'
                                    ]);
                                    ?>
                                    <a href="<?= BASE_URL ?>/admin/disposal-add?<?= $params ?>" class="alert-card-btn">
                                        Hủy
                                    </a>
                                <?php elseif ($level == 'TRONG_7_NGAY'): ?>
                                    <a href="javascript:void(0)" onclick="fetchAndEditProduct(<?= $batch['ID_sp'] ?>)" class="alert-card-btn warning">
                                        Giảm giá
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px 20px;">
                                <i class="fas fa-box-open" style="font-size: 48px; color: var(--admin-text-light); display: block; margin-bottom: 16px;"></i>
                                <p style="color: var(--admin-text-muted);">Không có cảnh báo</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="productModal" class="modal-overlay hidden">
    <div class="admin-card modal-content" style="max-width: 800px;">
        <div class="admin-card-header">
            <h3 class="admin-card-title">Cập nhật Sản phẩm</h3>
            <button class="btn-icon" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <form id="productForm" action="<?= BASE_URL ?>/admin/product-save" method="POST" enctype="multipart/form-data" class="admin-card-body">
            <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
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
                                <?php foreach ($categories ?? [] as $cat): ?>
                                    <option value="<?= $cat['ID_danh_muc'] ?>"><?= htmlspecialchars($cat['Ten_danh_muc']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Mã hiển thị (SKU)</label>
                            <input type="text" name="ma_hien_thi" id="productSku" class="form-control">
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
                            <input type="number" name="so_luong" id="productStock" class="form-control" readonly style="background: #f1f5f9;">
                        </div>
                        <div class="form-group">
                            <label>Đơn vị tính</label>
                            <input type="text" name="don_vi" id="productUnit" class="form-control">
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

<script>
function fetchAndEditProduct(id) {
    if(!id) return;
    
    // Show modal loading state? Or just wait
    fetch('<?= BASE_URL ?>/admin/get-product-detail?id=' + id, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                openEditModal(data.product);
            } else {
                // Check for 'message' or 'error'
                let msg = data.message || data.error || 'Lỗi không xác định';
                alert('Không thể tải thông tin sản phẩm: ' + msg);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Có lỗi xảy ra khi tải thông tin sản phẩm');
        });
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
    
    document.getElementById('productModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('productModal').classList.add('hidden');
}

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
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>
