<?php
/**
 * =============================================================================
 * ORDER MODEL - QUẢN LÝ ĐƠN HÀNG
 * =============================================================================
 * 
 * Bảng: don_hang, chi_tiet_don_hang
 * 
 * Chức năng:
 * - Tạo đơn hàng (gọi stored procedure)
 * - Quản lý trạng thái đơn hàng
 * - Lấy lịch sử đơn hàng
 * - Thống kê doanh thu
 */

class Order extends Model {
    
    protected $table = 'don_hang';
    protected $primaryKey = 'ID_dh';
    
    /**
     * ==========================================================================
     * FEFO Multi-Batch Allocation - Phân bổ từ nhiều lô theo FEFO
     * ==========================================================================
     * 
     * Khi 1 lô không đủ hàng, sẽ lấy từ nhiều lô theo thứ tự sắp hết hạn
     * 
     * @param int $productId
     * @param int $quantityNeeded Số lượng cần
     * @return array Mảng các lô với số lượng phân bổ
     *               [['ID_chi_tiet_nhap' => x, 'Don_gia_nhap' => y, 'So_luong_xuat' => z], ...]
     */
    public function findBatchesFEFO($productId, $quantityNeeded) {
        // Lấy tất cả lô còn hàng, chưa hết hạn, sắp xếp theo FEFO
        $sql = "SELECT ID_chi_tiet_nhap, Don_gia_nhap, So_luong_con, Ngay_het_han
                FROM chi_tiet_phieu_nhap
                WHERE ID_sp = ? 
                  AND So_luong_con > 0
                  AND (Ngay_het_han IS NULL OR Ngay_het_han > CURDATE())
                ORDER BY 
                    CASE WHEN Ngay_het_han IS NULL THEN 1 ELSE 0 END,  -- Có HSD trước
                    Ngay_het_han ASC,  -- Sắp hết hạn trước
                    ID_chi_tiet_nhap ASC  -- FIFO nếu cùng HSD";
        
        $batches = $this->db->query($sql, [$productId])->fetchAll();
        
        $allocations = [];
        $remaining = $quantityNeeded;
        
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            
            // Lấy tối đa từ lô này
            $takeFromBatch = min($remaining, $batch['So_luong_con']);
            
            $allocations[] = [
                'ID_chi_tiet_nhap' => $batch['ID_chi_tiet_nhap'],
                'Don_gia_nhap' => $batch['Don_gia_nhap'],
                'So_luong_xuat' => $takeFromBatch,
                'Ngay_het_han' => $batch['Ngay_het_han']
            ];
            
            $remaining -= $takeFromBatch;
        }
        
        // Nếu không đủ hàng trong tất cả lô, fallback giá nhập từ sản phẩm
        if ($remaining > 0) {
            $sql = "SELECT Gia_nhap FROM san_pham WHERE ID_sp = ?";
            $product = $this->db->query($sql, [$productId])->fetch();
            
            // Thêm phần còn thiếu với ID_chi_tiet_nhap = NULL (không track lô)
            $allocations[] = [
                'ID_chi_tiet_nhap' => null,
                'Don_gia_nhap' => $product ? $product['Gia_nhap'] : 0,
                'So_luong_xuat' => $remaining,
                'Ngay_het_han' => null
            ];
        }
        
