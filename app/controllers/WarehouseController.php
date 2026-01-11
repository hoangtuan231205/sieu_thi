<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class WarehouseController extends Controller
{
    private $warehouseModel;
    private $productModel;
    private $supplierModel;

    public function __construct()
    {
        if (class_exists('Middleware') && method_exists('Middleware', 'warehouse')) {
            Middleware::warehouse();
        }

        $this->warehouseModel = $this->model('Warehouse');
        $this->productModel = $this->model('Product');
        $this->supplierModel = $this->model('Supplier');
    }



    public function index()
    {

        return $this->dashboard();
    }

    public function dashboard()
    {
        $filters = [
            'ma_phieu' => trim(get('ma_phieu', '')),
            'nguoi_tao' => trim(get('nguoi_tao', '')),
            'ngay_nhap' => trim(get('ngay_nhap', '')),
            'page' => max(1, (int) get('page', 1)),
        ];

        $perPage = 10;
        $total = $this->warehouseModel->countImports3Fields($filters);
        $pagination = $this->paginate($total, $perPage, $filters['page']);

        $imports = $this->warehouseModel->getImports3Fields(
            $filters,
            $pagination['per_page'],
            $pagination['offset']
        );

        // Load danh sách nhà cung cấp cho dropdown
        $suppliers = $this->supplierModel->getForDropdown();
        
        // Load danh sách danh mục cho dropdown
        $categoryModel = $this->model('Category');
        $categories = $categoryModel->getAll();

        $data = [
            'page_title' => 'Quản Lý Phiếu Nhập',
            'filters' => $filters,
            'imports' => $imports,
            'pagination' => $pagination,
            'total' => $total,
            'suppliers' => $suppliers,
            'categories' => $categories,
            'csrf_token' => class_exists('Session') ? Session::getCsrfToken() : ''
        ];

        $this->view('warehouse/dashboard', $data);
    }

    /**
     * AJAX: search product
     * GET q
     * Có hỗ trợ tiếng Việt: cá ≠ cải
     */
    public function searchProduct()
    {
        if (!$this->isAjax()) {
            return $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $q = trim(get('q', ''));
        
        // Cho phép query rỗng hoặc ngắn
        if (mb_strlen($q) < 1) {
            // Query rỗng - trả về rỗng (hoặc có thể trả về tất cả nếu muốn)
            return $this->json(['success' => true, 'products' => []]);
        }

        // Nếu bạn có hàm sẵn trong Product model thì dùng
        if ($this->productModel && method_exists($this->productModel, 'searchForWarehouse')) {
            $products = $this->productModel->searchForWarehouse($q, 20);
            // Chuẩn hoá response
            $out = [];
            foreach ($products as $p) {
                $out[] = [
                    'id' => (int) ($p['ID_sp'] ?? $p['id'] ?? 0),
                    'ma' => (string) ($p['Ma_hien_thi'] ?? $p['ma'] ?? ''),
                    'ten' => (string) ($p['Ten'] ?? $p['ten'] ?? ''),
                    'dvt' => (string) ($p['Don_vi_tinh'] ?? $p['dvt'] ?? 'SP'),
                    'gia' => (float) ($p['Gia_tien'] ?? $p['gia'] ?? 0),
                ];
            }
            return $this->json(['success' => true, 'products' => $out]);
        }

        // Fallback: search trong Warehouse model
        $products = $this->warehouseModel->searchProductsSimple($q, 20);
        return $this->json(['success' => true, 'products' => $products]);
    }

    /**
     * AJAX: lấy detail phiếu nhập (edit popup)
     * GET id
     */
    public function importDetail()
    {
        if (!$this->isAjax())
            $this->json(['success' => false, 'message' => 'Invalid request'], 400);

        $id = (int) get('id', 0);
        if ($id <= 0)
            $this->json(['success' => false, 'message' => 'ID không hợp lệ'], 422);

        $import = $this->warehouseModel->getImportById($id);
        if (!$import)
            $this->json(['success' => false, 'message' => 'Không tìm thấy phiếu nhập'], 404);

        $items = $this->warehouseModel->getImportDetails($id);

        $this->json([
            'success' => true,
            'import' => $import,
            'items' => $items
        ]);
    }

    /**
     * AJAX: tạo phiếu nhập (POST)
     * POST:
     * - csrf_token
     * - ngay_nhap
     * - ghi_chu
     * - items (JSON)
     */
    public function importCreate()
    {
        if (!$this->isAjax() || !$this->isMethod('POST')) {
            $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        // CSRF nếu bạn đang dùng
        if (class_exists('Middleware') && method_exists('Middleware', 'verifyCsrf')) {
            if (!Middleware::verifyCsrf(post('csrf_token', ''))) {
                $this->json(['success' => false, 'message' => 'Invalid token'], 400);
            }
        }

        $ngayNhap = trim(post('ngay_nhap', ''));
        $ghiChu = trim(post('ghi_chu', ''));

        $items = json_decode(post('items', '[]'), true);
        if ($ngayNhap === '' || empty($items) || !is_array($items)) {
            $this->json(['success' => false, 'message' => 'Thiếu dữ liệu ngày nhập hoặc danh sách sản phẩm'], 422);
        }

        $userId = class_exists('Session') ? (int) Session::getUserId() : 1;

        try {
            $newId = $this->warehouseModel->createImport($userId, $ngayNhap, $ghiChu, $items);
            $this->json(['success' => true, 'id' => $newId]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: cập nhật phiếu nhập (POST)
     * POST:
     * - csrf_token
     * - id_phieu_nhap
     * - ngay_nhap
     * - ghi_chu
     * - items (JSON)
     */
    public function importUpdate()
    {
        if (!$this->isAjax() || !$this->isMethod('POST')) {
            $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        if (class_exists('Middleware') && method_exists('Middleware', 'verifyCsrf')) {
            if (!Middleware::verifyCsrf(post('csrf_token', ''))) {
                $this->json(['success' => false, 'message' => 'Invalid token'], 400);
            }
        }

        $id = (int) post('id_phieu_nhap', 0);
        $ngayNhap = trim(post('ngay_nhap', ''));
        $ghiChu = trim(post('ghi_chu', ''));

        $items = json_decode(post('items', '[]'), true);

        if ($id <= 0 || $ngayNhap === '' || empty($items) || !is_array($items)) {
            $this->json(['success' => false, 'message' => 'Thiếu dữ liệu cập nhật'], 422);
        }

        try {
            $ok = $this->warehouseModel->updateImport($id, $ngayNhap, $ghiChu, $items);
            $this->json(['success' => (bool) $ok]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    /**
     * AJAX: xóa phiếu nhập
     * POST id
     */
    public function importDelete()
    {
        if (!$this->isAjax() || !$this->isMethod('POST')) {
            $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        $id = (int) post('id', 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'ID không hợp lệ'], 422);
        }

        try {
            $ok = $this->warehouseModel->deleteImport($id);
            $this->json(['success' => (bool) $ok]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function exportImport()
    {
        while (ob_get_level() > 0)
            ob_end_clean();

        $filters = [
            'ma_phieu' => $_GET['ma_phieu'] ?? '',
            'nguoi_tao' => $_GET['nguoi_tao'] ?? '',
            'ngay_nhap' => $_GET['ngay_nhap'] ?? ''
        ];

        $imports = $this->warehouseModel->getImportsForExport($filters);
        $details = $this->warehouseModel->getAllImportDetailsForExport($filters);

        $spreadsheet = new Spreadsheet();

        /* ================= SHEET 1 ================= */
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Danh sách phiếu nhập');

        $sheet1->fromArray(
            ['Mã phiếu', 'Ngày nhập', 'Người tạo', 'Tổng tiền', 'Ghi chú'],
            null,
            'A1'
        );

        $row = 2;
        foreach ($imports as $item) {
            $sheet1->fromArray([
                $item['Ma_hien_thi'],
                date('d/m/Y', strtotime($item['Ngay_nhap'])),
                $item['Nguoi_tao_ten'],
                $item['Tong_tien'],
                $item['Ghi_chu']
            ], null, "A$row");
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet1->getColumnDimension($col)->setAutoSize(true);
        }

        /* ================= SHEET 2 ================= */
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Chi tiết phiếu nhập');

        $sheet2->fromArray(
            ['Mã phiếu', 'Mã SP', 'Tên SP', 'Số lượng', 'Đơn giá', 'Thành tiền'],
            null,
            'A1'
        );

        $row = 2;
        foreach ($details as $d) {
            $sheet2->fromArray([
                $d['Ma_phieu'],
                $d['Ma_sp'],
                $d['Ten_sp'],
                $d['So_luong'],
                $d['Don_gia_nhap'],
                $d['Thanh_tien']
            ], null, "A$row");
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="PhieuNhap_ChiTiet_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }




    /**
     * AJAX: Tạo sản phẩm mới nhanh (khi tìm kiếm không ra)
     * POST:
     * - csrf_token
     * - ten_sp (tên sản phẩm)
     * - id_danh_muc (ID danh mục)
     * - don_vi_tinh (đơn vị tính)
     * - gia_ban (giá bán - mặc định 0)
     * 
     * Mã SP sẽ được trigger tự sinh
     * Trả về: product info để fill vào form
     */
    public function createProductQuick()
    {
        if (!$this->isAjax() || !$this->isMethod('POST')) {
            return $this->json(['success' => false, 'message' => 'Invalid request'], 400);
        }

        // CSRF check
        if (class_exists('Middleware') && method_exists('Middleware', 'verifyCsrf')) {
            if (!Middleware::verifyCsrf(post('csrf_token', ''))) {
                return $this->json(['success' => false, 'message' => 'Invalid token'], 400);
            }
        }

        $tenSp = trim(post('ten_sp', ''));
        $danhMuc = (int)post('id_danh_muc', 0);
        $donViTinh = trim(post('don_vi_tinh', 'Cái'));
        $giaBan = (float)post('gia_ban', 0);

        // Validate
        if (empty($tenSp)) {
            return $this->json(['success' => false, 'message' => 'Tên sản phẩm không được để trống'], 422);
        }

        if ($danhMuc <= 0) {
            return $this->json(['success' => false, 'message' => 'Vui lòng chọn danh mục'], 422);
        }

        try {
            // Set user ID for trigger
            if (method_exists($this->productModel, 'setCurrentUserId')) {
                $this->productModel->setCurrentUserId(Session::getUserId());
            }

            // Tạo sản phẩm mới - Mã SP sẽ được trigger tự sinh
            $productData = [
                'Ten' => $tenSp,
                'ID_danh_muc' => $danhMuc,
                'Don_vi_tinh' => $donViTinh,
                'Gia_tien' => $giaBan,
                'So_luong_ton' => 0, // Sẽ được cập nhật khi lưu phiếu nhập
                'Trang_thai' => 'active'
            ];

            $productId = $this->productModel->create($productData);

            if (!$productId) {
                return $this->json(['success' => false, 'message' => 'Không thể tạo sản phẩm'], 500);
            }

            // Lấy thông tin SP vừa tạo (bao gồm mã tự sinh)
            $newProduct = $this->productModel->findById($productId);

            return $this->json([
                'success' => true,
                'message' => 'Đã tạo sản phẩm mới',
                'product' => [
                    'id' => (int)$newProduct['ID_sp'],
                    'ma' => $newProduct['Ma_hien_thi'] ?? 'SP' . str_pad($productId, 5, '0', STR_PAD_LEFT),
                    'ten' => $newProduct['Ten'],
                    'dvt' => $newProduct['Don_vi_tinh'],
                    'gia' => (float)$newProduct['Gia_tien']
                ]
            ]);

        } catch (Exception $e) {
            return $this->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()], 500);
        }
    }

}
