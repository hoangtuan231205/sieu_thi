<?php
/**
 * Profile Page - Modern Design with AJAX Avatar Upload
 * Features: Instant upload, validation, toast notifications
 */
include __DIR__ . '/../layouts/header.php';
?>

<style>
/* ===== TOAST NOTIFICATION ===== */
.toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
}

.toast {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 12px;
    background: white;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    margin-bottom: 10px;
    transform: translateX(120%);
    transition: transform 0.3s ease;
    min-width: 300px;
}

.toast.show {
    transform: translateX(0);
}

.toast.success {
    border-left: 4px solid var(--primary-500, #7BC043);
}

.toast.error {
    border-left: 4px solid #ef4444;
}

.toast-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.toast.success .toast-icon {
    background: var(--primary-100, #E2FBBD);
    color: var(--primary-600, #5FA332);
}

.toast.error .toast-icon {
    background: #fee2e2;
    color: #ef4444;
}

.toast-message {
    flex: 1;
    font-size: 14px;
    color: var(--secondary-500, #2D3657);
}

/* ===== PROFILE CONTAINER ===== */
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px 20px;
}

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
    color: var(--primary-500, #7BC043);
}

/* Content Wrapper */
.profile-content-wrapper {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 2rem;
}

/* ===== SIDEBAR ===== */
.profile-sidebar {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    height: fit-content;
    position: sticky;
    top: 100px;
}

.profile-avatar-section {
    text-align: center;
    margin-bottom: 2rem;
}

/* Avatar with hover effect */
.profile-avatar-container {
    width: 130px;
    height: 130px;
    margin: 0 auto 1rem;
    position: relative;
    cursor: pointer;
}

.profile-avatar-circle {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-100, #E2FBBD) 0%, var(--primary-200, #DEF6B2) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.5rem;
    color: var(--primary-600, #5FA332);
    border: 4px solid var(--primary-500, #7BC043);
    overflow: hidden;
    transition: all 0.3s;
}

.profile-avatar-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-avatar-container:hover .profile-avatar-circle {
    filter: brightness(0.9);
}

.profile-avatar-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 13px;
    opacity: 0;
    transition: opacity 0.3s;
}

.profile-avatar-container:hover .profile-avatar-overlay {
    opacity: 1;
}

.profile-avatar-overlay i {
    font-size: 24px;
    margin-bottom: 6px;
}

/* Loading spinner */
.avatar-loading {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.9);
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
}

.avatar-loading.show {
    display: flex;
}

.avatar-loading i {
    font-size: 24px;
    color: var(--primary-500, #7BC043);
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.profile-avatar-hint {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 8px;
}

.profile-user-name {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0.3rem;
    color: var(--secondary-500, #2D3657);
}

.profile-member-since {
    color: #6b7280;
    font-size: 0.85rem;
    margin-bottom: 0.8rem;
}

.profile-member-badge {
    display: inline-block;
    background: var(--primary-100, #E2FBBD);
    color: var(--primary-600, #5FA332);
    padding: 0.4rem 1.2rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Sidebar Menu */
.profile-sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
    border-top: 1px solid #e5e7eb;
    padding-top: 1.5rem;
}

.profile-sidebar-menu li {
    margin-bottom: 0.5rem;
}

.profile-menu-item {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 0.9rem 1rem;
    color: #4b5563;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.2s;
    font-size: 0.95rem;
}

.profile-menu-item:hover {
    background: #f3f4f6;
    color: var(--primary-600, #5FA332);
}

.profile-menu-item.active {
    background: var(--primary-100, #E2FBBD);
    color: var(--primary-600, #5FA332);
    font-weight: 600;
}

.profile-menu-item i {
    width: 20px;
    text-align: center;
}

/* ===== MAIN CONTENT ===== */
.profile-main-content {
    background: white;
    border-radius: 16px;
    padding: 2.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.profile-page-title {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--secondary-500, #2D3657);
}

.profile-page-subtitle {
    color: #6b7280;
    margin-bottom: 2rem;
}

/* Form Grid */
.profile-form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.profile-form-group {
    display: flex;
    flex-direction: column;
}

.profile-form-group.full-width {
    grid-column: 1 / -1;
}

.profile-form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--secondary-500, #2D3657);
    margin-bottom: 0.6rem;
    font-size: 0.9rem;
}

.profile-form-label i {
    color: var(--primary-500, #7BC043);
}

.profile-form-label .required {
    color: #ef4444;
}

.profile-form-input {
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.95rem;
    transition: all 0.2s;
    background: white;
}

.profile-form-input:focus {
    outline: none;
    border-color: var(--primary-500, #7BC043);
    box-shadow: 0 0 0 4px var(--primary-100, rgba(123, 192, 67, 0.15));
}

.profile-form-input:disabled {
    background: #f9fafb;
    color: #6b7280;
    cursor: not-allowed;
}

.profile-form-input.error {
    border-color: #ef4444;
}

.profile-input-wrapper {
    position: relative;
}


/* Error message */
.field-error {
    font-size: 12px;
    color: #ef4444;
    margin-top: 6px;
    display: none;
}

.field-error.show {
    display: block;
}

/* Form Actions */
.profile-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
}

.profile-btn {
    padding: 0.8rem 2rem;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.profile-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.profile-btn-cancel {
    background: white;
    color: #4b5563;
    border: 2px solid #e5e7eb;
}

.profile-btn-cancel:hover:not(:disabled) {
    background: #f3f4f6;
}

.profile-btn-save {
    background: var(--primary-500, #7BC043);
    color: white;
    box-shadow: 0 4px 12px rgba(123, 192, 67, 0.3);
}

.profile-btn-save:hover:not(:disabled) {
    background: var(--primary-600, #5FA332);
    transform: translateY(-1px);
}

.profile-btn-edit {
    background: var(--primary-500, #7BC043);
    color: white;
    box-shadow: 0 4px 12px rgba(123, 192, 67, 0.3);
}

.profile-btn-edit:hover {
    background: var(--primary-600, #5FA332);
}

/* Loading state */
.btn-loading .btn-text { display: none; }
.btn-loading .btn-spinner { display: inline-flex; }
.btn-spinner { display: none; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .profile-content-wrapper {
        grid-template-columns: 1fr;
    }
    
    .profile-sidebar {
        position: static;
    }
    
    .profile-form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Breadcrumb -->
<div class="breadcrumb-section">
    <a href="<?= BASE_URL ?>/">Trang chủ</a>
    <span>›</span>
    <span>Tài khoản của tôi</span>
</div>

<div class="profile-container">
    <div class="profile-content-wrapper">
        <!-- Sidebar -->
        <aside class="profile-sidebar">
            <div class="profile-avatar-section">
                <!-- Avatar Upload Form (auto-submit on file select) -->
                <form id="avatarForm" method="post" action="<?= BASE_URL ?>/user/updateAvatar" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/jpg" style="display: none;">
                    
                    <div class="profile-avatar-container" onclick="document.getElementById('avatarInput').click();">
                        <div class="profile-avatar-circle" id="avatarPreview">
                            <?php 
                            $avatarPath = PUBLIC_PATH . '/assets/img/avatars/' . ($user['avatar'] ?? 'default-avatar.png');
                            if (!empty($user['avatar']) && $user['avatar'] !== 'default-avatar.png' && file_exists($avatarPath)):
                            ?>
                                <img src="<?= asset('img/avatars/' . $user['avatar']) ?>?v=<?= time() ?>" alt="Avatar">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Hover Overlay -->
                        <div class="profile-avatar-overlay">
                            <i class="fas fa-camera"></i>
                            <span>Thay đổi</span>
                        </div>
                    </div>
                </form>
                
                <p class="profile-avatar-hint">Click để chọn ảnh • JPG, PNG • Max 10MB</p>
                
                <div class="profile-user-name"><?= htmlspecialchars($user['fullname'] ?? 'User') ?></div>
                <div class="profile-member-since">Thành viên từ <?= date('Y', strtotime($user['created_at'] ?? 'now')) ?></div>
                <div class="profile-member-badge">Khách hàng</div>
            </div>

            <ul class="profile-sidebar-menu">
                <li>
                    <a href="#" class="profile-menu-item active" id="tabProfileInfo">
                        <i class="fas fa-user"></i>
                        <span>Thông tin tài khoản</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="profile-menu-item" id="tabChangePassword">
                        <i class="fas fa-lock"></i>
                        <span>Đổi mật khẩu</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/orders" class="profile-menu-item">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Đơn hàng của tôi</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/auth/logout" class="profile-menu-item" style="color: #ef4444;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Đăng xuất</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="profile-main-content">
            <!-- Profile Info Section -->
            <div id="profileInfoSection">
                <h1 class="profile-page-title">Hồ Sơ Của Tôi</h1>
                <p class="profile-page-subtitle">Quản lý thông tin hồ sơ để bảo mật tài khoản</p>

                <!-- Edit Form -->
                <form id="profileForm" method="post">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    
                    <div class="profile-form-grid">
                        <!-- Họ và tên -->
                        <div class="profile-form-group">
                            <label class="profile-form-label">
                                <i class="fas fa-user"></i>
                                Họ và tên <span class="required">*</span>
                            </label>
                            <input type="text" name="fullname" id="inputName" class="profile-form-input" 
                                   value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" required>
                            <span class="field-error" id="errorName"></span>
                        </div>

                        <!-- Email -->
                        <div class="profile-form-group">
                            <label class="profile-form-label">
                                <i class="fas fa-envelope"></i>
                                Email <span class="required">*</span>
                            </label>
                            <div class="profile-input-wrapper">
                                <input type="email" name="email" id="inputEmail" class="profile-form-input" 
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" required
                                       style="padding-right: 120px;">

                            </div>
                            <span class="field-error" id="errorEmail"></span>
                        </div>

                        <!-- Số điện thoại -->
                        <div class="profile-form-group">
                            <label class="profile-form-label">
                                <i class="fas fa-phone"></i>
                                Số điện thoại
                            </label>
                            <input type="tel" name="phone" id="inputPhone" class="profile-form-input" 
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                   placeholder="0xxxxxxxxx">
                            <span class="field-error" id="errorPhone"></span>
                        </div>

                        <!-- Địa chỉ -->
                        <div class="profile-form-group">
                            <label class="profile-form-label">
                                <i class="fas fa-map-marker-alt"></i>
                                Địa chỉ
                            </label>
                            <input type="text" name="address" id="inputAddress" class="profile-form-input" 
                                   value="<?= htmlspecialchars($user['address'] ?? '') ?>"
                                   placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành">
                        </div>
                    </div>

                    <div class="profile-form-actions">
                        <button type="submit" class="profile-btn profile-btn-save" id="btnSave">
                            <span class="btn-text"><i class="fas fa-save"></i> Lưu thay đổi</span>
                            <span class="btn-spinner"><i class="fas fa-spinner fa-spin"></i> Đang lưu...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Change Password Section -->
            <div id="changePasswordSection" style="display:none;">
                <h1 class="profile-page-title">Bảo mật & Mật khẩu</h1>
                <p class="profile-page-subtitle">Cập nhật mật khẩu để bảo vệ tài khoản của bạn</p>

                <form id="passwordForm" method="post" action="<?= BASE_URL ?>/user/changePassword">
                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                    
                    <div class="profile-form-grid">
                        <div class="profile-form-group full-width">
                            <label class="profile-form-label">
                                <i class="fas fa-lock"></i>
                                Mật khẩu hiện tại <span class="required">*</span>
                            </label>
                            <input type="password" name="current_password" class="profile-form-input" required>
                        </div>

                        <div class="profile-form-group">
                            <label class="profile-form-label">
                                <i class="fas fa-key"></i>
                                Mật khẩu mới <span class="required">*</span>
                            </label>
                            <input type="password" name="new_password" class="profile-form-input" required minlength="6">
                        </div>

                        <div class="profile-form-group">
                            <label class="profile-form-label">
                                <i class="fas fa-key"></i>
                                Xác nhận mật khẩu <span class="required">*</span>
                            </label>
                            <input type="password" name="confirm_password" class="profile-form-input" required>
                        </div>
                    </div>

                    <div class="profile-form-actions">
                        <button type="submit" class="profile-btn profile-btn-save">
                            <i class="fas fa-save"></i> Đổi mật khẩu
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script>
const baseUrl = '<?= BASE_URL ?>';
const csrfToken = '<?= Session::getCsrfToken() ?>';
let hasUnsavedChanges = false;

// ===== TOAST NOTIFICATION =====
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}"></i>
        </div>
        <div class="toast-message">${message}</div>
    `;
    container.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ===== TAB SWITCHING =====
document.getElementById('tabProfileInfo').onclick = function(e) {
    e.preventDefault();
    this.classList.add('active');
    document.getElementById('tabChangePassword').classList.remove('active');
    document.getElementById('profileInfoSection').style.display = 'block';
    document.getElementById('changePasswordSection').style.display = 'none';
};

document.getElementById('tabChangePassword').onclick = function(e) {
    e.preventDefault();
    this.classList.add('active');
    document.getElementById('tabProfileInfo').classList.remove('active');
    document.getElementById('profileInfoSection').style.display = 'none';
    document.getElementById('changePasswordSection').style.display = 'block';
};

// ===== AVATAR UPLOAD - AUTO SUBMIT FORM =====
const avatarInput = document.getElementById('avatarInput');

// Khi file được chọn, kiểm tra và submit form ngay lập tức
avatarInput.onchange = function(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Kiểm tra loại file
    const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!validTypes.includes(file.type)) {
        showToast('Chỉ chấp nhận file JPG, PNG', 'error');
        this.value = ''; // Xóa lựa chọn
        return;
    }
    
    // Kiểm tra kích thước file (10MB)
    if (file.size > 10 * 1024 * 1024) {
        showToast('Ảnh không được vượt quá 10MB', 'error');
        this.value = ''; // Xóa lựa chọn
        return;
    }
    
    // Hiển thị xem trước ngay trước khi submit
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('avatarPreview').innerHTML = `<img src="${e.target.result}" alt="Avatar">`;
    };
    reader.readAsDataURL(file);
    
    // Submit form ngay lập tức (trang sẽ tải lại)
    document.getElementById('avatarForm').submit();
};

// ===== FORM VALIDATION =====
const profileForm = document.getElementById('profileForm');
const inputs = profileForm.querySelectorAll('input:not([type="hidden"])');

// Theo dõi thay đổi
inputs.forEach(input => {
    input.addEventListener('input', () => {
        hasUnsavedChanges = true;
        // Xóa lỗi khi đang gõ
        const errorEl = document.getElementById('error' + input.id.replace('input', ''));
        if (errorEl) {
            errorEl.classList.remove('show');
            input.classList.remove('error');
        }
    });
});

// Các quy tắc validation
function validateForm() {
    let isValid = true;
    
    // Kiểm tra tên
    const name = document.getElementById('inputName');
    if (name.value.trim().length < 2) {
        showFieldError('Name', 'Họ tên phải có ít nhất 2 ký tự');
        isValid = false;
    }
    
    // Kiểm tra email
    const email = document.getElementById('inputEmail');
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email.value)) {
        showFieldError('Email', 'Email không hợp lệ');
        isValid = false;
    }
    
    // Kiểm tra số điện thoại (tùy chọn, nhưng nếu điền thì phải hợp lệ)
    const phone = document.getElementById('inputPhone');
    if (phone.value && !/^0\d{9}$/.test(phone.value)) {
        showFieldError('Phone', 'Số điện thoại phải có 10 số, bắt đầu bằng 0');
        isValid = false;
    }
    
    return isValid;
}

function showFieldError(field, message) {
    const errorEl = document.getElementById('error' + field);
    const inputEl = document.getElementById('input' + field);
    if (errorEl && inputEl) {
        errorEl.textContent = message;
        errorEl.classList.add('show');
        inputEl.classList.add('error');
    }
}

// Submit form với AJAX
profileForm.onsubmit = async function(e) {
    e.preventDefault();
    
    if (!validateForm()) return;
    
    const btn = document.getElementById('btnSave');
    btn.classList.add('btn-loading');
    btn.disabled = true;
    
    const formData = new FormData(this);
    formData.append('ajax', '1');
    
    try {
        const response = await fetch(baseUrl + '/user/updateProfile', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Cập nhật thông tin thành công!', 'success');
            hasUnsavedChanges = false;
        } else {
            showToast(result.error || 'Có lỗi xảy ra', 'error');
        }
    } catch (error) {
        showToast('Lỗi kết nối, vui lòng thử lại', 'error');
    } finally {
        btn.classList.remove('btn-loading');
        btn.disabled = false;
    }
};

// Cảnh báo trước khi rời trang với thay đổi chưa lưu
window.addEventListener('beforeunload', function(e) {
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = 'Bạn có thay đổi chưa lưu. Bạn có chắc muốn rời đi?';
    }
});

// Hiển thị thông báo flash PHP
<?php if (Session::hasFlash('success')): ?>
showToast('<?= addslashes(Session::getFlash('success')) ?>', 'success');
<?php endif; ?>

<?php if (Session::hasFlash('error')): ?>
showToast('<?= addslashes(Session::getFlash('error')) ?>', 'error');
<?php endif; ?>
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