        return $allocations;
    }
    
    /**
     * FEFO đơn lô (backward compatible) - Dùng khi chắc chắn 1 lô đủ
     */
    public function findBatchFEFO($productId, $quantity = 1) {
        $batches = $this->findBatchesFEFO($productId, $quantity);
        return !empty($batches) ? $batches[0] : null;
    }
    
    /**
     * Tạo đơn hàng từ giỏ hàng
     * 
     * @param int $userId
     * @param string $receiverName
     * @param string $receiverPhone
     * @param string $receiverAddress
     * @param string $note
     * @return int|false Order ID nếu thành công
     */
    public function createOrder($userId, $receiverName, $receiverPhone, $receiverAddress, $note = '', $selectedCartIds = []) {
        try {
            // Lấy các items trong giỏ hàng
            $cartModel = new Cart();
            if (!empty($selectedCartIds)) {
                $cartItems = $cartModel->getCartItemsByIds($userId, $selectedCartIds);
            } else {
                $cartItems = $cartModel->getCartItems($userId);
            }
            
            if (empty($cartItems)) {
                return false;
            }
            
            // Tính tổng
            $subtotal = 0;
            foreach ($cartItems as $item) {
                $subtotal += $item['Thanh_tien'];
            }
            
            $shippingFee = ($subtotal >= 150000) ? 0 : 20000;
            $total = $subtotal + $shippingFee;
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Insert order
            $orderSql = "INSERT INTO don_hang 
                        (ID_tk, Ten_nguoi_nhan, Sdt_nguoi_nhan, Dia_chi_giao_hang, Ghi_chu, Tong_tien, Phi_van_chuyen, Thanh_tien, Trang_thai) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'dang_xu_ly')";
            
            $this->db->query($orderSql, [
                $userId,
                $receiverName,
                $receiverPhone,
                $receiverAddress,
                $note,
                $subtotal,
                $shippingFee,
                $total
            ]);
            
            // Lấy ID đơn hàng vừa thêm
            $orderId = $this->db->lastInsertId();
            
            if (!$orderId) {
                $this->db->rollback();
                return false;
            }
            
            // Thêm chi tiết đơn hàng từ giỏ hàng với lựa chọn lô theo FEFO
            $detailSql = "INSERT INTO chi_tiet_don_hang 
                         (ID_dh, ID_sp, Ten_sp, So_luong, Gia_tien, Thanh_tien, Hinh_anh, Don_gia_von, ID_chi_tiet_nhap) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            // SQL trừ kho tổng
            $updateProductStockSql = "UPDATE san_pham SET So_luong_ton = GREATEST(0, So_luong_ton - ?) WHERE ID_sp = ?";
            
            // SQL trừ kho lô
            $updateBatchStockSql = "UPDATE chi_tiet_phieu_nhap SET So_luong_con = GREATEST(0, So_luong_con - ?) WHERE ID_chi_tiet_nhap = ?";
            
            foreach ($cartItems as $item) {
                // FEFO Multi-Batch: Phân bổ từ nhiều lô nếu 1 lô không đủ
                $allocations = $this->findBatchesFEFO($item['ID_sp'], $item['So_luong']);
                
                foreach ($allocations as $batch) {
                    // Tính thành tiền tỷ lệ theo số lượng từ lô này
                    $batchQuantity = $batch['So_luong_xuat'];
                    $batchTotal = $item['Gia_tien'] * $batchQuantity;
                    
                    $this->db->query($detailSql, [
                        $orderId,
                        $item['ID_sp'],
                        $item['Ten'],
                        $batchQuantity,                    // Số lượng từ lô này
                        $item['Gia_tien'],                 // Đơn giá bán
                        $batchTotal,                       // Thành tiền của lô này
                        $item['Hinh_anh'],
                        $batch['Don_gia_nhap'],            // Giá vốn từ lô
                        $batch['ID_chi_tiet_nhap']         // ID lô (cho trigger trừ kho)
                    ]);
                }
            }
            
            // Xóa chỉ các items được chọn
            if (!empty($selectedCartIds)) {
                $placeholders = implode(',', array_fill(0, count($selectedCartIds), '?'));
                $clearCartSql = "DELETE FROM gio_hang WHERE ID_tk = ? AND ID_gio IN ($placeholders)";
                $params = array_merge([$userId], $selectedCartIds);
                $this->db->query($clearCartSql, $params);
            } else {
                $clearCartSql = "DELETE FROM gio_hang WHERE ID_tk = ?";
                $this->db->query($clearCartSql, [$userId]);
            }
            
            // Commit transaction
            $this->db->commit();
            
            return (int) $orderId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Create Order Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * ==========================================================================
     * TẠO ĐƠN HÀNG TỪ MUA NGAY (DIRECT CHECKOUT)
     * ==========================================================================
     * 
     * Tạo đơn hàng trực tiếp từ sản phẩm mà không qua giỏ hàng
     * 
     * @param int $userId
     * @param string $receiverName
     * @param string $receiverPhone
     * @param string $receiverAddress
     * @param array $productData - Mảng chứa thông tin sản phẩm
     * @param string $note
     * @return int|false Order ID nếu thành công
     */
    public function createOrderFromDirectCheckout($userId, $receiverName, $receiverPhone, $receiverAddress, $productData, $note = '') {
        try {
            // Kiểm tra dữ liệu sản phẩm
            if (empty($productData) || !isset($productData['ID_sp'])) {
                return false;
            }
            
            // Tính tổng
            $subtotal = $productData['Thanh_tien'];
            $shippingFee = ($subtotal >= 150000) ? 0 : 20000;
            $total = $subtotal + $shippingFee;
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Insert order
            $orderSql = "INSERT INTO don_hang 
                        (ID_tk, Ten_nguoi_nhan, Sdt_nguoi_nhan, Dia_chi_giao_hang, Ghi_chu, Tong_tien, Phi_van_chuyen, Thanh_tien, Trang_thai) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'dang_xu_ly')";
            
            $this->db->query($orderSql, [
                $userId,
                $receiverName,
                $receiverPhone,
                $receiverAddress,
                $note,
                $subtotal,
                $shippingFee,
                $total
            ]);
            
            // Lấy ID đơn hàng vừa thêm
            $orderId = $this->db->lastInsertId();
            
            if (!$orderId) {
                $this->db->rollback();
                return false;
            }
            
            // FEFO Multi-Batch: Phân bổ từ nhiều lô nếu 1 lô không đủ
            $allocations = $this->findBatchesFEFO($productData['ID_sp'], $productData['So_luong']);
            
            // Thêm chi tiết đơn hàng với thông tin lô FEFO
            $detailSql = "INSERT INTO chi_tiet_don_hang 
                         (ID_dh, ID_sp, Ten_sp, So_luong, Gia_tien, Thanh_tien, Hinh_anh, Don_gia_von, ID_chi_tiet_nhap) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            // SQL trừ kho tổng
            $updateProductStockSql = "UPDATE san_pham SET So_luong_ton = GREATEST(0, So_luong_ton - ?) WHERE ID_sp = ?";
            
            // SQL trừ kho lô
            $updateBatchStockSql = "UPDATE chi_tiet_phieu_nhap SET So_luong_con = GREATEST(0, So_luong_con - ?) WHERE ID_chi_tiet_nhap = ?";
            
            foreach ($allocations as $batch) {
                $batchQuantity = $batch['So_luong_xuat'];
                $batchTotal = $productData['Gia_tien'] * $batchQuantity;
                
                $this->db->query($detailSql, [
                    $orderId,
                    $productData['ID_sp'],
                    $productData['Ten'],
                    $batchQuantity,
                    $productData['Gia_tien'],
                    $batchTotal,
                    $productData['Hinh_anh'],
                    $batch['Don_gia_nhap'],        // Giá vốn từ lô FEFO
                    $batch['ID_chi_tiet_nhap']     // ID lô (cho trigger trừ kho)
                ]);
               
            }
            
            // Commit transaction
            $this->db->commit();
            
            return (int) $orderId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Create Order From Direct Checkout Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * QUẢN LÝ ĐƠN HÀNG (CUSTOMER)
     */
    
    /**
     * Lấy đơn hàng của user
     * 
     * @param int $userId
     * @param string $status
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getUserOrders($userId, $status = '', $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE ID_tk = ?";
        $params = [$userId];
        
        if ($status) {
            $sql .= " AND Trang_thai = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY Ngay_dat DESC LIMIT {$limit} OFFSET {$offset}";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Đếm đơn hàng của user
     * 
     * @param int $userId
     * @param string $status
     * @return int
     */
    public function countUserOrders($userId, $status = '') {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE ID_tk = ?";
        $params = [$userId];
        
        if ($status) {
            $sql .= " AND Trang_thai = ?";
            $params[] = $status;
        }
        
        $result = $this->db->query($sql, $params)->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Lấy chi tiết đơn hàng
     * 
     * @param int $orderId
     * @return array
     */
    public function getOrderDetails($orderId) {
        $sql = "SELECT * FROM chi_tiet_don_hang WHERE ID_dh = ?";
        return $this->db->query($sql, [$orderId])->fetchAll();
    }
    
    /**
     * Hủy đơn hàng
     * Chỉ hủy được khi trạng thái = 'dang_xu_ly'
     * UPDATE: Hoàn lại tồn kho khi hủy
     * 
     * @param int $orderId
     * @return bool
     */
    public function cancelOrder($orderId) {
        $this->db->beginTransaction();
        try {
            $order = $this->findById($orderId);
            
            if (!$order || $order['Trang_thai'] !== 'dang_xu_ly') {
                $this->db->rollBack();
                return false;
            }
            
            // 1. Lấy chi tiết đơn hàng
            $details = $this->getOrderDetails($orderId);
            
            // 2. Hoàn lại tồn kho - ĐÃ XỬ LÝ BỞI TRIGGER `trg_huy_don_hoan_kho`
            // $sqlRefundProduct = "UPDATE san_pham SET So_luong_ton = So_luong_ton + ? WHERE ID_sp = ?";
            // $sqlRefundBatch = "UPDATE chi_tiet_phieu_nhap SET So_luong_con = So_luong_con + ? WHERE ID_chi_tiet_nhap = ?";
            
            // foreach ($details as $detail) {
            //    // Hoàn kho tổng
            //    $this->db->query($sqlRefundProduct, [$detail['So_luong'], $detail['ID_sp']]);
            //    
            //    // Hoàn kho lô
            //    if (!empty($detail['ID_chi_tiet_nhap'])) {
            //        $this->db->query($sqlRefundBatch, [$detail['So_luong'], $detail['ID_chi_tiet_nhap']]);
            //    }
            // }
            
            // 3. Cập nhật trạng thái
            $this->updateStatus($orderId, 'huy');
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Order::cancelOrder Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy timeline đơn hàng
     * 
     * @param array $order
     * @return array
     */
    public function getOrderTimeline($order) {
        $timeline = [];
        
        // Đặt hàng
        $timeline[] = [
            'status' => 'dang_xu_ly',
            'label' => 'Đơn hàng đã đặt',
            'date' => date('d/m/Y H:i', strtotime($order['Ngay_dat'])),
            'completed' => true
        ];
        
        // Đang giao
        $timeline[] = [
            'status' => 'dang_giao',
            'label' => 'Đang giao hàng',
            'date' => in_array($order['Trang_thai'], ['dang_giao', 'da_giao']) ? date('d/m/Y H:i', strtotime($order['Ngay_cap_nhat'])) : '',
            'completed' => in_array($order['Trang_thai'], ['dang_giao', 'da_giao'])
        ];
        
        // Đã giao hoặc Hủy
        if ($order['Trang_thai'] === 'da_giao') {
            $timeline[] = [
                'status' => 'da_giao',
                'label' => 'Giao hàng thành công',
                'date' => date('d/m/Y H:i', strtotime($order['Ngay_cap_nhat'])),
                'completed' => true
            ];
        } elseif ($order['Trang_thai'] === 'huy') {
            $timeline[] = [
                'status' => 'huy',
                'label' => 'Đơn hàng đã hủy',
                'date' => date('d/m/Y H:i', strtotime($order['Ngay_cap_nhat'])),
                'completed' => true,
                'is_cancelled' => true
            ];
        } else {
            $timeline[] = [
                'status' => 'da_giao',
                'label' => 'Hoàn thành',
                'date' => '',
                'completed' => false
            ];
        }
        
        return $timeline;
    }
    
    /**
     * ==========================================================================
     * QUẢN LÝ ĐƠN HÀNG (ADMIN)
     * ==========================================================================
     */
    
    /**
     * Lấy tất cả đơn hàng (admin)
     * 
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllOrders($filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT 
                    dh.*,
                    tk.Ho_ten as Ten_khach_hang,
                    tk.Email as Email_khach_hang,
                    (SELECT COUNT(*) FROM chi_tiet_don_hang WHERE ID_dh = dh.ID_dh) as Tong_so_thuc,
                    (SELECT SUM(So_luong) FROM chi_tiet_don_hang WHERE ID_dh = dh.ID_dh) as Tong_so_luong_sp,
                    (SELECT sp.Hinh_anh FROM chi_tiet_don_hang ct JOIN san_pham sp ON ct.ID_sp = sp.ID_sp WHERE ct.ID_dh = dh.ID_dh LIMIT 1) as Hinh_anh_dai_dien
                FROM {$this->table} dh
                LEFT JOIN tai_khoan tk ON dh.ID_tk = tk.ID
                WHERE 1=1";
        
        $params = [];
        
        // Filter theo trạng thái
        if (!empty($filters['status'])) {
            $sql .= " AND dh.Trang_thai = ?";
            $params[] = $filters['status'];
        }
        
        // Filter theo ngày
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(dh.Ngay_dat) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(dh.Ngay_dat) <= ?";
            $params[] = $filters['date_to'];
        }
        
        // Search theo tên, SĐT
        if (!empty($filters['keyword'])) {
            $sql .= " AND (dh.Ten_nguoi_nhan LIKE ? OR dh.Sdt_nguoi_nhan LIKE ? OR tk.Ho_ten LIKE ?)";
            $keyword = '%' . $filters['keyword'] . '%';
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        $sql .= " ORDER BY dh.Ngay_dat DESC LIMIT {$limit} OFFSET {$offset}";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * Đếm tất cả đơn hàng
     * 
     * @param array $filters
     * @return int
     */
    public function countAllOrders($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} dh WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND dh.Trang_thai = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(dh.Ngay_dat) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(dh.Ngay_dat) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['keyword'])) {
            $sql .= " AND (dh.Ten_nguoi_nhan LIKE ? OR dh.Sdt_nguoi_nhan LIKE ?)";
            $keyword = '%' . $filters['keyword'] . '%';
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        $result = $this->db->query($sql, $params)->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Cập nhật trạng thái đơn hàng
     * 
     * @param int $orderId
     * @param string $status
     * @return bool
     */
    public function updateStatus($orderId, $status) {
        return $this->update($orderId, ['Trang_thai' => $status]);
    }
    
    /**
     * Lấy đơn hàng mới nhất
     * 
     * @param int $limit
     * @return array
     */
    public function getRecentOrders($limit = 10) {
        $sql = "SELECT 
                    dh.*,
                    tk.Ho_ten as Ten_khach_hang
                FROM {$this->table} dh
                LEFT JOIN tai_khoan tk ON dh.ID_tk = tk.ID
                ORDER BY dh.Ngay_dat DESC
                LIMIT {$limit}";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Xuất Excel đơn hàng
     * 
     * @param array $filters
     * @return array
     */
    public function getAllOrdersForExport($filters = []) {
        $sql = "SELECT 
                    dh.ID_dh,
                    dh.Ten_nguoi_nhan,
                    dh.Sdt_nguoi_nhan,
                    dh.Dia_chi_giao_hang,
                    dh.Thanh_tien,
                    dh.Trang_thai,
                    dh.Ngay_dat,
                    tk.Ho_ten as Ten_khach_hang
                FROM {$this->table} dh
                LEFT JOIN tai_khoan tk ON dh.ID_tk = tk.ID
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND dh.Trang_thai = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(dh.Ngay_dat) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(dh.Ngay_dat) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY dh.Ngay_dat DESC";
        
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    /**
     * ==========================================================================
     * STATISTICS
     * ==========================================================================
     */
    
    /**
     * Tổng doanh thu (chỉ tính đơn đã giao)
     * 
     * @return float
     */
    public function getTotalRevenue() {
        $sql = "SELECT SUM(Thanh_tien) as total 
                FROM {$this->table} 
                WHERE Trang_thai = 'da_giao'";
        
        $result = $this->db->query($sql)->fetch();
        return (float) ($result['total'] ?? 0);
    }
    
    /**
     * Tổng số đơn hàng
     * 
     * @return int
     */
    public function getTotalOrders() {
        return $this->count();
    }
    
    /**
     * Số đơn hàng hôm nay
     * 
     * @return int
     */
    public function getOrdersToday() {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} 
                WHERE DATE(Ngay_dat) = CURDATE()";
        
        $result = $this->db->query($sql)->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Doanh thu 7 ngày gần nhất (cho biểu đồ)
     * 
     * @return array
     */
    public function getRevenueLast7Days() {
        $sql = "SELECT 
                    DATE(Ngay_dat) as Ngay,
                    COUNT(*) as So_don_hang,
                    SUM(Thanh_tien) as Doanh_thu
                FROM {$this->table}
                WHERE Trang_thai = 'da_giao'
                    AND Ngay_dat >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(Ngay_dat)
                ORDER BY Ngay ASC";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Báo cáo doanh thu
     * 
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getRevenueReport($dateFrom, $dateTo) {
        $sql = "SELECT 
                    DATE(Ngay_dat) as Ngay,
                    COUNT(*) as So_don_hang,
                    SUM(Tong_tien) as Tong_tien_hang,
                    SUM(Phi_van_chuyen) as Tong_phi_ship,
                    SUM(Thanh_tien) as Tong_doanh_thu,
                    AVG(Thanh_tien) as Gia_tri_TB
                FROM {$this->table}
                WHERE Trang_thai = 'da_giao'
                    AND DATE(Ngay_dat) BETWEEN ? AND ?
                GROUP BY DATE(Ngay_dat)
                ORDER BY Ngay DESC";
        
        return $this->db->query($sql, [$dateFrom, $dateTo])->fetchAll();
    }
    
    /**
     * Thống kê theo trạng thái
     * 
     * @return array
     */
    public function getOrdersByStatus() {
        $sql = "SELECT 
                    Trang_thai,
                    COUNT(*) as So_luong,
                    SUM(Thanh_tien) as Tong_tien
                FROM {$this->table}
                GROUP BY Trang_thai";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    /**
     * Đếm tổng số đơn đã giao (tất cả thời gian)
     * 
     * @return int
     */
    public function countDeliveredTotal() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE Trang_thai = 'da_giao'";
        $result = $this->db->query($sql)->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Đếm đơn đã giao hôm nay để tính % tăng/giảm
     * 
     * @return int
     */
    public function countDeliveredToday() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE Trang_thai = 'da_giao' 
                AND DATE(Ngay_cap_nhat) = CURDATE()";
        $result = $this->db->query($sql)->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Đếm đơn đã giao hôm qua để tính % tăng/giảm
     * 
     * @return int
     */
    public function countDeliveredYesterday() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE Trang_thai = 'da_giao' 
                AND DATE(Ngay_cap_nhat) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        $result = $this->db->query($sql)->fetch();
        return (int) $result['total'];
    }
}