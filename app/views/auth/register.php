<?php
/**
 * =============================================================================
 * TRANG ĐĂNG KÝ - REGISTER PAGE
 * =============================================================================
 */

$data['page_title'] = $data['page_title'] ?? 'Đăng ký - FreshMart';
include __DIR__ . '/../layouts/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-6">
                <div class="auth-card">
                    <!-- Header -->
                    <div class="auth-header">
                        <h2 class="auth-title">Tạo tài khoản mới</h2>
                        <p class="auth-subtitle">Tham gia FreshMart ngay hôm nay</p>
                    </div>

                    <!-- Error Message -->
                    <?php if (Session::hasFlash('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= htmlspecialchars(Session::getFlash('error')) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Success Message -->
                    <?php if (Session::hasFlash('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars(Session::getFlash('success')) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Form -->
                    <form method="POST" action="<?= BASE_URL ?>/auth/registerProcess" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
                        <!-- Name Input -->
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Họ và tên</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       placeholder="Nhập họ và tên"
                                       required>
                            </div>
                        </div>

                        <!-- Username Input -->
                        <div class="form-group mb-3">
                            <label for="username" class="form-label">Tên đăng nhập</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-at"></i>
                                </span>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Nhập tên đăng nhập"
                                       minlength="3"
                                       required>
                            </div>
                            <small class="form-text text-muted">Tối thiểu 3 ký tự, chỉ dùng chữ, số, dấu gạch dưới</small>
                        </div>

                        <!-- Email Input -->
                        <div class="form-group mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       placeholder="Nhập email"
                                       required>
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="form-group mb-3">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)"
                                       required>
                                <button class="btn btn-outline-secondary toggle-password" 
                                        type="button" 
                                        onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password Input -->
                        <div class="form-group mb-3">
                            <label for="password_confirm" class="form-label">Xác nhận mật khẩu</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" 
                                       class="form-control" 
                                       id="password_confirm" 
                                       name="password_confirm" 
                                       placeholder="Nhập lại mật khẩu"
                                       required>
                                <button class="btn btn-outline-secondary toggle-password" 
                                        type="button" 
                                        onclick="togglePassword('password_confirm')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Terms & Conditions -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="agree" name="agree" required>
                            <label class="form-check-label" for="agree">
                                Tôi đồng ý với 
                                <a href="#" class="term-link">Điều khoản sử dụng</a> 
                                và 
                                <a href="#" class="term-link">Chính sách riêng tư</a>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-user-plus me-2"></i>
                            Đăng ký
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="divider mb-3">
                        <span>hoặc</span>
                    </div>

                    <!-- Social Login -->
                    <div class="social-login">
                        <button class="social-btn facebook" title="Đăng ký với Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </button>
                        <button class="social-btn google" title="Đăng ký với Google">
                            <i class="fab fa-google"></i>
                        </button>
                    </div>

                    <!-- Login Link -->
                    <div class="auth-footer">
                        <p>Đã có tài khoản? 
                            <a href="<?= BASE_URL ?>/auth/login" class="register-link">
                                Đăng nhập
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.auth-section {
    min-height: 100vh;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #BEDEC1 0%, #E1EBDA 100%);
    padding: 40px 0;
}

.auth-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    padding: 40px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.auth-header {
    text-align: center;
    margin-bottom: 30px;
}

.auth-title {
    font-size: 28px;
    font-weight: 700;
    color: #333;
    margin-bottom: 8px;
}

.auth-subtitle {
    font-size: 14px;
    color: #999;
}

.auth-form .form-label {
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-control, .input-group-text {
    border-color: #ddd;
    border-radius: 8px;
    height: 45px;
    font-size: 14px;
}

.form-control:focus {
    border-color: #BEDEC1;
    box-shadow: 0 0 0 0.2rem rgba(190, 222, 193, 0.25);
}

.input-group-text {
    background: #f8f9fa;
    border-right: 0;
    color: #666;
}

.input-group .form-control {
    border-left: 0;
}

.toggle-password {
    border-left: 0;
    color: #666;
}

.toggle-password:hover {
    background: #f0f0f0;
}

.form-check-label {
    font-size: 13px;
    color: #666;
    margin-left: 5px;
}

.term-link {
    color: #BEDEC1;
    text-decoration: none;
    transition: color 0.3s;
}

.term-link:hover {
    color: #7ba796;
}

.divider {
    display: flex;
    align-items: center;
    color: #ddd;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #ddd;
}

.divider span {
    margin: 0 15px;
    color: #999;
    font-size: 13px;
}

.social-login {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.social-btn {
    flex: 1;
    height: 45px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 18px;
}

.social-btn:hover {
    border-color: #BEDEC1;
    background: #f9fdfb;
}

.social-btn.facebook {
    color: #1877f2;
}

.social-btn.google {
    color: #ea4335;
}

.auth-footer {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #666;
}

.auth-footer a {
    color: #BEDEC1;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.3s;
}

.auth-footer a:hover {
    color: #7ba796;
}

.btn-primary {
    background: linear-gradient(135deg, #BEDEC1 0%, #7ba796 100%);
    border: none;
    height: 45px;
    font-weight: 600;
    transition: transform 0.3s, box-shadow 0.3s;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #7ba796 0%, #5a8a77 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(123, 167, 150, 0.3);
    color: white;
}

.alert {
    border: none;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-danger {
    background: #fff5f5;
    color: #dc3545;
}

.alert-success {
    background: #f0fdf4;
    color: #28a745;
}
</style>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const btn = event.currentTarget;
    const icon = btn.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php
include __DIR__ . '/../layouts/footer.php';
?>
