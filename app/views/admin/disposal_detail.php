<?php
/**
 * ADMIN - CHI TIẾT PHIẾU HỦY
 * Thiết kế giao diện hiện đại - Theme Woodland
 */
$loaiLabels = ['huy' => 'Hủy bỏ', 'hong' => 'Hư hỏng', 'het_han' => 'Hết hạn', 'dieu_chinh' => 'Điều chỉnh'];
$statusLabels = ['cho_duyet' => 'Chờ duyệt', 'da_duyet' => 'Đã duyệt', 'tu_choi' => 'Từ chối'];
$statusClasses = ['cho_duyet' => 'pending', 'da_duyet' => 'approved', 'tu_choi' => 'rejected'];
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
            <span class="current"><?= htmlspecialchars($disposal['Ma_hien_thi']) ?></span>
        </div>
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title"><?= htmlspecialchars($disposal['Ma_hien_thi']) ?></h1>
                <p class="admin-page-subtitle">Chi tiết phiếu hủy hàng</p>
            </div>
            <?php if ($disposal['Trang_thai'] == 'cho_duyet'): ?>
            <div class="admin-header-actions">
                <button onclick="showRejectModal()" class="btn-admin-secondary" style="color: var(--admin-danger); border-color: var(--admin-danger);">
                    <i class="fas fa-times"></i>
                    <span>Từ chối</span>
                </button>
                <button onclick="approveDisposal(<?= $disposal['ID_phieu_huy'] ?>)" class="btn-admin-primary" style="background: #7BC043; border-color: #7BC043; color: white;">
                    <i class="fas fa-check"></i>
                    <span>Duyệt phiếu</span>
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Info Card -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Thông tin phiếu</h3>
                        <span class="status-badge <?= $statusClasses[$disposal['Trang_thai']] ?? '' ?>">
                            <span class="dot"></span>
                            <?= $statusLabels[$disposal['Trang_thai']] ?>
                        </span>
                    </div>
                    <div class="admin-card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Mã phiếu:</span>
                                <span class="info-value" style="color: var(--admin-primary);"><?= $disposal['Ma_hien_thi'] ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Loại phiếu:</span>
                                <span class="info-value">
                                    <span class="type-badge <?= $disposal['Loai_phieu'] ?>"><?= $loaiLabels[$disposal['Loai_phieu']] ?></span>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Ngày hủy:</span>
                                <span class="info-value"><?= date('d/m/Y', strtotime($disposal['Ngay_huy'])) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Ngày tạo:</span>
                                <span class="info-value"><?= date('d/m/Y H:i', strtotime($disposal['Ngay_tao'])) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Người tạo:</span>
                                <span class="info-value"><?= htmlspecialchars($disposal['Ten_nguoi_tao']) ?></span>
                            </div>
                            <?php if ($disposal['Nguoi_duyet']): ?>
                            <div class="info-item">
                                <span class="info-label">Người duyệt:</span>
                                <span class="info-value"><?= htmlspecialchars($disposal['Ten_nguoi_duyet']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr style="margin: 20px 0; border-color: #f1f5f9;">
                        
                        <div>
                            <span class="info-label" style="display: block; margin-bottom: 8px;">Lý do hủy:</span>
                            <p style="margin: 0; color: var(--admin-text); line-height: 1.6;"><?= nl2br(htmlspecialchars($disposal['Ly_do'])) ?></p>
                        </div>
                        
                        <?php if ($disposal['Trang_thai'] == 'tu_choi' && $disposal['Ly_do_tu_choi']): ?>
                        <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 10px; padding: 16px; margin-top: 20px;">
                            <div style="display: flex; gap: 12px;">
                                <i class="fas fa-times-circle" style="color: var(--admin-danger); margin-top: 2px;"></i>
                                <div>
                                    <p style="font-size: 14px; font-weight: 600; color: #991b1b; margin: 0 0 4px 0;">Lý do từ chối</p>
                                    <p style="font-size: 13px; color: #dc2626; margin: 0;"><?= nl2br(htmlspecialchars($disposal['Ly_do_tu_choi'])) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Products Card -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Danh sách sản phẩm</h3>
                        <span style="font-size: 13px; color: var(--admin-text-muted);"><?= count($details) ?> sản phẩm</span>
                    </div>
                    <div class="admin-card-body no-padding">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Sản phẩm</th>
                                    <th>Lô nhập</th>
                                    <th style="text-align: center;">Số lượng</th>
                                    <th style="text-align: right;">Giá nhập</th>
                                    <th style="text-align: right;">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $stt = 1; foreach ($details as $item): ?>
                                <tr>
                                    <td style="color: var(--admin-text-muted);"><?= $stt++ ?></td>
                                    <td>
                                        <div style="font-weight: 500;"><?= htmlspecialchars($item['Ten_sp']) ?></div>
                                        <div style="font-size: 12px; color: var(--admin-text-muted);"><?= htmlspecialchars($item['Ma_SP'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <?php if ($item['Ma_phieu_nhap']): ?>
                                            <span class="type-badge huy"><?= $item['Ma_phieu_nhap'] ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--admin-text-muted);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center; font-weight: 600;"><?= number_format($item['So_luong']) ?></td>
                                    <td style="text-align: right;"><?= number_format($item['Gia_nhap'], 0, ',', '.') ?>đ</td>
                                    <td style="text-align: right; font-weight: 600;"><?= number_format($item['Thanh_tien'], 0, ',', '.') ?>đ</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot style="background: #f8fafc;">
                                <tr>
                                    <td colspan="5" style="text-align: right; font-weight: 600;">TỔNG GIÁ TRỊ:</td>
                                    <td style="text-align: right; font-weight: 700; color: var(--admin-danger); font-size: 18px;">
                                        <?= number_format($disposal['Tong_tien_huy'], 0, ',', '.') ?>đ
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Status Card -->
                <div class="admin-card">
                    <div class="admin-card-body" style="text-align: center; padding: 40px 24px;">
                        <?php if ($disposal['Trang_thai'] == 'cho_duyet'): ?>
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(245, 158, 11, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                                <i class="fas fa-clock" style="font-size: 36px; color: var(--admin-warning);"></i>
                            </div>
                            <h4 style="font-size: 18px; font-weight: 600; margin: 0 0 8px 0;">Đang chờ duyệt</h4>
                            <p style="font-size: 14px; color: var(--admin-text-muted); margin: 0;">Kho chưa bị trừ số lượng</p>
                        <?php elseif ($disposal['Trang_thai'] == 'da_duyet'): ?>
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                                <i class="fas fa-check-circle" style="font-size: 36px; color: var(--admin-success);"></i>
                            </div>
                            <h4 style="font-size: 18px; font-weight: 600; margin: 0 0 8px 0;">Đã duyệt</h4>
                            <p style="font-size: 14px; color: var(--admin-text-muted); margin: 0;">Kho đã trừ tự động</p>
                        <?php else: ?>
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                                <i class="fas fa-times-circle" style="font-size: 36px; color: var(--admin-danger);"></i>
                            </div>
                            <h4 style="font-size: 18px; font-weight: 600; margin: 0 0 8px 0;">Đã từ chối</h4>
                            <p style="font-size: 14px; color: var(--admin-text-muted); margin: 0;">Không ảnh hưởng kho</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Summary Card -->
                <div class="admin-card">
                    <div class="admin-card-body">
                        <div class="summary-box">
                            <div class="summary-row">
                                <span>Số sản phẩm</span>
                                <span style="font-weight: 600;"><?= count($details) ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Tổng số lượng</span>
                                <span style="font-weight: 600;"><?= number_format(array_sum(array_column($details, 'So_luong'))) ?></span>
                            </div>
                            <div class="summary-row">
                                <span style="color: var(--admin-danger);">Giá trị hủy</span>
                                <span style="color: var(--admin-danger); font-size: 18px;"><?= number_format($disposal['Tong_tien_huy'], 0, ',', '.') ?>đ</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Back Button -->
                <a href="<?= BASE_URL ?>/admin/disposals" class="btn-admin-secondary" style="width: 100%; justify-content: center; padding: 14px;">
                    <i class="fas fa-arrow-left"></i>
                    <span>Quay lại danh sách</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog" style="max-width: 500px;">
        <div class="modal-content" style="border-radius: 12px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid #f1f5f9;">
                <h5 class="modal-title" style="font-weight: 600;">Từ chối phiếu hủy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-medium">Lý do từ chối <span class="text-danger">*</span></label>
                <textarea class="form-control" id="rejectReason" rows="4" placeholder="Nhập lý do từ chối phiếu này..." style="border-radius: 8px;"></textarea>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f1f5f9; gap: 12px;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" onclick="rejectDisposal()">Xác nhận từ chối</button>
            </div>
        </div>
    </div>
</div>

<script>
function approveDisposal(id) {
    if (!confirm('Duyệt phiếu này?\n\nKho sẽ TỰ ĐỘNG TRỪ số lượng.')) return;
    
    const formData = new FormData();
    formData.append('disposal_id', id);
    formData.append('csrf_token', csrfToken);
    
    fetch(baseUrl + '/admin/disposal-approve', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 1500);
    });
}

function showRejectModal() {
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function rejectDisposal() {
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) {
        showNotification('Vui lòng nhập lý do từ chối', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('disposal_id', <?= $disposal['ID_phieu_huy'] ?>);
    formData.append('reason', reason);
    formData.append('csrf_token', csrfToken);
    
    fetch(baseUrl + '/admin/disposal-reject', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 1500);
    });
}
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>
