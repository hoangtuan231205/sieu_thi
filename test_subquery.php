<?php
/**
 * TEST SCRIPT - Debug subquery loại trừ phiếu hủy
 */
require_once __DIR__ . '/config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("❌ DB Error: " . $e->getMessage() . "\n");
}

echo "===== DEBUG SQL SUBQUERY =====\n\n";

// 1. Kiểm tra chi tiết phiếu hủy
echo "1. CHI TIẾT PHIẾU HỦY (ID_lo_nhap):\n";
$stmt = $pdo->query("SELECT cth.ID_lo_nhap, cth.ID_sp, ph.Trang_thai, sp.Ten
    FROM chi_tiet_phieu_huy cth 
    JOIN phieu_huy ph ON cth.ID_phieu_huy = ph.ID_phieu_huy 
    LEFT JOIN san_pham sp ON cth.ID_sp = sp.ID_sp
    LIMIT 10");
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($details as $d) {
    $loNhap = $d['ID_lo_nhap'] ?? 'NULL';
    echo "   - Lô nhập: {$loNhap}, SP: {$d['Ten']}, Trạng thái PH: {$d['Trang_thai']}\n";
}
echo "\n";

// 2. Chạy subquery để xem nó trả về gì
echo "2. SUBQUERY LOẠI TRỪ (phiếu chờ duyệt):\n";
$stmt = $pdo->query("SELECT cth.ID_lo_nhap 
    FROM chi_tiet_phieu_huy cth 
    JOIN phieu_huy ph ON cth.ID_phieu_huy = ph.ID_phieu_huy 
    WHERE ph.Trang_thai = 'cho_duyet'");
$excluded = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "   Số lô bị loại trừ: " . count($excluded) . "\n";
if (!empty($excluded)) {
    echo "   IDs: " . implode(', ', $excluded) . "\n";
}
echo "\n";

// 3. Chạy query KHÔNG có subquery để so sánh
echo "3. QUERY KHÔNG LOẠI TRỪ (để so sánh):\n";
$stmt = $pdo->query("SELECT COUNT(*) as cnt
    FROM chi_tiet_phieu_nhap ct
    JOIN san_pham p ON ct.ID_sp = p.ID_sp
    WHERE ct.Ngay_het_han IS NOT NULL
    AND (ct.So_luong_con > 0 OR ct.So_luong_con IS NULL)
    AND DATEDIFF(ct.Ngay_het_han, CURDATE()) <= 30");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   Kết quả: {$result['cnt']} lô\n\n";

// 4. Chạy query CÓ subquery
echo "4. QUERY CÓ LOẠI TRỪ:\n";
$stmt = $pdo->query("SELECT COUNT(*) as cnt
    FROM chi_tiet_phieu_nhap ct
    JOIN san_pham p ON ct.ID_sp = p.ID_sp
    WHERE ct.Ngay_het_han IS NOT NULL
    AND (ct.So_luong_con > 0 OR ct.So_luong_con IS NULL)
    AND DATEDIFF(ct.Ngay_het_han, CURDATE()) <= 30
    AND ct.ID_chi_tiet_nhap NOT IN (
        SELECT cth.ID_lo_nhap 
        FROM chi_tiet_phieu_huy cth 
        JOIN phieu_huy ph ON cth.ID_phieu_huy = ph.ID_phieu_huy 
        WHERE ph.Trang_thai = 'cho_duyet'
    )");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   Kết quả: {$result['cnt']} lô\n\n";

echo "===== KẾT THÚC =====\n";
