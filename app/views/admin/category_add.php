<?php
/**
 * ADMIN - THÊM DANH MỤC MỚI
 * Standardized UI - Theme Woodland
 */

$parents = $parents ?? [];
$csrf_token = $csrf_token ?? '';
?>
<?php include __DIR__ . '/layouts/header.php'; ?>
<link rel="stylesheet" href="<?= asset('css/admin-modern.css') ?>">

<div class="admin-modern">
    <div class="admin-modern-container">
        <!-- Breadcrumb -->
        <div class="admin-breadcrumb">
            <a href="<?= BASE_URL ?>/">Trang chủ</a>
            <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
            <a href="<?= BASE_URL ?>/admin/categories">Danh mục</a>
            <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
            <span class="current">Thêm mới</span>
        </div>
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Thêm Danh Mục Mới</h1>
                <p class="admin-page-subtitle">Tạo danh mục sản phẩm mới cho siêu thị</p>
            </div>
            <div class="admin-header-actions">
                <a href="<?= BASE_URL ?>/admin/categories" class="btn-admin-secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span>Quay lại</span>
                </a>
            </div>
        </div>
        
        <!-- Form Card -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Thông tin danh mục</h3>
            </div>
            <div class="admin-card-body">
                <form action="<?= BASE_URL ?>/admin/category-save" method="POST" id="categoryForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--admin-text);">
                            Tên danh mục <span style="color: red;">*</span>
                        </label>
                        <input type="text" name="ten_danh_muc" class="form-control" 
                               placeholder="VD: Đồ uống, Bánh kẹo..." required
                               style="width: 100%; padding: 12px 16px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 14px;">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--admin-text);">
                            Danh mục cha
                        </label>
                        <select name="danh_muc_cha" class="form-control"
                                style="width: 100%; padding: 12px 16px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 14px;">
                            <option value="">-- Không có (Danh mục gốc) --</option>
                            <?php foreach ($parents as $parent): ?>
                                <option value="<?= (int)$parent['ID_danh_muc'] ?>">
                                    <?= htmlspecialchars($parent['Ten_danh_muc']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--admin-text);">
                            Mô tả
                        </label>
                        <textarea name="mo_ta" class="form-control" rows="4"
                                  placeholder="Nhập mô tả ngắn về danh mục..."
                                  style="width: 100%; padding: 12px 16px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 14px; resize: vertical;"></textarea>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px; color: var(--admin-text);">
                            Trạng thái
                        </label>
                        <select name="trang_thai" class="form-control"
                                style="width: 100%; padding: 12px 16px; border: 1px solid var(--admin-border); border-radius: 8px; font-size: 14px;">
                            <option value="active" selected>Hoạt động</option>
                            <option value="inactive">Tạm ẩn</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 12px; padding-top: 16px; border-top: 1px solid var(--admin-border);">
                        <button type="submit" class="btn-admin-primary">
                            <i class="fas fa-save"></i>
                            <span>Lưu danh mục</span>
                        </button>
                        <a href="<?= BASE_URL ?>/admin/categories" class="btn-admin-secondary">
                            <span>Hủy</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>
