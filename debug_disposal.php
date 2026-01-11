<?php
/**
 * DEBUG: Kiểm tra phiếu hủy hiện tại
 */
require_once __DIR__ . '/config/config.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "===== DEBUG PHIẾU HỦY HIỆN TẠI =====\n\n";

// 1. Phiếu hủy và trạng thái
echo "1. TẤT CẢ PHIẾU HỦY:\n";
$stmt = $pdo->query("SELECT ID_phieu_huy, Ma_hien_thi, Trang_thai, Tong_tien_huy FROM phieu_huy ORDER BY ID_phieu_huy DESC");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ph) {
    echo "   {$ph['Ma_hien_thi']}: {$ph['Trang_thai']} - " . number_format($ph['Tong_tien_huy']) . "đ\n";
}
echo "\n";

// 2. Chi tiết phiếu hủy - ID_sp và ID_lo_nhap
echo "2. CHI TIẾT (ID_sp, ID_lo_nhap):\n";
$stmt = $pdo->query("SELECT ct.ID_sp, ct.ID_lo_nhap, ct.Ten_sp, ph.Ma_hien_thi, ph.Trang_thai
    FROM chi_tiet_phieu_huy ct
    JOIN phieu_huy ph ON ct.ID_phieu_huy = ph.ID_phieu_huy
    ORDER BY ph.ID_phieu_huy DESC");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ct) {
    $lo = $ct['ID_lo_nhap'] ?? 'NULL';
    echo "   SP ID={$ct['ID_sp']}, Lô={$lo}: {$ct['Ten_sp']} ({$ct['Ma_hien_thi']} - {$ct['Trang_thai']})\n";
}
echo "\n";

// 3. Query loại trừ thực tế
echo "3. QUERY LOẠI TRỪ (sản phẩm có phiếu hủy với ID_lo_nhap NULL):\n";
$stmt = $pdo->query("SELECT DISTINCT cth.ID_sp 
    FROM chi_tiet_phieu_huy cth 
    JOIN phieu_huy ph ON cth.ID_phieu_huy = ph.ID_phieu_huy 
    WHERE ph.Trang_thai IN ('cho_duyet', 'da_duyet') 
    AND cth.ID_lo_nhap IS NULL");
$excluded = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "   ID sản phẩm bị loại trừ: " . (empty($excluded) ? "KHÔNG CÓ" : implode(', ', $excluded)) . "\n";
echo "\n";

// 4. Kiểm tra sản phẩm "Cá hồi Nauy" có ID gì
echo "4. TÌM SẢN PHẨM 'Cá hồi Nauy':\n";
$stmt = $pdo->query("SELECT ID_sp, Ma_hien_thi, Ten FROM san_pham WHERE Ten LIKE '%hồi%Nauy%'");
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if ($product) {
    echo "   ID_sp: {$product['ID_sp']}, Mã: {$product['Ma_hien_thi']}, Tên: {$product['Ten']}\n";
}
echo "\n";

echo "===== KẾT THÚC =====\n";
