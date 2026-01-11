<?php
/**
 * TEST: Kiểm tra chức năng tìm kiếm sản phẩm cho phiếu hủy
 * 
 * Chạy: php test_search_disposal.php
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/models/Model.php';
require_once __DIR__ . '/app/models/Product.php';

echo "===== TEST TÌM KIẾM SẢN PHẨM CHO PHIẾU HỦY =====\n\n";

$productModel = new Product();

// Test 1: Tìm kiếm với từ khóa "sữa"
echo "1. TÌM KIẾM: 'sữa'\n";
$results = $productModel->searchForDisposal('sữa', 10);
echo "   Kết quả: " . count($results) . " sản phẩm\n";
foreach ($results as $r) {
    echo "   - [{$r['Ma_hien_thi']}] {$r['Ten']} - Tồn: {$r['So_luong_ton']}\n";
}
echo "\n";

// Test 2: Tìm kiếm với từ khóa "cá"
echo "2. TÌM KIẾM: 'cá'\n";
$results = $productModel->searchForDisposal('cá', 10);
echo "   Kết quả: " . count($results) . " sản phẩm\n";
foreach ($results as $r) {
    echo "   - [{$r['Ma_hien_thi']}] {$r['Ten']} - Tồn: {$r['So_luong_ton']}\n";
}
echo "\n";

// Test 3: Tìm kiếm với từ khóa "hồi" (cho Cá hồi)
echo "3. TÌM KIẾM: 'hồi' (Cá hồi)\n";
$results = $productModel->searchForDisposal('hồi', 10);
echo "   Kết quả: " . count($results) . " sản phẩm\n";
foreach ($results as $r) {
    echo "   - [{$r['Ma_hien_thi']}] {$r['Ten']} - Tồn: {$r['So_luong_ton']}\n";
}
echo "\n";

// Test 4: Kiểm tra getBatches cho một sản phẩm
echo "4. KIỂM TRA getBatches() cho SP ID 17 (Cá hồi):\n";
$batches = $productModel->getBatches(17);
echo "   Số lô: " . count($batches) . "\n";
foreach ($batches as $b) {
    echo "   - Lô [{$b['ID_chi_tiet_nhap']}]: {$b['Ma_phieu_nhap']}, còn {$b['So_luong_con']}, HSD: {$b['Ngay_het_han']}\n";
}
if (empty($batches)) {
    echo "   ⚠️ Không có lô nào (có thể đã bị loại trừ do phiếu hủy chờ duyệt)\n";
}
echo "\n";

// Test 5: So sánh với truy vấn trực tiếp
echo "5. SO SÁNH VỚI TRUY VẤN TRỰC TIẾP (không có điều kiện loại trừ):\n";
$db = Database::getInstance();
$directBatches = $db->query("SELECT ct.ID_chi_tiet_nhap, pn.Ma_hien_thi, ct.So_luong_con, ct.Ngay_het_han
    FROM chi_tiet_phieu_nhap ct
    JOIN phieu_nhap_kho pn ON ct.ID_phieu_nhap = pn.ID_phieu_nhap
    WHERE ct.ID_sp = 17 AND ct.So_luong_con > 0")->fetchAll();
echo "   Số lô (không filter): " . count($directBatches) . "\n";
foreach ($directBatches as $b) {
    echo "   - Lô [{$b['ID_chi_tiet_nhap']}]: {$b['Ma_hien_thi']}, còn {$b['So_luong_con']}, HSD: {$b['Ngay_het_han']}\n";
}
echo "\n";

echo "===== KẾT THÚC TEST =====\n";
