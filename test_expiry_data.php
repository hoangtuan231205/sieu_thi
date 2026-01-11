<?php
/**
 * TEST SCRIPT - Kiểm tra dữ liệu hết hạn
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

echo "===== KIỂM TRA DỮ LIỆU HẾT HẠN =====\n\n";

// 1. Có bản ghi nào có Ngay_het_han không?
echo "1. KIỂM TRA CỘT Ngay_het_han:\n";
$stmt = $pdo->query("SELECT COUNT(*) as total, 
    SUM(CASE WHEN Ngay_het_han IS NOT NULL THEN 1 ELSE 0 END) as co_han,
    SUM(CASE WHEN Ngay_het_han IS NULL THEN 1 ELSE 0 END) as ko_han
    FROM chi_tiet_phieu_nhap");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   Tổng lô: {$result['total']}\n";
echo "   Có ngày hết hạn: {$result['co_han']}\n";
echo "   Không có HSD: {$result['ko_han']}\n\n";

// 2. Các lô hàng có ngày hết hạn
echo "2. CÁC LÔ CÓ NGÀY HẾT HẠN (TOP 10):\n";
$stmt = $pdo->query("SELECT ct.ID_chi_tiet_nhap, sp.Ten, ct.Ngay_het_han, ct.So_luong_con, ct.So_luong,
    DATEDIFF(ct.Ngay_het_han, CURDATE()) as So_ngay_con
    FROM chi_tiet_phieu_nhap ct
    JOIN san_pham sp ON ct.ID_sp = sp.ID_sp
    WHERE ct.Ngay_het_han IS NOT NULL
    ORDER BY ct.Ngay_het_han ASC
    LIMIT 10");
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($batches)) {
    echo "   ⚠️ KHÔNG CÓ LÔ NÀO CÓ NGÀY HẾT HẠN!\n";
    echo "   → Khi nhập kho, cần nhập ngày hết hạn (Ngay_het_han)\n\n";
} else {
    foreach ($batches as $b) {
        $sl = $b['So_luong_con'] ?? $b['So_luong'];
        echo "   - [{$b['ID_chi_tiet_nhap']}] {$b['Ten']}\n";
        echo "     HSD: {$b['Ngay_het_han']}, còn {$b['So_ngay_con']} ngày, SL: {$sl}\n";
    }
}
echo "\n";

// 3. Kiểm tra các lô đã hết hạn hoặc sắp hết
echo "3. LÔ HẾT HẠN/SẮP HẾT (trong 30 ngày):\n";
$stmt = $pdo->query("SELECT ct.ID_chi_tiet_nhap, sp.Ten, ct.Ngay_het_han, 
    COALESCE(ct.So_luong_con, ct.So_luong) as SL,
    DATEDIFF(ct.Ngay_het_han, CURDATE()) as So_ngay_con
    FROM chi_tiet_phieu_nhap ct
    JOIN san_pham sp ON ct.ID_sp = sp.ID_sp
    WHERE ct.Ngay_het_han IS NOT NULL
    AND DATEDIFF(ct.Ngay_het_han, CURDATE()) <= 30
    AND (ct.So_luong_con > 0 OR ct.So_luong_con IS NULL)
    ORDER BY ct.Ngay_het_han ASC");
$expiring = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($expiring)) {
    echo "   ✅ Không có lô nào hết hạn/sắp hết trong 30 ngày tới\n";
} else {
    foreach ($expiring as $e) {
        $status = $e['So_ngay_con'] < 0 ? "ĐÃ HẾT HẠN" : "còn {$e['So_ngay_con']} ngày";
        echo "   - {$e['Ten']}: {$status}, SL: {$e['SL']}\n";
    }
}

echo "\n===== KẾT THÚC =====\n";
