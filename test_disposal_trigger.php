<?php
/**
 * TEST SCRIPT - Kiểm tra trigger phiếu hủy
 * Chạy từ command line: php test_disposal_trigger.php
 */

// Đọc config database từ file
require_once __DIR__ . '/config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("❌ Không kết nối được database: " . $e->getMessage() . "\n");
}

echo "===== KIỂM TRA HỆ THỐNG PHIẾU HỦY =====\n\n";

// 1. Kiểm tra trigger có tồn tại không
echo "1. KIỂM TRA TRIGGER:\n";
$stmt = $pdo->query("SHOW TRIGGERS WHERE `Table` = 'phieu_huy'");
$triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($triggers)) {
    echo "   ⚠️ KHÔNG TÌM THẤY TRIGGER cho bảng phieu_huy!\n";
    echo "   → Cần chạy: database/create_views_and_data.sql\n\n";
} else {
    foreach ($triggers as $t) {
        echo "   ✅ {$t['Trigger']} ({$t['Timing']} {$t['Event']})\n";
    }
    echo "\n";
}

// 2. Kiểm tra phiếu hủy
echo "2. THỐNG KÊ PHIẾU HỦY:\n";
$stmt = $pdo->query("SELECT Trang_thai, COUNT(*) as cnt, SUM(Tong_tien_huy) as total 
    FROM phieu_huy GROUP BY Trang_thai");
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($stats as $s) {
    $label = ['cho_duyet' => 'Chờ duyệt', 'da_duyet' => 'Đã duyệt', 'tu_choi' => 'Từ chối'][$s['Trang_thai']] ?? $s['Trang_thai'];
    echo "   - {$label}: {$s['cnt']} phiếu, tổng " . number_format($s['total']) . "đ\n";
}
echo "\n";

// 3. Kiểm tra phiếu đang chờ duyệt
echo "3. PHIẾU CHỜ DUYỆT:\n";
$stmt = $pdo->query("SELECT ph.*, tk.Ho_ten as Ten_nguoi_tao 
    FROM phieu_huy ph 
    LEFT JOIN tai_khoan tk ON ph.Nguoi_tao = tk.ID_tk 
    WHERE Trang_thai = 'cho_duyet'");
$pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($pending)) {
    echo "   ✅ Không có phiếu chờ duyệt\n";
} else {
    foreach ($pending as $p) {
        echo "   - ID: {$p['ID_phieu_huy']}, Mã: {$p['Ma_hien_thi']}\n";
        echo "     Người tạo: {$p['Ten_nguoi_tao']}, Tổng: " . number_format($p['Tong_tien_huy']) . "đ\n";
    }
}
echo "\n";

// 4. Kiểm tra chi tiết một phiếu mẫu
echo "4. CHI TIẾT PHIẾU MẪU (phiếu gần nhất):\n";
$stmt = $pdo->query("SELECT ph.Ma_hien_thi, ct.*, sp.Ten, sp.So_luong_ton
    FROM chi_tiet_phieu_huy ct
    JOIN phieu_huy ph ON ct.ID_phieu_huy = ph.ID_phieu_huy
    JOIN san_pham sp ON ct.ID_sp = sp.ID_sp
    ORDER BY ph.ID_phieu_huy DESC
    LIMIT 5");
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($details as $d) {
    echo "   [{$d['Ma_hien_thi']}] {$d['Ten']}\n";
    echo "     SL hủy: {$d['So_luong']}, Giá: " . number_format($d['Gia_nhap']) . "đ";
    echo ", Tồn hiện tại: {$d['So_luong_ton']}\n";
}
echo "\n";

// 5. Kiểm tra xem trigger có hoạt động (test logic)
echo "5. KIỂM TRA LOGIC TRIGGER:\n";
// Tìm một phiếu đã duyệt và kiểm tra xem tồn kho có bị trừ đúng không
$stmt = $pdo->query("SELECT 
    ph.Ma_hien_thi,
    ph.Ngay_duyet,
    ct.ID_sp,
    ct.So_luong as SL_huy,
    sp.Ten,
    sp.So_luong_ton as Ton_hien_tai
    FROM phieu_huy ph
    JOIN chi_tiet_phieu_huy ct ON ph.ID_phieu_huy = ct.ID_phieu_huy
    JOIN san_pham sp ON ct.ID_sp = sp.ID_sp
    WHERE ph.Trang_thai = 'da_duyet'
    ORDER BY ph.Ngay_duyet DESC
    LIMIT 3");
$approved = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($approved)) {
    echo "   ⚠️ Chưa có phiếu nào được duyệt để kiểm tra\n";
} else {
    foreach ($approved as $a) {
        echo "   [{$a['Ma_hien_thi']} - Duyệt: {$a['Ngay_duyet']}]\n";
        echo "     SP: {$a['Ten']}, Đã hủy: {$a['SL_huy']}, Tồn: {$a['Ton_hien_tai']}\n";
    }
    echo "   → Nếu tồn kho đã giảm sau duyệt, trigger hoạt động đúng.\n";
}
echo "\n";

echo "===== KẾT THÚC KIỂM TRA =====\n";
