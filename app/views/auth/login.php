<?php
/**
 * =============================================================================
 * TRANG ĐĂNG NHẬP - LOGIN PAGE
 * =============================================================================
 */

$data['page_title'] = $data['page_title'] ?? 'Đăng nhập - FreshMart';
include __DIR__ . '/../layouts/header.php';
?>

<section class="auth-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-6">
                <div class="auth-card">
                    <!-- Header -->
                    <div class="auth-header">
                        <h2 class="auth-title">Đăng nhập tài khoản</h2>
                        <p class="auth-subtitle">Chào mừng quay lại FreshMart</p>
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
                    <form method="POST" action="<?= BASE_URL ?>/auth/loginProcess" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">
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
                                       placeholder="Nhập email của bạn"
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
                                       placeholder="Nhập mật khẩu"
                                       required>
                                <button class="btn btn-outline-secondary toggle-password" 
                                        type="button" 
                                        onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember & Forgot -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Ghi nhớ đăng nhập
                                </label>
                            </div>
                            <a href="<?= BASE_URL ?>/auth/forgot-password" class="forgot-link">
                                Quên mật khẩu?
                            </a>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Đăng nhập
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="divider mb-3">
                        <span>hoặc</span>
                    </div>

                    <!-- Social Login -->
                    <div class="social-login">
                        <button class="social-btn facebook" title="Đăng nhập với Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </button>
                        <button class="social-btn google" title="Đăng nhập với Google">
                            <i class="fab fa-google"></i>
                        </button>
                    </div>

                    <!-- Register Link -->
                    <div class="auth-footer">
                        <p>Chưa có tài khoản? 
                            <a href="<?= BASE_URL ?>/auth/register" class="register-link">
                                Đăng ký ngay
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

.forgot-link {
    font-size: 13px;
    color: #BEDEC1;
    text-decoration: none;
    transition: color 0.3s;
}

.forgot-link:hover {
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

.form-check-label {
    font-size: 13px;
    color: #666;
    margin-left: 5px;
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
