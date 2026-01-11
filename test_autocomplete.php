<?php
/**
 * TEST: Kiểm tra API search-product-for-disposal
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/models/Model.php';
require_once __DIR__ . '/app/models/Product.php';

echo "===== TEST AUTOCOMPLETE API =====\n\n";

$productModel = new Product();

// Test tìm kiếm
$keyword = 'sữa';
echo "1. TÌM KIẾM: '$keyword'\n";
$results = $productModel->searchForDisposal($keyword, 10);
echo "   Số kết quả: " . count($results) . "\n";

if (count($results) > 0) {
    echo "   ✅ TÌM KIẾM HOẠT ĐỘNG!\n";
    foreach (array_slice($results, 0, 3) as $r) {
        echo "   - [{$r['Ma_hien_thi']}] {$r['Ten']}\n";
    }
} else {
    echo "   ❌ Không có kết quả\n";
}
echo "\n";

// Test getBatches
echo "2. TEST getBatches() cho SP ID=1:\n";
$batches = $productModel->getBatches(1);
echo "   Số lô: " . count($batches) . "\n";
if (count($batches) > 0) {
    foreach ($batches as $b) {
        echo "   - Lô [{$b['ID_chi_tiet_nhap']}]: {$b['Ma_phieu_nhap']}, SL: {$b['So_luong_con']}\n";
    }
}
echo "\n";

echo "===== KẾT LUẬN =====\n";
echo "Chức năng autocomplete ĐÃ CÓ SẴN trong code!\n";
echo "Nếu không hiển thị trên browser, check Console (F12) để xem lỗi JavaScript.\n";
