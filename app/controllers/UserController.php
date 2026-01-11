    
<?php
/**
 * USER CONTROLLER - Trang thông tin tài khoản cá nhân
 */
class UserController extends Controller {
    public function profile() {
        // Kiểm tra session
        if (!Session::getUserId()) {
            redirect(BASE_URL . '/auth/login');
            exit;
        }
        
        // Lấy ID từ session
        $userId = Session::getUserId();
        $userModel = $this->model('User');
        
        // Tìm user theo ID
        $userDb = $userModel->findById($userId);
        
        if (!$userDb) {
            redirect(BASE_URL . '/auth/login');
            exit;
        }
        
        // Tìm ảnh đại diện theo ID trên hệ thống file (do DB không có cột Avatar)
        $avatarFile = '';
        $avatarDir = PUBLIC_PATH . '/assets/img/avatars/';
        if (is_dir($avatarDir)) {
            $files = glob($avatarDir . 'avatar_' . $userId . '.*');
            if ($files && count($files) > 0) {
                $avatarFile = basename($files[0]);
            }
        }

        $user = [
            'fullname' => $userDb['Ho_ten'] ?? '',
            'email' => $userDb['Email'] ?? '',
            'phone' => $userDb['Sdt'] ?? '',
            'address' => $userDb['Dia_chi'] ?? '',
            'avatar' => $avatarFile,
            'created_at' => $userDb['Ngay_tao'] ?? date('Y-m-d'),
        ];
        $this->view('customer/profile', ['user' => $user]);
    }
    
    public function updateProfile() {
        if (!Session::getUserId()) {
            redirect(BASE_URL . '/auth/login');
            exit;
        }
    
        // CSRF Check
        if (!Middleware::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Phiên làm việc không hợp lệ');
            redirect(BASE_URL . '/user/profile');
            exit;
        }
    
        $userId = Session::getUserId();
        $userModel = $this->model('User');
        
        // Sanitize inputs
        $data = [
            'Ho_ten' => $this->sanitize($_POST['fullname'] ?? ''),
            'Email' => $this->sanitize($_POST['email'] ?? ''),
            'Sdt' => $this->sanitize($_POST['phone'] ?? ''),
            'Dia_chi' => $this->sanitize($_POST['address'] ?? ''),
        ];
        
        $userModel->updateProfile($userId, $data);
        
        // Cập nhật lại session user với thông tin mới
        $user = Session::get('user', []);
        $user['Ho_ten'] = $data['Ho_ten'];
        $user['Email'] = $data['Email'];
        $user['Sdt'] = $data['Sdt'];
        $user['Dia_chi'] = $data['Dia_chi'];
        Session::set('user', $user);
        
        // Cập nhật từng field lẻ cho Session helpers
        Session::set('user_name', $data['Ho_ten']);
        Session::set('user_email', $data['Email']);
        
        Session::flash('success', 'Cập nhật thông tin thành công');
        
        // Handle AJAX Response
        if ($this->isAjax() || isset($_POST['ajax'])) {
            return $this->json(['success' => true, 'message' => 'Cập nhật thành công']);
        }
        
        redirect(BASE_URL . '/user/profile');
    }
    
    public function updateAvatar() {
        if (!Session::getUserId()) {
            redirect(BASE_URL . '/auth/login');
            exit;
        }

        // CSRF Check
        if (!Middleware::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Phiên làm việc không hợp lệ');
            redirect(BASE_URL . '/user/profile');
            exit;
        }
        
        $userId = Session::getUserId();
        
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
            $file = $_FILES['avatar'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                Session::flash('error', 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)');
                redirect(BASE_URL . '/user/profile');
                exit;
            }
            
            if ($file['size'] > 10 * 1024 * 1024) { // 10MB
                Session::flash('error', 'Kích thước ảnh tối đa 10MB');
                redirect(BASE_URL . '/user/profile');
                exit;
            }
            
            // Create avatars directory if not exists
            $uploadDir = PUBLIC_PATH . '/assets/img/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Xóa ảnh cũ của user này trước khi upload ảnh mới (để tránh nhiều file khác extension)
            $oldFiles = glob($uploadDir . 'avatar_' . $userId . '.*');
            if ($oldFiles) {
                foreach ($oldFiles as $oldFile) {
                    if (file_exists($oldFile)) unlink($oldFile);
                }
            }
            
            // Generate unique/predictable filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $userId . '.' . $extension;
            $uploadPath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Update session user array if needed
                $user = Session::get('user', []);
                $user['Avatar'] = $filename;
                Session::set('user', $user);
                
                Session::flash('success', 'Cập nhật ảnh đại diện thành công!');
            } else {
                Session::flash('error', 'Lỗi khi upload ảnh');
            }
        }
        
        redirect(BASE_URL . '/user/profile');
    }
    public function changePassword() {
        if (!Session::getUserId()) {
            redirect(BASE_URL . '/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/user/profile');
            exit;
        }

        // CSRF Check
        if (!Middleware::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Phiên làm việc không hợp lệ');
            redirect(BASE_URL . '/user/profile');
            exit;
        }

        $userId = Session::getUserId();
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            Session::flash('error', 'Vui lòng điền đầy đủ thông tin');
            redirect(BASE_URL . '/user/profile');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            Session::flash('error', 'Mật khẩu mới không khớp');
            redirect(BASE_URL . '/user/profile');
            exit;
        }

        if (strlen($newPassword) < 6) {
            Session::flash('error', 'Mật khẩu phải có ít nhất 6 ký tự');
            redirect(BASE_URL . '/user/profile');
            exit;
        }

        $userModel = $this->model('User');
        if ($userModel->changePassword($userId, $currentPassword, $newPassword)) {
            Session::flash('success', 'Đổi mật khẩu thành công!');
        } else {
            Session::flash('error', 'Mật khẩu hiện tại không chính xác');
        }

        redirect(BASE_URL . '/user/profile');
    }
}
