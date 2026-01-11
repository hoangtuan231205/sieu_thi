<?php
/**
 * ADMIN - QUẢN LÝ DANH MỤC
 * Standardized UI - Theme Woodland
 * Reference: admin-modern.css
 */

// Data from controller
$categories = $categories ?? [];
$pagination = $pagination ?? ['total' => 0, 'current_page' => 1, 'last_page' => 1];
$filters = $filters ?? [];

// Icon mapping based on category name patterns
function getCategoryIcon($name) {
    $name = mb_strtolower($name);
    $icons = [
        'đồ uống' => ['fa-glass-whiskey', 'orange'],
        'nước' => ['fa-glass-whiskey', 'orange'],
        'sữa' => ['fa-glass-whiskey', 'blue'],
        'thực phẩm' => ['fa-apple-alt', 'green'],
        'rau' => ['fa-leaf', 'green'],
        'trái cây' => ['fa-apple-alt', 'green'],
        'thịt' => ['fa-drumstick-bite', 'red'],
        'cá' => ['fa-fish', 'blue'],
        'bánh' => ['fa-cookie', 'yellow'],
        'kẹo' => ['fa-candy-cane', 'pink'],
        'gia dụng' => ['fa-home', 'rose'],
        'hóa phẩm' => ['fa-soap', 'purple'],
        'chăm sóc' => ['fa-heart', 'pink'],
        'gia vị' => ['fa-pepper-hot', 'red'],
        'đông lạnh' => ['fa-snowflake', 'cyan'],
        'đóng gói' => ['fa-box', 'amber'],
    ];
    
    foreach ($icons as $pattern => $config) {
        if (strpos($name, $pattern) !== false) {
            return $config;
        }
    }
    return ['fa-folder', 'blue']; // default
}
?>
<?php include __DIR__ . '/layouts/header.php'; ?>
<link rel="stylesheet" href="<?= asset('css/admin-modern.css') ?>">

<style>
/* Custom Grid Styles that extend admin-modern */
.category-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    margin-bottom: 24px;
}

@media (max-width: 1200px) {
    .category-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
    .category-grid { grid-template-columns: 1fr; }
}

.category-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 24px;
    border: 1px solid var(--admin-border);
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.category-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-2px);
    border-color: var(--admin-primary);
}

.category-card.inactive {
    opacity: 0.8;
    background: #fafafa;
}

.category-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 16px;
}

