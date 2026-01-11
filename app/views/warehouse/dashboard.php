<?php
/**
 * WAREHOUSE - QUẢN LÝ PHIẾU NHẬP
 * Standardized UI - Theme Woodland
 * Reference: report_expiry.php style
 */

$imports = $imports ?? [];
$filters = $filters ?? ['ma_phieu' => '', 'nguoi_tao' => '', 'ngay_nhap' => '', 'page' => 1];
$pagination = $pagination ?? ['current_page' => 1, 'total_pages' => 1, 'per_page' => 10, 'offset' => 0];
$csrf_token = $csrf_token ?? '';
$categories = $categories ?? [];
$total = $total ?? count($imports);
?>
<?php include dirname(__DIR__) . '/admin/layouts/header.php'; ?>
<link rel="stylesheet" href="<?= asset('css/admin-modern.css') ?>">
<!-- We can keep warehouse.css if it contains specific modal styles, but main layout should use admin-modern -->
<link rel="stylesheet" href="<?= ASSETS_DIR ?>/css/warehouse.css">

<div class="admin-modern">
    <div class="admin-modern-container">
        <!-- Breadcrumb -->
        <div class="admin-breadcrumb">
            <a href="<?= BASE_URL ?>/">Trang chủ</a>
            <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
            <span class="current">Quản lý phiếu nhập</span>
        </div>

        <?php include dirname(__DIR__) . '/admin/components/warehouse_tabs.php'; ?>

        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Quản lý Phiếu Nhập</h1>
                <p class="admin-page-subtitle">Tìm kiếm và quản lý lịch sử nhập hàng hóa</p>
            </div>
            <div class="admin-header-actions">
                <?php
                $qs = http_build_query([
                    'ma_phieu' => $filters['ma_phieu'] ?? '',
                    'nguoi_tao' => $filters['nguoi_tao'] ?? '',
                    'ngay_nhap' => $filters['ngay_nhap'] ?? '',
                ]);
                ?>
                <a href="<?= BASE_URL ?>/index.php?url=warehouse/exportImport&<?= $qs ?>" class="btn-admin-secondary">
                    <i class="fas fa-download"></i>
                    <span>Xuất Excel</span>
                </a>
                <button class="btn-admin-primary" type="button" onclick="openAdd()">
                    <i class="fas fa-plus"></i>
                    <span>Tạo Phiếu Nhập</span>
                </button>
            </div>
        </div>

        <!-- Filters Toolbar (Using admin-card style for consistency) -->
        <div class="admin-card mb-4" style="margin-bottom: 24px;">
            <div class="admin-card-body">
                <form id="wh-search-form" class="admin-filter-bar" method="GET"
                    action="<?= BASE_URL ?>/warehouse/dashboard" style="flex-wrap: wrap; gap: 16px;">
                    <div class="form-group">
                        <label>Mã phiếu</label>
                        <input class="form-control" name="ma_phieu"
                            value="<?= htmlspecialchars($filters['ma_phieu']) ?>" placeholder="VD: PNK20251229">
                    </div>
                    <div class="form-group">
                        <label>Người tạo</label>
                        <input class="form-control" name="nguoi_tao"
                            value="<?= htmlspecialchars($filters['nguoi_tao']) ?>" placeholder="Tên nhân viên...">
                    </div>
                    <div class="form-group">
                        <label>Ngày nhập</label>
                        <input class="form-control" type="date" name="ngay_nhap"
                            value="<?= htmlspecialchars($filters['ngay_nhap']) ?>">
                    </div>
                    <div style="display: flex; gap: 8px; align-items: flex-end;">
                        <button class="btn-admin-primary" type="submit">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                        <button type="button" class="btn-admin-secondary" id="wh-reset-search">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Data Table -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Danh sách phiếu nhập <span
                        style="font-size: 14px; font-weight: 400; color: var(--admin-text-muted);">(<?= $total ?>)</span>
                </h3>
            </div>

            <div class="admin-card-body no-padding">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã Phiếu</th>
                            <th>Ngày Nhập</th>
                            <th>Người Tạo</th>
                            <th>Tổng Tiền</th>
                            <th>Ghi Chú</th>
                            <th style="text-align: center;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($imports)): ?>
                            <tr>
                                <td colspan="6">
                                    <div style="padding: 60px 20px; text-align: center; color: var(--admin-text-muted);">
                                        <i class="fas fa-inbox"
                                            style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                        <p>Không có dữ liệu</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($imports as $row): ?>
                                <tr>
                                    <td>
                                        <span
                                            style="color: var(--admin-primary); fontWeight: 600;"><?= htmlspecialchars($row['Ma_hien_thi'] ?? '') ?></span>
                                    </td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($row['Ngay_nhap'] ?? 'now'))) ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <i class="fas fa-user-circle" style="color: var(--admin-text-light);"></i>
                                            <?= htmlspecialchars($row['Nguoi_tao_ten'] ?? $row['Nguoi_tao'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td style="font-weight: 700; color: var(--admin-primary);">
                                        <?= number_format((float) ($row['Tong_tien'] ?? 0), 0, ',', '.') ?> đ
                                    </td>
                                    <td><?= htmlspecialchars($row['Ghi_chu'] ?? '') ?></td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; justify-content: center; gap: 8px;">
                                            <button class="btn-icon" type="button" title="Sửa"
                                                onclick="openEdit(<?= (int) $row['ID_phieu_nhap'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon" type="button" title="Xóa"
                                                style="color: var(--admin-danger);"
                                                onclick="deleteImport(<?= (int) $row['ID_phieu_nhap'] ?>)">
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

            <!-- Pagination Footer -->
            <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
                <div class="admin-card-footer">
                    <div style="font-size: 13px; color: var(--admin-text-muted);">
                        Hiển thị trang <?= $pagination['current_page'] ?> / <?= $pagination['total_pages'] ?>
                    </div>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= (int) $pagination['total_pages']; $i++): ?>
                            <a class="page-link <?= ($i == (int) $pagination['current_page']) ? 'active' : '' ?>"
                                href="<?= BASE_URL ?>/warehouse/dashboard?ma_phieu=<?= urlencode($filters['ma_phieu']) ?>&nguoi_tao=<?= urlencode($filters['nguoi_tao']) ?>&ngay_nhap=<?= urlencode($filters['ngay_nhap']) ?>&page=<?= $i ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    window.WH_BASE = "<?= rtrim(BASE_URL, '/') ?>";
    window.WH_CSRF = "<?= htmlspecialchars($csrf_token) ?>";

    // Reset tìm kiếm
    document.getElementById('wh-reset-search')?.addEventListener('click', function () {
        window.location.href = '<?= BASE_URL ?>/warehouse/dashboard';
    });
</script>
<script src="<?= ASSETS_DIR ?>/js/warehouse.js" defer></script>

<?php require __DIR__ . '/modal_import_add.php'; ?>
<?php require __DIR__ . '/modal_import_edit.php'; ?>

<?php include dirname(__DIR__) . '/admin/layouts/footer.php'; ?>