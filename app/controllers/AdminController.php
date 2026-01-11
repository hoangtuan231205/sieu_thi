<?php
/**
 * =============================================================================
 * ADMIN CONTROLLER - QUẢN TRỊ HỆ THỐNG
 * =============================================================================
 * 
 * URL STRUCTURE:
 * - /admin                     → Dashboard
 * - /admin/products            → Quản lý sản phẩm (CRUD)
 * - /admin/categories          → Quản lý danh mục (CRUD)
 * - /admin/orders              → Quản lý đơn hàng
 * - /admin/users               → Quản lý người dùng
 * - /admin/reports             → Báo cáo thống kê
 * 
 * QUYỀN: Chỉ ADMIN mới truy cập được
 * 
 * TRAITS: Code được tổ chức thành các traits để dễ maintain:
 * - AdminReportTrait    → Báo cáo (reports, reportProfit, reportExpiry, reportTopProducts)
 * - AdminDisposalTrait  → Phiếu hủy (disposals, disposalAdd, disposalDetail, ...)
 * - AdminSupplierTrait  → Nhà cung cấp (suppliers, supplierAdd, supplierUpdate, ...)
 * - AdminUserTrait      → Quản lý người dùng (users)
 */

// Nạp các traits
require_once __DIR__ . '/traits/AdminCategoryTrait.php';
require_once __DIR__ . '/traits/AdminReportTrait.php';
require_once __DIR__ . '/traits/AdminDisposalTrait.php';
require_once __DIR__ . '/traits/AdminSupplierTrait.php';
require_once __DIR__ . '/traits/AdminUserTrait.php';

class AdminController extends Controller {
    
    // Sử dụng traits để tổ chức code
    use AdminCategoryTrait;
    use AdminReportTrait;
    use AdminDisposalTrait;
    use AdminSupplierTrait;
    use AdminUserTrait;
    
    private $productModel;
    private $categoryModel;
    private $orderModel;
    private $userModel;
    
    /**
     * Constructor - Kiểm tra quyền ADMIN
     */
    public function __construct() {
        Middleware::admin();
        
        $this->productModel = $this->model('Product');
        $this->categoryModel = $this->model('Category');
        $this->orderModel = $this->model('Order');
        $this->userModel = $this->model('User');
    }
    
