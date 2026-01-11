<?php
/**
 * DEBUG: Kiểm tra TẤT CẢ phiếu hủy (kể cả đã duyệt)
 */
require_once __DIR__ . '/config/config.php';

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "===== DEBUG TẤT CẢ PHIẾU HỦY =====\n\n";

// 1. Tất cả phiếu hủy gần đây
echo "1. TẤT CẢ PHIẾU HỦY (mới nhất):\n";
$stmt = $pdo->query("SELECT ph.ID_phieu_huy, ph.Ma_hien_thi, ph.Tong_tien_huy, ph.Trang_thai, ph.Ngay_tao
    FROM phieu_huy ph 
    ORDER BY ph.ID_phieu_huy DESC
    LIMIT 10");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ph) {
    echo "   {$ph['Ma_hien_thi']}: " . number_format($ph['Tong_tien_huy']) . "đ - {$ph['Trang_thai']} ({$ph['Ngay_tao']})\n";
}
echo "\n";

// 2. Chi tiết sản phẩm - tìm duplicate theo ID_sp
echo "2. PHÂN TÍCH DUPLICATE THEO SẢN PHẨM:\n";
$stmt = $pdo->query("SELECT 
    ct.ID_sp,
    ct.Ten_sp,
    COUNT(DISTINCT ph.ID_phieu_huy) as so_phieu,
    SUM(ct.So_luong) as tong_sl,
    GROUP_CONCAT(ph.Ma_hien_thi SEPARATOR ', ') as danh_sach_phieu
    FROM chi_tiet_phieu_huy ct
    JOIN phieu_huy ph ON ct.ID_phieu_huy = ph.ID_phieu_huy
    GROUP BY ct.ID_sp, ct.Ten_sp
    HAVING so_phieu > 1
    ORDER BY so_phieu DESC");
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($duplicates)) {
    echo "   ✅ Không có sản phẩm nào bị hủy nhiều lần\n";
} else {
    foreach ($duplicates as $d) {
        echo "   ⚠️ SP ID {$d['ID_sp']} ({$d['Ten_sp']}): {$d['so_phieu']} phiếu\n";
        echo "      Phiếu: {$d['danh_sach_phieu']}\n";
    }
}
echo "\n";

// 3. Chi tiết ID_lo_nhap
echo "3. PHÂN TÍCH DUPLICATE THEO LÔ HÀNG:\n";
$stmt = $pdo->query("SELECT 
    ct.ID_lo_nhap,
    ct.Ten_sp,
    COUNT(*) as so_lan,
    GROUP_CONCAT(ph.Ma_hien_thi SEPARATOR ', ') as danh_sach_phieu
    FROM chi_tiet_phieu_huy ct
    JOIN phieu_huy ph ON ct.ID_phieu_huy = ph.ID_phieu_huy
    WHERE ct.ID_lo_nhap IS NOT NULL
    GROUP BY ct.ID_lo_nhap, ct.Ten_sp
    HAVING so_lan > 1
    ORDER BY so_lan DESC");
$dupLo = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($dupLo)) {
    echo "   ✅ Không có lô nào bị hủy nhiều lần\n";
} else {
    foreach ($dupLo as $d) {
        echo "   ⚠️ Lô {$d['ID_lo_nhap']} ({$d['Ten_sp']}): {$d['so_lan']} lần\n";
        echo "      Phiếu: {$d['danh_sach_phieu']}\n";
    }
}
echo "\n";

// 4. Kiểm tra các record với ID_lo_nhap NULL
echo "4. CÁC RECORD CÓ ID_lo_nhap = NULL:\n";
$stmt = $pdo->query("SELECT ct.*, ph.Ma_hien_thi, ph.Trang_thai
    FROM chi_tiet_phieu_huy ct
    JOIN phieu_huy ph ON ct.ID_phieu_huy = ph.ID_phieu_huy
    WHERE ct.ID_lo_nhap IS NULL
    ORDER BY ph.ID_phieu_huy DESC
    LIMIT 10");
$nulls = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($nulls)) {
    echo "   ✅ Không có record nào có ID_lo_nhap = NULL\n";
} else {
    foreach ($nulls as $n) {
        echo "   - {$n['Ma_hien_thi']}: SP={$n['Ten_sp']}, SL={$n['So_luong']} ({$n['Trang_thai']})\n";
    }
}

echo "\n===== KẾT THÚC =====\n";
