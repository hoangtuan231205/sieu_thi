<?php
/**
 * ADMIN - QUẢN LÝ NHÀ CUNG CẤP
 * Standardized UI - Theme Woodland
 * Reference: admin-modern.css
 */

// Data from controller
$suppliers = $suppliers ?? [];
$pagination = $pagination ?? ['total' => 0, 'current_page' => 1, 'last_page' => 1, 'per_page' => 12];
$stats = $stats ?? ['all' => 0, 'active' => 0, 'inactive' => 0];
$filters = $filters ?? [];
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
            <span class="current">Quản lý nhà cung cấp</span>
        </div>
        
        <?php include __DIR__ . '/components/management_tabs.php'; ?>
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Quản Lý Nhà Cung Cấp</h1>
                <p class="admin-page-subtitle">Danh sách và thông tin đối tác cung ứng cho siêu thị.</p>
            </div>
            <button class="btn-admin-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i>
                <span>Thêm Nhà Cung Cấp</span>
            </button>
        </div>
        
        <!-- Filter Toolbar -->
        <div class="admin-card mb-4" style="margin-bottom: 24px;">
            <div class="admin-card-body">
                <form method="GET" action="" class="admin-filter-bar" style="justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                    <div class="form-group" style="flex: 1; max-width: 400px; min-width: 250px;">
                         <div style="position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--admin-text-light);"></i>
                            <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm theo tên, SĐT, Email..." 
                                   value="<?= htmlspecialchars($filters['keyword'] ?? '') ?>"
                                   style="padding-left: 36px;">
                        </div>
                    </div>
                    
                     <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="min-width: 200px;">
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">Tất cả trạng thái</option>
                                <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Đang hợp tác</option>
                                <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Ngừng hợp tác</option>
                            </select>
                        </div>

                    </div>
                </form>
            </div>
        </div>
        
        <!-- Data Table Card -->
        <div class="admin-card">
            <?php if (empty($suppliers)): ?>
                <div style="padding: 60px 20px; text-align: center;">
                    <i class="fas fa-building" style="font-size: 48px; color: var(--admin-text-light); margin-bottom: 16px;"></i>
                    <h3 style="font-size: 16px; color: var(--admin-text-muted); margin-bottom: 8px;">Chưa có nhà cung cấp nào</h3>
                    <p style="font-size: 14px; color: var(--admin-text-muted);">Nhấn "Thêm Nhà Cung Cấp" để bắt đầu</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">ID</th>
                                <th>Nhà Cung Cấp</th>
                                <th>Liên Hệ</th>
                                <th>Người Đại Diện</th>
                                <th>Trạng Thái</th>
                                <th style="width: 100px; text-align: center;">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $avatarColors = ['#dbeafe', '#ffedd5', '#f3e8ff', '#ccfbf1', '#ffe4e6', '#dcfce7'];
                            $textColors = ['#2563eb', '#ea580c', '#9333ea', '#0d9488', '#e11d48', '#16a34a'];
                            
                            foreach ($suppliers as $index => $supplier): 
                                $colorIdx = $index % count($avatarColors);
                                $bg = $avatarColors[$colorIdx];
                                $color = $textColors[$colorIdx];
                                $firstLetter = mb_substr($supplier['Ten_ncc'] ?? 'N', 0, 1, 'UTF-8');
                            ?>
                            <tr>
                                <td style="color: var(--admin-text-light); font-weight: 500;">#<?= str_pad($supplier['ID_ncc'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; flex-shrink: 0; background: <?= $bg ?>; color: <?= $color ?>;">
                                            <?= strtoupper($firstLetter) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 2px;">
                                                <?= htmlspecialchars($supplier['Ten_ncc']) ?>
                                            </div>
                                            <div style="font-size: 12px; color: var(--admin-text-muted); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <?= htmlspecialchars($supplier['Dia_chi'] ?? 'Chưa có địa chỉ') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-dark);">
                                            <i class="fas fa-phone" style="width: 14px; font-size: 12px; color: var(--admin-text-light);"></i>
                                            <?= htmlspecialchars($supplier['Sdt'] ?? '---') ?>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--admin-text-muted);">
                                            <i class="fas fa-envelope" style="width: 14px; font-size: 12px; color: var(--admin-text-light);"></i>
                                            <?= htmlspecialchars($supplier['Email'] ?? '---') ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="color: var(--text-dark);">
                                    <?= htmlspecialchars($supplier['Nguoi_lien_he'] ?? '---') ?>
                                </td>
                                <td>
                                    <?php if ($supplier['Trang_thai'] === 'active'): ?>
                                    <span class="status-badge success">
                                        <span class="dot"></span>
                                        Đang hợp tác
                                    </span>
                                    <?php else: ?>
                                    <span class="status-badge normal">
                                        <span class="dot"></span>
                                        Ngừng hợp tác
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: flex; justify-content: center; gap: 4px;">
                                        <button class="btn-icon" onclick="openEditModal(<?= htmlspecialchars(json_encode($supplier)) ?>)" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon" style="color: var(--admin-danger);" onclick="deleteSupplier(<?= $supplier['ID_ncc'] ?>, '<?= htmlspecialchars($supplier['Ten_ncc']) ?>')" title="Xóa">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if (!empty($pagination) && isset($pagination['total']) && $pagination['total'] > 0): ?>
                <div class="admin-card-footer" style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 13px; color: var(--admin-text-muted);">
                         Hiển thị <strong><?= (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 ?></strong> đến <strong><?= min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) ?></strong> 
                         trong tổng <strong><?= number_format($pagination['total']) ?></strong> nhà cung cấp
                    </div>
                    <div class="pagination">
                        <?php 
                        $queryParams = [];
                        if (!empty($filters['keyword'])) $queryParams['keyword'] = $filters['keyword'];
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
                        
                        // Logic to show pages similar to Products
                        if ($lastPage <= 7) {
                             $showPages = range(1, $lastPage);
                        } else {
                             // Complex logic if needed, but products.php effectively shows 1-5 and Last. 
                             // Wait, products.php code I saw was: for ($i = 1; $i <= min($lastPage, 5); $i++) ... if ($lastPage > 5) show last.
                             // I will strictly follow that logic for consistency.
                             $showPages = range(1, min($lastPage, 5));
                        }
                        
                        foreach ($showPages as $i): 
                        ?>
                            <a href="?page=<?= $i ?>&<?= http_build_query($queryParams) ?>" 
                               class="page-link <?= $i == $currentPage ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endforeach; ?>
                        
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
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Modal (Custom Style to match Products) -->
<div id="supplierModal" class="modal-overlay hidden">
    <div class="admin-card modal-content" style="max-width: 600px;">
        <div class="admin-card-header">
            <h3 class="admin-card-title" id="modalTitle">Thêm Nhà Cung Cấp Mới</h3>
            <button class="btn-icon" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <form id="supplierForm" onsubmit="handleSupplierSubmit(event)" class="admin-card-body">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="id" id="supplierId">
            
            <div style="display: grid; gap: 16px;">
                <div class="form-group">
                    <label>Tên nhà cung cấp <span style="color: var(--admin-danger);">*</span></label>
                    <input type="text" name="name" id="supplierName" class="form-control" required placeholder="Công ty TNHH...">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label>Người liên hệ</label>
                        <input type="text" name="contact_person" id="supplierContact" class="form-control" placeholder="Tên người đại diện">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại <span style="color: var(--admin-danger);">*</span></label>
                        <input type="text" name="phone" id="supplierPhone" class="form-control" required placeholder="09xxxx">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                     <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="supplierEmail" class="form-control" placeholder="contact@example.com">
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="status" id="supplierStatus" class="form-select">
                            <option value="active">Đang hợp tác</option>
                            <option value="inactive">Ngừng hợp tác</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Địa chỉ</label>
                    <textarea name="address" id="supplierAddress" class="form-control" rows="3" placeholder="Địa chỉ văn phòng/kho..."></textarea>
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn-admin-secondary" onclick="closeModal()">Hủy</button>
                <button type="submit" class="btn-admin-primary">Lưu thông tin</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal Functions matching products.php style