    /**
     * ==========================================================================
     * METHOD: index() - DASHBOARD
     * ==========================================================================
     * 
     * URL: /admin
     * 
     * Hiển thị:
     * - Tổng quan: Doanh thu, đơn hàng, sản phẩm, user
     * - Biểu đồ doanh thu 7 ngày gần nhất
     * - Sản phẩm bán chạy
     * - Sản phẩm sắp hết hàng
     * - Đơn hàng mới nhất
     */
    public function index() {
        // =====================================================================
        // BƯỚC 1: THỐNG KÊ TỔNG QUAN
        // =====================================================================
        // =====================================================================
        // BƯỚC 1: THỐNG KÊ TỔNG QUAN (UPDATED FOR NEW DASHBOARD)
        // =====================================================================
        $db = Database::getInstance();

        // 1. Doanh thu & Đơn hàng
        $today = date('Y-m-d');
        $revenueStats = $db->query("
            SELECT 
                SUM(CASE WHEN DATE(Ngay_dat) = ? AND Trang_thai = 'da_giao' THEN Thanh_tien ELSE 0 END) as today_revenue,
                SUM(CASE WHEN Trang_thai = 'da_giao' THEN Thanh_tien ELSE 0 END) as total_revenue,
                COUNT(CASE WHEN DATE(Ngay_dat) = ? THEN 1 END) as today_orders,
                COUNT(*) as total_orders,
                COUNT(CASE WHEN Trang_thai = 'dang_xu_ly' THEN 1 END) as pending_orders
            FROM don_hang
        ", [$today, $today])->fetch();

        // 2. Thống kê sản phẩm
        $productStats = $db->query("
            SELECT 
                COUNT(*) as total_products,
                COUNT(CASE WHEN Ngay_tao >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_products_week,
                COUNT(CASE WHEN So_luong_ton <= 5 THEN 1 END) as critical_stock
            FROM san_pham
        ")->fetch();

        // 3. Thống kê hết hạn (Dữ liệu thực từ kiểm tra lô hàng)
        $expiryStats = $db->query("
            SELECT COUNT(DISTINCT ID_sp) as expired_count
            FROM chi_tiet_phieu_nhap
            WHERE Ngay_het_han <= CURDATE() 
            AND (So_luong_con > 0 OR So_luong_con IS NULL)
        ")->fetch();


        $stats = [
            'today_revenue' => $revenueStats['today_revenue'] ?? 0,
            'total_revenue' => $revenueStats['total_revenue'] ?? 0,
            'today_orders' => $revenueStats['today_orders'] ?? 0,
            'total_orders' => $revenueStats['total_orders'] ?? 0,
            'pending_orders' => $revenueStats['pending_orders'] ?? 0,
            'total_products' => $productStats['total_products'] ?? 0,
            'new_products_week' => $productStats['new_products_week'] ?? 0,
            'critical_stock' => $productStats['critical_stock'] ?? 0,
            'expired_products' => $expiryStats['expired_count'] ?? 0
        ];
        
        // =====================================================================
        // BƯỚC 2: DOANH THU 7 NGÀY GẦN NHẤT (cho biểu đồ)
        // =====================================================================
        $revenueChart = $this->orderModel->getRevenueLast7Days();
        
        // =====================================================================
        // BƯỚC 3: TOP 5 SẢN PHẨM BÁN CHẠY
        // =====================================================================
        $bestSellers = $this->productModel->getBestSellers(5);
        
        // =====================================================================
        // BƯỚC 4: SẢN PHẨM SẮP HẾT HÀNG (từ VIEW: v_san_pham_sap_het)
        // =====================================================================
        $lowStockProducts = $this->productModel->getLowStockProducts(10);
        
        // =====================================================================
        // BƯỚC 5: 10 ĐƠN HÀNG MỚI NHẤT
        // =====================================================================
        $recentOrders = $this->orderModel->getRecentOrders(10);
        
        // =====================================================================
        // BƯỚC 6: CHUẨN BỊ DATA
        // =====================================================================
        // DỮ LIỆU THỰC: Lấy cảnh báo hết hạn từ các lô hàng (chi_tiet_phieu_nhap)
        // =====================================================================
        $db = Database::getInstance();
        
        // Truy vấn các lô hàng đã hết hạn HOẶC sắp hết hạn trong 7 ngày tới
        // Chỉ xét các lô còn tồn kho (So_luong_con > 0 hoặc So_luong_con IS NULL)
        $sqlExpiry = "
            SELECT 
                ct.ID_sp, 
                sp.Ten AS name, 
                dm.Ten_danh_muc AS category,
                ct.Ngay_het_han,
                COALESCE(ct.So_luong_con, ct.So_luong) AS quantity,
                DATEDIFF(ct.Ngay_het_han, CURDATE()) AS days_left
            FROM chi_tiet_phieu_nhap ct
            JOIN san_pham sp ON ct.ID_sp = sp.ID_sp
            LEFT JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
            WHERE ct.Ngay_het_han IS NOT NULL 
            AND (ct.So_luong_con > 0 OR ct.So_luong_con IS NULL)
            AND ct.Ngay_het_han <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY ct.Ngay_het_han ASC
            LIMIT 10
        ";
        
        $rawExpiry = $db->query($sqlExpiry)->fetchAll();
        $expiry_products = [];
        
        foreach ($rawExpiry as $item) {
            $days = (int)$item['days_left'];
            
            // Logic xác định nhãn trạng thái
            if ($days < 0) {
                // Đã hết hạn
                $status = 'expired';
                $label = 'Đã hết hạn';
                $badge_class = 'red';
            } elseif ($days <= 7) {
                // Nguy cấp (<= 7 ngày)
                $status = 'expiring_soon';
                $label = 'Còn ' . $days . ' ngày';
                $badge_class = 'red';
            } elseif ($days <= 15) {
                // Cảnh báo (8-15 ngày)
                $status = 'expiring_soon';
                $label = 'Còn ' . $days . ' ngày';
                $badge_class = 'orange';
            } else {
                // Vàng (16-30 ngày)
                $status = 'expiring_soon';
                $label = 'Còn ' . $days . ' ngày';
                $badge_class = 'yellow';
            }
            
            $expiry_products[] = [
                'id' => $item['ID_sp'],
                'name' => $item['name'],
                'category' => $item['category'] ?? 'Khác',
                'qty' => $item['quantity'],
                'status' => $status,
                'label' => $label,
                'badge_class' => $badge_class,
                'days_left' => $days
            ];
        }

        // =====================================================================
        // BƯỚC 4: CATEGORY STATS (For Chart)
        // =====================================================================
        $catStatsRaw = $db->query("
            SELECT dm.Ten_danh_muc as name, COUNT(sp.ID_sp) as count
            FROM san_pham sp
            JOIN danh_muc dm ON sp.ID_danh_muc = dm.ID_danh_muc
            GROUP BY dm.ID_danh_muc
            ORDER BY count DESC
            LIMIT 5
        ")->fetchAll();

        $totalProds = array_sum(array_column($catStatsRaw, 'count'));
        $category_stats = [];
        $colors = ['#7BC043', '#3b82f6', '#8b5cf6', '#f97316', '#ef4444'];
        
        foreach ($catStatsRaw as $i => $cat) {
            $percent = $totalProds > 0 ? round(($cat['count'] / $totalProds) * 100) : 0;
            $category_stats[] = [
                'name' => $cat['name'],
                'percent' => $percent,
                'color' => $colors[$i % count($colors)]
            ];
        }

        // =====================================================================
        // BƯỚC 6: CHUẨN BỊ DATA (Mapped for new dashboard)
        // =====================================================================
        
        // Ánh xạ thống kê sang định dạng dashboard mới
        $dashStats = [
            'doanh_thu' => $stats['today_revenue'] ?? 0,
            'don_hang' => $stats['today_orders'] ?? 0,
            'san_pham' => $stats['total_products'] ?? 0,
            'don_cho_xu_ly' => $stats['pending_orders'] ?? 0,
            'sp_moi_tuan' => $stats['new_products_week'] ?? 0
        ];
        
        // Chuẩn bị dữ liệu biểu đồ (doanh thu 7 ngày)
        $chartLabels = [];
        $chartData = [];
        $profitData = [];
        $dates = [];

        // 1. Khởi tạo 7 ngày gần nhất với giá trị 0
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $dates[$d] = 0;
            // Ví dụ nhãn: 04/01 (Ngày/Tháng)
            $chartLabels[] = date('d/m', strtotime($d));
        }

        // 2. Điền dữ liệu thực
        if (!empty($revenueChart)) {
            foreach ($revenueChart as $row) {
                // Định dạng $row['Ngay'] là Y-m-d từ SQL
                $d = $row['Ngay'] ?? '';
                if (isset($dates[$d])) {
                    $dates[$d] = (float)($row['Doanh_thu'] ?? 0);
                }
            }
        }
        
        // 3. Chuyển đổi sang mảng cho Chart.js
        foreach ($dates as $rev) {
            // Chuyển đổi sang triệu, độ chính xác 3 chữ số thập phân (ví dụ: 0.129 Tr)
            $chartData[] = round($rev / 1000000, 3); 
            $profitData[] = round(($rev * 0.25) / 1000000, 3); // Ước tính 25% lợi nhuận
        }

        // Nếu không có dữ liệu (cài mới), có nên hiển thị mẫu? 
        // Không, giữ 0 để phản ánh thực tế. Nhưng nếu cần mẫu, kiểm tra tổng = 0.
        if (array_sum($chartData) == 0 && empty($revenueChart)) {
             // Tùy chọn: Bỏ comment để hiển thị mẫu demo
             // $chartData = [0.5, 0.8, 0.4, 1.2, 0.9, 1.5, 1.2];
             // $profitData = [0.1, 0.2, 0.1, 0.3, 0.2, 0.4, 0.3];
        }
        
        // Ánh xạ sản phẩm hết hạn sang định dạng mới
        $expiringProducts = [];
        foreach ($expiry_products as $exp) {
            $expiringProducts[] = [
                'ID_sp' => $exp['id'],
                'Ten' => $exp['name'],
                'Ten_danh_muc' => $exp['category'] ?? 'Chưa phân loại',
                'So_luong_ton' => $exp['quantity'] ?? 0,
                'Ngay_con_lai' => $exp['days_left']
            ];
        }
        
        // Ánh xạ thống kê danh mục sang định dạng mới
        $categoryStats = [];
        foreach ($category_stats as $cat) {
            $categoryStats[] = [
                'Ten_danh_muc' => $cat['name'],
                'percent' => $cat['percent']
            ];
        }
        
        $data = [
            'page_title' => 'Dashboard - Admin',
            'stats' => $dashStats,
            'low_stock_products' => $lowStockProducts,
            'recent_orders' => $recentOrders,
            'expiring_products' => $expiringProducts,
            'category_stats' => $categoryStats,
            'chart_data' => $chartData,
            'profit_data' => $profitData,
            'chart_labels' => $chartLabels, // Truyền nhãn động
            'user_name' => Session::get('user_name'),
            'user_role' => 'Admin'
        ];
        
        $this->view('admin/dashboard', $data);
    }
    
    /**
     * Dashboard alias - ánh xạ /admin/dashboard đến index()
     */
    public function dashboard() {
        $this->index();
    }
    
    /**
     * SEED DATA CHO KIỂM THỬ HẾT HẠN
     * URL: /admin/seed
     */
    public function seed() {
        $db = Database::getInstance();
        
        try {
            $db->beginTransaction();
            
            // 1. Tạo phiếu nhập
            $id_phieu = $db->insert("INSERT INTO phieu_nhap_kho (Ma_hien_thi, Nguoi_tao, Ngay_nhap, Tong_tien, Ghi_chu) VALUES (?, ?, CURDATE(), ?, ?)", 
                ['PN-TEST-' . time(), 1, 5000000, 'Data mẫu alert hết hạn']);
                
            // 2. Lấy ngẫu nhiên sản phẩm trước để tránh lỗi khóa bảng (Error 1442)
            // Lỗi xảy ra vì Trigger trên chi_tiet_phieu_nhap cố cập nhật san_pham trong khi ta đang đọc.
            $products = $db->query("SELECT ID_sp, Ten FROM san_pham LIMIT 3")->fetchAll();
            
            if (count($products) < 3) {
                 throw new Exception("Cần ít nhất 3 sản phẩm trong database để tạo dữ liệu mẫu.");
            }

            // 3. Thêm các lô hàng sử dụng dữ liệu đã lấy
            
            // Đã hết hạn (5 ngày trước) - Sản phẩm 1
            $p1 = $products[0];
            $db->query("INSERT INTO chi_tiet_phieu_nhap (ID_phieu_nhap, ID_sp, Ten_sp, So_luong, Don_gia_nhap, Thanh_tien, Ngay_het_han, So_luong_con) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                [$id_phieu, $p1['ID_sp'], $p1['Ten'], 10, 50000, 500000, date('Y-m-d', strtotime('-5 days')), 5]);
                
            // Sắp hết hạn (còn 3 ngày) - Sản phẩm 2
            $p2 = $products[1];
            $db->query("INSERT INTO chi_tiet_phieu_nhap (ID_phieu_nhap, ID_sp, Ten_sp, So_luong, Don_gia_nhap, Thanh_tien, Ngay_het_han, So_luong_con) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                [$id_phieu, $p2['ID_sp'], $p2['Ten'], 20, 20000, 400000, date('Y-m-d', strtotime('+3 days')), 15]);
                
            // Cảnh báo (còn 15 ngày) - Sản phẩm 3
            $p3 = $products[2];
            $db->query("INSERT INTO chi_tiet_phieu_nhap (ID_phieu_nhap, ID_sp, Ten_sp, So_luong, Don_gia_nhap, Thanh_tien, Ngay_het_han, So_luong_con) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                [$id_phieu, $p3['ID_sp'], $p3['Ten'], 50, 10000, 500000, date('Y-m-d', strtotime('+15 days')), 50]);

            $db->commit();
            echo "SUCCESS: Đã thêm dữ liệu mẫu (1 phiếu nhập + 3 lô hàng có HSD)! <a href='" . BASE_URL . "/public/admin/dashboard'>Quay lại Dashboard</a>";
            
        } catch (Exception $e) {
            $db->rollBack();
            echo "ERROR: " . $e->getMessage();
        }
    }
    
    /**
     * ==========================================================================
     * METHOD: products() - QUẢN LÝ SẢN PHẨM (LIST + CRUD)
     * ==========================================================================
     * 
     * URL: /admin/products
     * URL với filter: /admin/products?category=5&search=sữa&page=2
     */
    public function products() {
        // =====================================================================
        // BƯỚC 1: LẤY FILTERS
        // =====================================================================
        $filters = [
            'category_id' => get('category', ''),
            'keyword' => get('search', ''),
            'status' => get('status', ''),
            'page' => max(1, (int)get('page', 1))
        ];
        
        // =====================================================================
        // BƯỚC 2: ĐẾM TỔNG SỐ SẢN PHẨM
        // =====================================================================
        $totalProducts = $this->productModel->countProductsForAdmin($filters);
        
        // =====================================================================
        // BƯỚC 3: PHÂN TRANG
        // =====================================================================
        $perPage = 10;
        $currentPage = $filters['page'];
        $lastPage = ceil($totalProducts / $perPage);
        $offset = ($currentPage - 1) * $perPage;
    
        // Tính from và to
        $from = $totalProducts > 0 ? $offset + 1 : 0;
        $to = min($offset + $perPage, $totalProducts);
    
        $pagination = [
            'total' => $totalProducts,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'offset' => $offset,
            'from' => $from,
            'to' => $to
    ];
        // =====================================================================
        // BƯỚC 4: LẤY DANH SÁCH SẢN PHẨM
        // =====================================================================
        $products = $this->productModel->getProductsForAdmin(
            $filters,
            $pagination['per_page'],
            $pagination['offset']
        );
        
        // =====================================================================
        // BƯỚC 5: LẤY DANH MỤC
        // =====================================================================
        $categories = $this->categoryModel->getCategoriesTree();
        
        // =====================================================================
        // BƯỚC 6: CHUẨN BỊ DATA
        // =====================================================================
        $data = [
            'page_title' => 'Quản lý sản phẩm - Admin',
            'products' => $products,
            'categories' => $categories,
            'filters' => $filters,
            'pagination' => $pagination,
            'total_products' => $totalProducts,
            'csrf_token' => Session::getCsrfToken()
        ];
        
        $this->view('admin/products', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: productAdd() - THÊM SẢN PHẨM MỚI
     * ==========================================================================
     * 
     * URL: /admin/product-add (GET + POST)
     */
    public function productAdd() {
        // =====================================================================
        // POST: XỬ LÝ THÊM SẢN PHẨM
        // =====================================================================
        if ($this->isMethod('POST')) {
            // Verify CSRF
            if (!Middleware::verifyCsrf(post('csrf_token', ''))) {
                $this->json(['success' => false, 'message' => 'Invalid token'], 400);
            }
            
            // Kiểm tra dữ liệu - Đã loại bỏ kiểm tra 'stock' (tồn kho được quản lý qua phiếu nhập kho)
            $validation = $this->validate($_POST, [
                'name' => 'required|max:200',
                'category_id' => 'required|numeric',
                'price' => 'required|numeric',
                'unit' => 'required|max:50'
            ]);
            
            if (!$validation['valid']) {
                $firstError = reset($validation['errors']);
                $this->json(['success' => false, 'message' => $firstError[0]]);
            }
            
            // Upload ảnh (nếu có)
            $imageName = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $uploadResult = $this->uploadFile($_FILES['image'], UPLOAD_PRODUCT_PATH);
                if ($uploadResult['success']) {
                    $imageName = $uploadResult['filename'];
                }
            }
            
            // Chuẩn bị data
            // NOTE: So_luong_ton = 0 - Tồn kho CHỈ được thêm qua phiếu nhập kho
            // Đây là quy tắc nghiệp vụ: Product creation = Master data only
            $productData = [
                'Ten' => $this->sanitize(post('name')),
                'ID_danh_muc' => (int)post('category_id'),
                'Gia_tien' => (float)post('price'),
                'So_luong_ton' => 0, // FIXED: Stock = 0, only increased via warehouse import
                'Don_vi_tinh' => $this->sanitize(post('unit')),
                'Xuat_xu' => $this->sanitize(post('origin', '')),
                'Thanh_phan' => $this->sanitize(post('ingredients', '')),
                'Mo_ta_sp' => $this->sanitize(post('description', '')),
                'Hinh_anh' => $imageName,
                'Trang_thai' => 'active'
            ];
            
            // Set user ID cho trigger ghi log
            $this->productModel->setCurrentUserId(Session::getUserId());
            
            // Thêm sản phẩm
            $productId = $this->productModel->create($productData);
            
            if ($productId) {
                Session::flash('success', 'Thêm sản phẩm thành công');
                    redirect('/admin/products');
            } else {
                Session::flash('error', 'Thêm sản phẩm thất bại');
                redirect('/admin/product-add');
            }
        }
        
        // =====================================================================
        // GET: HIỂN THỊ FORM THÊM
        // =====================================================================
        $data = [
            'page_title' => 'Thêm sản phẩm mới - Admin',
            'categories' => $this->categoryModel->getCategoriesNested(),
            'csrf_token' => Session::getCsrfToken()
        ];
        
        $this->view('admin/product_add', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: productEdit() - SỬA SẢN PHẨM
     * ==========================================================================
     * 
     * URL: /admin/product-edit/5 (GET + POST)
     */
    public function productEdit($productId = null) {
        if (!$productId || !is_numeric($productId)) {
            Session::flash('error', 'Sản phẩm không tồn tại');
            redirect('/admin/products');
        }
        
        // =====================================================================
        // POST: XỬ LÝ CẬP NHẬT
        // =====================================================================
        if ($this->isMethod('POST')) {
            if (!Middleware::verifyCsrf(post('csrf_token', ''))) {
                $this->json(['success' => false, 'message' => 'Invalid token'], 400);
            }
            
            // Validate
            $validation = $this->validate($_POST, [
                'name' => 'required|max:200',
                'category_id' => 'required|numeric',
                'price' => 'required|numeric',
                'unit' => 'required|max:50'
            ]);
            
            if (!$validation['valid']) {
                $firstError = reset($validation['errors']);
                $this->json(['success' => false, 'message' => $firstError[0]]);
            }
            
            // Kiểm tra sản phẩm tồn tại
            $product = $this->productModel->findById($productId);
            if (!$product) {
                $this->json(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
            }
            
            // Upload ảnh mới (nếu có)
            $imageName = $product['Hinh_anh'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                // Xóa ảnh cũ
                if ($imageName && file_exists(UPLOAD_PRODUCT_PATH . '/' . $imageName)) {
                    unlink(UPLOAD_PRODUCT_PATH . '/' . $imageName);
                }
                
                $uploadResult = $this->uploadFile($_FILES['image'], UPLOAD_PRODUCT_PATH);
                if ($uploadResult['success']) {
                    $imageName = $uploadResult['filename'];
                }
            }
            
            // Chuẩn bị data
            $productData = [
                'Ten' => $this->sanitize(post('name')),
                'ID_danh_muc' => (int)post('category_id'),
                'Gia_tien' => (float)post('price'),
                // KHÔNG cập nhật số lượng tồn ở đây
                'Don_vi_tinh' => $this->sanitize(post('unit')),
                'Xuat_xu' => $this->sanitize(post('origin', '')),
                'Thanh_phan' => $this->sanitize(post('ingredients', '')),
                'Mo_ta_sp' => $this->sanitize(post('description', '')),
                'Hinh_anh' => $imageName,
                'Trang_thai' => post('status', 'active')
            ];
            
            // Set user ID cho trigger ghi log
            $this->productModel->setCurrentUserId(Session::getUserId());
            
            // Cập nhật
            $result = $this->productModel->update($productId, $productData);
            
            if ($result) {
                Session::flash('success', 'Cập nhật sản phẩm thành công');
                redirect(BASE_URL . '/admin/products');
            } else {
                Session::flash('error', 'Cập nhật sản phẩm thất bại');
                redirect(BASE_URL . '/admin/product-edit/' . $productId);
            }
        }
        
        // =====================================================================
        // GET: HIỂN THỊ FORM SỬA
        // =====================================================================
        $product = $this->productModel->findById($productId);
        
        if (!$product) {
            Session::flash('error', 'Sản phẩm không tồn tại');
            redirect(BASE_URL . '/admin/products');
        }
        
        $data = [
            'page_title' => 'Sửa sản phẩm - Admin',
            'product' => $product,
            'categories' => $this->categoryModel->getCategoriesNested(),
            'csrf_token' => Session::getCsrfToken()
        ];
        
        $this->view('admin/product_edit', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: productSave() - LƯU SẢN PHẨM (ADD/EDIT VIA MODAL)
     * ==========================================================================
     * 
     * URL: /admin/product-save (POST)
     * Handles both Create (no ID) and Update (has ID)
     */
    public function productSave() {
        if (!$this->isMethod('POST')) {
            Session::flash('error', 'Invalid method');
            redirect(BASE_URL . '/admin/products');
        }
        
        $id = post('id');
        
        // Router to Add or Edit based on ID presence
        if (!empty($id) && is_numeric($id)) {
            // Edit logic
            // Manually invoke productEdit but handle logic here to reuse code or redirect
            // Since productEdit handles POST checks itself if we call it, but we need to pass ID.
            // However, productEdit expects $productId as argument for URL router.
            // For cleaner code, let's implement the logic here directly or refactor.
            // Re-simulating POST handling for Edit:
            
            // Verify and Validate
            if (!Middleware::verifyCsrf(post('csrf_token', ''))) {
                 Session::flash('error', 'Invalid token');
                 redirect(BASE_URL . '/admin/products');
            }
            
            $validation = $this->validate($_POST, [
                'ten' => 'required|max:200',
                'danh_muc_id' => 'required|numeric',
                'gia_tien' => 'required|numeric',
                'don_vi' => 'required|max:50'
            ]);
            
            if (!$validation['valid']) {
                $firstError = reset($validation['errors']);
                Session::flash('error', $firstError[0]);
                redirect(BASE_URL . '/admin/products');
            }

            // ... (Image upload and Update Logic equivalent to productEdit)
            $product = $this->productModel->findById($id);
            if (!$product) {
                Session::flash('error', 'Product not found');
                redirect(BASE_URL . '/admin/products');
            }
            
            $imageName = $product['Hinh_anh'];
             if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == 0) {
                if ($imageName && file_exists(UPLOAD_PRODUCT_PATH . '/' . $imageName)) {
                    unlink(UPLOAD_PRODUCT_PATH . '/' . $imageName);
                }
                $uploadResult = $this->uploadFile($_FILES['hinh_anh'], UPLOAD_PRODUCT_PATH);
                if ($uploadResult['success']) {
                    $imageName = $uploadResult['filename'];
                }
            }

            $productData = [
                'Ten' => $this->sanitize(post('ten')),
                'ID_danh_muc' => (int)post('danh_muc_id'),
                'Gia_tien' => (float)post('gia_tien'),
                'Gia_nhap' => (float)post('gia_nhap', 0),
                // NO STOCK UPDATE
                'Don_vi_tinh' => $this->sanitize(post('don_vi')),
                'Ma_hien_thi' => $this->sanitize(post('ma_hien_thi', '')),
                'Mo_ta_sp' => $this->sanitize(post('mo_ta', '')),
                'Hinh_anh' => $imageName,
                'Trang_thai' => post('trang_thai', 'active')
            ];
            
             // Create SKU if empty
             if (empty($productData['Ma_hien_thi'])) {
                $productData['Ma_hien_thi'] = 'SP' . str_pad($id, 6, '0', STR_PAD_LEFT);
             }

            $this->productModel->setCurrentUserId(Session::getUserId());
            $this->productModel->update($id, $productData);
            Session::flash('success', 'Đã cập nhật sản phẩm');
            redirect(BASE_URL . '/admin/products');

        } else {
            // Add logic
            if (!Middleware::verifyCsrf(post('csrf_token', ''))) {
                 Session::flash('error', 'Invalid token');
                 redirect(BASE_URL . '/admin/products');
            }
             $validation = $this->validate($_POST, [
                'ten' => 'required|max:200', 
                'danh_muc_id' => 'required|numeric',
                'gia_tien' => 'required|numeric',
                 'don_vi' => 'required|max:50'
            ]);
            
            if (!$validation['valid']) {
                 Session::flash('error', 'Vui lòng kiểm tra lại thông tin');
                 redirect(BASE_URL . '/admin/products');
            }
            
            $imageName = null;
            if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] == 0) {
                $uploadResult = $this->uploadFile($_FILES['hinh_anh'], UPLOAD_PRODUCT_PATH);
                if ($uploadResult['success']) $imageName = $uploadResult['filename'];
            }
            
            $productData = [
                'Ten' => $this->sanitize(post('ten')),
                'ID_danh_muc' => (int)post('danh_muc_id'),
                'Gia_tien' => (float)post('gia_tien'),
                'Gia_nhap' => (float)post('gia_nhap', 0),
                'So_luong_ton' => 0, // Initial 0
                'Don_vi_tinh' => $this->sanitize(post('don_vi')),
                 'Ma_hien_thi' => $this->sanitize(post('ma_hien_thi', '')),
                'Mo_ta_sp' => $this->sanitize(post('mo_ta', '')),
                'Hinh_anh' => $imageName,
                'Trang_thai' => post('trang_thai', 'active')
            ];
            
            $this->productModel->setCurrentUserId(Session::getUserId());
            $newId = $this->productModel->create($productData);
            
             if (!$productData['Ma_hien_thi'] && $newId) {
                $this->productModel->update($newId, ['Ma_hien_thi' => 'SP' . str_pad($newId, 6, '0', STR_PAD_LEFT)]);
             }

            Session::flash('success', 'Thêm sản phẩm thành công');
            redirect(BASE_URL . '/admin/products');
        }
    }
    
    /**
     * ==========================================================================
     * METHOD: productDelete() - XÓA SẢN PHẨM
     * ==========================================================================
     * 
     * URL: /admin/product-delete (POST - AJAX or Form)
     */
    public function productDelete() {
        $isAjax = $this->isAjax();
        
        if (!$this->isMethod('POST')) {
            if ($isAjax) {
                $this->json(['error' => 'Invalid request'], 400);
            } else {
                Session::flash('error', 'Phương thức không hợp lệ');
                redirect(BASE_URL . '/admin/products');
            }
            return;
        }
        
        $productId = (int)post('product_id', 0);
        
        if (!$productId) {
            if ($isAjax) {
                $this->json(['success' => false, 'message' => 'ID không hợp lệ']);
            } else {
                Session::flash('error', 'ID sản phẩm không hợp lệ');
                redirect(BASE_URL . '/admin/products');
            }
            return;
        }
        
        // Kiểm tra sản phẩm có trong đơn hàng chưa
        if ($this->productModel->hasOrders($productId)) {
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => 'Không thể xóa sản phẩm đã có trong đơn hàng'
                ]);
            } else {
                Session::flash('error', 'Không thể xóa sản phẩm đã có trong đơn hàng');
                redirect(BASE_URL . '/admin/products');
            }
            return;
        }
        
        // Set user ID
        $this->productModel->setCurrentUserId(Session::getUserId());
        
        // Xóa sản phẩm
        $result = $this->productModel->delete($productId);
        
        if ($result) {
            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => 'Xóa sản phẩm thành công'
                ]);
            } else {
                Session::flash('success', 'Xóa sản phẩm thành công');
                redirect(BASE_URL . '/admin/products');
            }
        } else {
            if ($isAjax) {
                $this->json([
                    'success' => false,
                    'message' => 'Xóa sản phẩm thất bại'
                ]);
            } else {
                Session::flash('error', 'Xóa sản phẩm thất bại');
                redirect(BASE_URL . '/admin/products');
            }
        }
    }

    /**
     * ==========================================================================
     * METHOD: getProductDetail() - LẤY CHI TIẾT SẢN PHẨM (AJAX)
     * ==========================================================================
     * 
     * URL: /admin/get-product-detail?id=123
     */
    public function getProductDetail() {
        if (!$this->isAjax()) {
            $this->json(['error' => 'Invalid request'], 400);
            return;
        }
        
        $id = (int)get('id');
        if (!$id) {
            $this->json(['success' => false, 'message' => 'Missing ID']);
            return;
        }
        
        $product = $this->productModel->findById($id);
        if ($product) {
            $this->json(['success' => true, 'product' => $product]);
        } else {
            $this->json(['success' => false, 'message' => 'Not found']);
        }
    }
    
    // =========================================================================
    // CATEGORY METHODS → AdminCategoryTrait
    // Methods: categories, categoryAdd, categoryEdit, categorySave, categoryDelete
    // =========================================================================
    
    /**
     * ==========================================================================
     * METHOD: orders() - QUẢN LÝ ĐƠN HÀNG
     * ==========================================================================
     * 
     * URL: /admin/orders
     */
    public function orders() {
        // Lấy filters
        $filters = [
            'status' => get('status', ''),
            'date_from' => get('date_from', ''),
            'date_to' => get('date_to', ''),
            'keyword' => get('search', ''),
            'page' => max(1, (int)get('page', 1))
        ];
        
        // Đếm tổng đơn hàng
        $totalOrders = $this->orderModel->countAllOrders($filters);
        
        // Phân trang
        $perPage = 20;
        // $pagination = $this->paginate($totalOrders, $perPage, $filters['page']);
        
        // Tính toán thủ công để đảm bảo đủ keys cho view
        $totalPages = ceil($totalOrders / $perPage);
        if ($totalPages < 1) $totalPages = 1;
        
        $offset = ($filters['page'] - 1) * $perPage;
        
        $pagination = [
            'total' => $totalOrders,
            'per_page' => $perPage,
            'current_page' => $filters['page'],
            'last_page' => $totalPages,
            'from' => ($totalOrders > 0) ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $totalOrders),
            'offset' => $offset
        ];
        
        // Lấy danh sách đơn hàng
        $orders = $this->orderModel->getAllOrders(
            $filters,
            $pagination['per_page'],
            $pagination['offset']
        );
        
        // Đếm theo trạng thái
        $statusCounts = [
            'all' => $this->orderModel->countAllOrders(['status' => '']),
            'dang_xu_ly' => $this->orderModel->countAllOrders(['status' => 'dang_xu_ly']),
            'dang_giao' => $this->orderModel->countAllOrders(['status' => 'dang_giao']),
            'da_giao' => $this->orderModel->countAllOrders(['status' => 'da_giao']),
            'huy' => $this->orderModel->countAllOrders(['status' => 'huy'])
        ];
        
        // Thống kê cho 4 stat cards
        $deliveredToday = $this->orderModel->countDeliveredToday();
        $deliveredYesterday = $this->orderModel->countDeliveredYesterday();
        $percentChange = $deliveredYesterday > 0 
            ? round((($deliveredToday - $deliveredYesterday) / $deliveredYesterday) * 100) 
            : ($deliveredToday > 0 ? 100 : 0);
        
        $statusStats = [
            'pending' => $statusCounts['dang_xu_ly'],
            'shipping' => $statusCounts['dang_giao'],
            'delivered' => $this->orderModel->countDeliveredTotal(),
            'cancelled' => $statusCounts['huy'],
            'delivered_today' => $deliveredToday,
            'percent_change' => $percentChange
        ];
        
        $data = [
            'page_title' => 'Quản lý đơn hàng - Admin',
            'orders' => $orders,
            'filters' => $filters,
            'pagination' => $pagination,
            'status_counts' => $statusCounts,
            'status_stats' => $statusStats,
            'csrf_token' => Session::getCsrfToken(),
            'cart_count' => Session::getCartCount()  // Added for header cart badge
        ];
        
        $this->view('admin/orders/index', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: orderDetail() - CHI TIẾT ĐƠN HÀNG (ADMIN)
     * ==========================================================================
     * 
     * URL: /admin/order-detail/5
     */
    public function orderDetail($orderId = null) {
        if (!$orderId || !is_numeric($orderId)) {
            Session::flash('error', 'Đơn hàng không tồn tại');
            redirect('/admin/orders');
        }
        
        $order = $this->orderModel->findById($orderId);
        
        if (!$order) {
            Session::flash('error', 'Đơn hàng không tồn tại');
            redirect('/admin/orders');
        }
        
        $orderDetails = $this->orderModel->getOrderDetails($orderId);
        $timeline = $this->orderModel->getOrderTimeline($order);
        
        $data = [
            'page_title' => 'Chi tiết đơn hàng #' . $orderId . ' - Admin',
            'order' => $order,
            'order_details' => $orderDetails,
            'timeline' => $timeline,
            'csrf_token' => Session::getCsrfToken()
        ];
        
        $this->view('admin/order_detail', $data);
    }
    
    /**
     * ==========================================================================
     * METHOD: orderUpdateStatus() - CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG (AJAX)
     * ==========================================================================
     * 
     * URL: /admin/order-update-status (POST AJAX)
     * 
     * Params:
     * - order_id: ID đơn hàng
     * - status: Trạng thái mới (dang_xu_ly, dang_giao, da_giao, huy)
     */
    public function orderUpdateStatus() {
        if (!$this->isAjax() || !$this->isMethod('POST')) {
            $this->json(['error' => 'Invalid request'], 400);
        }
        
        // CSRF Check
        if (!Middleware::verifyCsrf($_POST['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'Phiên làm việc hết hạn, vui lòng tải lại trang']);
            return;
        }
        
        $orderId = (int)post('order_id', 0);
        $newStatus = post('status', '');
        
        // Validate
        $allowedStatuses = ['dang_xu_ly', 'dang_giao', 'da_giao', 'huy'];
        
        if (!$orderId || !in_array($newStatus, $allowedStatuses)) {
            $this->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
        }
        
        // Kiểm tra trạng thái hiện tại (để tránh lỗi update trùng)
        $currentOrder = $this->orderModel->findById($orderId);
        if (!$currentOrder) {
            $this->json(['success' => false, 'message' => 'Đơn hàng không tồn tại']);
            return;
        }
        
        if ($currentOrder['Trang_thai'] === $newStatus) {
             $this->json(['success' => true, 'message' => 'Trạng thái đã được cập nhật (Không có thay đổi)']);
             return;
        }
        
        // Cập nhật trạng thái
        if ($newStatus === 'huy') {
            // Nếu hủy, dùng hàm này để hoàn kho
            $result = $this->orderModel->cancelOrder($orderId);
        } else {
            // Các trạng thái khác chỉ cập nhật status
            $result = $this->orderModel->updateStatus($orderId, $newStatus);
        }
        
        if ($result) {
            // Ghi log hoạt động
            Middleware::logActivity('update_order_status', [
                'admin_id' => Session::getUserId(),
                'order_id' => $orderId,
                'new_status' => $newStatus
            ]);
            
            $this->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công'
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Cập nhật thất bại (Có thể đơn hàng đã hủy hoặc trạng thái không hợp lệ)']);
        }
    }
    
    /**
     * ==========================================================================
     * METHOD: bulkUpdateOrderStatus() - CẬP NHẬT TRẠNG THÁI HÀNG LOẠT (AJAX)
     * ==========================================================================
     * 
     * URL: /admin/bulk-update-order-status (POST AJAX)
     * 
     * Luồng trạng thái 1 chiều: dang_xu_ly -> dang_giao -> da_giao
     * Đơn hàng đã hủy hoặc đã giao không được phép cập nhật.
     */
    public function bulkUpdateOrderStatus() {
        if (!$this->isAjax() || !$this->isMethod('POST')) {
            $this->json(['error' => 'Invalid request'], 400);
            return;
        }
        
        // Đọc JSON body
        $input = json_decode(file_get_contents('php://input'), true);
        
        // CSRF Check
        if (!Middleware::verifyCsrf($input['csrf_token'] ?? '')) {
            $this->json(['success' => false, 'message' => 'Phiên làm việc hết hạn, vui lòng tải lại trang']);
            return;
        }
        
        $orderIds = $input['order_ids'] ?? [];
        $newStatus = $input['new_status'] ?? '';
        
        // Validate input
        if (empty($orderIds) || !is_array($orderIds)) {
            $this->json(['success' => false, 'message' => 'Không có đơn hàng nào được chọn']);
            return;
        }
        
        // Validate new status (chỉ cho phép 2 trạng thái tiến tới)
        $allowedNextStatuses = ['dang_giao', 'da_giao'];
        if (!in_array($newStatus, $allowedNextStatuses)) {
            $this->json(['success' => false, 'message' => 'Trạng thái mới không hợp lệ']);
            return;
        }
        
        // Xác định trạng thái hiện tại hợp lệ dựa trên trạng thái mới
        $validCurrentStatus = ($newStatus === 'dang_giao') ? 'dang_xu_ly' : 'dang_giao';
        
        $successCount = 0;
        $failCount = 0;
        $errors = [];
        
        foreach ($orderIds as $orderId) {
            $orderId = (int)$orderId;
            if ($orderId <= 0) continue;
            
            // Kiểm tra đơn hàng
            $order = $this->orderModel->findById($orderId);
            if (!$order) {
                $failCount++;
                $errors[] = "Đơn #{$orderId} không tồn tại";
                continue;
            }
            
            // Kiểm tra trạng thái hiện tại
            if ($order['Trang_thai'] === 'huy') {
                $failCount++;
                $errors[] = "Đơn #{$orderId} đã hủy, không thể cập nhật";
                continue;
            }
            
            if ($order['Trang_thai'] !== $validCurrentStatus) {
                $failCount++;
                $errors[] = "Đơn #{$orderId} không ở trạng thái phù hợp để chuyển";
                continue;
            }
            
            // Cập nhật
            $result = $this->orderModel->updateStatus($orderId, $newStatus);
            if ($result) {
                $successCount++;
                
                // Log activity
                Middleware::logActivity('bulk_update_order_status', [
                    'admin_id' => Session::getUserId(),
                    'order_id' => $orderId,
                    'new_status' => $newStatus
                ]);
            } else {
                $failCount++;
                $errors[] = "Đơn #{$orderId} cập nhật thất bại";
            }
        }
        
        // Trả kết quả
        if ($successCount > 0 && $failCount === 0) {
            $this->json([
                'success' => true,
                'message' => "Đã cập nhật thành công {$successCount} đơn hàng"
            ]);
        } elseif ($successCount > 0 && $failCount > 0) {
            $total = $successCount + $failCount;
            $this->json([
                'success' => true,
                'message' => "Cập nhật {$successCount}/{$total} đơn. Lỗi: " . implode(', ', $errors)
            ]);
        } else {
            $this->json([
                'success' => false,
                'message' => 'Không có đơn hàng nào được cập nhật. ' . implode(', ', $errors)
            ]);
        }
    }
    
    // =========================================================================
    // USER METHODS → AdminUserTrait
    // Method users() is implemented in AdminUserTrait
    // =========================================================================
    
    // =========================================================================
    // SUPPLIER METHODS → AdminSupplierTrait
    // Methods: suppliers, supplierGet, supplierAdd, supplierUpdate, supplierDelete
    // =========================================================================
    
    /**
     * ==========================================================================
     * METHOD: exportProducts() - XUẤT EXCEL SẢN PHẨM
     * ==========================================================================
     * 
     * URL: /admin/export-products
     */
    public function exportProducts() {
        // Lấy tất cả sản phẩm
        $products = $this->productModel->getAllProductsForExport();
        
        // Tạo file Excel với HTML table format (Excel có thể đọc đúng)
        $filename = 'san_pham_' . date('Y-m-d_His') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Xuất định dạng bảng HTML mà Excel có thể đọc
        echo '<!DOCTYPE html>';
        echo '<html>';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body>';
        echo '<table border="1">';
        
        // Dòng tiêu đề
        echo '<tr style="background-color: #496C2C; color: white; font-weight: bold;">';
        echo '<th>Mã SP</th>';
        echo '<th>Tên sản phẩm</th>';
        echo '<th>Danh mục</th>';
        echo '<th>Giá tiền</th>';
        echo '<th>Số lượng tồn</th>';
        echo '<th>Đơn vị tính</th>';
        echo '<th>Xuất xứ</th>';
        echo '<th>Trạng thái</th>';
        echo '<th>Ngày tạo</th>';
        echo '</tr>';
        
        // Các dòng dữ liệu
        foreach ($products as $product) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($product['Ma_hien_thi'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($product['Ten'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($product['Ten_danh_muc'] ?? '') . '</td>';
            echo '<td style="text-align: right;">' . number_format($product['Gia_tien'] ?? 0) . '</td>';
            echo '<td style="text-align: center;">' . ($product['So_luong_ton'] ?? 0) . '</td>';
            echo '<td>' . htmlspecialchars($product['Don_vi_tinh'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($product['Xuat_xu'] ?? '') . '</td>';
            echo '<td>' . ($product['Trang_thai'] == 'active' ? 'Đang bán' : 'Ngừng bán') . '</td>';
            echo '<td>' . date('d/m/Y', strtotime($product['Ngay_tao'])) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
    }
    
    /**
     * ==========================================================================
     * METHOD: downloadSampleImport() - TẢI FILE MẪU IMPORT
     * ==========================================================================
     * URL: /admin/download-sample-import
     */
    public function downloadSampleImport() {
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Headers - Cấu trúc mới hỗ trợ Import tồn kho
        $headers = [
            'A1' => 'Tên sản phẩm (Bắt buộc)', 
            'B1' => 'ID Danh mục (Bắt buộc)', 
            'C1' => 'Giá bán (VNĐ)', 
            'D1' => 'Số lượng nhập', 
            'E1' => 'Đơn vị tính', 
            'F1' => 'Xuất xứ',
            'G1' => 'Giá nhập (VNĐ)'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            // Style header
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FF496C2C'); // Green branding
            $sheet->getStyle($cell)->getFont()->getColor()->setARGB('FFFFFFFF'); // White text
        }
        
        // Sample Data - Bao gồm số lượng và giá nhập
        $data = [
            ['Sữa Tươi Vinamilk 100% (Mẫu)', 1, 35000, 50, 'Hộp', 'Việt Nam', 28000],
            ['Bánh Quy Cosy (Mẫu)', 21, 55000, 30, 'Gói', 'Việt Nam', 42000]
        ];
        
        $row = 2;
        foreach ($data as $item) {
            $sheet->fromArray($item, NULL, 'A' . $row);
            $row++;
        }
        
        // Auto size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $filename = 'mau_import_san_pham.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    
    /**
     * ==========================================================================
     * METHOD: productImport() - NHẬP EXCEL SẢN PHẨM
     * ==========================================================================
     * 
     * URL: /admin/product-import (POST)
     * Sử dụng PhpSpreadsheet để đọc file Excel thật (.xls, .xlsx)
     */
    public function productImport() {
        if (!$this->isMethod('POST')) {
            Session::flash('error', 'Phương thức không hợp lệ');
            redirect(BASE_URL . '/admin/products');
        }
        
        // Verify CSRF
        if (!Middleware::verifyCsrf(post('csrf_token', ''))) {
            Session::flash('error', 'Token không hợp lệ');
            redirect(BASE_URL . '/admin/products');
        }
        
        // Kiểm tra file đã được tải lên chưa
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] != 0) {
            Session::flash('error', 'Vui lòng chọn file Excel');
            redirect(BASE_URL . '/admin/products');
        }
        
        $file = $_FILES['import_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Kiểm tra phần mở rộng
        if (!in_array($ext, ['xls', 'xlsx', 'csv'])) {
            Session::flash('error', 'Định dạng file không hợp lệ. Chấp nhận: .xls, .xlsx, .csv');
            redirect(BASE_URL . '/admin/products');
        }
        
        try {
            // Sử dụng PhpSpreadsheet để đọc file Excel
            require_once __DIR__ . '/../../vendor/autoload.php';
            
            // Tắt các cảnh báo PHP từ PhpSpreadsheet
            $previousErrorReporting = error_reporting();
            error_reporting(E_ERROR | E_PARSE);
            
            $imported = 0;
            $errors = 0;
            $errorMessages = [];
            
            // Lấy các ID danh mục hợp lệ
            $validCategories = [];
            $allCategories = $this->categoryModel->getAll();
            foreach ($allCategories as $cat) {
                $validCategories[] = (int)$cat['ID_danh_muc'];
            }
            
            if ($ext == 'csv') {
                // Với file CSV, dùng fgetcsv đơn giản
                $handle = fopen($file['tmp_name'], 'r');
                if (!$handle) {
                    throw new \Exception('Không thể đọc file');
                }
                
                $lineNumber = 0;
                while (($row = fgetcsv($handle)) !== false) {
                    $lineNumber++;
                    if ($lineNumber == 1) continue; // Bỏ qua dòng tiêu đề
                    
                    $result = $this->importProductRow($row, $validCategories, $lineNumber, $errorMessages);
                    if ($result === true) $imported++; 
                    elseif ($result === false) $errors++;
                    // null nghĩa là bỏ qua dòng trống - không đếm
                }
                fclose($handle);
            } else {
                // Với file XLS/XLSX, dùng PhpSpreadsheet
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();
                
                foreach ($rows as $index => $row) {
                    if ($index == 0) continue; // Bỏ qua dòng tiêu đề
                    
                    $result = $this->importProductRow($row, $validCategories, $index + 1, $errorMessages);
                    if ($result === true) $imported++; 
                    elseif ($result === false) $errors++;
                    // null nghĩa là bỏ qua dòng trống - không đếm
                }
            }
            
            // Khôi phục báo lỗi
            error_reporting($previousErrorReporting);
            
            if ($imported > 0) {
                $message = "Import thành công {$imported} sản phẩm";
                if ($errors > 0) {
                    $message .= ", {$errors} lỗi";
                }
                Session::flash('success', $message);
            } else {
                $errorDetail = !empty($errorMessages) ? ' Chi tiết: ' . implode(', ', array_slice($errorMessages, 0, 3)) : '';
                Session::flash('error', 'Không có sản phẩm nào được import.' . $errorDetail);
            }
            
        } catch (\Exception $e) {
            error_reporting($previousErrorReporting ?? E_ALL);
            Session::flash('error', 'Lỗi đọc file: ' . $e->getMessage());
        }
        
        redirect(BASE_URL . '/admin/products');
    }
    
    /**
     * Helper: Import một row sản phẩm
     * Expected columns: Tên, ID_danh_mục, Giá bán, Số lượng, Đơn vị, Xuất xứ, Giá nhập
     * 
     * SENIOR UPGRADE: 
     * - Đọc Số lượng (cột D) và Giá nhập (cột G)
     * - Nếu Số lượng > 0 → Tự động tạo Phiếu nhập kho (ACID compliant)
     * - Cập nhật Gia_nhap trong san_pham để dùng cho báo cáo Lãi/Lỗ
     */
    private function importProductRow($row, $validCategories = [], $lineNumber = 0, &$errorMessages = []) {
        // Skip completely empty rows (don't count as error)
        $isEmptyRow = true;
        foreach ($row as $cell) {
            if (!empty(trim((string)$cell))) {
                $isEmptyRow = false;
                break;
            }
        }
        if ($isEmptyRow) {
            return null; // Trả về null để chỉ ra bỏ qua, không phải lỗi
        }
        
        // Kiểm tra dòng có đủ cột không (tối thiểu 4 cột: Tên, Danh mục, Giá bán, Số lượng)
        if (count($row) < 4) {
            $errorMessages[] = "Dòng {$lineNumber}: thiếu cột";
            return false;
        }
        
        // Phân tích dữ liệu
        $categoryId = (int)($row[1] ?? 1);
        
        // Kiểm tra danh mục tồn tại
        if (!empty($validCategories) && !in_array($categoryId, $validCategories)) {
            $errorMessages[] = "Dòng {$lineNumber}: ID danh mục {$categoryId} không tồn tại";
            return false;
        }
        
        // Đọc số lượng và giá nhập từ Excel
        $soLuongNhap = (int)preg_replace('/[^0-9]/', '', $row[3] ?? 0);
        $giaNhap = (float)preg_replace('/[^0-9.]/', '', $row[6] ?? 0);
        
        // Nếu Giá nhập = 0, ước tính = 70% giá bán
        $giaBan = (float)preg_replace('/[^0-9.]/', '', $row[2] ?? 0);
        if ($giaNhap <= 0 && $giaBan > 0) {
            $giaNhap = $giaBan * 0.7;
        }
        
        $productData = [
            'Ten' => trim($row[0] ?? ''),
            'ID_danh_muc' => $categoryId,
            'Gia_tien' => $giaBan,
            'So_luong_ton' => 0, // Sẽ được cập nhật bởi Phiếu nhập kho
            'Don_vi_tinh' => trim($row[4] ?? 'Cái'),
            'Xuat_xu' => trim($row[5] ?? ''),
            'Gia_nhap' => $giaNhap, // Lưu giá nhập để dùng cho báo cáo
            'Trang_thai' => 'active'
        ];
        
        // Bỏ qua nếu không có tên
        if (empty($productData['Ten'])) {
            $errorMessages[] = "Dòng {$lineNumber}: thiếu tên sản phẩm";
            return false;
        }
        
        // Tạo sản phẩm
        $this->productModel->setCurrentUserId(Session::getUserId());
        $productId = $this->productModel->create($productData);
        
        if (!$productId) {
            $errorMessages[] = "Dòng {$lineNumber}: không thể tạo sản phẩm";
            return false;
        }
        
        // =====================================================================
        // SENIOR UPGRADE: Tự động tạo Phiếu nhập kho nếu có số lượng
        // Đảm bảo ACID compliance và audit trail cho tồn kho
        // =====================================================================
        if ($soLuongNhap > 0) {
            try {
                $warehouseModel = $this->model('Warehouse');
                
                $importItems = [[
                    'ID_sp' => $productId,
                    'Ten_sp' => $productData['Ten'],
                    'Don_vi_tinh' => $productData['Don_vi_tinh'],
                    'So_luong' => $soLuongNhap,
                    'Don_gia_nhap' => $giaNhap,
                    'Xuat_xu' => $productData['Xuat_xu'],
                    'Nha_cung_cap' => 'Import Excel'
                ]];
                
                $warehouseModel->createImport(
                    Session::getUserId(),
                    date('Y-m-d'),
                    "Import tự động từ Excel - Dòng {$lineNumber}",
                    $importItems
                );
                
            } catch (\Exception $e) {
                // Ghi log nhưng không fail - sản phẩm đã được tạo
                error_log("Import warehouse error (line {$lineNumber}): " . $e->getMessage());
            }
        }
        
        return true;
    }
    
    /**
     * ==========================================================================
     * METHOD: exportOrders() - XUẤT EXCEL ĐƠN HÀNG
     * ==========================================================================
     * 
     * URL: /admin/export-orders
     */
    public function exportOrders() {
        $filters = [
            'status' => get('status', ''),
            'date_from' => get('date_from', ''),
            'date_to' => get('date_to', '')
        ];
        
        $orders = $this->orderModel->getAllOrdersForExport($filters);
        
        $filename = 'don_hang_' . date('Y-m-d_His') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output HTML table format that Excel can read
        echo '<!DOCTYPE html>';
        echo '<html>';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body>';
        echo '<table border="1">';
        
        // Header row
        echo '<tr style="background-color: #496C2C; color: white; font-weight: bold;">';
        echo '<th>Mã đơn hàng</th>';
        echo '<th>Khách hàng</th>';
        echo '<th>SĐT</th>';
        echo '<th>Địa chỉ</th>';
        echo '<th>Tổng tiền</th>';
        echo '<th>Trạng thái</th>';
        echo '<th>Ngày đặt</th>';
        echo '</tr>';
        
        // Ánh xạ trạng thái
        $statusLabels = [
            'dang_xu_ly' => 'Đang xử lý',
            'dang_giao' => 'Đang giao',
            'da_giao' => 'Đã giao',
            'huy' => 'Đã hủy'
        ];
        
        foreach ($orders as $order) {
            $status = $statusLabels[$order['Trang_thai']] ?? $order['Trang_thai'];
            echo '<tr>';
            echo '<td>' . htmlspecialchars($order['ID_dh'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($order['Ten_nguoi_nhan'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($order['Sdt_nguoi_nhan'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($order['Dia_chi_giao_hang'] ?? '') . '</td>';
            echo '<td style="text-align: right;">' . number_format($order['Thanh_tien'] ?? 0) . ' ₫</td>';
            echo '<td>' . $status . '</td>';
            echo '<td>' . date('d/m/Y H:i', strtotime($order['Ngay_dat'])) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
        exit;
    }
    
}