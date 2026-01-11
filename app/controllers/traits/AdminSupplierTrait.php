<?php
/**
 * =============================================================================
 * ADMIN SUPPLIER TRAIT - QUẢN LÝ NHÀ CUNG CẤP
 * =============================================================================
 * 
 * Chứa các methods liên quan đến nhà cung cấp:
 * - suppliers() - Danh sách NCC
 * - supplierGet() - Lấy thông tin NCC (AJAX)
 * - supplierAdd() - Thêm NCC (AJAX)
 * - supplierUpdate() - Cập nhật NCC (AJAX)
 * - supplierDelete() - Xóa NCC (AJAX)
 * 
 * Sử dụng trong AdminController:
 *   use AdminSupplierTrait;
 */

trait AdminSupplierTrait {
    
    /**
     * ==========================================================================
     * METHOD: suppliers() - DANH SÁCH NHÀ CUNG CẤP
     * ==========================================================================
     * 
     * URL: /admin/suppliers
     */
    public function suppliers() {
        // Nạp model Supplier
        $supplierModel = $this->model('Supplier');
        
        // Bộ lọc
        $filters = [
            'keyword' => $_GET['keyword'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        
        $page = (int)($_GET['page'] ?? 1);
        
        // Lấy danh sách NCC có phân trang
        $result = $supplierModel->getAll($filters, $page, 12);
        
        // Lấy thống kê
        $stats = $supplierModel->countByStatus();
        
        $data = [
            'page_title' => 'Quản lý nhà cung cấp - Admin',
            'suppliers' => $result['data'],
            'pagination' => $result['pagination'],
            'stats' => $stats,
            'filters' => $filters,
            'csrf_token' => Session::getCsrfToken()
        ];
        
        $this->view('admin/suppliers', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: supplierGet() - LẤY THÔNG TIN NHÀ CUNG CẤP (AJAX)
     * ==========================================================================
     * 
     * URL: /admin/supplier-get/{id}
     */
    public function supplierGet($id = null) {
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID không hợp lệ']);
            return;
        }
        
        $supplierModel = $this->model('Supplier');
        $supplier = $supplierModel->getById($id);
        
        if ($supplier) {
            $this->json(['success' => true, 'supplier' => $supplier]);
        } else {
            $this->json(['success' => false, 'message' => 'Không tìm thấy nhà cung cấp']);
        }
    }
    
    /**
     * ==========================================================================
     * METHOD: supplierAdd() - THÊM NHÀ CUNG CẤP (AJAX)
     * ==========================================================================
     * 
     * URL: /admin/supplier-add (POST)
     */
    public function supplierAdd() {
        if (!$this->isAjax() || !$this->isMethod('POST')) {
            $this->json(['error' => 'Invalid request'], 400);
            return;
        }
        
        // Kiểm tra dữ liệu
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $this->json(['success' => false, 'message' => 'Tên nhà cung cấp là bắt buộc']);
            return;
        }
        
        $supplierModel = $this->model('Supplier');
        $data = [
            'Ten_ncc' => $name,
            'Sdt' => $_POST['phone'] ?? null,
            'Email' => $_POST['email'] ?? null,
            'Dia_chi' => $_POST['address'] ?? null,
            'Nguoi_lien_he' => $_POST['contact_person'] ?? null,
            'Mo_ta' => $_POST['description'] ?? null,
            'Trang_thai' => $_POST['status'] ?? 'active'
        ];
        
        $result = $supplierModel->create($data);
        
        if ($result) {
            // Ghi log kiểm toán
            AuditLog::logInsert('nha_cung_cap', $result, $data, 
                'Thêm nhà cung cấp: ' . $name);
            
            $this->json(['success' => true, 'message' => 'Thêm nhà cung cấp thành công']);
        } else {
            $this->json(['success' => false, 'message' => 'Không thể thêm nhà cung cấp']);
        }
    }
    
    /**
     * ==========================================================================
     * METHOD: supplierUpdate() - CẬP NHẬT NHÀ CUNG CẤP (AJAX)
     * ==========================================================================
     * 
     * URL: /admin/supplier-update (POST)
     */
    public function supplierUpdate() {
        if (!$this->isAjax() || !$this->isMethod('POST')) {
            $this->json(['error' => 'Invalid request'], 400);
            return;
        }
        
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        
        if (!$id || empty($name)) {
            $this->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            return;
        }
        
        $supplierModel = $this->model('Supplier');
        
        // Lấy data cũ để log
        $oldData = $supplierModel->getById($id);
        
        $data = [
            'Ten_ncc' => $name,
            'Sdt' => $_POST['phone'] ?? null,
            'Email' => $_POST['email'] ?? null,
            'Dia_chi' => $_POST['address'] ?? null,
            'Nguoi_lien_he' => $_POST['contact_person'] ?? null,
            'Mo_ta' => $_POST['description'] ?? null,
            'Trang_thai' => $_POST['status'] ?? 'active'
        ];
        
        $result = $supplierModel->update($id, $data);
        
        if ($result) {
            // Ghi log kiểm toán
            AuditLog::logUpdate('nha_cung_cap', $id, $oldData, $data,
                'Cập nhật nhà cung cấp: ' . $name);
            
            $this->json(['success' => true, 'message' => 'Cập nhật thành công']);
        } else {
            $this->json(['success' => false, 'message' => 'Không thể cập nhật']);
        }
    }
    
    /**
     * ==========================================================================
     * METHOD: supplierDelete() - XÓA NHÀ CUNG CẤP (AJAX)
     * ==========================================================================
     * 
     * URL: /admin/supplier-delete (POST)
     */
    public function supplierDelete() {
        if (!$this->isAjax() || !$this->isMethod('POST')) {
            $this->json(['error' => 'Invalid request'], 400);
            return;
        }
        
        $id = (int)($_POST['id'] ?? 0);
        
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID không hợp lệ']);
            return;
        }
        
        $supplierModel = $this->model('Supplier');
        
        // Lấy data cũ để log
        $oldData = $supplierModel->getById($id);
        
        $result = $supplierModel->delete($id);
        
        if ($result) {
            // Ghi log kiểm toán
            AuditLog::logDelete('nha_cung_cap', $id, $oldData,
                'Xóa nhà cung cấp: ' . ($oldData['Ten_ncc'] ?? 'Unknown'));
            
            $this->json(['success' => true, 'message' => 'Xóa thành công']);
        } else {
            $this->json(['success' => false, 'message' => 'Không thể xóa']);
        }
    }
}