function openAddModal() {
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierId').value = '';
    document.getElementById('modalTitle').textContent = 'Thêm Nhà Cung Cấp Mới';
    document.getElementById('supplierModal').classList.remove('hidden');
}

function openEditModal(supplier) {
    document.getElementById('supplierId').value = supplier.ID_ncc;
    // Map controller field names (Note: View table uses DB columns, Form uses generic names for controller)
    document.getElementById('supplierName').value = supplier.Ten_ncc;
    document.getElementById('supplierContact').value = supplier.Nguoi_lien_he;
    document.getElementById('supplierPhone').value = supplier.Sdt;
    document.getElementById('supplierEmail').value = supplier.Email;
    document.getElementById('supplierAddress').value = supplier.Dia_chi;
    document.getElementById('supplierStatus').value = supplier.Trang_thai;
    
    document.getElementById('modalTitle').textContent = 'Cập nhật Nhà Cung Cấp';
    document.getElementById('supplierModal').classList.remove('hidden');
}

function handleSupplierSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    
    // Determine URL based on ID existence
    const url = id ? '<?= BASE_URL ?>/admin/supplier-update' : '<?= BASE_URL ?>/admin/supplier-add';
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Thành công');
            location.reload();
        } else {
            alert(data.message || 'Có lỗi xảy ra');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi kết nối');
    });
}

function closeModal() {
    document.getElementById('supplierModal').classList.add('hidden');
}

function closeDeleteModal() { // For compatibility
    // Implement delete modal if needed or use confirm
}

function deleteSupplier(id, name) {
    if(confirm('Bạn có chắc chắn muốn xóa nhà cung cấp "' + name + '"?\nLưu ý: Không thể xóa nếu đã có phiếu nhập từ nhà cung cấp này.')) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('csrf_token', '<?= $csrf_token ?>');

        fetch('<?= BASE_URL ?>/admin/supplier-delete', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Xóa thành công');
                location.reload();
            } else {
                alert(data.message || 'Không thể xóa nhà cung cấp này');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi kết nối');
        });
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
