<?php
/**
 * =============================================================================
 * ADMIN DISPOSAL TRAIT - QUẢN LÝ PHIẾU HỦY
 * =============================================================================
 * 
 * Chứa các methods liên quan đến phiếu hủy:
 * - disposals() - Danh sách phiếu hủy
 * - disposalAdd() - Tạo phiếu hủy
 * - disposalDetail() - Chi tiết phiếu hủy
 * - disposalApprove() - Duyệt phiếu hủy
 * - disposalReject() - Từ chối phiếu hủy
 * - searchProductForDisposal() - Tìm sản phẩm
 * 
 * Sử dụng trong AdminController:
 *   use AdminDisposalTrait;
 */

trait AdminDisposalTrait {
    
    /**
     * ==========================================================================
     * METHOD: disposals() - DANH SÁCH PHIẾU HỦY
     * ==========================================================================
     * 
     * URL: /admin/disposals
     */
    /**
     * ==========================================================================
     * METHOD: disposals() - DANH SÁCH PHIẾU HỦY
     * ==========================================================================
     * 
     * URL: /admin/disposals
     */
    public function disposals() {
        $disposalModel = $this->model('Disposal');
        
        // Bộ lọc
        $filters = [
            'trang_thai' => get('status', ''),
            'loai_phieu' => get('type', ''),
            'tu_ngay' => get('date_from', ''),
            'den_ngay' => get('date_to', ''),
            'keyword' => get('keyword', ''),
            'page' => max(1, (int)get('page', 1))
        ];
        
        $limit = 15;
        $offset = ($filters['page'] - 1) * $limit;
        
        $disposals = $disposalModel->getDisposals($filters, $limit, $offset);
        $totalCount = $disposalModel->countDisposals($filters);
        $totalPages = ceil($totalCount / $limit);
        
        $statusCounts = $disposalModel->countByStatus();
        
        // Tính tổng giá trị thiệt hại từ phiếu đã duyệt
        $dateFrom = !empty($filters['tu_ngay']) ? $filters['tu_ngay'] : date('Y-01-01');
        $dateTo = !empty($filters['den_ngay']) ? $filters['den_ngay'] : date('Y-m-d');
        $total_value = $disposalModel->getTotalDisposalValue($dateFrom, $dateTo);
        
        $data = [
            'page_title' => 'Quản lý phiếu hủy - Admin',
            'disposals' => $disposals,
            'filters' => $filters,
            'total_count' => $totalCount,
            'total_pages' => $totalPages,
            'current_page' => $filters['page'],
            'status_counts' => $statusCounts,
            'total_value' => $total_value
        ];
        
        $this->view('admin/disposals', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: disposalAdd() - TẠO PHIẾU HỦY
     * ==========================================================================
     * 
     * URL: /admin/disposal-add (GET + POST)
     */
    public function disposalAdd() {
        if (is_post()) {
            // Xử lý tạo phiếu hủy
            $disposalModel = $this->model('Disposal');
            
            $disposalData = [
                'Nguoi_tao' => $_SESSION['user_id'],
                'Loai_phieu' => post('loai_phieu'),
                'Ly_do' => post('ly_do'),
                'Ngay_huy' => post('ngay_huy') ?: date('Y-m-d')
            ];
            
            $items = post('items', []);
            
            if (empty($items)) {
                $_SESSION['flash_error'] = 'Vui lòng thêm ít nhất một sản phẩm';
                redirect(BASE_URL . '/admin/disposal-add');
                return;
            }
            
            $disposalId = $disposalModel->createDisposal(
                $disposalData['Nguoi_tao'],
                $disposalData['Ngay_huy'],
                $disposalData['Loai_phieu'],
                $disposalData['Ly_do'],
                $items
            );
            
            if ($disposalId) {
                // Ghi log kiểm toán
                AuditLog::logInsert('phieu_huy', $disposalId, $disposalData, 
                    'Tạo phiếu hủy với ' . count($items) . ' sản phẩm');
                
                $_SESSION['flash_success'] = 'Tạo phiếu hủy thành công!';
                redirect(BASE_URL . '/admin/disposals');
            } else {
                $_SESSION['flash_error'] = 'Có lỗi xảy ra khi tạo phiếu hủy';
                redirect(BASE_URL . '/admin/disposal-add');
            }
            return;
        }
        
        // Pre-fill data from URL params (từ trang cảnh báo hết hạn)
        $prefillProduct = null;
        $prefillReason = '';
        $prefillQuantity = 0;
        $prefillBatchCode = '';
        $prefillPrice = 0;
        $prefillBatchId = 0;
        
        $productId = (int)get('product_id', 0);
        if ($productId > 0) {
            $product = $this->productModel->findById($productId);
            if ($product) {
                $prefillProduct = [
                    'ID_sp' => $product['ID_sp'],
                    'Ma_hien_thi' => $product['Ma_hien_thi'],
                    'Ten' => $product['Ten'],
                    'Don_vi_tinh' => $product['Don_vi_tinh'],
                    'So_luong_ton' => $product['So_luong_ton']
                ];
                $prefillQuantity = (int)get('quantity', $product['So_luong_ton']);
                $prefillReason = get('reason', '');
                $prefillBatchCode = get('batch_code', '');
                $prefillPrice = (int)get('price', 0);
                $prefillBatchId = (int)get('batch_id', 0);
            }
        }
        
        $data = [
            'page_title' => 'Tạo phiếu hủy - Admin',
            'prefill_product' => $prefillProduct,
            'prefill_quantity' => $prefillQuantity,
            'prefill_reason' => $prefillReason,
            'prefill_batch_code' => $prefillBatchCode,
            'prefill_price' => $prefillPrice,
            'prefill_batch_id' => $prefillBatchId
        ];
        
        $this->view('admin/disposal_add', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: disposalDetail() - CHI TIẾT PHIẾU HỦY
     * ==========================================================================
     * 
     * URL: /admin/disposal-detail/{id}
     */
    public function disposalDetail($id = null) {
        if (!$id) {
            redirect(BASE_URL . '/admin/disposals');
            return;
        }
        
        $disposalModel = $this->model('Disposal');
        $disposal = $disposalModel->getDisposalById($id);
        
        if (!$disposal) {
            $_SESSION['flash_error'] = 'Không tìm thấy phiếu hủy';
            redirect(BASE_URL . '/admin/disposals');
            return;
        }
        
        $details = $disposalModel->getDisposalDetails($id);
        
        $data = [
            'page_title' => 'Chi tiết phiếu hủy - Admin',
            'disposal' => $disposal,
            'details' => $details
        ];
        
        $this->view('admin/disposal_detail', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: disposalApprove() - DUYỆT PHIẾU HỦY (AJAX)
     * ==========================================================================
     * 
     * URL: /admin/disposal-approve (POST)
     */
    public function disposalApprove() {
        header('Content-Type: application/json');
        
        if (!is_post()) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        
        $id = post('disposal_id');
        $disposalModel = $this->model('Disposal');
        
        // Lấy data cũ để log
        $oldData = $disposalModel->getDisposalById($id);
        
        $result = $disposalModel->approve($id, $_SESSION['user_id']);
        
        if ($result) {
            // Ghi log kiểm toán
            AuditLog::logUpdate('phieu_huy', $id, 
                ['Trang_thai' => $oldData['Trang_thai'] ?? 'cho_duyet'],
                ['Trang_thai' => 'da_duyet'],
                'Duyệt phiếu hủy #' . $id);
        }
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Duyệt phiếu hủy thành công' : 'Có lỗi xảy ra'
        ]);
    }
    
    /**
     * ==========================================================================
     * METHOD: disposalReject() - TỪ CHỐI PHIẾU HỦY (AJAX)
     * ==========================================================================
     * 
     * URL: /admin/disposal-reject (POST)
     */
    public function disposalReject() {
        header('Content-Type: application/json');
        
        if (!is_post()) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        
        $id = post('disposal_id');
        $reason = post('reason', '');
        $disposalModel = $this->model('Disposal');
        
        $result = $disposalModel->reject($id, $_SESSION['user_id'], $reason);
        
        if ($result) {
            // Ghi log kiểm toán
            AuditLog::logUpdate('phieu_huy', $id,
                ['Trang_thai' => 'cho_duyet'],
                ['Trang_thai' => 'tu_choi', 'Ly_do_tu_choi' => $reason],
                'Từ chối phiếu hủy #' . $id);
        }
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Từ chối phiếu hủy thành công' : 'Có lỗi xảy ra'
        ]);
    }
    
    /**
     * ==========================================================================
     * METHOD: searchProductForDisposal() - TÌM SẢN PHẨM CHO PHIẾU HỦY (AJAX)
     * ==========================================================================
     * 
     * URL: /admin/search-product-for-disposal
     */
    public function searchProductForDisposal() {
        header('Content-Type: application/json');
        
        $keyword = get('q', '');
        
        if (strlen(trim($keyword)) < 1) {
            echo json_encode([]);
            return;
        }
        
        $results = $this->productModel->searchForDisposal($keyword, 20);
        echo json_encode($results);
    }
    
    /**
     * ==========================================================================
     * METHOD: getProductBatches() - LẤY LÔ HÀNG CỦA SẢN PHẨM (AJAX)
     * ==========================================================================
     * 
     * URL: /admin/get-product-batches
     */
    public function getProductBatches() {
        header('Content-Type: application/json');
        
        $productId = (int)get('product_id', 0);
        
        if ($productId <= 0) {
            echo json_encode([]);
            return;
        }
        
        $batches = $this->productModel->getBatches($productId);
        echo json_encode($batches);
    }
}
