<?php
trait AdminCategoryTrait {
    
    /**
     * ==========================================================================
     * METHOD: categories() - QUẢN LÝ DANH MỤC
     * ==========================================================================
     * 
     * URL: /admin/categories
     */
    public function categories() {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 12; // Grid view usually takes more items per page
        $offset = ($page - 1) * $perPage;
        
        $filters = [
            'keyword' => $_GET['keyword'] ?? ''
        ];
        
        // Lấy danh sách flat (không phân cấp) cho Card Grid
        $flatCategories = $this->categoryModel->getAllFlat();
        
        // Filter by keyword if exists
        if (!empty($filters['keyword'])) {
            $keyword = mb_strtolower($filters['keyword']);
            $flatCategories = array_filter($flatCategories, function($cat) use ($keyword) {
                return strpos(mb_strtolower($cat['Ten_danh_muc']), $keyword) !== false;
            });
        }
        
        // Thêm product count cho từng danh mục (Lấy từ AdminController logic)
        $productCounts = $this->categoryModel->getProductCountByCategory();
        $countMap = [];
        foreach ($productCounts as $pc) {
            $countMap[$pc['ID_danh_muc']] = $pc['So_san_pham'];
        }
        
        foreach ($flatCategories as &$cat) {
            $cat['So_san_pham'] = $countMap[$cat['ID_danh_muc']] ?? 0;
        }
        unset($cat);
        
        $total = count($flatCategories);
        $paginatedCats = array_slice($flatCategories, $offset, $perPage);
        $totalPages = ceil($total / $perPage);
        
        $pagination = [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $totalPages,
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
        
        $data = [
            'page_title' => 'Quản lý danh mục - Admin',
            'categories' => $paginatedCats,
            'pagination' => $pagination,
            'filters' => $filters,
            'csrf_token' => Session::getCsrfToken()
        ];
        
        $this->view('admin/categories', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: categoryAdd() - FORM THÊM MỚI
     * ==========================================================================
     * 
     * URL: /admin/category-add
     */
    public function categoryAdd() {
        $parents = $this->categoryModel->getForDropdown();
        
        $data = [
            'page_title' => 'Thêm danh mục mới - Admin',
            'parents' => $parents,
            'csrf_token' => Session::getCsrfToken()
        ];
        
        $this->view('admin/category_add', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: categoryEdit() - FORM SỬA DANH MỤC
     * ==========================================================================
     * 
     * URL: /admin/category-edit/{id}
     */
    public function categoryEdit($id = null) {
        if (!$id) redirect(BASE_URL . '/admin/categories');
        
        $category = $this->categoryModel->findById($id);
        if (!$category) {
            Session::flash('error', 'Danh mục không tồn tại');
            redirect(BASE_URL . '/admin/categories');
            return;
        }
        
        $parents = $this->categoryModel->getForDropdown();
        
        $data = [
            'page_title' => 'Sửa danh mục - Admin',
            'category' => $category,
            'parents' => $parents,
            'csrf_token' => Session::getCsrfToken()
        ];
        
        $this->view('admin/category_edit', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: categorySave() - LƯU DANH MỤC (ADD/EDIT)
     * ==========================================================================
     * 
     * URL: /admin/category-save (POST)
     */
    public function categorySave() {
        if (!$this->isMethod('POST')) {
            redirect(BASE_URL . '/admin/categories');
        }
        
        // Csrf Check
        if (!Middleware::verifyCsrf(post('csrf_token'))) {
             Session::flash('error', 'Phiên làm việc hết hạn');
             redirect(BASE_URL . '/admin/categories');
        }
        
        $id = post('id');
        $isEdit = !empty($id);
        
        // Validate
        $name = trim(post('ten_danh_muc', ''));
        $parentId = post('danh_muc_cha');
        $parentId = ($parentId === '' || $parentId === '0') ? null : (int)$parentId;
        $order = (int)post('thu_tu_hien_thi', 0);
        $status = post('trang_thai', 'active');
        $desc = trim(post('mo_ta', ''));
        
        if (empty($name)) {
            Session::flash('error', 'Tên danh mục không được để trống');
            redirect($isEdit ? BASE_URL . "/admin/category-edit/$id" : BASE_URL . '/admin/category-add');
            return;
        }
        
        // Check duplicate name
        if ($this->categoryModel->nameExists($name, $isEdit ? $id : null)) {
            Session::flash('error', 'Tên danh mục đã tồn tại');
            redirect($isEdit ? BASE_URL . "/admin/category-edit/$id" : BASE_URL . '/admin/category-add');
            return;
        }
        
        // Check parent valid
        if ($isEdit && $parentId) {
            if (!$this->categoryModel->isValidParent($id, $parentId)) {
                Session::flash('error', 'Danh mục cha không hợp lệ (không thể chọn chính mình hoặc con của mình)');
                redirect(BASE_URL . "/admin/category-edit/$id");
                return;
            }
        }
        
        $data = [
            'Ten_danh_muc' => $name,
            'Danh_muc_cha' => $parentId,
            'Thu_tu_hien_thi' => $order,
            'Trang_thai' => $status,
            'Mo_ta' => $desc
        ];
        
        if ($isEdit) {
            $result = $this->categoryModel->update($id, $data);
            $msgSuccess = 'Cập nhật danh mục thành công';
            $msgFail = 'Cập nhật thất bại';
            $redirectUrl = BASE_URL . '/admin/categories';
        } else {
            $result = $this->categoryModel->create($data);
            $msgSuccess = 'Thêm danh mục thành công';
            $msgFail = 'Thêm mới thất bại';
            $redirectUrl = BASE_URL . '/admin/categories';
        }
        
        if ($result) {
            Session::flash('success', $msgSuccess);
            redirect($redirectUrl);
        } else {
            Session::flash('error', $msgFail);
            redirect($isEdit ? BASE_URL . "/admin/category-edit/$id" : BASE_URL . '/admin/category-add');
        }
    }
    
    /**
     * ==========================================================================
     * METHOD: categoryDelete() - XÓA DANH MỤC (AJAX)
     * ==========================================================================
     * 
     * URL: /admin/category-delete (POST)
     */
    public function categoryDelete() {
        if (!$this->isAjax() || !$this->isMethod('POST')) {
            $this->json(['success' => false, 'message' => 'Invalid Request']);
            return;
        }
        
        if (!Middleware::verifyCsrf(post('csrf_token'))) {
            $this->json(['success' => false, 'message' => 'Phiên làm việc hết hạn']);
            return;
        }
        
        $id = (int)post('category_id');
        
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID không hợp lệ']);
            return;
        }
        if ($this->categoryModel->hasChildren($id)) {
            $this->json(['success' => false, 'message' => 'Không thể xóa: Danh mục này có chứa danh mục con']);
            return;
        }
        
        if ($this->categoryModel->hasProducts($id)) {
            $this->json(['success' => false, 'message' => 'Không thể xóa: Danh mục này đang chứa sản phẩm']);
            return;
        }
        
        if ($this->categoryModel->delete($id)) {
            $this->json(['success' => true, 'message' => 'Xóa danh mục thành công']);
        } else {
            $this->json(['success' => false, 'message' => 'Xóa thất bại']);
        }
    }
}
