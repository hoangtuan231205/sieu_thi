<?php
/**
 * =============================================================================
 * ADMIN REPORT TRAIT - BÁO CÁO & THỐNG KÊ
 * =============================================================================
 * 
 * Chứa các methods liên quan đến báo cáo:
 * - reports() - Tổng quan báo cáo
 * - reportProfit() - Báo cáo lãi/lỗ
 * - reportExpiry() - Cảnh báo hết hạn
 * - reportTopProducts() - Sản phẩm bán chạy
 * 
 * Sử dụng trong AdminController:
 *   use AdminReportTrait;
 */

trait AdminReportTrait {
    
    /**
     * ==========================================================================
     * METHOD: reports() - BÁO CÁO THỐNG KÊ TỔNG QUAN
     * ==========================================================================
     * 
     * URL: /admin/reports
     */
    public function reports() {
        // Lấy tham số
        $reportType = get('type', 'revenue'); // revenue, products, customers
        $dateFrom = get('date_from', date('Y-m-01')); // Đầu tháng
        $dateTo = get('date_to', date('Y-m-d')); // Hôm nay
        
        $reportData = [];
        
        switch ($reportType) {
            case 'revenue':
                $reportData = $this->orderModel->getRevenueReport($dateFrom, $dateTo);
                break;
            case 'products':
                $reportData = $this->productModel->getProductReport($dateFrom, $dateTo);
                break;
            case 'customers':
                $reportData = $this->userModel->getCustomerReport($dateFrom, $dateTo);
                break;
        }
        
        $data = [
            'page_title' => 'Báo cáo thống kê - Admin',
            'report_type' => $reportType,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'report_data' => $reportData
        ];
        
        $this->view('admin/reports', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: reportProfit() - BÁO CÁO DOANH THU & LỢI NHUẬN
     * ==========================================================================
     * 
     * URL: /admin/report-profit
     */
    public function reportProfit() {
        $date_from = get('date_from', date('Y-m-01'));
        $date_to = get('date_to', date('Y-m-d'));
        
        $db = Database::getInstance();
        
        // Lấy dữ liệu lợi nhuận theo ngày
        // LưU Ý: Sử dụng Don_gia_von từ chi_tiet_don_hang (giá vốn thực tế tại thời điểm bán)
        // Điều này chính xác hơn so với dùng san_pham.Gia_nhap (giá vốn trung bình)
        $sql = "SELECT 
                    DATE(dh.Ngay_dat) as Ngay,
                    COALESCE(SUM(ct.thanh_tien), 0) as Doanh_thu,
                    COALESCE(SUM(ct.so_luong * CASE WHEN ct.Don_gia_von > 0 THEN ct.Don_gia_von ELSE COALESCE(p.Gia_nhap, p.Gia_tien * 0.7) END), 0) as Gia_von,
                    COALESCE(SUM(ct.thanh_tien) - SUM(ct.so_luong * CASE WHEN ct.Don_gia_von > 0 THEN ct.Don_gia_von ELSE COALESCE(p.Gia_nhap, p.Gia_tien * 0.7) END), 0) as Loi_nhuan,
                    CASE 
                        WHEN SUM(ct.thanh_tien) > 0 
                        THEN ROUND((SUM(ct.thanh_tien) - SUM(ct.so_luong * CASE WHEN ct.Don_gia_von > 0 THEN ct.Don_gia_von ELSE COALESCE(p.Gia_nhap, p.Gia_tien * 0.7) END)) / SUM(ct.thanh_tien) * 100, 1)
                        ELSE 0 
                    END as Ty_le_LN,
                    COUNT(DISTINCT dh.ID_dh) as So_don
                FROM don_hang dh
                JOIN chi_tiet_don_hang ct ON dh.ID_dh = ct.id_dh
                JOIN san_pham p ON ct.id_sp = p.ID_sp
                WHERE dh.Trang_thai = 'da_giao'
                AND DATE(dh.Ngay_dat) BETWEEN ? AND ?
                GROUP BY DATE(dh.Ngay_dat)
                ORDER BY Ngay DESC";
        
        $profit_data = $db->query($sql, [$date_from, $date_to])->fetchAll();
        
        // Tính tổng hợp
        $summary = [
            'doanh_thu' => array_sum(array_column($profit_data, 'Doanh_thu')),
            'gia_von' => array_sum(array_column($profit_data, 'Gia_von')),
            'loi_nhuan' => array_sum(array_column($profit_data, 'Loi_nhuan')),
            'so_don' => array_sum(array_column($profit_data, 'So_don'))
        ];
        
        // Sản phẩm có lợi nhuận cao nhất - Sử dụng Don_gia_von để tính lợi nhuận chính xác
        $top_sql = "SELECT 
                        p.ID_sp, p.Ten, p.Hinh_anh,
                        COALESCE(SUM(ct.thanh_tien) - SUM(ct.so_luong * CASE WHEN ct.Don_gia_von > 0 THEN ct.Don_gia_von ELSE COALESCE(p.Gia_nhap, p.Gia_tien * 0.7) END), 0) as LN_thuc
                    FROM san_pham p
                    JOIN chi_tiet_don_hang ct ON p.ID_sp = ct.id_sp
                    JOIN don_hang dh ON ct.id_dh = dh.ID_dh
                    WHERE dh.Trang_thai = 'da_giao'
                    AND DATE(dh.Ngay_dat) BETWEEN ? AND ?
                    GROUP BY p.ID_sp
                    ORDER BY LN_thuc DESC
                    LIMIT 10";
        
        $top_products = $db->query($top_sql, [$date_from, $date_to])->fetchAll();
        
        // Log báo cáo
        AuditLog::log('report', 0, 'EXPORT', null, [
            'type' => 'profit',
            'date_from' => $date_from,
            'date_to' => $date_to
        ], 'Xem báo cáo lãi/lỗ');
        
        $data = [
            'page_title' => 'Báo cáo Lãi/Lỗ - Admin',
            'date_from' => $date_from,
            'date_to' => $date_to,
            'profit_data' => $profit_data,
            'summary' => $summary,
            'top_products' => $top_products,
            'chart_data' => array_reverse($profit_data)
        ];
        
        $this->view('admin/report_profit', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: reportExpiry() - CẢNH BÁO HẾT HẠN
     * ==========================================================================
     * 
     * URL: /admin/report-expiry
     */
    public function reportExpiry() {
        $daysWarning = (int)get('days', 30);
        $categoryId = get('category', '');
        
        $db = Database::getInstance();
        
        // Truy vấn các lô hàng sắp hết hạn với tất cả các cột cần thiết cho view
        $sql = "SELECT 
                    ct.ID_chi_tiet_nhap,
                    p.ID_sp,
                    p.Ten as Ten_SP,
                    p.Ma_hien_thi as Ma_SP,
                    p.Hinh_anh,
                    p.Gia_tien,
                    dm.Ten_danh_muc,
                    pn.Ma_hien_thi as Ma_phieu_nhap,
                    pn.Ngay_nhap,
                    ct.Ngay_het_han,
                    COALESCE(ct.So_luong_con, ct.So_luong) as So_luong_con,
                    COALESCE(ct.So_luong_con, ct.So_luong) * p.Gia_tien as Gia_tri_ton,
                    DATEDIFF(ct.Ngay_het_han, CURDATE()) as So_ngay_con,
                    CASE 
                        WHEN DATEDIFF(ct.Ngay_het_han, CURDATE()) < 0 THEN 'DA_HET_HAN'
                        WHEN DATEDIFF(ct.Ngay_het_han, CURDATE()) <= 7 THEN 'TRONG_7_NGAY'
                        WHEN DATEDIFF(ct.Ngay_het_han, CURDATE()) <= 30 THEN 'TRONG_30_NGAY'
                        ELSE 'BINH_THUONG'
                    END as Muc_canh_bao
                FROM chi_tiet_phieu_nhap ct
                JOIN san_pham p ON ct.ID_sp = p.ID_sp
                LEFT JOIN danh_muc dm ON p.ID_danh_muc = dm.ID_danh_muc
                JOIN phieu_nhap_kho pn ON ct.ID_phieu_nhap = pn.ID_phieu_nhap
                WHERE ct.Ngay_het_han IS NOT NULL
                AND (ct.So_luong_con > 0 OR ct.So_luong_con IS NULL)
                AND DATEDIFF(ct.Ngay_het_han, CURDATE()) <= ?";
        
        $params = [$daysWarning];
        
        if ($categoryId) {
            $sql .= " AND p.ID_danh_muc = ?";
            $params[] = $categoryId;
        }
        
        $sql .= " AND ct.ID_chi_tiet_nhap NOT IN (
                    SELECT cth.ID_lo_nhap 
                    FROM chi_tiet_phieu_huy cth 
                    JOIN phieu_huy ph ON cth.ID_phieu_huy = ph.ID_phieu_huy 
                    WHERE ph.Trang_thai IN ('cho_duyet', 'da_duyet')
                    AND cth.ID_lo_nhap IS NOT NULL
                  )";
        
        $sql .= " ORDER BY ct.Ngay_het_han ASC";
        
        $expiringBatches = $db->query($sql, $params)->fetchAll();
        
        // Tính thống kê
        $stats = [
            'expired' => 0,
            'in_7_days' => 0,
            'in_30_days' => 0,
            'total_value' => 0
        ];
        
        foreach ($expiringBatches as $batch) {
            $days = (int)$batch['So_ngay_con'];
            $value = (float)$batch['Gia_tri_ton'];
            $stats['total_value'] += $value;
            
            if ($days < 0) {
                $stats['expired']++;
            } elseif ($days <= 7) {
                $stats['in_7_days']++;
            } elseif ($days <= 30) {
                $stats['in_30_days']++;
            }
        }
        
        // Lấy danh mục cho bộ lọc
        $categories = $db->query("SELECT ID_danh_muc, Ten_danh_muc FROM danh_muc WHERE Trang_thai = 'active' ORDER BY Ten_danh_muc")->fetchAll();
        
        $data = [
            'page_title' => 'Cảnh báo hết hạn - Admin',
            'days' => $daysWarning,
            'category_id' => $categoryId,
            'expiring_batches' => $expiringBatches,
            'stats' => $stats,
            'categories' => $categories
        ];
        
        $this->view('admin/report_expiry', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: reportTopProducts() - BÁO CÁO SẢN PHẨM BÁN CHẠY
     * ==========================================================================
     * 
     * URL: /admin/report-top-products
     */
    public function reportTopProducts() {
        $date_from = get('date_from', date('Y-m-01'));
        $date_to = get('date_to', date('Y-m-d'));
        $limit = (int)get('limit', 50);
        
        $db = Database::getInstance();
        
        // Sản phẩm bán chạy nhất
        $sql = "SELECT 
                    p.ID_sp,
                    p.Ma_hien_thi,
                    p.Ten,
                    p.Hinh_anh,
                    p.Gia_tien,
                    dm.Ten_danh_muc,
                    SUM(ct.So_luong) as Tong_ban,
                    SUM(ct.Thanh_tien) as Doanh_thu,
                    COUNT(DISTINCT ct.ID_dh) as So_don
                FROM chi_tiet_don_hang ct
                JOIN san_pham p ON ct.ID_sp = p.ID_sp
                LEFT JOIN danh_muc dm ON p.ID_danh_muc = dm.ID_danh_muc
                JOIN don_hang dh ON ct.ID_dh = dh.ID_dh
                WHERE dh.Trang_thai IN ('da_giao', 'dang_giao')
                AND DATE(dh.Ngay_dat) BETWEEN ? AND ?
                GROUP BY p.ID_sp
                ORDER BY Tong_ban DESC
                LIMIT ?";
        
        $topProducts = $db->query($sql, [$date_from, $date_to, $limit])->fetchAll();
        
        // Thống kê tổng hợp
        $summary = [
            'total_qty' => array_sum(array_column($topProducts, 'Tong_ban')),
            'total_revenue' => array_sum(array_column($topProducts, 'Doanh_thu')),
            'total_products' => count($topProducts)
        ];
        
        $data = [
            'page_title' => 'Sản phẩm bán chạy - Admin',
            'date_from' => $date_from,
            'date_to' => $date_to,
            'products' => $topProducts,
            'summary' => $summary
        ];
        
        $this->view('admin/report_top_products', $data);
    }
}
