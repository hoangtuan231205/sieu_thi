<?php
// Debug Database State for Autocomplete
require_once 'app/config/config.php';
require_once 'app/core/Database.php';

$db = new Database();
$pdo = $db->connect();

echo "===== KIỂM TRA DATABASE CHO TỪ KHÓA 'sữa' =====\n";

// 1. Tìm trong bảng san_pham
$stmt = $pdo->prepare("SELECT ID_sp, Ma_hien_thi, Ten, Trang_thai FROM san_pham WHERE Ten LIKE ? OR Ma_hien_thi LIKE ?");
$term = '%sữa%';
$stmt->execute([$term, $term]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "1. Tìm thấy " . count($products) . " sản phẩm trong database:\n";
foreach ($products as $p) {
    echo "   - [{$p['ID_sp']}] {$p['Ma_hien_thi']}: {$p['Ten']} (Trạng thái: {$p['Trang_thai']})\n";
}
echo "\n";

// 2. Kiểm tra danh sách loại trừ (Excluded IDs)
echo "2. Kiểm tra Logic Loại Trừ (Disposal Exclusion):\n";
$sqlExcl = "SELECT DISTINCT cth.ID_sp 
    FROM chi_tiet_phieu_huy cth 
    JOIN phieu_huy ph ON cth.ID_phieu_huy = ph.ID_phieu_huy 
    WHERE ph.Trang_thai IN ('cho_duyet', 'da_duyet') 
    AND cth.ID_lo_nhap IS NULL";
$excludedIds = $pdo->query($sqlExcl)->fetchAll(PDO::FETCH_COLUMN);

echo "   Các ID bị loại trừ (đang có phiếu hủy chờ duyệt/đã duyệt cho cả sản phẩm): " . implode(', ', $excludedIds) . "\n";

// 3. Kết luận
echo "\n3. KẾT QUẢ DỰ KIẾN TRÊN UI:\n";
foreach ($products as $p) {
    if ($p['Trang_thai'] !== 'active') {
        echo "   - [{$p['ID_sp']}] BỊ ẨN (Trạng thái không active)\n";
    } elseif (in_array($p['ID_sp'], $excludedIds)) {
        echo "   - [{$p['ID_sp']}] BỊ ẨN (Đang trong phiếu hủy khác)\n";
    } else {
        echo "   - [{$p['ID_sp']}] HIỂN THỊ OK ✅\n";
    }
}