/* Icon Colors using variables where possible or custom matching */
.category-icon.orange { background: rgba(249, 115, 22, 0.1); color: #f97316; }
.category-icon.green { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
.category-icon.blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.category-icon.purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.category-icon.rose { background: rgba(244, 63, 94, 0.1); color: #f43f5e; }
.category-icon.yellow { background: rgba(234, 179, 8, 0.1); color: #eab308; }
.category-icon.pink { background: rgba(236, 72, 153, 0.1); color: #ec4899; }
.category-icon.red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
.category-icon.cyan { background: rgba(6, 182, 212, 0.1); color: #06b6d4; }
.category-icon.amber { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

.category-card-add {
    background: #f8fafc;
    border: 2px dashed var(--admin-border);
    border-radius: var(--border-radius);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    min-height: 250px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    color: var(--admin-text-light);
}

.category-card-add:hover {
    border-color: var(--admin-primary);
    background: rgba(123, 192, 67, 0.05);
    color: var(--admin-primary);
}
</style>

<div class="admin-modern">
    <div class="admin-modern-container">
        <!-- Breadcrumb -->
        <div class="admin-breadcrumb">
            <a href="<?= BASE_URL ?>/">Trang chủ</a>
            <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
            <span class="current">Quản lý danh mục</span>
        </div>
        
        <?php include __DIR__ . '/components/management_tabs.php'; ?>
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Danh Mục Sản Phẩm</h1>
                <p class="admin-page-subtitle">Quản lý và tổ chức danh mục hàng hóa của siêu thị.</p>
            </div>
            <div class="admin-header-actions">
                <a href="<?= BASE_URL ?>/admin/category-add" class="btn-admin-primary">
                    <i class="fas fa-plus"></i>
                    <span>Thêm danh mục</span>
                </a>
            </div>
        </div>
        
        <!-- Toolbar -->
        <div class="admin-card mb-4" style="margin-bottom: 24px;">
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-bar" style="justify-content: space-between;">
                    <div class="form-group" style="flex: 1; max-width: 400px;">
                        <div style="position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--admin-text-light);"></i>
                            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm danh mục..." 
                                   value="<?= htmlspecialchars($filters['keyword'] ?? '') ?>"
                                   style="padding-left: 36px;">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Card Grid -->
        <div class="category-grid">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $index => $cat): ?>
                    <?php 
                    $iconConfig = getCategoryIcon($cat['Ten_danh_muc']);
                    $isActive = ($cat['Trang_thai'] ?? 'active') === 'active';
                    $productCount = $cat['So_san_pham'] ?? $cat['product_count'] ?? 0;
                    ?>
                    <div class="category-card <?= $isActive ? '' : 'inactive' ?>">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div class="category-icon <?= $iconConfig[1] ?>">
                                <i class="fas <?= $iconConfig[0] ?>"></i>
                            </div>
                            <?php if ($isActive): ?>
                                <span class="status-badge success">Hoạt động</span>
                            <?php else: ?>
                                <span class="status-badge normal">Tạm ẩn</span>
                            <?php endif; ?>
                        </div>
                        
                        <h3 style="font-size: 18px; font-weight: 700; color: var(--text-dark); margin-bottom: 8px;">
                            <?= htmlspecialchars($cat['Ten_danh_muc']) ?>
                        </h3>
                        <p style="font-size: 14px; color: var(--admin-text-muted); margin-bottom: 20px; flex-grow: 1; line-height: 1.5;">
                            <?= htmlspecialchars($cat['Mo_ta'] ?? 'Chưa có mô tả') ?>
                        </p>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--admin-border); padding-top: 16px; margin-top: auto;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                    <?= number_format($productCount) ?> SP
                                </span>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <a href="<?= BASE_URL ?>/admin/category-edit/<?= $cat['ID_danh_muc'] ?>" class="btn-icon" title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="deleteCategory(<?= $cat['ID_danh_muc'] ?>)" class="btn-icon" style="color: var(--admin-danger);" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Add New Card -->
            <a href="<?= BASE_URL ?>/admin/category-add" class="category-card-add">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                    <i class="fas fa-plus"></i>
                </div>
                <h4 style="font-weight: 700; margin-bottom: 4px;">Tạo danh mục mới</h4>
                <p style="font-size: 13px; color: var(--admin-text-muted);">Thêm danh mục sản phẩm vào kho</p>
            </a>
        </div>
        
        <!-- Pagination inside Card is not suitable for Grid, so separate block -->
        <?php if (($pagination['last_page'] ?? 1) > 1): ?>
        <div class="admin-card">
            <div class="admin-card-footer" style="border-top: none; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 13px; color: var(--admin-text-muted);">
                    Hiển thị <strong><?= count($categories) ?></strong> / <?= $pagination['total'] ?? 0 ?> danh mục
                </div>
                <div class="pagination">
                    <?php if (($pagination['current_page'] ?? 1) > 1): ?>
                        <a href="?page=<?= ($pagination['current_page'] ?? 1) - 1 ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="page-link disabled"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>
                    
                    <?php if (($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1)): ?>
                        <a href="?page=<?= ($pagination['current_page'] ?? 1) + 1 ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="page-link disabled"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<script>
// Use existing global functions if any, otherwise define here
function deleteCategory(id) {
    if (!confirm('Xóa danh mục này?\n\nLưu ý: Không thể xóa danh mục có sản phẩm hoặc danh mục con.')) return;
    
    // Check if csrfToken is available globally or define strict
    const token = '<?= Session::getCsrfToken() ?>'; // assuming we can access this or pass via JS
    
    // Using vanilla JS fetch
    const formData = new FormData();
    formData.append('category_id', id);
    formData.append('csrf_token', token);
    
    fetch('<?= BASE_URL ?>/admin/category-delete', { // Adjust if needed
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Đã xóa danh mục');
            location.reload();
        } else {
            alert(data.message || 'Không thể xóa');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Có lỗi xảy ra');
    });
}
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>