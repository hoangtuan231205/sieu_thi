<?php
/**
 * ADMIN - QUẢN LÝ PHIẾU HỦY
 * Standardized UI - Theme Woodland
 * Reference: admin-modern.css
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
            <span class="current">Quản lý phiếu hủy</span>
        </div>
        
        <?php include __DIR__ . '/components/warehouse_tabs.php'; ?>
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Quản lý Phiếu Hủy</h1>
                <p class="admin-page-subtitle">Theo dõi và kiểm soát thất thoát hàng hóa</p>
            </div>
            <div class="admin-header-actions">
                <a href="<?= BASE_URL ?>/admin/export-disposal-excel?status=<?= $filters['trang_thai'] ?? '' ?>" class="btn-admin-secondary">
                    <i class="fas fa-download"></i>
                    <span>Xuất Excel</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/disposal-add" class="btn-admin-primary">
                    <i class="fas fa-plus"></i>
                    <span>Tạo Phiếu Hủy Mới</span>
                </a>
            </div>
        </div>
        
        <!-- Stats Cards using standard admin-modern style -->
        <div class="stat-cards-row">
            <!-- Card 1: Tổng phiếu -->
            <div class="stat-card" style="border-left: 4px solid var(--admin-primary);">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Phiếu hủy tháng này</h4>
                        <p class="stat-card-value"><?= number_format($status_counts['all'] ?? 0) ?></p>
                    </div>
                    <div class="stat-card-icon success">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge normal">
                        Tổng số phiếu
                    </span>
                </div>
            </div>
            
            <!-- Card 2: Thiệt hại -->
            <div class="stat-card" style="border-left: 4px solid var(--admin-danger);">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Giá trị thiệt hại</h4>
                        <p class="stat-card-value" style="color: var(--admin-danger);"><?= number_format($total_value ?? 0, 0, ',', '.') ?>đ</p>
                    </div>
                    <div class="stat-card-icon danger">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge danger">
                        <i class="fas fa-exclamation-triangle"></i> Cần chú ý
                    </span>
                </div>
            </div>
            
            <!-- Card 3: Chờ duyệt -->
            <div class="stat-card" style="border-left: 4px solid var(--admin-warning);">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Hàng hỏng</h4>
                        <p class="stat-card-value" style="color: var(--admin-warning);"><?= number_format($status_counts['cho_duyet'] ?? 0) ?> phiếu</p>
                    </div>
                    <div class="stat-card-icon warning">
                        <i class="fas fa-box-open"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge warning">
                         Chờ xử lý
                    </span>
                </div>
            </div>
            
            <!-- Card 4: Đã duyệt -->
            <div class="stat-card" style="border-left: 4px solid var(--admin-info);">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Đã duyệt</h4>
                        <p class="stat-card-value" style="color: var(--admin-info);"><?= number_format($status_counts['da_duyet'] ?? 0) ?> phiếu</p>
                    </div>
                    <div class="stat-card-icon info">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge info">
                        <i class="fas fa-check"></i> Đã trừ kho
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Filters Toolbar -->
        <div class="admin-card mb-4" style="margin-bottom: 24px;">
            <div class="admin-card-body">
                <form method="GET" class="admin-filter-bar" style="flex-wrap: wrap; gap: 16px;">
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <div style="position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--admin-text-light);"></i>
                            <input type="text" name="keyword" class="form-control" placeholder="Tìm theo mã phiếu, sản phẩm..." 
                                   value="<?= htmlspecialchars($filters['keyword'] ?? '') ?>"
                                   style="padding-left: 42px;">
                        </div>
                    </div>
                    
                    <div class="form-group" style="min-width: 150px;">
                         <select name="type" class="form-select" onchange="this.form.submit()">
                            <option value="">Lý do: Tất cả</option>
                            <option value="hong" <?= ($filters['loai_phieu'] ?? '') == 'hong' ? 'selected' : '' ?>>Hàng hỏng</option>
                            <option value="het_han" <?= ($filters['loai_phieu'] ?? '') == 'het_han' ? 'selected' : '' ?>>Hết hạn</option>
                            <option value="huy" <?= ($filters['loai_phieu'] ?? '') == 'huy' ? 'selected' : '' ?>>Hủy bỏ</option>
                            <option value="dieu_chinh" <?= ($filters['loai_phieu'] ?? '') == 'dieu_chinh' ? 'selected' : '' ?>>Điều chỉnh</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="min-width: 150px;">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Trạng thái</option>
                            <option value="cho_duyet" <?= ($filters['trang_thai'] ?? '') == 'cho_duyet' ? 'selected' : '' ?>>Chờ duyệt</option>
                            <option value="da_duyet" <?= ($filters['trang_thai'] ?? '') == 'da_duyet' ? 'selected' : '' ?>>Đã duyệt</option>
                            <option value="tu_choi" <?= ($filters['trang_thai'] ?? '') == 'tu_choi' ? 'selected' : '' ?>>Từ chối</option>
                        </select>
                    </div>
                    
                    <a href="<?= BASE_URL ?>/admin/disposals" class="btn-admin-secondary" title="Reset">
                        <i class="fas fa-undo"></i>
                    </a>
                </form>
            </div>
        </div>
        
        <!-- Data Table -->
        <div class="admin-card">
            <div class="admin-card-body no-padding">
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAll" style="cursor: pointer;">
                                </th>
                                <th>Mã Phiếu</th>
                                <th>Ngày Tạo</th>
                                <th>Người Thực Hiện</th>
                                <th>Lý Do</th>
                                <th style="text-align: right;">Thiệt Hại</th>
                                <th>Trạng Thái</th>
                                <th style="text-align: center;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($disposals)): ?>
                                <?php 
                                $loaiLabels = ['huy' => 'Hủy bỏ', 'hong' => 'Hàng hỏng', 'het_han' => 'Hết hạn', 'dieu_chinh' => 'Điều chỉnh'];
                                $statusLabels = ['cho_duyet' => 'Chờ duyệt', 'da_duyet' => 'Đã duyệt', 'tu_choi' => 'Từ chối'];
                                // Map status to admin-modern badge classes
                                $statusClasses = ['cho_duyet' => 'warning', 'da_duyet' => 'success', 'tu_choi' => 'danger'];
                                ?>
                                <?php foreach ($disposals as $d): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="selected[]" value="<?= $d['ID_phieu_huy'] ?>" style="cursor: pointer;">
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/admin/disposal-detail/<?= $d['ID_phieu_huy'] ?>" style="color: var(--admin-primary); fontWeight: 600;">
                                                <?= htmlspecialchars($d['Ma_hien_thi']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($d['Ngay_tao'])) ?>
                                            <div style="font-size: 12px; color: var(--admin-text-muted);"><?= date('H:i', strtotime($d['Ngay_tao'])) ?></div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class="fas fa-user-circle" style="color: var(--admin-text-light);"></i>
                                                <span><?= htmlspecialchars($d['Ten_nguoi_tao'] ?? 'Unknown') ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="font-size: 13px; padding: 2px 8px; border-radius: 12px; background: #f1f5f9; color: #475569;">
                                                <?= $loaiLabels[$d['Loai_phieu']] ?? $d['Loai_phieu'] ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right; font-weight: 700; color: var(--admin-danger);">
                                            <?= number_format($d['Tong_tien_huy'], 0, ',', '.') ?>đ
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $statusClasses[$d['Trang_thai']] ?? 'normal' ?>">
                                                <span class="dot"></span>
                                                <?= $statusLabels[$d['Trang_thai']] ?? $d['Trang_thai'] ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; justify-content: center; gap: 8px;">
                                                <a href="<?= BASE_URL ?>/admin/disposal-detail/<?= $d['ID_phieu_huy'] ?>" class="btn-icon" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($d['Trang_thai'] == 'cho_duyet'): ?>
                                                    <button onclick="approveDisposal(<?= $d['ID_phieu_huy'] ?>)" class="btn-icon" style="color: var(--admin-success);" title="Duyệt">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8">
                                        <div style="padding: 60px 20px; text-align: center;">
                                            <i class="fas fa-inbox" style="font-size: 48px; color: var(--admin-text-light); margin-bottom: 16px;"></i>
                                            <h3 style="font-size: 16px; color: var(--admin-text-muted);">Chưa có phiếu hủy nào</h3>
                                            <p style="font-size: 14px; color: var(--admin-text-muted);">Tạo phiếu hủy mới để bắt đầu theo dõi</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Footer -->
                <?php if (($pagination['last_page'] ?? 1) > 1): ?>
                <div class="admin-card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 13px; color: var(--admin-text-muted);">
                        Hiển thị trang <?= $pagination['current_page'] ?> / <?= $pagination['last_page'] ?>
                    </div>
                    <div class="pagination">
                         <?php if (($pagination['current_page'] ?? 1) > 1): ?>
                            <a href="?page=<?= ($pagination['current_page'] ?? 1) - 1 ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="page-link disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>
                        
                        <?php for($i=1; $i<=$pagination['last_page']; $i++): ?>
                            <a href="?page=<?= $i ?>" class="page-link <?= ($i == $pagination['current_page']) ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        
                         <?php if (($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1)): ?>
                            <a href="?page=<?= ($pagination['current_page'] ?? 1) + 1 ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="page-link disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function approveDisposal(id) {
        if(confirm('Xác nhận duyệt phiếu hủy này? Kho sẽ bị trừ số lượng tương ứng.')) {
            const formData = new FormData();
            formData.append('disposal_id', id);
            
            // Lấy CSRF token nếu cần (thường controller check)
            // formData.append('csrf_token', '<?= Session::getCsrfToken() ?>');

            fetch('<?= BASE_URL ?>/admin/disposal-approve', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Duyệt thành công');
                    location.reload();
                } else {
                    alert(data.message || 'Có lỗi xảy ra');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi kết nối');
            });
        }
    }
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>
