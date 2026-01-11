
DELIMITER $$
--
-- Thủ tục
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cap_nhat_trang_thai_don` (IN `p_id_dh` INT, IN `p_trang_thai_moi` VARCHAR(20), IN `p_id_nguoi_sua` INT)   BEGIN
    DECLARE v_quyen VARCHAR(20);
    
    SELECT Phan_quyen INTO v_quyen 
    FROM tai_khoan 
    WHERE ID = p_id_nguoi_sua;
    
    IF v_quyen NOT IN ('ADMIN', 'QUAN_LY_KHO') THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Khong co quyen cap nhat don hang';
    END IF;
    
    UPDATE don_hang 
    SET Trang_thai = p_trang_thai_moi 
    WHERE ID_dh = p_id_dh;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_dat_hang` (IN `p_id_tk` INT, IN `p_ten_nguoi_nhan` VARCHAR(200), IN `p_sdt` VARCHAR(15), IN `p_dia_chi` TEXT, IN `p_ghi_chu` TEXT, OUT `p_id_don_hang` INT, OUT `p_thong_bao` VARCHAR(500))   BEGIN
    DECLARE v_tong_tien DECIMAL(15,2) DEFAULT 0;
    DECLARE v_phi_ship DECIMAL(15,2) DEFAULT 20000;
    DECLARE v_thanh_tien DECIMAL(15,2);
    DECLARE v_error VARCHAR(500);
    
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error = MESSAGE_TEXT;
        SET p_thong_bao = CONCAT('Loi: ', v_error);
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    SELECT SUM(sp.Gia_tien * gh.So_luong) 
    INTO v_tong_tien
    FROM gio_hang gh
    INNER JOIN san_pham sp ON gh.ID_sp = sp.ID_sp
    WHERE gh.ID_tk = p_id_tk AND sp.Trang_thai = 'active';
    
    IF v_tong_tien IS NULL OR v_tong_tien = 0 THEN
        SET p_thong_bao = 'Gio hang trong';
        ROLLBACK;
    ELSE
        SET v_thanh_tien = v_tong_tien + v_phi_ship;
        
        INSERT INTO don_hang 
        (ID_tk, Ten_nguoi_nhan, Sdt_nguoi_nhan, Dia_chi_giao_hang, 
         Ghi_chu, Tong_tien, Phi_van_chuyen, Thanh_tien)
        VALUES 
        (p_id_tk, p_ten_nguoi_nhan, p_sdt, p_dia_chi, 
         p_ghi_chu, v_tong_tien, v_phi_ship, v_thanh_tien);
        
        SET p_id_don_hang = LAST_INSERT_ID();
        
        INSERT INTO chi_tiet_don_hang 
        (ID_dh, ID_sp, Ten_sp, So_luong, Gia_tien, Thanh_tien, Hinh_anh)
        SELECT 
            p_id_don_hang, 
            gh.ID_sp, 
            sp.Ten, 
            gh.So_luong, 
            sp.Gia_tien, 
            gh.So_luong * sp.Gia_tien, 
            sp.Hinh_anh
        FROM gio_hang gh
        INNER JOIN san_pham sp ON gh.ID_sp = sp.ID_sp
        WHERE gh.ID_tk = p_id_tk AND sp.Trang_thai = 'active';
        
        DELETE FROM gio_hang WHERE ID_tk = p_id_tk;
        
        SET p_thong_bao = CONCAT('Dat hang thanh cong. Ma don hang: ', p_id_don_hang);
        COMMIT;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_kiem_tra_phan_quyen` (IN `p_id_tk` INT, IN `p_quyen_yeu_cau` VARCHAR(20))   BEGIN
    DECLARE v_quyen VARCHAR(20);
    DECLARE v_trang_thai VARCHAR(20);
    
    SELECT Phan_quyen, Trang_thai 
    INTO v_quyen, v_trang_thai
    FROM tai_khoan 
    WHERE ID = p_id_tk;
    
    IF v_trang_thai != 'active' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Tai khoan bi khoa';
    END IF;
    
    IF p_quyen_yeu_cau = 'ADMIN' AND v_quyen != 'ADMIN' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Khong co quyen ADMIN';
    END IF;
    
    IF p_quyen_yeu_cau = 'QUAN_LY_KHO' AND v_quyen NOT IN ('ADMIN', 'QUAN_LY_KHO') THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Khong co quyen QUAN_LY_KHO';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tao_phieu_nhap` (IN `p_nguoi_tao` INT, IN `p_ngay_nhap` DATE, IN `p_ghi_chu` TEXT, OUT `p_id_phieu` INT, OUT `p_ma_phieu` VARCHAR(50))   BEGIN
    INSERT INTO phieu_nhap_kho 
    (Nguoi_tao, Ngay_nhap, Ghi_chu)
    VALUES 
    (p_nguoi_tao, p_ngay_nhap, p_ghi_chu);
    
    SET p_id_phieu = LAST_INSERT_ID();
    
    SELECT Ma_hien_thi INTO p_ma_phieu 
    FROM phieu_nhap_kho 
    WHERE ID_phieu_nhap = p_id_phieu;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `audit_log`
--

CREATE TABLE `audit_log` (
  `ID_log` int(11) NOT NULL,
  `User_id` int(11) NOT NULL COMMENT 'ID người dùng thực hiện',
  `User_name` varchar(100) DEFAULT NULL COMMENT 'Tên người dùng (cache)',
  `User_role` varchar(50) DEFAULT NULL COMMENT 'Quyền của người dùng',
  `Table_name` varchar(100) NOT NULL COMMENT 'Tên bảng bị thay đổi',
  `Record_id` int(11) NOT NULL COMMENT 'ID record bị thay đổi',
  `Action_type` enum('INSERT','UPDATE','DELETE','LOGIN','LOGOUT','EXPORT','IMPORT') NOT NULL,
  `Old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Giá trị cũ (JSON)' CHECK (json_valid(`Old_values`)),
  `New_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Giá trị mới (JSON)' CHECK (json_valid(`New_values`)),
  `Changed_fields` text DEFAULT NULL COMMENT 'Danh sách fields đã thay đổi',
  `Description` text DEFAULT NULL COMMENT 'Mô tả hành động',
  `Ip_address` varchar(45) DEFAULT NULL COMMENT 'IP người dùng',
  `User_agent` text DEFAULT NULL COMMENT 'Browser/Device info',
  `Request_url` varchar(500) DEFAULT NULL COMMENT 'URL của request',
  `Created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lưu lịch sử thay đổi hệ thống';

--
-- Đang đổ dữ liệu cho bảng `audit_log`
--

INSERT INTO `audit_log` (`ID_log`, `User_id`, `User_name`, `User_role`, `Table_name`, `Record_id`, `Action_type`, `Old_values`, `New_values`, `Changed_fields`, `Description`, `Ip_address`, `User_agent`, `Request_url`, `Created_at`) VALUES
(1, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 07:47:08'),
(2, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 07:53:39'),
(3, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 07:54:08'),
(4, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 07:54:11'),
(5, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 07:54:29'),
(6, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 07:57:01'),
(7, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 08:00:43'),
(8, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 08:01:39'),
(9, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 08:37:57'),
(10, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 08:38:11'),
(11, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 10:53:01'),
(12, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 11:19:20'),
(13, 1, 'Kim', 'ADMIN', 'phieu_huy', 1, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"huy\",\"Ly_do\":\"hết date 3\\/1\\/2026\",\"Ngay_huy\":\"2026-01-04\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add', '2026-01-04 11:26:19'),
(14, 1, 'Kim', 'ADMIN', 'phieu_huy', 1, 'UPDATE', '{\"Trang_thai\":\"cho_duyet\"}', '{\"Trang_thai\":\"da_duyet\"}', 'Trang_thai', 'Duyệt phiếu hủy #1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-approve', '2026-01-04 11:34:59'),
(15, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 11:35:34'),
(16, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 11:45:29'),
(17, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 11:45:47'),
(18, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 15:49:05'),
(19, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 15:49:18'),
(20, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-04\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-04 16:18:50'),
(21, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 14:01:39'),
(22, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 14:01:47'),
(23, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 14:55:07'),
(24, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 14:55:10'),
(25, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 14:58:32'),
(26, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 14:58:56'),
(27, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 15:01:18'),
(28, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 15:08:18'),
(29, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 15:17:15'),
(30, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 15:17:51'),
(31, 1, 'Kim', 'ADMIN', 'phieu_huy', 2, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"huy\",\"Ly_do\":\"Sản phẩm hỏng thối \",\"Ngay_huy\":\"2026-01-08\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add', '2026-01-08 15:25:36'),
(32, 1, 'Kim', 'ADMIN', 'phieu_huy', 2, 'UPDATE', '{\"Trang_thai\":\"cho_duyet\"}', '{\"Trang_thai\":\"da_duyet\"}', 'Trang_thai', 'Duyệt phiếu hủy #2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-approve', '2026-01-08 15:25:47'),
(33, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 15:26:07'),
(34, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 15:28:18'),
(35, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 15:28:30'),
(36, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 15:30:29'),
(37, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 15:54:40'),
(38, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 15:56:12'),
(39, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 15:57:03'),
(40, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 15:59:37'),
(41, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 16:01:43'),
(42, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 16:02:10'),
(43, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 16:04:06'),
(44, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-08 16:06:06'),
(45, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit?date_from=2026-01-01&date_to=2026-01-08', '2026-01-08 16:06:19'),
(46, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-08\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit?date_from=2026-01-01&date_to=2026-01-08', '2026-01-08 16:12:25'),
(47, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-09\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-09 08:11:05'),
(48, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-09\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-09 08:14:38'),
(49, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-09\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-09 08:17:19'),
(50, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-10\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-10 08:55:14'),
(51, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2025-12-11\",\"date_to\":\"2026-01-10\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit?date_from=2025-12-11&date_to=2026-01-10', '2026-01-10 08:56:01'),
(52, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-10\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-10 09:08:25'),
(53, 1, 'Kim', 'ADMIN', 'nha_cung_cap', 2, 'INSERT', NULL, '{\"Ten_ncc\":\"Kim\",\"Sdt\":\"0975387464\",\"Email\":\"kim@gmail.com\",\"Dia_chi\":\"123 nulllllllllllll\",\"Nguoi_lien_he\":\"Choco\",\"Mo_ta\":null,\"Trang_thai\":\"active\"}', NULL, 'Thêm nhà cung cấp: Kim', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/supplier-add', '2026-01-10 10:10:34'),
(54, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-10\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-10 10:45:38'),
(55, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-10\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-10 14:05:02'),
(56, 1, 'Kim', 'ADMIN', 'nha_cung_cap', 1, 'UPDATE', '{\"ID_ncc\":1,\"Ma_hien_thi\":\"NCC-001\",\"Ten_ncc\":\"Vinamilk\",\"Dia_chi\":null,\"Sdt\":\"028-1234-5678\",\"Email\":\"contact@vinamilk.com\",\"Nguoi_lien_he\":null,\"Mo_ta\":null,\"Trang_thai\":\"active\",\"Ngay_tao\":\"2026-01-03 02:13:09\"}', '{\"Ten_ncc\":\"Vinamilk\",\"Sdt\":\"028-1234-5678\",\"Email\":\"contact@vinamilk.com\",\"Dia_chi\":\"\",\"Nguoi_lien_he\":\"Kim\",\"Mo_ta\":null,\"Trang_thai\":\"active\"}', 'Dia_chi, Nguoi_lien_he, Mo_ta', 'Cập nhật nhà cung cấp: Vinamilk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/supplier-update', '2026-01-10 14:08:18'),
(57, 1, 'Kim', 'ADMIN', 'nha_cung_cap', 1, 'UPDATE', '{\"ID_ncc\":1,\"Ma_hien_thi\":\"NCC-001\",\"Ten_ncc\":\"Vinamilk\",\"Dia_chi\":\"\",\"Sdt\":\"028-1234-5678\",\"Email\":\"contact@vinamilk.com\",\"Nguoi_lien_he\":\"Kim\",\"Mo_ta\":null,\"Trang_thai\":\"active\",\"Ngay_tao\":\"2026-01-03 02:13:09\"}', '{\"Ten_ncc\":\"Vinamilk\",\"Sdt\":\"028-1234-5678\",\"Email\":\"contact@vinamilk.com\",\"Dia_chi\":\"\",\"Nguoi_lien_he\":\"Kim\",\"Mo_ta\":null,\"Trang_thai\":\"active\"}', 'Mo_ta', 'Cập nhật nhà cung cấp: Vinamilk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/supplier-update', '2026-01-10 14:08:25'),
(58, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-10\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-10 15:41:28'),
(59, 1, 'Kim', 'ADMIN', 'nha_cung_cap', 3, 'INSERT', NULL, '{\"Ten_ncc\":\"Công Ty TNHH Mộc Châu\",\"Sdt\":\"0123698852\",\"Email\":\"huong@gmail.com\",\"Dia_chi\":\"Cổ Nhuế , Bắc Từ Liêm , Hà Nội \\r\\n\",\"Nguoi_lien_he\":\"Trần Thị Thu Hương\",\"Mo_ta\":null,\"Trang_thai\":\"active\"}', NULL, 'Thêm nhà cung cấp: Công Ty TNHH Mộc Châu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/supplier-add', '2026-01-10 16:02:27'),
(60, 1, 'Kim', 'ADMIN', 'nha_cung_cap', 2, 'UPDATE', '{\"ID_ncc\":2,\"Ma_hien_thi\":\"NCC-002\",\"Ten_ncc\":\"Kim\",\"Dia_chi\":\"123 nulllllllllllll\",\"Sdt\":\"0975387464\",\"Email\":\"kim@gmail.com\",\"Nguoi_lien_he\":\"Choco\",\"Mo_ta\":null,\"Trang_thai\":\"active\",\"Ngay_tao\":\"2026-01-10 17:10:34\"}', '{\"Ten_ncc\":\"Kim\",\"Sdt\":\"0975387464\",\"Email\":\"kim@gmail.com\",\"Dia_chi\":\"123 nulllllllllllll\",\"Nguoi_lien_he\":\"Choco\",\"Mo_ta\":null,\"Trang_thai\":\"inactive\"}', 'Mo_ta, Trang_thai', 'Cập nhật nhà cung cấp: Kim', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/supplier-update', '2026-01-10 16:02:41'),
(61, 1, 'Kim', 'ADMIN', 'phieu_huy', 8, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-10\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=47&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=195000&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-10 16:17:34'),
(62, 1, 'Kim', 'ADMIN', 'phieu_huy', 8, 'UPDATE', '{\"Trang_thai\":\"cho_duyet\"}', '{\"Trang_thai\":\"da_duyet\"}', 'Trang_thai', 'Duyệt phiếu hủy #8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-approve', '2026-01-10 16:17:39'),
(63, 1, 'Kim', 'ADMIN', 'phieu_huy', 9, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-10\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=44&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=3499000&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-10 16:22:46'),
(64, 1, 'Kim', 'ADMIN', 'phieu_huy', 10, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-10\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=46&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=75000&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-10 16:34:57'),
(65, 1, 'Kim', 'ADMIN', 'phieu_huy', 10, 'UPDATE', '{\"Trang_thai\":\"cho_duyet\"}', '{\"Trang_thai\":\"da_duyet\"}', 'Trang_thai', 'Duyệt phiếu hủy #10', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-approve', '2026-01-10 16:35:01'),
(66, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-10\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-10 17:02:57'),
(67, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-10\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-10 18:12:20'),
(68, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-10\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-10 19:10:35'),
(69, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-10\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-10 19:20:45'),
(70, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-10\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-10 19:20:48'),
(71, 1, 'Kim', 'ADMIN', 'nha_cung_cap', 4, 'INSERT', NULL, '{\"Ten_ncc\":\"Công Ty TNHH Han Kim\",\"Sdt\":\"0975387488\",\"Email\":\"han@gmail.com\",\"Dia_chi\":\"null \\r\\n\",\"Nguoi_lien_he\":\"Hắn Nè\",\"Mo_ta\":null,\"Trang_thai\":\"active\"}', NULL, 'Thêm nhà cung cấp: Công Ty TNHH Han Kim', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/supplier-add', '2026-01-10 19:24:05'),
(72, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-10\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-10 20:47:11'),
(73, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-11\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-11 06:22:25'),
(74, 1, 'Kim', 'ADMIN', 'phieu_huy', 11, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-11\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=46&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=75000&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-11 06:22:54'),
(75, 1, 'Kim', 'ADMIN', 'phieu_huy', 11, 'UPDATE', '{\"Trang_thai\":\"cho_duyet\"}', '{\"Trang_thai\":\"da_duyet\"}', 'Trang_thai', 'Duyệt phiếu hủy #11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-approve', '2026-01-11 06:28:33'),
(76, 1, 'Kim', 'ADMIN', 'phieu_huy', 9, 'UPDATE', '{\"Trang_thai\":\"cho_duyet\"}', '{\"Trang_thai\":\"da_duyet\"}', 'Trang_thai', 'Duyệt phiếu hủy #9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-approve', '2026-01-11 06:28:38'),
(77, 1, 'Kim', 'ADMIN', 'phieu_huy', 12, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-11\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=45&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=125000&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-11 06:34:10'),
(78, 1, 'Kim', 'ADMIN', 'phieu_huy', 13, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-11\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=45&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=125000&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-11 07:15:44'),
(79, 1, 'Kim', 'ADMIN', 'phieu_huy', 13, 'UPDATE', '{\"Trang_thai\":\"cho_duyet\"}', '{\"Trang_thai\":\"da_duyet\"}', 'Trang_thai', 'Duyệt phiếu hủy #13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-approve', '2026-01-11 07:15:49'),
(80, 1, 'Kim', 'ADMIN', 'phieu_huy', 14, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-11\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=44&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=3499000&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-11 07:24:47'),
(81, 1, 'Kim', 'ADMIN', 'phieu_huy', 15, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-11\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=44&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=3499000&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-11 07:25:12'),
(82, 1, 'Kim', 'ADMIN', 'phieu_huy', 16, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-11\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=44&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=3499000&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-11 07:25:42'),
(83, 1, 'Kim', 'ADMIN', 'phieu_huy', 17, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-11\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=44&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=3499000&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-11 07:27:35'),
(84, 1, 'Kim', 'ADMIN', 'phieu_huy', 18, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-11\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=45&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=125000&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-11 07:31:56'),
(85, 1, 'Kim', 'ADMIN', 'phieu_huy', 19, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-11\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=46&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=75000&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-11 07:32:04'),
(86, 1, 'Kim', 'ADMIN', 'phieu_huy', 19, 'UPDATE', '{\"Trang_thai\":\"cho_duyet\"}', '{\"Trang_thai\":\"da_duyet\"}', 'Trang_thai', 'Duyệt phiếu hủy #19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-approve', '2026-01-11 07:32:09'),
(87, 1, 'Kim', 'ADMIN', 'phieu_huy', 20, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-11\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=46&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=75000&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-11 07:34:12'),
(88, 1, 'Kim', 'ADMIN', 'phieu_huy', 21, 'INSERT', NULL, '{\"Nguoi_tao\":1,\"Loai_phieu\":\"het_han\",\"Ly_do\":\"Sản phẩm đã hết hạn sử dụng\",\"Ngay_huy\":\"2026-01-11\"}', NULL, 'Tạo phiếu hủy với 1 sản phẩm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/disposal-add?product_id=48&batch_id=&batch_code=PNK20260104-INIT&quantity=25&price=66900&reason=S%E1%BA%A3n+ph%E1%BA%A9m+%C4%91%C3%A3+h%E1%BA%BFt+h%E1%BA%A1n+s%E1%BB%AD+d%E1%BB%A5ng', '2026-01-11 07:41:51'),
(89, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-11\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-11 07:47:06'),
(90, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-11\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-11 14:39:30'),
(91, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-11\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-11 14:40:31'),
(92, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-11\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-11 14:40:44'),
(93, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-11\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-11 15:01:47'),
(94, 1, 'Kim', 'ADMIN', 'report', 0, 'EXPORT', NULL, '{\"type\":\"profit\",\"date_from\":\"2026-01-01\",\"date_to\":\"2026-01-11\"}', NULL, 'Xem báo cáo lãi/lỗ', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '/sieu_thi/public/admin/report-profit', '2026-01-11 16:25:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_don_hang`
--

CREATE TABLE `chi_tiet_don_hang` (
  `ID_ct_dh` int(11) NOT NULL,
  `ID_dh` int(11) NOT NULL,
  `ID_sp` int(11) NOT NULL,
  `Ten_sp` varchar(200) DEFAULT NULL,
  `So_luong` int(11) NOT NULL,
  `Gia_tien` decimal(15,2) NOT NULL,
  `Thanh_tien` decimal(15,2) NOT NULL,
  `Hinh_anh` varchar(255) DEFAULT NULL,
  `Don_gia_von` decimal(15,2) DEFAULT 0.00 COMMENT 'Gi├í vß╗æn tß║íi thß╗Øi ─æiß╗âm b├ín (copy tß╗½ l├┤ h├áng)',
  `ID_chi_tiet_nhap` int(11) DEFAULT NULL COMMENT 'FK ÔåÆ chi_tiet_phieu_nhap (l├┤ h├áng ─æã░ß╗úc xuß║Ñt)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_don_hang`
--

INSERT INTO `chi_tiet_don_hang` (`ID_ct_dh`, `ID_dh`, `ID_sp`, `Ten_sp`, `So_luong`, `Gia_tien`, `Thanh_tien`, `Hinh_anh`, `Don_gia_von`, `ID_chi_tiet_nhap`) VALUES
(1, 1, 3, 'Pediasure Sữa bột hương vani 1.6kg', 1, 1226000.00, 1226000.00, '3.png', 844272.25, NULL),
(2, 2, 25, 'Downy Nước xả nắng mải tươi 1.5L', 1, 122800.00, 122800.00, '25.png', 85960.00, NULL),
(3, 3, 2, 'Vinamilk Sữa tiệt trùng không đường 180ml', 1, 34200.00, 34200.00, '2.png', 23576.87, NULL),
(4, 3, 3, 'Pediasure Sữa bột hương vani 1.6kg', 1, 1226000.00, 1226000.00, '3.png', 844272.25, NULL),
(5, 3, 5, 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml (lốc 4 hộp)', 2, 33400.00, 66800.00, '5.png', 23380.00, NULL),
(6, 3, 4, 'Ensure Gold Sữa bột 800g', 1, 982500.00, 982500.00, '4.png', 687750.00, NULL),
(7, 4, 50, 'Chocopie Bánh Orion hộp 396g', 2, 58200.00, 116400.00, '50.png', 40740.00, NULL),
(9, 6, 25, 'Downy Nước xả nắng mải tươi 1.5L', 1, 122800.00, 122800.00, '25.png', 85960.00, NULL),
(10, 6, 2, 'Vinamilk Sữa tiệt trùng không đường 180ml', 1, 34200.00, 34200.00, '2.png', 23576.87, NULL),
(11, 7, 5, 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml (lốc 4 hộp)', 1, 33400.00, 33400.00, '5.png', 23380.00, NULL),
(12, 8, 4, 'Ensure Gold Sữa bột 800g', 1, 982500.00, 982500.00, '4.png', 687750.00, NULL),
(13, 9, 30, 'Lock&Lock Máy tăm nước không dây 200ml ENR156BLU', 1, 689000.00, 689000.00, '30.png', 482300.00, NULL),
(14, 10, 30, 'Lock&Lock Máy tăm nước không dây 200ml ENR156BLU', 1, 689000.00, 689000.00, '30.png', 482300.00, NULL),
(15, 11, 32, 'P/S Muối hồng & Hoa cúc 230g', 1, 49000.00, 49000.00, '32.png', 34300.00, NULL),
(16, 11, 30, 'Lock&Lock Máy tăm nước không dây 200ml ENR156BLU', 1, 689000.00, 689000.00, '30.png', 482300.00, NULL),
(17, 12, 3, 'Pediasure Sữa bột hương vani 1.6kg', 1, 1226000.00, 1226000.00, '3.png', 844272.25, NULL),
(18, 12, 65, 'Đức Việt Xúc xích Roma gói 500g', 1, 60400.00, 60400.00, '65.png', 42280.00, NULL),
(20, 14, 5, 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml (lốc 4 hộp)', 1, 33400.00, 33400.00, '5.png', 23380.00, NULL),
(21, 15, 5, 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml (lốc 4 hộp)', 1, 33400.00, 33400.00, '5.png', 23380.00, NULL),
(22, 16, 5, 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml (lốc 4 hộp)', 1, 33400.00, 33400.00, '5.png', 23380.00, NULL),
(23, 17, 5, 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml (lốc 4 hộp)', 1, 33400.00, 33400.00, '5.png', 23380.00, NULL),
(24, 18, 10, 'Súp lơ bông cải trắng L1', 1, 47920.00, 47920.00, '10.png', 33544.00, NULL),
(25, 19, 9, 'Bắp cải trái tim L1', 1, 22000.00, 22000.00, '9.png', 15400.00, NULL),
(26, 19, 10, 'Súp lơ bông cải trắng L1', 1, 47920.00, 47920.00, '10.png', 33544.00, NULL),
(27, 19, 13, 'BioVegi Nấm BúnApi gói 125g', 1, 35000.00, 35000.00, '13.png', 24500.00, NULL),
(28, 19, 14, 'Cà rốt', 1, 12000.00, 12000.00, '14.png', 8400.00, NULL),
(29, 19, 15, 'Bí đỏ tròn', 1, 17000.00, 17000.00, '15.png', 11900.00, NULL),
(30, 19, 26, 'Sunlight Nước rửa chén thiên nhiên túi 2.1kg + 2kg', 1, 49000.00, 49000.00, '26.png', 34300.00, NULL),
(31, 19, 5, 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml (lốc 4 hộp)', 1, 33400.00, 33400.00, '5.png', 23380.00, NULL),
(32, 19, 7, 'Yakult Sữa chua uống lên men 65ml', 1, 4500.00, 4500.00, '7.png', 3150.00, NULL),
(33, 19, 8, 'Yomost Sữa chua uống hương cam 170ml', 3, 18000.00, 54000.00, '8.png', 12600.00, NULL),
(34, 20, 38, 'Diana BVS Super Night 29cm 4 miếng', 1, 19800.00, 19800.00, '38.png', 13860.00, NULL),
(35, 20, 4, 'Ensure Gold Sữa bột 800g', 1, 982500.00, 982500.00, '4.png', 687750.00, NULL),
(36, 20, 3, 'Pediasure Sữa bột hương vani 1.6kg', 1, 1226000.00, 1226000.00, '3.png', 844272.25, NULL),
(37, 20, 67, 'Bibigo Kim chi cải thảo cắt lát 100g', 1, 14200.00, 14200.00, '67.png', 9940.00, NULL),
(38, 20, 65, 'Đức Việt Xúc xích Roma gói 500g', 1, 60400.00, 60400.00, '65.png', 42280.00, NULL),
(39, 20, 66, 'Phú Mỹ Bakery Bánh bao nhân xíu mại 300g', 1, 29950.00, 29950.00, '66.png', 20965.00, NULL),
(40, 20, 68, 'Sa Giang Bánh phồng tôm mini 15 – 100g', 1, 12200.00, 12200.00, '68.png', 8540.00, NULL),
(41, 20, 64, 'SGM Trà sữa Thái 300ml', 1, 16800.00, 16800.00, '64.png', 11760.00, NULL),
(42, 20, 69, 'Đồi Dừa Vàng Tôm Thịt 3×140g (Khay 252g)', 1, 167000.00, 167000.00, '69.png', 116900.00, NULL),
(43, 20, 73, 'Há cảo Thực Phẩm Cầu Tre gói 500g', 1, 78800.00, 78800.00, '73.png', 55160.00, NULL),
(44, 20, 72, 'LC Food Viên thả lẩu phô mai trứng muối 500g', 1, 84200.00, 84200.00, '72.png', 58940.00, NULL),
(45, 20, 71, 'Meat Deli Giò lụa Hảo Hạng 300g', 1, 55000.00, 55000.00, '71.png', 38500.00, NULL),
(46, 20, 70, 'Orifood Ba chỉ bò Mỹ 500g sốt sukiyaki', 1, 195300.00, 195300.00, '70.png', 136710.00, NULL),
(47, 20, 87, 'Bếp nướng điện Lock&Lock', 1, 1472000.00, 1472000.00, '87.png', 1030400.00, NULL),
(48, 20, 88, 'Hokkaido Hộp Thực phẩm trộn 750ml', 1, 31000.00, 31000.00, '88.png', 21700.00, NULL),
(49, 20, 84, 'Lock&Lock Nồi chiên không dầu 5.5L EJF179IVY', 1, 3437000.00, 3437000.00, '84.png', 2405900.00, NULL),
(50, 20, 91, 'Biên Hòa Đường mía Thượng hạng gói 500g', 1, 18600.00, 18600.00, '91.png', 13020.00, NULL),
(51, 20, 93, 'Chin-Su Tương ớt 250g', 1, 18300.00, 18300.00, '93.png', 12810.00, NULL),
(52, 20, 92, 'Kikkoman Nước tương chai 150ml T24', 1, 100900.00, 100900.00, '92.png', 70630.00, NULL),
(53, 20, 89, 'Meizan Dầu đậu nành chai 2L', 1, 80900.00, 80900.00, '89.png', 56630.00, NULL),
(54, 20, 90, 'Nước mắm Nam Ngư 900ml', 1, 60200.00, 60200.00, '90.png', 42140.00, NULL),
(55, 21, 91, 'Biên Hòa Đường mía Thượng hạng gói 500g', 1, 18600.00, 18600.00, '91.png', 13020.00, NULL),
(56, 21, 93, 'Chin-Su Tương ớt 250g', 1, 18300.00, 18300.00, '93.png', 12810.00, NULL),
(57, 21, 92, 'Kikkoman Nước tương chai 150ml T24', 1, 100900.00, 100900.00, '92.png', 70630.00, NULL),
(58, 21, 89, 'Meizan Dầu đậu nành chai 2L', 1, 80900.00, 80900.00, '89.png', 56630.00, NULL),
(59, 21, 90, 'Nước mắm Nam Ngư 900ml', 1, 60200.00, 60200.00, '90.png', 42140.00, NULL),
(60, 21, 87, 'Bếp nướng điện Lock&Lock', 1, 1472000.00, 1472000.00, '87.png', 1030400.00, NULL),
(61, 21, 88, 'Hokkaido Hộp Thực phẩm trộn 750ml', 1, 31000.00, 31000.00, '88.png', 21700.00, NULL),
(62, 21, 84, 'Lock&Lock Nồi chiên không dầu 5.5L EJF179IVY', 1, 3437000.00, 3437000.00, '84.png', 2405900.00, NULL),
(63, 21, 86, 'Máy xay thịt 1L Lock&Lock 400W màu đen', 1, 972200.00, 972200.00, '86.png', 680540.00, NULL),
(64, 21, 85, 'Nồi cơm điện tử cao tần Lock & Lock 1.5L', 1, 6176000.00, 6176000.00, '85.png', 4323200.00, NULL),
(65, 21, 69, 'Đồi Dừa Vàng Tôm Thịt 3×140g (Khay 252g)', 1, 167000.00, 167000.00, '69.png', 116900.00, NULL),
(66, 21, 73, 'Há cảo Thực Phẩm Cầu Tre gói 500g', 1, 78800.00, 78800.00, '73.png', 55160.00, NULL),
(67, 21, 72, 'LC Food Viên thả lẩu phô mai trứng muối 500g', 1, 84200.00, 84200.00, '72.png', 58940.00, NULL),
(68, 21, 71, 'Meat Deli Giò lụa Hảo Hạng 300g', 1, 55000.00, 55000.00, '71.png', 38500.00, NULL),
(69, 21, 70, 'Orifood Ba chỉ bò Mỹ 500g sốt sukiyaki', 1, 195300.00, 195300.00, '70.png', 136710.00, NULL),
(70, 21, 67, 'Bibigo Kim chi cải thảo cắt lát 100g', 1, 14200.00, 14200.00, '67.png', 9940.00, NULL),
(71, 21, 65, 'Đức Việt Xúc xích Roma gói 500g', 1, 60400.00, 60400.00, '65.png', 42280.00, NULL),
(72, 21, 66, 'Phú Mỹ Bakery Bánh bao nhân xíu mại 300g', 1, 29950.00, 29950.00, '66.png', 20965.00, NULL),
(73, 22, 67, 'Bibigo Kim chi cải thảo cắt lát 100g', 1, 14200.00, 14200.00, '67.png', 9940.00, NULL),
(74, 22, 65, 'Đức Việt Xúc xích Roma gói 500g', 1, 60400.00, 60400.00, '65.png', 42280.00, NULL),
(75, 22, 66, 'Phú Mỹ Bakery Bánh bao nhân xíu mại 300g', 1, 29950.00, 29950.00, '66.png', 20965.00, NULL),
(76, 22, 68, 'Sa Giang Bánh phồng tôm mini 15 – 100g', 1, 12200.00, 12200.00, '68.png', 8540.00, NULL),
(77, 22, 64, 'SGM Trà sữa Thái 300ml', 1, 16800.00, 16800.00, '64.png', 11760.00, NULL),
(78, 22, 87, 'Bếp nướng điện Lock&Lock', 1, 1472000.00, 1472000.00, '87.png', 1030400.00, NULL),
(79, 22, 69, 'Đồi Dừa Vàng Tôm Thịt 3×140g (Khay 252g)', 1, 167000.00, 167000.00, '69.png', 116900.00, NULL),
(80, 22, 73, 'Há cảo Thực Phẩm Cầu Tre gói 500g', 1, 78800.00, 78800.00, '73.png', 55160.00, NULL),
(81, 22, 72, 'LC Food Viên thả lẩu phô mai trứng muối 500g', 1, 84200.00, 84200.00, '72.png', 58940.00, NULL),
(82, 22, 71, 'Meat Deli Giò lụa Hảo Hạng 300g', 1, 55000.00, 55000.00, '71.png', 38500.00, NULL),
(83, 22, 70, 'Orifood Ba chỉ bò Mỹ 500g sốt sukiyaki', 1, 195300.00, 195300.00, '70.png', 136710.00, NULL),
(84, 22, 9, 'Bắp cải trái tim L1', 1, 22000.00, 22000.00, '9.png', 15400.00, NULL),
(85, 22, 13, 'BioVegi Nấm BúnApi gói 125g', 1, 35000.00, 35000.00, '13.png', 24500.00, NULL),
(86, 22, 11, 'Cải bó xôi 300g', 1, 12000.00, 12000.00, '11.png', 8400.00, NULL),
(87, 22, 12, 'Kim bôi măng trúc Quân Tử 300g', 1, 29000.00, 29000.00, '12.png', 20300.00, NULL),
(88, 22, 10, 'Súp lơ bông cải trắng L1', 1, 47920.00, 47920.00, '10.png', 33544.00, NULL),
(89, 23, 4, 'Ensure Gold Sữa bột 800g', 1, 982500.00, 982500.00, '4.png', 687750.00, NULL),
(90, 23, 5, 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml (lốc 4 hộp)', 1, 33400.00, 33400.00, '5.png', 23380.00, NULL),
(91, 23, 30, 'Lock&Lock Máy tăm nước không dây 200ml ENR156BLU', 1, 689000.00, 689000.00, '30.png', 482300.00, NULL),
(92, 24, 32, 'P/S Muối hồng & Hoa cúc 230g', 1, 49000.00, 49000.00, '32.png', 34300.00, NULL),
(93, 24, 25, 'Downy Nước xả nắng mải tươi 1.5L', 2, 122800.00, 245600.00, '25.png', 85960.00, NULL),
(94, 24, 24, 'Comfort Nước xả vải thời thượng 3.8kg', 1, 220000.00, 220000.00, '24.png', 154000.00, NULL),
(95, 24, 9, 'Bắp cải trái tim L1', 1, 22000.00, 22000.00, '9.png', 15400.00, NULL),
(96, 24, 13, 'BioVegi Nấm BúnApi gói 125g', 1, 35000.00, 35000.00, '13.png', 24500.00, NULL),
(97, 24, 11, 'Cải bó xôi 300g', 1, 12000.00, 12000.00, '11.png', 8400.00, NULL),
(98, 24, 12, 'Kim bôi măng trúc Quân Tử 300g', 1, 29000.00, 29000.00, '12.png', 20300.00, NULL),
(99, 24, 10, 'Súp lơ bông cải trắng L1', 1, 47920.00, 47920.00, '10.png', 33544.00, NULL),
(100, 24, 42, 'AceFoods Lõi Bắp Rùa Bò Tây Ban Nha 500g', 1, 209900.00, 209900.00, '42.png', 146930.00, NULL),
(101, 24, 43, 'Gà ta nguyên con 3F', 1, 155880.00, 155880.00, '43.png', 109116.00, NULL),
(102, 24, 41, 'KiaOra Ba chỉ bò Úc cắt lát khay 200g', 1, 79900.00, 79900.00, '41.png', 55930.00, NULL),
(103, 24, 39, 'Pacow Bắp Shank khay 250g', 1, 118900.00, 118900.00, '39.png', 83230.00, NULL),
(104, 24, 40, 'Thịt Ba Rọi có da_MH Giun Quế', 1, 234900.00, 234900.00, '40.png', 164430.00, NULL),
(105, 24, 1, 'Sữa tiệt trùng Meiji không Lactose 946ml', 1, 84500.00, 84500.00, '1.png', 58310.55, NULL),
(106, 24, 2, 'Vinamilk Sữa tiệt trùng không đường 180ml', 1, 34200.00, 34200.00, '2.png', 23576.87, NULL),
(107, 24, 14, 'Cà rốt', 2, 12000.00, 24000.00, '14.png', 8400.00, NULL),
(108, 24, 15, 'Bí đỏ tròn', 1, 17000.00, 17000.00, '15.png', 11900.00, NULL),
(109, 24, 17, 'Chanh có hạt 250g', 1, 17000.00, 17000.00, '17.png', 11900.00, NULL),
(110, 24, 16, 'Khoai tây', 1, 11000.00, 11000.00, '16.png', 7700.00, NULL),
(111, 24, 18, 'Ớt chuông màu Baby HFG 250g', 1, 26000.00, 26000.00, '18.png', 18200.00, NULL),
(112, 24, 44, 'Cá hồi Nauy nguyên con 6–8kg', 1, 3499000.00, 3499000.00, '44.png', 2449300.00, NULL),
(113, 24, 48, 'Hàu sữa sống 18–20 con/kg', 1, 66900.00, 66900.00, '48.png', 46830.00, NULL),
(114, 24, 45, 'HDC Tôm thẻ nõn tự nhiên 200g', 1, 133000.00, 133000.00, '45.png', 93100.00, NULL),
(115, 24, 46, 'Lenger Ngao (nghêu) trắng sạch hộp 1200g', 1, 75000.00, 75000.00, '46.png', 52500.00, NULL),
(116, 24, 47, 'NTF Râu bạch tuộc đông lạnh 300g', 1, 195000.00, 195000.00, '47.png', 136500.00, NULL),
(117, 25, 4, 'Ensure Gold Sữa bột 800g', 1, 982500.00, 982500.00, '4.png', 687750.00, NULL),
(118, 25, 75, 'Bia Sài Gòn Special Sleek lon 330ml', 1, 333000.00, 333000.00, '75.png', 233100.00, NULL),
(119, 25, 78, 'Heineken Bia lon cao 330ml', 1, 451000.00, 451000.00, '78.png', 315700.00, NULL),
(120, 26, 5, 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml (lốc 4 hộp)', 1, 33400.00, 33400.00, '5.png', 23380.00, NULL),
(121, 27, 30, 'Lock&Lock Máy tăm nước không dây 200ml ENR156BLU', 1, 689000.00, 689000.00, '30.png', 482300.00, NULL),
(122, 27, 3, 'Pediasure Sữa bột hương vani 1.6kg', 1, 1226000.00, 1226000.00, '3.png', 844272.25, NULL),
(123, 27, 4, 'Ensure Gold Sữa bột 800g', 1, 982500.00, 982500.00, '4.png', 687750.00, NULL),
(124, 28, 4, 'Ensure Gold Sữa bột 800g', 1, 982500.00, 982500.00, '4.png', 687750.00, NULL),
(125, 28, 5, 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml (lốc 4 hộp)', 1, 33400.00, 33400.00, '5.png', 23380.00, NULL),
(126, 29, 15, 'Bí đỏ tròn', 2, 17000.00, 34000.00, '15.png', 11900.00, NULL),
(127, 30, 17, 'Chanh có hạt 250g', 1, 17000.00, 17000.00, '17.png', 11900.00, NULL),
(128, 31, 8, 'Yomost Sữa chua uống hương cam 170ml', 1, 18000.00, 18000.00, '8.png', 12600.00, NULL),
(129, 31, 9, 'Bắp cải trái tim L1', 1, 22000.00, 22000.00, '9.png', 15400.00, NULL),
(130, 31, 10, 'Súp lơ bông cải trắng L1', 1, 47920.00, 47920.00, '10.png', 33544.00, NULL),
(131, 31, 74, 'Bia Hà Nội Premium lon Sleek 330ml', 1, 298300.00, 298300.00, '74.png', 208810.00, NULL),
(132, 31, 75, 'Bia Sài Gòn Special Sleek lon 330ml', 1, 333000.00, 333000.00, '75.png', 233100.00, NULL),
(133, 31, 78, 'Heineken Bia lon cao 330ml', 1, 451000.00, 451000.00, '78.png', 315700.00, NULL),
(134, 31, 77, 'Tiger Bia Crystal lon 330ml', 1, 410000.00, 410000.00, '77.png', 287000.00, NULL),
(135, 32, 84, 'Lock&Lock Nồi chiên không dầu 5.5L EJF179IVY', 5, 3437000.00, 17185000.00, '84.png', 2405900.00, NULL),
(136, 33, 65, 'Đức Việt Xúc xích Roma gói 500g', 9, 60400.00, 543600.00, '65.png', 42280.00, NULL),
(137, 34, 82, 'Nescafé Café 3in1 Đậm Đà Hài Hòa 20 × 16g', 1, 80500.00, 80500.00, '82.png', 56350.00, NULL),
(138, 34, 81, 'La Vie Nước khoáng 500ml', 3, 5800.00, 17400.00, '81.png', 4060.00, NULL),
(139, 34, 80, 'Nutri Boost Nước ngọt cam sữa 297ml', 3, 10600.00, 31800.00, '80.png', 7420.00, NULL),
(140, 35, 28, 'GIFT Nước lau sàn Pink Sakura 3.8kg', 1, 922000.00, 922000.00, '28.png', 737600.00, 62),
(141, 35, 62, 'Lốc 3 Miwon lá kim tảo biển cao cấp 5g', 1, 41300.00, 41300.00, '62.png', 33040.00, 111),
(142, 35, 63, 'Meizan Bột mì đa dụng 500g MB', 1, 16100.00, 16100.00, '63.png', 12880.00, 112),
(143, 36, 81, 'La Vie Nước khoáng 500ml', 7, 5800.00, 40600.00, '81.png', 4640.00, 138),
(144, 37, 50, 'Chocopie Bánh Orion hộp 396g', 4, 58200.00, 232800.00, '50.png', 46560.00, 95),
(145, 38, 75, 'Bia Sài Gòn Special Sleek lon 330ml', 4, 333000.00, 1332000.00, '75.png', 266400.00, 130),
(146, 39, 75, 'Bia Sài Gòn Special Sleek lon 330ml', 1, 333000.00, 333000.00, '75.png', 266400.00, 130),
(147, 39, 12, 'Kim bôi măng trúc Quân Tử 300g', 4, 29000.00, 116000.00, '12.png', 23200.00, 40),
(148, 39, 11, 'Cải bó xôi 300g', 4, 12000.00, 48000.00, '11.png', 9600.00, 39),
(149, 40, 19, 'Dưa hấu ruột vàng', 1, 14900.00, 14900.00, '19.png', 11920.00, 51),
(150, 40, 3, 'Pediasure Sữa bột hương vani 1.6kg', 1, 1226000.00, 1226000.00, '3.png', 980800.00, 24),
(151, 40, 91, 'Biên Hòa Đường mía Thượng hạng gói 500g', 1, 18600.00, 18600.00, '91.png', 14880.00, 152),
(152, 41, 10, 'Súp lơ bông cải trắng L1', 1, 47920.00, 47920.00, '10.png', 38336.00, 38),
(153, 42, 81, 'La Vie Nước khoáng 500ml', 1, 5800.00, 5800.00, '81.png', 4640.00, 138),
(154, 42, 91, 'Biên Hòa Đường mía Thượng hạng gói 500g', 1, 18600.00, 18600.00, '91.png', 14880.00, 152),
(155, 42, 10, 'Súp lơ bông cải trắng L1', 1, 47920.00, 47920.00, '10.png', 38336.00, 38),
(156, 43, 24, 'Comfort Nước xả vải thời thượng 3.8kg', 1, 220000.00, 220000.00, '24.png', 176000.00, 58),
(157, 43, 75, 'Bia Sài Gòn Special Sleek lon 330ml', 3, 333000.00, 999000.00, '75.png', 266400.00, 130),
(158, 44, 11, 'Cải bó xôi 300g', 3, 12000.00, 36000.00, '11.png', 9600.00, 39),
(159, 45, 86, 'Máy xay thịt 1L Lock&Lock 400W màu đen', 1, 972200.00, 972200.00, '86.png', 777760.00, 145),
(160, 45, 81, 'La Vie Nước khoáng 500ml', 1, 5800.00, 5800.00, '81.png', 4640.00, 138),
(161, 46, 1, 'Sữa tiệt trùng Meiji không Lactose 946ml', 1, 84500.00, 84500.00, '1.png', 67600.00, 22),
(162, 46, 109, 'Hạt điều rang muối Minh Việt vỏ hạt to 400g', 1, 230000.00, 230000.00, 'img_6962b7ed59175_1768077293.jpg', 195000.00, NULL),
(163, 46, 110, 'Hạt Macadamia Nữ Hoàng Hạt 250g', 1, 150000.00, 150000.00, 'img_6962b7dfe8985_1768077279.jpg', 140000.00, NULL),
(164, 47, 26, 'Sunlight Nước rửa chén thiên nhiên túi 2.1kg + 2kg', 1, 49000.00, 49000.00, '26.png', 39200.00, 60),
(165, 47, 3, 'Pediasure Sữa bột hương vani 1.6kg', 1, 1226000.00, 1226000.00, '3.png', 980800.00, 24),
(166, 47, 14, 'Cà rốt', 3, 12000.00, 36000.00, '14.png', 9600.00, 44),
(167, 47, 62, 'Lốc 3 Miwon lá kim tảo biển cao cấp 5g', 1, 41300.00, 41300.00, '62.png', 33040.00, 111),
(168, 47, 63, 'Meizan Bột mì đa dụng 500g MB', 3, 16100.00, 48300.00, '63.png', 12880.00, 112),
(169, 48, 84, 'Lock&amp;Lock Nồi chiên không dầu 5.5L EJF179IVY', 1, 3400000.00, 3400000.00, '84.png', 2749600.00, 143),
(170, 48, 86, 'Máy xay thịt 1L Lock&Lock 400W màu đen', 1, 972200.00, 972200.00, '86.png', 777760.00, 145),
(171, 48, 109, 'Hạt điều rang muối Minh Việt vỏ hạt to 400g', 1, 230000.00, 230000.00, 'img_6962b7ed59175_1768077293.jpg', 195000.00, NULL),
(172, 48, 110, 'Hạt Macadamia Nữ Hoàng Hạt 250g', 1, 150000.00, 150000.00, 'img_6962b7dfe8985_1768077279.jpg', 140000.00, NULL),
(173, 49, 9, 'Bắp cải trái tim L1', 1, 22000.00, 22000.00, '9.png', 17600.00, 37),
(174, 50, 10, 'Súp lơ bông cải trắng L1', 1, 47920.00, 47920.00, '10.png', 38336.00, 38);

--
-- Bẫy `chi_tiet_don_hang`
--
DELIMITER $$
CREATE TRIGGER `trg_dat_hang_tru_kho` AFTER INSERT ON `chi_tiet_don_hang` FOR EACH ROW BEGIN
    
    UPDATE san_pham 
    SET So_luong_ton = GREATEST(So_luong_ton - NEW.So_luong, 0) 
    WHERE ID_sp = NEW.ID_sp;
    
    
    IF NEW.ID_chi_tiet_nhap IS NOT NULL THEN
        UPDATE chi_tiet_phieu_nhap 
        SET So_luong_con = GREATEST(So_luong_con - NEW.So_luong, 0)
        WHERE ID_chi_tiet_nhap = NEW.ID_chi_tiet_nhap;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_dat_hang_validate` BEFORE INSERT ON `chi_tiet_don_hang` FOR EACH ROW BEGIN
    DECLARE v_ten VARCHAR(200);
    DECLARE v_gia DECIMAL(15,2);
    DECLARE v_hinh VARCHAR(255);
    DECLARE v_ton INT;
    
    SELECT Ten, Gia_tien, Hinh_anh, So_luong_ton
    INTO v_ten, v_gia, v_hinh, v_ton
    FROM san_pham 
    WHERE ID_sp = NEW.ID_sp AND Trang_thai = 'active';
    
    IF v_ton IS NULL THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'San pham khong ton tai hoac khong kha dung';
    END IF;
    
    IF v_ton < NEW.So_luong THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'So luong ton khong du';
    END IF;
    
    IF NEW.Ten_sp IS NULL THEN 
        SET NEW.Ten_sp = v_ten; 
    END IF;
    
    IF NEW.Gia_tien IS NULL OR NEW.Gia_tien = 0 THEN 
        SET NEW.Gia_tien = v_gia; 
    END IF;
    
    IF NEW.Hinh_anh IS NULL THEN 
        SET NEW.Hinh_anh = v_hinh; 
    END IF;
    
    SET NEW.Thanh_tien = NEW.So_luong * NEW.Gia_tien;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_phieu_huy`
--

CREATE TABLE `chi_tiet_phieu_huy` (
  `ID_chi_tiet` int(11) NOT NULL,
  `ID_phieu_huy` int(11) NOT NULL,
  `ID_sp` int(11) NOT NULL,
  `ID_lo_nhap` int(11) DEFAULT NULL,
  `Ten_sp` varchar(200) NOT NULL,
  `So_luong` int(11) NOT NULL,
  `Gia_nhap` decimal(15,2) NOT NULL,
  `Thanh_tien` decimal(15,2) NOT NULL,
  `Ghi_chu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_phieu_huy`
--

INSERT INTO `chi_tiet_phieu_huy` (`ID_chi_tiet`, `ID_phieu_huy`, `ID_sp`, `ID_lo_nhap`, `Ten_sp`, `So_luong`, `Gia_nhap`, `Thanh_tien`, `Ghi_chu`) VALUES
(17, 17, 44, NULL, 'Cá hồi Nauy nguyên con 6–8kg', 25, 3499000.00, 87475000.00, NULL),
(18, 18, 45, NULL, 'HDC Tôm thẻ nõn tự nhiên 200g', 25, 125000.00, 3125000.00, NULL),
(19, 19, 46, NULL, 'Lenger Ngao (nghêu) trắng sạch hộp 1200g', 25, 75000.00, 1875000.00, NULL),
(21, 21, 48, NULL, 'Hàu sữa sống 18–20 con/kg', 25, 66900.00, 1672500.00, NULL);

--
-- Bẫy `chi_tiet_phieu_huy`
--
DELIMITER $$
CREATE TRIGGER `trg_cap_nhat_tong_huy` AFTER INSERT ON `chi_tiet_phieu_huy` FOR EACH ROW BEGIN
    UPDATE phieu_huy 
    SET Tong_tien_huy = (
        SELECT COALESCE(SUM(Thanh_tien), 0) 
        FROM chi_tiet_phieu_huy 
        WHERE ID_phieu_huy = NEW.ID_phieu_huy
    )
    WHERE ID_phieu_huy = NEW.ID_phieu_huy;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_chi_tiet_huy_validate` BEFORE INSERT ON `chi_tiet_phieu_huy` FOR EACH ROW BEGIN
    -- Validate số lượng
    IF NEW.So_luong <= 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Số lượng hủy phải lớn hơn 0';
    END IF;
    
    -- Tự động tính thành tiền
    SET NEW.Thanh_tien = NEW.So_luong * NEW.Gia_nhap;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_phieu_nhap`
--

CREATE TABLE `chi_tiet_phieu_nhap` (
  `ID_chi_tiet_nhap` int(11) NOT NULL,
  `ID_phieu_nhap` int(11) NOT NULL,
  `ID_sp` int(11) NOT NULL,
  `Ten_sp` varchar(200) NOT NULL,
  `Don_vi_tinh` varchar(50) DEFAULT NULL,
  `Xuat_xu` varchar(100) DEFAULT NULL,
  `Nha_cung_cap` varchar(200) DEFAULT NULL,
  `So_luong` int(11) NOT NULL,
  `Don_gia_nhap` decimal(15,2) NOT NULL,
  `Thanh_tien` decimal(15,2) NOT NULL,
  `Ngay_het_han` date DEFAULT NULL COMMENT 'Ngày hết hạn của lô hàng này',
  `So_luong_con` int(11) DEFAULT NULL COMMENT 'Số lượng còn lại trong lô'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_phieu_nhap`
--

INSERT INTO `chi_tiet_phieu_nhap` (`ID_chi_tiet_nhap`, `ID_phieu_nhap`, `ID_sp`, `Ten_sp`, `Don_vi_tinh`, `Xuat_xu`, `Nha_cung_cap`, `So_luong`, `Don_gia_nhap`, `Thanh_tien`, `Ngay_het_han`, `So_luong_con`) VALUES
(22, 6, 1, 'Sữa tiệt trùng Meiji không Lactose 946ml', NULL, NULL, NULL, 100, 67600.00, 6760000.00, '2026-07-04', 99),
(23, 6, 2, 'Vinamilk Sữa tiệt trùng không đường 180ml', NULL, NULL, NULL, 100, 27360.00, 2736000.00, '2026-07-04', 100),
(24, 6, 3, 'Pediasure Sữa bột hương vani 1.6kg', NULL, NULL, NULL, 100, 980800.00, 98080000.00, '2026-07-04', 98),
(25, 6, 4, 'Ensure Gold Sữa bột 800g', NULL, NULL, NULL, 100, 786000.00, 78600000.00, '2026-07-04', 100),
(26, 6, 5, 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml (lốc 4 hộp)', NULL, NULL, NULL, 100, 26720.00, 2672000.00, '2026-07-04', 100),
(27, 6, 6, 'Mixxi Váng sữa vị kem vani 75g (lốc 4 hộp)', NULL, NULL, NULL, 100, 51200.00, 5120000.00, '2026-07-04', 100),
(28, 6, 7, 'Yakult Sữa chua uống lên men 65ml', NULL, NULL, NULL, 100, 3600.00, 360000.00, '2026-07-04', 100),
(29, 6, 8, 'Yomost Sữa chua uống hương cam 170ml', NULL, NULL, NULL, 100, 14400.00, 1440000.00, '2026-07-04', 100),
(37, 6, 9, 'Bắp cải trái tim L1', NULL, NULL, NULL, 50, 17600.00, 880000.00, '2026-01-30', 39),
(38, 6, 10, 'Súp lơ bông cải trắng L1', NULL, NULL, NULL, 50, 38336.00, 1916800.00, '2026-01-20', 47),
(39, 6, 11, 'Cải bó xôi 300g', NULL, NULL, NULL, 50, 9600.00, 480000.00, '2026-01-14', 46),
(40, 6, 12, 'Kim bôi măng trúc Quân Tử 300g', NULL, NULL, '', 50, 23200.00, 1160000.00, '2026-01-14', 46),
(41, 6, 13, 'BioVegi Nấm BúnApi gói 125g', NULL, NULL, NULL, 50, 28000.00, 1400000.00, '2026-01-14', 50),
(44, 6, 14, 'Cà rốt', NULL, NULL, NULL, 80, 9600.00, 768000.00, '2026-01-25', 77),
(45, 6, 15, 'Bí đỏ tròn', NULL, NULL, NULL, 80, 13600.00, 1088000.00, '2026-01-25', 80),
(46, 6, 16, 'Khoai tây', NULL, NULL, NULL, 80, 8800.00, 704000.00, '2026-01-25', 80),
(47, 6, 17, 'Chanh có hạt 250g', NULL, NULL, NULL, 80, 13600.00, 1088000.00, '2026-01-25', 80),
(48, 6, 18, 'Ớt chuông màu Baby HFG 250g', NULL, NULL, NULL, 80, 20800.00, 1664000.00, '2026-01-25', 80),
(51, 6, 19, 'Dưa hấu ruột vàng', NULL, NULL, NULL, 60, 11920.00, 715200.00, '2026-01-18', 59),
(52, 6, 20, 'Táo đỏ Mỹ', NULL, NULL, NULL, 60, 52000.00, 3120000.00, '2026-01-18', 60),
(53, 6, 21, 'Bưởi Diễn quả', NULL, NULL, NULL, 60, 33600.00, 2016000.00, '2026-01-18', 60),
(54, 6, 22, 'Dâu tây Sơn La 500g', NULL, NULL, NULL, 60, 100000.00, 6000000.00, '2026-01-03', 0),
(55, 6, 23, 'Nho xanh Shine Muscat Hàn Quốc 450g', NULL, NULL, NULL, 60, 135200.00, 8112000.00, '2026-01-18', 60),
(58, 6, 24, 'Comfort Nước xả vải thời thượng 3.8kg', NULL, NULL, NULL, 150, 176000.00, 26400000.00, '2028-01-04', 149),
(59, 6, 25, 'Downy Nước xả nắng mải tươi 1.5L', NULL, NULL, NULL, 150, 98240.00, 14736000.00, '2028-01-04', 150),
(60, 6, 26, 'Sunlight Nước rửa chén thiên nhiên túi 2.1kg + 2kg', NULL, NULL, NULL, 150, 39200.00, 5880000.00, '2028-01-04', 149),
(61, 6, 27, 'Good Care Nước rửa cao cấp thiên nhiên 3.6kg', NULL, NULL, NULL, 150, 159200.00, 23880000.00, '2028-01-04', 150),
(62, 6, 28, 'GIFT Nước lau sàn Pink Sakura 3.8kg', NULL, NULL, NULL, 150, 737600.00, 110640000.00, '2028-01-04', 149),
(63, 6, 29, 'GIFT Nước lau kính 800ml', NULL, NULL, NULL, 150, 22000.00, 3300000.00, '2028-01-04', 150),
(65, 6, 30, 'Lock&Lock Máy tăm nước không dây 200ml ENR156BLU', NULL, NULL, NULL, 120, 551200.00, 66144000.00, '2027-07-04', 120),
(66, 6, 31, 'Listerine Natural Green Tea 750ml', NULL, NULL, NULL, 120, 148400.00, 17808000.00, '2027-07-04', 120),
(67, 6, 32, 'P/S Muối hồng & Hoa cúc 230g', NULL, NULL, NULL, 120, 39200.00, 4704000.00, '2027-07-04', 120),
(68, 6, 33, 'NIVEA Dưỡng thể săn da & trắng ban đêm 400ml', NULL, NULL, NULL, 120, 123200.00, 14784000.00, '2027-07-04', 120),
(69, 6, 34, 'Aiken Xà phòng sạch khuẩn Protection 90g', NULL, NULL, NULL, 120, 9840.00, 1180800.00, '2027-07-04', 120),
(70, 6, 35, 'Romano Sữa tắm giữ ẩm Classic 650g', NULL, NULL, NULL, 120, 153920.00, 18470400.00, '2027-07-04', 120),
(71, 6, 36, 'Kotex BVS Style Lưới Siêu Thấm SMC 8 miếng', NULL, NULL, NULL, 120, 18080.00, 2169600.00, '2027-07-04', 120),
(72, 6, 37, 'Laurier BVS Fresh & Free 22cm 20 miếng', NULL, NULL, NULL, 120, 36000.00, 4320000.00, '2027-07-04', 120),
(73, 6, 38, 'Diana BVS Super Night 29cm 4 miếng', NULL, NULL, NULL, 120, 15840.00, 1900800.00, '2027-07-04', 120),
(80, 6, 39, 'Pacow Bắp Shank khay 250g', NULL, NULL, NULL, 30, 95120.00, 2853600.00, '2026-01-11', 30),
(81, 6, 40, 'Thịt Ba Rọi có da_MH Giun Quế', NULL, NULL, NULL, 30, 187920.00, 5637600.00, '2026-01-11', 30),
(82, 6, 41, 'KiaOra Ba chỉ bò Úc cắt lát khay 200g', NULL, NULL, NULL, 30, 63920.00, 1917600.00, '2026-01-11', 30),
(83, 6, 42, 'AceFoods Lõi Bắp Rùa Bò Tây Ban Nha 500g', NULL, NULL, NULL, 30, 167920.00, 5037600.00, '2026-01-11', 30),
(84, 6, 43, 'Gà ta nguyên con 3F', NULL, NULL, NULL, 30, 124704.00, 3741120.00, '2026-01-11', 30),
(87, 6, 44, 'Cá hồi Nauy nguyên con 6–8kg', NULL, NULL, NULL, 25, 2799200.00, 69980000.00, '2026-01-09', 25),
(88, 6, 45, 'HDC Tôm thẻ nõn tự nhiên 200g', NULL, NULL, NULL, 25, 106400.00, 2660000.00, '2026-01-09', 25),
(89, 6, 46, 'Lenger Ngao (nghêu) trắng sạch hộp 1200g', NULL, NULL, NULL, 25, 60000.00, 1500000.00, '2026-01-09', 25),
(90, 6, 47, 'NTF Râu bạch tuộc đông lạnh 300g', NULL, NULL, NULL, 25, 156000.00, 3900000.00, '2026-01-09', 25),
(91, 6, 48, 'Hàu sữa sống 18–20 con/kg', NULL, NULL, NULL, 25, 53520.00, 1338000.00, '2026-01-09', 25),
(94, 6, 49, 'Danisa Bánh quy bơ hộp 908g', NULL, NULL, NULL, 200, 224800.00, 44960000.00, '2027-01-04', 200),
(95, 6, 50, 'Chocopie Bánh Orion hộp 396g', NULL, NULL, NULL, 200, 46560.00, 9312000.00, '2027-01-04', 196),
(96, 6, 51, 'Tipo Bánh Butter Cookies Matcha hộp 75g', NULL, NULL, NULL, 200, 17520.00, 3504000.00, '2027-01-04', 200),
(97, 6, 52, 'KitKat Trà xanh 8 thanh 136g', NULL, NULL, NULL, 200, 91200.00, 18240000.00, '2027-01-04', 200),
(98, 6, 53, 'Xylitol Kẹo gum Fresh Mint 58g', NULL, NULL, NULL, 200, 23040.00, 4608000.00, '2027-01-04', 200),
(101, 6, 54, 'Koreno Mì vị tôm Jumbo 1kg', NULL, NULL, NULL, 300, 63920.00, 19176000.00, '2026-09-04', 300),
(102, 6, 55, 'Hảo Hảo Mì tôm chua cay 75g', NULL, NULL, NULL, 300, 3360.00, 1008000.00, '2026-09-04', 300),
(103, 6, 56, 'Modern Mì ly vị lẩu Thái tôm 67g', NULL, NULL, NULL, 300, 6880.00, 2064000.00, '2026-09-04', 300),
(104, 6, 57, 'Omachi Mì đậm đà vị sườn ngũ quả 80g', NULL, NULL, NULL, 300, 6800.00, 2040000.00, '2026-09-04', 300),
(105, 6, 58, 'Indomie Mì xào khô vị bò cay 91g', NULL, NULL, NULL, 300, 4800.00, 1440000.00, '2026-09-04', 300),
(108, 6, 59, 'Bao Minh Gạo ST25 Lúa Ruộng Ruội 3kg', NULL, NULL, NULL, 150, 118080.00, 17712000.00, '2027-01-04', 150),
(109, 6, 60, 'Xuân An Yến mạch mật ong 384g', NULL, NULL, NULL, 150, 68000.00, 10200000.00, '2027-01-04', 150),
(110, 6, 61, 'An Vinh Khô cá sặc 200g', NULL, NULL, NULL, 150, 72800.00, 10920000.00, '2027-01-04', 150),
(111, 6, 62, 'Lốc 3 Miwon lá kim tảo biển cao cấp 5g', NULL, NULL, NULL, 150, 33040.00, 4956000.00, '2027-01-04', 148),
(112, 6, 63, 'Meizan Bột mì đa dụng 500g MB', NULL, NULL, NULL, 150, 12880.00, 1932000.00, '2027-01-04', 146),
(115, 6, 64, 'SGM Trà sữa Thái 300ml', NULL, NULL, NULL, 40, 13440.00, 537600.00, '2026-02-03', 40),
(116, 6, 65, 'Đức Việt Xúc xích Roma gói 500g', NULL, NULL, NULL, 40, 48320.00, 1932800.00, '2026-02-03', 40),
(117, 6, 66, 'Phú Mỹ Bakery Bánh bao nhân xíu mại 300g', NULL, NULL, NULL, 40, 23960.00, 958400.00, '2026-02-03', 40),
(118, 6, 67, 'Bibigo Kim chi cải thảo cắt lát 100g', NULL, NULL, NULL, 40, 11360.00, 454400.00, '2026-02-03', 40),
(119, 6, 68, 'Sa Giang Bánh phồng tôm mini 15 – 100g', NULL, NULL, NULL, 40, 9760.00, 390400.00, '2026-02-03', 40),
(122, 6, 69, 'Đồi Dừa Vàng Tôm Thịt 3×140g (Khay 252g)', NULL, NULL, NULL, 50, 133600.00, 6680000.00, '2026-07-04', 50),
(123, 6, 70, 'Orifood Ba chỉ bò Mỹ 500g sốt sukiyaki', NULL, NULL, NULL, 50, 156240.00, 7812000.00, '2026-07-04', 50),
(124, 6, 71, 'Meat Deli Giò lụa Hảo Hạng 300g', NULL, NULL, NULL, 50, 44000.00, 2200000.00, '2026-07-04', 50),
(125, 6, 72, 'LC Food Viên thả lẩu phô mai trứng muối 500g', NULL, NULL, NULL, 50, 67360.00, 3368000.00, '2026-07-04', 50),
(126, 6, 73, 'Há cảo Thực Phẩm Cầu Tre gói 500g', NULL, NULL, NULL, 50, 63040.00, 3152000.00, '2026-07-04', 50),
(129, 6, 74, 'Bia Hà Nội Premium lon Sleek 330ml', NULL, NULL, NULL, 200, 238640.00, 47728000.00, '2027-01-04', 200),
(130, 6, 75, 'Bia Sài Gòn Special Sleek lon 330ml', NULL, NULL, NULL, 200, 266400.00, 53280000.00, '2027-01-04', 192),
(131, 6, 76, 'Bia Hà Nội lon 330ml', NULL, NULL, NULL, 200, 22320.00, 4464000.00, '2027-01-04', 200),
(132, 6, 77, 'Tiger Bia Crystal lon 330ml', NULL, NULL, NULL, 200, 328000.00, 65600000.00, '2027-01-04', 200),
(133, 6, 78, 'Heineken Bia lon cao 330ml', NULL, NULL, NULL, 200, 360800.00, 72160000.00, '2027-01-04', 200),
(136, 6, 79, 'Coca-Cola Nước ngọt Coca 2.25L', NULL, NULL, NULL, 150, 20720.00, 3108000.00, '2026-10-04', 150),
(137, 6, 80, 'Nutri Boost Nước ngọt cam sữa 297ml', NULL, NULL, NULL, 150, 8480.00, 1272000.00, '2026-10-04', 150),
(138, 6, 81, 'La Vie Nước khoáng 500ml', NULL, NULL, NULL, 150, 4640.00, 696000.00, '2026-10-04', 141),
(139, 6, 82, 'Nescafé Café 3in1 Đậm Đà Hài Hòa 20 × 16g', NULL, NULL, NULL, 150, 64400.00, 9660000.00, '2026-10-04', 150),
(140, 6, 83, 'Nestea TPBS Trà vị chanh hộp 195g', NULL, NULL, NULL, 150, 28400.00, 4260000.00, '2026-10-21', 150),
(143, 6, 84, 'Lock&Lock Nồi chiên không dầu 5.5L EJF179IVY', NULL, NULL, NULL, 20, 2749600.00, 54992000.00, NULL, 19),
(144, 6, 85, 'Nồi cơm điện tử cao tần Lock & Lock 1.5L', NULL, NULL, NULL, 20, 4940800.00, 98816000.00, NULL, 20),
(145, 6, 86, 'Máy xay thịt 1L Lock&Lock 400W màu đen', NULL, NULL, NULL, 20, 777760.00, 15555200.00, NULL, 18),
(146, 6, 87, 'Bếp nướng điện Lock&Lock', NULL, NULL, NULL, 20, 1177600.00, 23552000.00, NULL, 20),
(147, 6, 88, 'Hokkaido Hộp Thực phẩm trộn 750ml', NULL, NULL, NULL, 20, 24800.00, 496000.00, NULL, 20),
(150, 6, 89, 'Meizan Dầu đậu nành chai 2L', NULL, NULL, NULL, 100, 64720.00, 6472000.00, '2027-07-04', 100),
(151, 6, 90, 'Nước mắm Nam Ngư 900ml', NULL, NULL, NULL, 100, 48160.00, 4816000.00, '2027-07-04', 100),
(152, 6, 91, 'Biên Hòa Đường mía Thượng hạng gói 500g', NULL, NULL, NULL, 100, 14880.00, 1488000.00, '2027-07-04', 98),
(153, 6, 92, 'Kikkoman Nước tương chai 150ml T24', NULL, NULL, NULL, 100, 80720.00, 8072000.00, '2027-07-04', 100),
(154, 6, 93, 'Chin-Su Tương ớt 250g', NULL, NULL, NULL, 100, 14640.00, 1464000.00, '2027-07-04', 100),
(155, 7, 44, 'Cá hồi Nauy nguyên con 6–8kg', 'Con', '', '', 200, 3499000.00, 699800000.00, NULL, NULL),
(156, 8, 108, 'Kẹo socola Snickers 240g', 'Cái', '', '', 100, 65000.00, 6500000.00, NULL, NULL),
(157, 9, 109, 'Hạt điều rang muối Minh Việt vỏ hạt to 400g', 'Hộp', '', '', 100, 195000.00, 19500000.00, NULL, NULL),
(158, 9, 110, 'Hạt Macadamia Nữ Hoàng Hạt 250g', 'Hộp', '', '', 50, 140000.00, 7000000.00, NULL, NULL),
(159, 9, 111, 'Hạt dẻ Tuấn Đạt 110g', 'Hộp', '', '', 80, 70000.00, 5600000.00, NULL, NULL),
(160, 10, 76, 'Bia Hà Nội lon 330ml', 'Lon', '', 'Công Ty TNHH Han Kim', 200, 27900.00, 5580000.00, '2026-05-30', NULL),
(161, 10, 1, 'Sữa tiệt trùng Meiji không Lactose 946ml', 'Hộp', '', 'Công Ty TNHH Mộc Châu', 20, 84500.00, 1690000.00, '2026-07-24', NULL),
(162, 11, 44, 'Cá hồi Nauy nguyên con 6–8kg', 'Con', '', 'Công Ty TNHH Han Kim', 1, 3499000.00, 3499000.00, '2026-01-07', NULL);

--
-- Bẫy `chi_tiet_phieu_nhap`
--
DELIMITER $$
CREATE TRIGGER `trg_cap_nhat_gia_nhap` AFTER INSERT ON `chi_tiet_phieu_nhap` FOR EACH ROW BEGIN
    DECLARE v_ton_hien_tai INT;
    DECLARE v_gia_nhap_cu DECIMAL(15,2);
    DECLARE v_gia_nhap_moi DECIMAL(15,2);
    
    -- Lấy tồn và giá nhập hiện tại
    -- Lưu ý: trigger trg_nhap_kho_tang_ton đã chạy, So_luong_ton đã được cộng
    SELECT So_luong_ton, COALESCE(Gia_nhap, 0)
    INTO v_ton_hien_tai, v_gia_nhap_cu
    FROM san_pham WHERE ID_sp = NEW.ID_sp;
    
    -- Tính giá nhập TB mới (weighted average)
    IF v_ton_hien_tai > 0 THEN
        SET v_gia_nhap_moi = (
            (v_ton_hien_tai - NEW.So_luong) * v_gia_nhap_cu 
            + NEW.So_luong * NEW.Don_gia_nhap
        ) / v_ton_hien_tai;
        
        UPDATE san_pham 
        SET Gia_nhap = v_gia_nhap_moi 
        WHERE ID_sp = NEW.ID_sp;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_cap_nhat_tong_phieu_nhap` AFTER INSERT ON `chi_tiet_phieu_nhap` FOR EACH ROW BEGIN
    UPDATE phieu_nhap_kho 
    SET Tong_tien = (
        SELECT IFNULL(SUM(Thanh_tien), 0) 
        FROM chi_tiet_phieu_nhap 
        WHERE ID_phieu_nhap = NEW.ID_phieu_nhap
    )
    WHERE ID_phieu_nhap = NEW.ID_phieu_nhap;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_nhap_kho_tang_ton` AFTER INSERT ON `chi_tiet_phieu_nhap` FOR EACH ROW BEGIN
    UPDATE san_pham 
    SET So_luong_ton = So_luong_ton + NEW.So_luong 
    WHERE ID_sp = NEW.ID_sp;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_phieu_nhap_validate` BEFORE INSERT ON `chi_tiet_phieu_nhap` FOR EACH ROW BEGIN
    IF NEW.So_luong <= 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'So luong phai lon hon 0';
    END IF;
    
    IF NEW.Don_gia_nhap < 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Don gia nhap khong the am';
    END IF;
    
    SET NEW.Thanh_tien = NEW.So_luong * NEW.Don_gia_nhap;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danh_muc`
--

CREATE TABLE `danh_muc` (
  `ID_danh_muc` int(11) NOT NULL,
  `Ten_danh_muc` varchar(100) NOT NULL,
  `Danh_muc_cha` int(11) DEFAULT NULL,
  `Mo_ta` text DEFAULT NULL,
  `Thu_tu_hien_thi` int(11) DEFAULT 0,
  `Trang_thai` enum('active','inactive') DEFAULT 'active',
  `Ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `danh_muc`
--

INSERT INTO `danh_muc` (`ID_danh_muc`, `Ten_danh_muc`, `Danh_muc_cha`, `Mo_ta`, `Thu_tu_hien_thi`, `Trang_thai`, `Ngay_tao`) VALUES
(1, 'Sữa các loại', NULL, NULL, 1, 'active', '2025-12-31 06:22:37'),
(2, 'Sữa Tươi', 1, NULL, 1, 'active', '2025-12-31 06:22:37'),
(3, 'Sữa Bột', 1, NULL, 2, 'active', '2025-12-31 06:22:37'),
(4, 'Sữa Chua - Váng Sữa', 1, NULL, 3, 'active', '2025-12-31 06:22:37'),
(5, 'Rau - Củ - Trái Cây', NULL, NULL, 2, 'active', '2025-12-31 06:22:37'),
(6, 'Rau Lá', 5, NULL, 1, 'active', '2025-12-31 06:22:37'),
(7, 'Củ, Quả', 5, NULL, 2, 'active', '2025-12-31 06:22:37'),
(8, 'Trái Cây Tươi', 5, NULL, 3, 'active', '2025-12-31 06:22:37'),
(9, 'Hóa Phẩm - Tẩy Rửa', NULL, NULL, 3, 'active', '2025-12-31 06:22:37'),
(10, 'Nước Giặt - Xả', 9, NULL, 1, 'active', '2025-12-31 06:22:37'),
(11, 'Nước Rửa Chén', 9, NULL, 2, 'active', '2025-12-31 06:22:37'),
(12, 'Nước Lau Sàn - Lau Kính', 9, NULL, 3, 'active', '2025-12-31 06:22:37'),
(13, 'Chăm Sóc Cá Nhân', NULL, NULL, 4, 'active', '2025-12-31 06:22:37'),
(14, 'Chăm Sóc Răng Miệng', 13, NULL, 1, 'active', '2025-12-31 06:22:37'),
(15, 'Chăm Sóc Da', 13, NULL, 2, 'active', '2025-12-31 06:22:37'),
(16, 'Chăm Sóc Phụ Nữ', 13, NULL, 3, 'active', '2025-12-31 06:22:37'),
(17, 'Thịt - Hải Sản Tươi', NULL, NULL, 5, 'active', '2025-12-31 06:22:37'),
(18, 'Thịt', 17, NULL, 1, 'active', '2025-12-31 06:22:37'),
(19, 'Hải Sản', 17, NULL, 2, 'active', '2025-12-31 06:22:37'),
(20, 'Đồ Ăn', NULL, NULL, 6, 'active', '2025-12-31 06:22:37'),
(21, 'Bánh Kẹo', 20, NULL, 1, 'active', '2025-12-31 06:22:37'),
(22, 'Mì', 20, NULL, 2, 'active', '2025-12-31 06:22:37'),
(23, 'Thực Phẩm Khô', 20, NULL, 3, 'active', '2025-12-31 06:22:37'),
(24, 'Thực Phẩm Chế Biến', 20, NULL, 4, 'active', '2025-12-31 06:22:37'),
(25, 'Thực Phẩm Đông Lạnh', 20, NULL, 5, 'active', '2025-12-31 06:22:37'),
(26, 'Đồ uống', NULL, NULL, 7, 'active', '2025-12-31 06:22:37'),
(27, 'Bia', 26, NULL, 1, 'active', '2025-12-31 06:22:37'),
(28, 'Giải Khát', 26, NULL, 2, 'active', '2025-12-31 06:22:37'),
(29, 'Đồ Dùng Bếp', NULL, NULL, 8, 'active', '2025-12-31 06:22:37'),
(30, 'Gia Vị', NULL, NULL, 9, 'active', '2025-12-31 06:22:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `don_hang`
--

CREATE TABLE `don_hang` (
  `ID_dh` int(11) NOT NULL,
  `ID_tk` int(11) NOT NULL,
  `Ten_nguoi_nhan` varchar(200) NOT NULL,
  `Sdt_nguoi_nhan` varchar(15) NOT NULL,
  `Dia_chi_giao_hang` text NOT NULL,
  `Ghi_chu` text DEFAULT NULL,
  `Tong_tien` decimal(15,2) NOT NULL,
  `Phi_van_chuyen` decimal(15,2) DEFAULT 20000.00,
  `Thanh_tien` decimal(15,2) NOT NULL,
  `Trang_thai` enum('dang_xu_ly','dang_giao','da_giao','huy') DEFAULT 'dang_xu_ly',
  `Ngay_dat` timestamp NOT NULL DEFAULT current_timestamp(),
  `Ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `don_hang`
--

INSERT INTO `don_hang` (`ID_dh`, `ID_tk`, `Ten_nguoi_nhan`, `Sdt_nguoi_nhan`, `Dia_chi_giao_hang`, `Ghi_chu`, `Tong_tien`, `Phi_van_chuyen`, `Thanh_tien`, `Trang_thai`, `Ngay_dat`, `Ngay_cap_nhat`) VALUES
(1, 1, 'Kim', '0987654321', 'ỵukiliujytr', 'yujkilkoiuhytre', 1226000.00, 0.00, 1226000.00, 'da_giao', '2025-12-31 07:51:59', '2025-12-31 10:58:15'),
(2, 1, 'Kim', '0987654321', 'hgj,klyjuk,.', 'jk,.kjuiyt7654', 122800.00, 20000.00, 142800.00, 'dang_giao', '2025-12-31 07:53:06', '2025-12-31 19:56:47'),
(3, 1, 'Kim', '0987654321', 'Nam Từ Liêm , Hà Nội', 'Giao hàng trước 12h trưa ngày 1/1/2026', 2309500.00, 0.00, 2309500.00, 'dang_giao', '2025-12-31 17:52:48', '2025-12-31 17:53:24'),
(4, 1, 'Kim', '0987654321', 'Nam Từ Liêm , Hà Nội', '', 116400.00, 20000.00, 136400.00, 'da_giao', '2025-12-31 18:35:51', '2025-12-31 19:56:57'),
(6, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 157000.00, 0.00, 157000.00, 'da_giao', '2026-01-01 10:23:24', '2026-01-01 10:23:24'),
(7, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 33400.00, 0.00, 33400.00, 'da_giao', '2026-01-01 10:30:51', '2026-01-01 10:30:51'),
(8, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 982500.00, 0.00, 982500.00, 'da_giao', '2026-01-01 10:34:53', '2026-01-01 10:34:53'),
(9, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 689000.00, 0.00, 689000.00, 'da_giao', '2026-01-01 10:41:44', '2026-01-01 10:41:44'),
(10, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 689000.00, 0.00, 689000.00, 'da_giao', '2026-01-01 10:42:05', '2026-01-01 10:42:05'),
(11, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 738000.00, 0.00, 738000.00, 'da_giao', '2026-01-01 10:42:28', '2026-01-01 10:42:28'),
(12, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 1286400.00, 0.00, 1286400.00, 'da_giao', '2026-01-01 10:49:55', '2026-01-01 10:49:55'),
(14, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 33400.00, 0.00, 33400.00, 'da_giao', '2026-01-01 10:52:05', '2026-01-01 10:52:05'),
(15, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 33400.00, 0.00, 33400.00, 'da_giao', '2026-01-01 10:52:36', '2026-01-01 10:52:36'),
(16, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 33400.00, 0.00, 33400.00, 'da_giao', '2026-01-01 10:54:50', '2026-01-01 10:54:50'),
(17, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 33400.00, 0.00, 33400.00, 'da_giao', '2026-01-01 11:02:12', '2026-01-01 11:02:12'),
(18, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 47920.00, 0.00, 47920.00, 'da_giao', '2026-01-01 11:02:37', '2026-01-01 11:02:37'),
(19, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 274820.00, 0.00, 274820.00, 'da_giao', '2026-01-01 11:04:02', '2026-01-01 11:04:02'),
(20, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 8161050.00, 0.00, 8161050.00, 'da_giao', '2026-01-01 11:05:50', '2026-01-01 11:05:50'),
(21, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 13051950.00, 0.00, 13051950.00, 'da_giao', '2026-01-01 11:07:10', '2026-01-01 11:07:10'),
(22, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 2331770.00, 0.00, 2331770.00, 'da_giao', '2026-01-01 11:07:39', '2026-01-01 11:07:39'),
(23, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 1704900.00, 0.00, 1704900.00, 'da_giao', '2026-01-01 11:19:19', '2026-01-01 11:19:19'),
(24, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 5642600.00, 0.00, 5642600.00, 'da_giao', '2026-01-01 11:20:39', '2026-01-01 11:20:39'),
(25, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 1766500.00, 0.00, 1766500.00, 'da_giao', '2026-01-01 14:57:39', '2026-01-01 14:57:39'),
(26, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 33400.00, 0.00, 33400.00, 'da_giao', '2026-01-01 14:59:12', '2026-01-01 14:59:12'),
(27, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 2897500.00, 0.00, 2897500.00, 'da_giao', '2026-01-01 15:07:59', '2026-01-01 15:07:59'),
(28, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 1015900.00, 0.00, 1015900.00, 'da_giao', '2026-01-01 15:27:09', '2026-01-01 15:27:09'),
(29, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 34000.00, 0.00, 34000.00, 'da_giao', '2026-01-01 16:58:35', '2026-01-01 16:58:35'),
(30, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 17000.00, 0.00, 17000.00, 'da_giao', '2026-01-01 17:47:58', '2026-01-01 17:47:58'),
(31, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 1580220.00, 0.00, 1580220.00, 'da_giao', '2026-01-02 07:02:46', '2026-01-02 07:02:46'),
(32, 1, 'Kim', '0987654321', 'Nam Từ Liêm , Hà Nội', 'ko có', 17185000.00, 0.00, 17185000.00, 'da_giao', '2026-01-02 07:03:46', '2026-01-02 07:04:00'),
(33, 1, 'Kim', '0987654321', 'Nam Từ Liêm , Hà Nội', '4/1/2026', 543600.00, 0.00, 543600.00, 'da_giao', '2026-01-04 02:39:02', '2026-01-08 12:07:35'),
(34, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 129700.00, 0.00, 129700.00, 'da_giao', '2026-01-04 08:36:50', '2026-01-04 08:36:50'),
(35, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 979400.00, 0.00, 979400.00, 'da_giao', '2026-01-04 15:41:40', '2026-01-04 15:41:40'),
(36, 1, 'Kim', '0987654321', 'Nam Từ Liêm , Hà Nội', '11:29 4/1/2026', 40600.00, 20000.00, 60600.00, 'da_giao', '2026-01-04 16:29:16', '2026-01-10 17:27:09'),
(37, 1, 'Kim', '0987654321', 'Nam Từ Liêm , Hà Nội', '11:30 4/1/2026', 232800.00, 0.00, 232800.00, 'da_giao', '2026-01-04 16:30:10', '2026-01-04 16:30:22'),
(38, 1, 'Kim', '0987654321', 'Nam Từ Liêm , Hà Nội', 'null', 1332000.00, 0.00, 1332000.00, 'da_giao', '2026-01-08 12:04:40', '2026-01-08 12:07:27'),
(39, 1, 'Kim', '0987654321', 'Nam Từ Liêm , Hà Nội', 'null 3:56 CH \r\n10/1/2026', 497000.00, 0.00, 497000.00, 'dang_giao', '2026-01-10 08:57:00', '2026-01-10 08:57:41'),
(40, 1, 'Kim', '0987654321', 'Nam Từ Liêm , Hà Nội', '3:57 CH\r\n10/1/2026', 1259500.00, 0.00, 1259500.00, 'da_giao', '2026-01-10 08:57:31', '2026-01-10 08:57:44'),
(41, 1, 'Kim', '0987654321', 'Nam Từ Liêm , Hà Nội', '', 47920.00, 20000.00, 67920.00, 'dang_giao', '2026-01-10 17:42:38', '2026-01-10 19:26:17'),
(42, 1, 'Kim', '0987654321', 'Nam Từ Liêm , Hà Nội', '12:43 SA\r\n11/1/2026', 72320.00, 20000.00, 92320.00, 'da_giao', '2026-01-10 17:43:43', '2026-01-10 18:14:21'),
(43, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 1219000.00, 0.00, 1219000.00, 'da_giao', '2026-01-10 17:47:56', '2026-01-10 17:47:56'),
(44, 1, 'Kim', '0987654321', 'hem có nhớ gì cạ', '1:14 SA\r\n11/1/2026', 36000.00, 20000.00, 56000.00, 'huy', '2026-01-10 18:14:55', '2026-01-10 18:15:01'),
(45, 1, 'Kim', '0987654321', 'Nam Từ Liêm , Hà Nội', '2:06 SA\r\n11/1/2026', 978000.00, 0.00, 978000.00, 'dang_giao', '2026-01-10 19:06:50', '2026-01-11 14:51:35'),
(46, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 464500.00, 0.00, 464500.00, 'da_giao', '2026-01-10 21:25:13', '2026-01-10 21:25:13'),
(47, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 1400600.00, 0.00, 1400600.00, 'da_giao', '2026-01-10 21:25:46', '2026-01-10 21:25:46'),
(48, 999999, 'Khách vãng lai', '0000000000', 'Mua tại quầy', 'Mua tại quầy - Thu ngân: Kim', 4752200.00, 0.00, 4752200.00, 'da_giao', '2026-01-11 14:59:21', '2026-01-11 14:59:21'),
(49, 1, 'Kim', '0987654321', 'Nam Từ Liêm , Hà Nội', 'hull', 22000.00, 20000.00, 42000.00, 'dang_xu_ly', '2026-01-11 16:15:34', '2026-01-11 16:15:34'),
(50, 1, 'Kim', '0987654321', 'hem có nhớ gì cạ', 'null', 47920.00, 20000.00, 67920.00, 'dang_xu_ly', '2026-01-11 16:18:38', '2026-01-11 16:18:38');

--
-- Bẫy `don_hang`
--
DELIMITER $$
CREATE TRIGGER `trg_huy_don_hoan_kho` AFTER UPDATE ON `don_hang` FOR EACH ROW BEGIN
    
    IF NEW.Trang_thai = 'huy' AND OLD.Trang_thai != 'huy' THEN
        
        UPDATE san_pham sp
        INNER JOIN chi_tiet_don_hang ct ON sp.ID_sp = ct.ID_sp
        SET sp.So_luong_ton = sp.So_luong_ton + ct.So_luong
        WHERE ct.ID_dh = NEW.ID_dh;
        
        
        UPDATE chi_tiet_phieu_nhap pn
        INNER JOIN chi_tiet_don_hang ct ON pn.ID_chi_tiet_nhap = ct.ID_chi_tiet_nhap
        SET pn.So_luong_con = pn.So_luong_con + ct.So_luong
        WHERE ct.ID_dh = NEW.ID_dh 
          AND ct.ID_chi_tiet_nhap IS NOT NULL;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `gio_hang`
--

CREATE TABLE `gio_hang` (
  `ID_gio` int(11) NOT NULL,
  `ID_tk` int(11) NOT NULL,
  `ID_sp` int(11) NOT NULL,
  `So_luong` int(11) DEFAULT 1,
  `Ngay_them` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `gio_hang`
--

INSERT INTO `gio_hang` (`ID_gio`, `ID_tk`, `ID_sp`, `So_luong`, `Ngay_them`) VALUES
(41, 1, 10, 3, '2026-01-10 18:54:57'),
(42, 1, 84, 1, '2026-01-10 19:05:56'),
(44, 1, 81, 2, '2026-01-10 19:06:57'),
(45, 1, 32, 1, '2026-01-10 19:07:10'),
(46, 1, 33, 1, '2026-01-10 19:07:10'),
(47, 1, 25, 1, '2026-01-10 19:07:16'),
(48, 1, 51, 3, '2026-01-10 19:15:04'),
(51, 1, 5, 2, '2026-01-10 20:55:37'),
(53, 1, 91, 1, '2026-01-11 15:08:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lich_su_san_pham`
--

CREATE TABLE `lich_su_san_pham` (
  `ID_lich_su` int(11) NOT NULL,
  `ID_sp` int(11) NOT NULL,
  `Ten_sp` varchar(200) DEFAULT NULL,
  `Gia_cu` decimal(15,2) DEFAULT NULL,
  `Gia_moi` decimal(15,2) DEFAULT NULL,
  `So_luong_cu` int(11) DEFAULT NULL,
  `So_luong_moi` int(11) DEFAULT NULL,
  `Nguoi_sua` int(11) NOT NULL,
  `Loai_thao_tac` enum('sua_gia','sua_so_luong','sua_thong_tin','xoa','them_moi') NOT NULL,
  `Ghi_chu` text DEFAULT NULL,
  `Ngay_sua` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `lich_su_san_pham`
--

INSERT INTO `lich_su_san_pham` (`ID_lich_su`, `ID_sp`, `Ten_sp`, `Gia_cu`, `Gia_moi`, `So_luong_cu`, `So_luong_moi`, `Nguoi_sua`, `Loai_thao_tac`, `Ghi_chu`, `Ngay_sua`) VALUES
(1, 25, 'Downy Nước xả nắng mải tươi 1.5L', NULL, NULL, 3500, 3499, 1, 'sua_so_luong', NULL, '2025-12-31 07:53:06'),
(2, 25, 'Downy Nước xả nắng mải tươi 1.5L', NULL, NULL, 3499, 3500, 1, 'sua_so_luong', NULL, '2025-12-31 07:53:13'),
(3, 2, 'Vinamilk Sữa tiệt trùng không đường 180ml', NULL, NULL, 200, 199, 1, 'sua_so_luong', NULL, '2025-12-31 17:52:48'),
(4, 3, 'Pediasure Sữa bột hương vani 1.6kg', NULL, NULL, 2999, 2998, 1, 'sua_so_luong', NULL, '2025-12-31 17:52:48'),
(5, 5, 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml (lốc 4 hộp)', NULL, NULL, 1000, 998, 1, 'sua_so_luong', NULL, '2025-12-31 17:52:48'),
(6, 4, 'Ensure Gold Sữa bột 800g', NULL, NULL, 3000, 2999, 1, 'sua_so_luong', NULL, '2025-12-31 17:52:48'),
(7, 45, 'HDC Tôm thẻ nõn tự nhiên 200g', 133000.00, 125000.00, NULL, NULL, 1, 'sua_gia', 'Gia thay doi tu 133000.00 thanh 125000.00', '2026-01-10 16:54:34'),
(8, 84, 'Lock&amp;Lock Nồi chiên không dầu 5.5L EJF179IVY', 3437000.00, 3400000.00, NULL, NULL, 1, 'sua_gia', 'Gia thay doi tu 3437000.00 thanh 3400000.00', '2026-01-10 16:59:23'),
(9, 41, 'KiaOra Ba chỉ bò Úc cắt lát khay 200g', 79900.00, 75000.00, NULL, NULL, 1, 'sua_gia', 'Gia thay doi tu 79900.00 thanh 75000.00', '2026-01-10 16:59:47'),
(10, 41, 'KiaOra Ba chỉ bò Úc cắt lát khay 200g', 75000.00, 750000.00, NULL, NULL, 1, 'sua_gia', 'Gia thay doi tu 75000.00 thanh 750000.00', '2026-01-10 17:00:03'),
(11, 41, 'KiaOra Ba chỉ bò Úc cắt lát khay 200g', 750000.00, 75000.00, NULL, NULL, 1, 'sua_gia', 'Gia thay doi tu 750000.00 thanh 75000.00', '2026-01-10 17:00:22'),
(12, 39, 'Pacow Bắp Shank khay 250g', 118900.00, 110000.00, NULL, NULL, 1, 'sua_gia', 'Gia thay doi tu 118900.00 thanh 110000.00', '2026-01-10 17:00:57'),
(13, 10, 'Súp lơ bông cải trắng L1', NULL, NULL, 50, 49, 1, 'sua_so_luong', NULL, '2026-01-10 17:42:38'),
(14, 81, 'La Vie Nước khoáng 500ml', NULL, NULL, 143, 142, 1, 'sua_so_luong', NULL, '2026-01-10 17:43:43'),
(15, 91, 'Biên Hòa Đường mía Thượng hạng gói 500g', NULL, NULL, 99, 98, 1, 'sua_so_luong', NULL, '2026-01-10 17:43:43'),
(16, 10, 'Súp lơ bông cải trắng L1', NULL, NULL, 49, 48, 1, 'sua_so_luong', NULL, '2026-01-10 17:43:43'),
(17, 86, 'Máy xay thịt 1L Lock&Lock 400W màu đen', NULL, NULL, 20, 19, 1, 'sua_so_luong', NULL, '2026-01-10 19:06:50'),
(18, 81, 'La Vie Nước khoáng 500ml', NULL, NULL, 142, 141, 1, 'sua_so_luong', NULL, '2026-01-10 19:06:50'),
(19, 108, 'Kẹo socola Snickers 240g', NULL, NULL, 0, 100, 1, 'sua_so_luong', NULL, '2026-01-10 19:59:54'),
(20, 109, 'Hạt điều rang muối Minh Việt vỏ hạt to 400g', NULL, NULL, 0, 100, 1, 'sua_so_luong', NULL, '2026-01-10 20:32:10'),
(21, 110, 'Hạt Macadamia Nữ Hoàng Hạt 250g', NULL, NULL, 0, 50, 1, 'sua_so_luong', NULL, '2026-01-10 20:32:10'),
(22, 111, 'Hạt dẻ Tuấn Đạt 110g', NULL, NULL, 0, 80, 1, 'sua_so_luong', NULL, '2026-01-10 20:32:10'),
(23, 111, 'Hạt dẻ Tuấn Đạt 110g', 70000.00, 90000.00, NULL, NULL, 1, 'sua_gia', 'Gia thay doi tu 70000.00 thanh 90000.00', '2026-01-10 20:34:23'),
(24, 110, 'Hạt Macadamia Nữ Hoàng Hạt 250g', 140000.00, 150000.00, NULL, NULL, 1, 'sua_gia', 'Gia thay doi tu 140000.00 thanh 150000.00', '2026-01-10 20:34:39'),
(25, 109, 'Hạt điều rang muối Minh Việt vỏ hạt to 400g', 195000.00, 230000.00, NULL, NULL, 1, 'sua_gia', 'Gia thay doi tu 195000.00 thanh 230000.00', '2026-01-10 20:34:53'),
(26, 1, 'Sữa tiệt trùng Meiji không Lactose 946ml', NULL, NULL, 100, 99, 1, 'sua_so_luong', NULL, '2026-01-10 21:25:13'),
(27, 109, 'Hạt điều rang muối Minh Việt vỏ hạt to 400g', NULL, NULL, 100, 99, 1, 'sua_so_luong', NULL, '2026-01-10 21:25:13'),
(28, 110, 'Hạt Macadamia Nữ Hoàng Hạt 250g', NULL, NULL, 50, 49, 1, 'sua_so_luong', NULL, '2026-01-10 21:25:13'),
(29, 26, 'Sunlight Nước rửa chén thiên nhiên túi 2.1kg + 2kg', NULL, NULL, 150, 149, 1, 'sua_so_luong', NULL, '2026-01-10 21:25:46'),
(30, 3, 'Pediasure Sữa bột hương vani 1.6kg', NULL, NULL, 99, 98, 1, 'sua_so_luong', NULL, '2026-01-10 21:25:46'),
(31, 14, 'Cà rốt', NULL, NULL, 80, 77, 1, 'sua_so_luong', NULL, '2026-01-10 21:25:46'),
(32, 62, 'Lốc 3 Miwon lá kim tảo biển cao cấp 5g', NULL, NULL, 149, 148, 1, 'sua_so_luong', NULL, '2026-01-10 21:25:46'),
(33, 63, 'Meizan Bột mì đa dụng 500g MB', NULL, NULL, 149, 146, 1, 'sua_so_luong', NULL, '2026-01-10 21:25:46'),
(34, 43, 'Gà ta nguyên con 3F', 155880.00, 145000.00, NULL, NULL, 1, 'sua_gia', 'Gia thay doi tu 155880.00 thanh 145000.00', '2026-01-11 07:33:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ma_phieu_sequence`
--

CREATE TABLE `ma_phieu_sequence` (
  `Ngay` date NOT NULL,
  `Stt_cuoi` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ma_phieu_sequence`
--

INSERT INTO `ma_phieu_sequence` (`Ngay`, `Stt_cuoi`) VALUES
('2026-01-04', 1),
('2026-01-08', 1),
('2026-01-11', 2),
('2026-01-12', 2);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nha_cung_cap`
--

CREATE TABLE `nha_cung_cap` (
  `ID_ncc` int(11) NOT NULL,
  `Ma_hien_thi` varchar(20) DEFAULT NULL,
  `Ten_ncc` varchar(200) NOT NULL,
  `Dia_chi` text DEFAULT NULL,
  `Sdt` varchar(20) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Nguoi_lien_he` varchar(100) DEFAULT NULL,
  `Mo_ta` text DEFAULT NULL,
  `Trang_thai` enum('active','inactive') DEFAULT 'active',
  `Ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nha_cung_cap`
--

INSERT INTO `nha_cung_cap` (`ID_ncc`, `Ma_hien_thi`, `Ten_ncc`, `Dia_chi`, `Sdt`, `Email`, `Nguoi_lien_he`, `Mo_ta`, `Trang_thai`, `Ngay_tao`) VALUES
(1, 'NCC-001', 'Vinamilk', '', '028-1234-5678', 'contact@vinamilk.com', 'Kim', NULL, 'active', '2026-01-02 19:13:09'),
(2, 'NCC-002', 'Kim', '123 nulllllllllllll', '0975387464', 'kim@gmail.com', 'Choco', NULL, 'inactive', '2026-01-10 10:10:34'),
(3, 'NCC-003', 'Công Ty TNHH Mộc Châu', 'Cổ Nhuế , Bắc Từ Liêm , Hà Nội \r\n', '0123698852', 'huong@gmail.com', 'Trần Thị Thu Hương', NULL, 'active', '2026-01-10 16:02:27'),
(4, 'NCC-004', 'Công Ty TNHH Han Kim', 'null \r\n', '0975387488', 'han@gmail.com', 'Hắn Nè', NULL, 'active', '2026-01-10 19:24:05');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieu_huy`
--

CREATE TABLE `phieu_huy` (
  `ID_phieu_huy` int(11) NOT NULL,
  `Ma_hien_thi` varchar(50) DEFAULT NULL,
  `Nguoi_tao` int(11) NOT NULL,
  `Ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `Ngay_huy` date NOT NULL,
  `Loai_phieu` enum('huy','hong','het_han','dieu_chinh') DEFAULT 'huy',
  `Ly_do` text NOT NULL,
  `Tong_tien_huy` decimal(15,2) DEFAULT 0.00,
  `Trang_thai` enum('cho_duyet','da_duyet','tu_choi') DEFAULT 'cho_duyet',
  `Nguoi_duyet` int(11) DEFAULT NULL,
  `Ngay_duyet` timestamp NULL DEFAULT NULL,
  `Ly_do_tu_choi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phieu_huy`
--

INSERT INTO `phieu_huy` (`ID_phieu_huy`, `Ma_hien_thi`, `Nguoi_tao`, `Ngay_tao`, `Ngay_huy`, `Loai_phieu`, `Ly_do`, `Tong_tien_huy`, `Trang_thai`, `Nguoi_duyet`, `Ngay_duyet`, `Ly_do_tu_choi`) VALUES
(17, 'PHK20260111-001', 1, '2026-01-11 07:27:35', '2026-01-11', 'het_han', 'Sản phẩm đã hết hạn sử dụng', 87475000.00, 'cho_duyet', NULL, NULL, NULL),
(18, 'PHK20260111-018', 1, '2026-01-11 07:31:56', '2026-01-11', 'het_han', 'Sản phẩm đã hết hạn sử dụng', 3125000.00, 'cho_duyet', NULL, NULL, NULL),
(19, 'PHK20260111-019', 1, '2026-01-11 07:32:04', '2026-01-11', 'het_han', 'Sản phẩm đã hết hạn sử dụng', 1875000.00, 'da_duyet', 1, '2026-01-11 07:32:09', NULL),
(21, 'PHK20260111-020', 1, '2026-01-11 07:41:51', '2026-01-11', 'het_han', 'Sản phẩm đã hết hạn sử dụng', 1672500.00, 'cho_duyet', NULL, NULL, NULL);

--
-- Bẫy `phieu_huy`
--
DELIMITER $$
CREATE TRIGGER `trg_duyet_phieu_huy_tru_kho` AFTER UPDATE ON `phieu_huy` FOR EACH ROW BEGIN
    -- Chỉ xử lý khi DUYỆT (cho_duyet -> da_duyet)
    IF OLD.Trang_thai = 'cho_duyet' AND NEW.Trang_thai = 'da_duyet' THEN
        -- Trừ tồn kho cho tất cả sản phẩm trong phiếu
        UPDATE san_pham sp
        INNER JOIN chi_tiet_phieu_huy ct ON sp.ID_sp = ct.ID_sp
        SET sp.So_luong_ton = GREATEST(sp.So_luong_ton - ct.So_luong, 0)
        WHERE ct.ID_phieu_huy = NEW.ID_phieu_huy;
        
        -- Cập nhật số lượng còn lại trong lô (nếu có link đến lô)
        UPDATE chi_tiet_phieu_nhap pn
        INNER JOIN chi_tiet_phieu_huy ct ON pn.ID_chi_tiet_nhap = ct.ID_lo_nhap
        SET pn.So_luong_con = GREATEST(pn.So_luong_con - ct.So_luong, 0)
        WHERE ct.ID_phieu_huy = NEW.ID_phieu_huy 
          AND ct.ID_lo_nhap IS NOT NULL;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_phieu_huy_ma` BEFORE INSERT ON `phieu_huy` FOR EACH ROW BEGIN
    DECLARE stt INT;
    DECLARE prefix VARCHAR(10);
    
    -- Prefix theo loại phiếu
    SET prefix = CASE NEW.Loai_phieu
        WHEN 'huy' THEN 'PHY'      -- Phiếu hủy
        WHEN 'hong' THEN 'PHH'     -- Phiếu hư hỏng
        WHEN 'het_han' THEN 'PHK'  -- Phiếu hết hạn
        WHEN 'dieu_chinh' THEN 'PDC' -- Phiếu điều chỉnh
        ELSE 'PHY'
    END;
    
    -- Lấy số thứ tự tiếp theo
    SET stt = (SELECT COALESCE(MAX(ID_phieu_huy), 0) + 1 FROM phieu_huy);
    
    -- Tạo mã nếu chưa có
    IF NEW.Ma_hien_thi IS NULL OR NEW.Ma_hien_thi = '' THEN
        SET NEW.Ma_hien_thi = CONCAT(
            prefix, 
            DATE_FORMAT(NEW.Ngay_huy, '%Y%m%d'), 
            '-', 
            LPAD(stt, 3, '0')
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phieu_nhap_kho`
--

CREATE TABLE `phieu_nhap_kho` (
  `ID_phieu_nhap` int(11) NOT NULL,
  `Ma_hien_thi` varchar(50) DEFAULT NULL,
  `Nguoi_tao` int(11) NOT NULL,
  `Ngay_nhap` date NOT NULL,
  `Tong_tien` decimal(15,2) DEFAULT 0.00,
  `Ghi_chu` text DEFAULT NULL,
  `Ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ID_ncc` int(11) DEFAULT NULL COMMENT 'FK ÔåÆ nha_cung_cap',
  `Trang_thai` enum('nhap','da_duyet','huy') DEFAULT 'da_duyet' COMMENT 'nhap=draft, da_duyet=confirmed, huy=cancelled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `phieu_nhap_kho`
--

INSERT INTO `phieu_nhap_kho` (`ID_phieu_nhap`, `Ma_hien_thi`, `Nguoi_tao`, `Ngay_nhap`, `Tong_tien`, `Ghi_chu`, `Ngay_cap_nhat`, `ID_ncc`, `Trang_thai`) VALUES
(6, 'PNK20260104-INIT', 1, '2026-01-04', 1299271920.00, 'Nhập kho  Full HSD.\r\nFix 4/1/2026', '2026-01-04 11:16:17', 1, 'da_duyet'),
(7, 'PNK20260108-001', 1, '2026-01-08', 699800000.00, 'null', '2026-01-10 06:54:59', NULL, 'da_duyet'),
(8, 'PNK20260111-001', 1, '2026-01-11', 6500000.00, 'Kẹo socola mới test thử', '2026-01-10 19:59:54', NULL, 'da_duyet'),
(9, 'PNK20260111-002', 1, '2026-01-11', 32100000.00, '3:31 SA\r\n11/1/2026', '2026-01-10 20:32:10', NULL, 'da_duyet'),
(10, 'PNK20260112-001', 1, '2026-01-12', 7270000.00, '', '2026-01-11 18:03:44', NULL, 'da_duyet'),
(11, 'PNK20260112-002', 1, '2026-01-12', 3499000.00, '', '2026-01-11 18:05:11', NULL, 'da_duyet');

--
-- Bẫy `phieu_nhap_kho`
--
DELIMITER $$
CREATE TRIGGER `trg_phieu_nhap_ma_hien_thi` BEFORE INSERT ON `phieu_nhap_kho` FOR EACH ROW BEGIN
    DECLARE stt_moi INT;
    DECLARE ngay_str VARCHAR(8);
    
    SET ngay_str = DATE_FORMAT(NEW.Ngay_nhap, '%Y%m%d');
    
    INSERT INTO ma_phieu_sequence (Ngay, Stt_cuoi)
    VALUES (NEW.Ngay_nhap, 1)
    ON DUPLICATE KEY UPDATE Stt_cuoi = Stt_cuoi + 1;
    
    SELECT Stt_cuoi INTO stt_moi 
    FROM ma_phieu_sequence 
    WHERE Ngay = NEW.Ngay_nhap 
    FOR UPDATE;
    
    IF NEW.Ma_hien_thi IS NULL OR NEW.Ma_hien_thi = '' THEN
        SET NEW.Ma_hien_thi = CONCAT('PNK', ngay_str, '-', LPAD(stt_moi, 3, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `san_pham`
--

CREATE TABLE `san_pham` (
  `ID_sp` int(11) NOT NULL,
  `Ma_hien_thi` varchar(20) DEFAULT NULL,
  `Ten` varchar(200) NOT NULL,
  `Slug` varchar(255) DEFAULT NULL,
  `ID_danh_muc` int(11) NOT NULL,
  `Thanh_phan` text DEFAULT NULL,
  `Mo_ta_sp` text DEFAULT NULL,
  `Gia_tien` decimal(15,2) NOT NULL,
  `So_luong_ton` int(11) DEFAULT 0,
  `Xuat_xu` varchar(100) DEFAULT NULL,
  `Don_vi_tinh` varchar(50) DEFAULT NULL,
  `Hinh_anh` varchar(255) DEFAULT NULL,
  `Trang_thai` enum('active','inactive','out_of_stock') DEFAULT 'active',
  `Ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `Ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Gia_nhap` decimal(15,2) DEFAULT 0.00 COMMENT 'Giá nhập trung bình'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `san_pham`
--

INSERT INTO `san_pham` (`ID_sp`, `Ma_hien_thi`, `Ten`, `Slug`, `ID_danh_muc`, `Thanh_phan`, `Mo_ta_sp`, `Gia_tien`, `So_luong_ton`, `Xuat_xu`, `Don_vi_tinh`, `Hinh_anh`, `Trang_thai`, `Ngay_tao`, `Ngay_cap_nhat`, `Gia_nhap`) VALUES
(1, 'SP000001', 'Sữa tiệt trùng Meiji không Lactose 946ml', 'sua-tuoi-meiji-khong-lactose-946ml', 2, 'Sữa bò, enzyme lactase', 'Sữa tiệt trùng không lactose, phù hợp cho người không dung nạp lactose', 84500.00, 119, 'Nhật Bản', 'Hộp', '1.png', 'active', '2025-12-31 06:22:44', '2026-01-11 18:03:44', 70440.34),
(2, 'SP000002', 'Vinamilk Sữa tiệt trùng không đường 180ml', 'vinamilk-sua-tiet-trung-khong-duong-180ml', 2, 'Sữa bò tươi (99.88%), chất ổn định (471, 460(i), 407, 466), vitamin (natri ascorbat, A, D3), khoáng chất (natri selenit)', 'Sữa tiệt trùng Vinamilk không đường hộp 180ml, bổ sung Vitamin D3 theo chuẩn EFSA Châu Âu giúp hỗ trợ miễn dịch cung cấp canxi và dưỡng chất cần thiết cho cơ thể.', 34200.00, 100, 'Việt Nam', 'Hộp', '2.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 27360.00),
(3, 'SP000003', 'Pediasure Sữa bột hương vani 1.6kg', 'pediasure-sua-bot-huong-vani-16kg', 3, 'Đạm sữa, chất béo thực vật, carbohydrate, vitamin và khoáng chất', 'Pediasure Sữa bột hương vani 1.6kg là sản phẩm dinh dưỡng công thức cao cấp, được nghiên cứu và phát triển nhằm hỗ trợ sự tăng trưởng và phát triển toàn diện cho trẻ em, đặc biệt là trẻ biếng ăn, chậm tăng cân hoặc có nhu cầu dinh dưỡng cao. Sản phẩm cung cấp nguồn năng lượng dồi dào cùng hệ dưỡng chất cân đối bao gồm đạm, chất béo, carbohydrate, vitamin và khoáng chất thiết yếu.\r\nPediasure chứa hệ protein chất lượng cao giúp xây dựng và phát triển khối cơ, kết hợp cùng các vitamin và khoáng chất như canxi, vitamin D, sắt, kẽm… hỗ trợ phát triển chiều cao, hệ xương và tăng cường sức đề kháng. Ngoài ra, sản phẩm còn bổ sung chất xơ và các vi chất giúp hỗ trợ hệ tiêu hóa, tăng khả năng hấp thu dưỡng chất, giúp trẻ ăn ngon miệng hơn.\r\nVới hương vani thơm ngon, dễ uống, Pediasure phù hợp với khẩu vị của nhiều trẻ em. Sản phẩm có thể sử dụng như bữa ăn bổ sung hoặc thay thế bữa phụ hàng ngày, giúp đảm bảo cung cấp đầy đủ năng lượng và dưỡng chất cần thiết cho sự phát triển khỏe mạnh. Pediasure Sữa bột hương vani 1.6kg là lựa chọn đáng tin cậy cho các bậc phụ huynh mong muốn chăm sóc dinh dưỡng toàn diện cho con em mình.', 1226000.00, 98, 'Singapore', 'Lon', '3.png', 'active', '2025-12-31 06:22:44', '2026-01-10 21:25:46', 980800.00),
(4, 'SP000004', 'Ensure Gold Sữa bột 800g', 'ensure-gold-sua-bot-800g', 3, 'Protein chất lượng cao, vitamin, khoáng chất, chất xơ FOS, HMB', 'Ensure Gold Sữa bột 800g là sản phẩm dinh dưỡng công thức cao cấp được thiết kế đặc biệt nhằm bổ sung dinh dưỡng cân đối cho người lớn, người cao tuổi, người ăn uống kém, người mới ốm dậy hoặc những người cần hỗ trợ sức khỏe tổng thể. Sản phẩm chứa một hỗn hợp chất dinh dưỡng đầy đủ và cân bằng, gồm protein chất lượng cao, vitamin, khoáng chất thiết yếu và các acid béo lành mạnh, giúp cung cấp năng lượng và dưỡng chất mà cơ thể cần mỗi ngày. Ensure Gold hỗ trợ duy trì và tăng cường sức khỏe cơ bắp nhờ thành phần HMB (β-hydroxy-β-methylbutyrate), đồng thời giúp hỗ trợ hệ miễn dịch nhờ beta-glucan và các vi chất quan trọng khác.\r\nNgoài ra, sản phẩm còn bổ sung chất xơ FOS giúp hỗ trợ tiêu hóa khỏe mạnh và hấp thu dưỡng chất tốt hơn. Canxi và vitamin D trong công thức giúp duy trì xương chắc khỏe; các vitamin A, C, E cùng kẽm giúp tăng cường khả năng chống oxy hóa và bảo vệ cơ thể khỏi các tác nhân gây hại. Với hương vani thơm nhẹ, Ensure Gold 800g dễ uống và phù hợp sử dụng hàng ngày như bữa ăn phụ hoặc bổ sung dinh dưỡng khi cần thiết. Sản phẩm là lựa chọn đáng tin cậy cho những ai muốn chăm sóc sức khỏe toàn diện và duy trì chất lượng cuộc sống khỏe mạnh.', 982500.00, 100, 'Singapore', 'Lon', '4.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 786000.00),
(5, 'SP000005', 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml (lốc 4 hộp)', 'topkid-scutt-huong-cam-180ml', 4, 'Sữa, nước, đường, chất ổn định, hương liệu tự nhiên cam, vitamin nhóm B', 'Topkid Scutt Sữa chua uống tiệt trùng hương cam 180ml là sản phẩm dinh dưỡng thơm ngon được làm từ sữa tươi sạch, kết hợp hương cam tự nhiên, bổ sung vitamin nhóm B giúp hỗ trợ phát triển não bộ và chiều cao cho trẻ. Sản phẩm được đóng gói dạng lốc 4 hộp 180ml tiện lợi và hấp dẫn cho trẻ em.', 33400.00, 100, 'Việt Nam', 'Lốc', '5.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 26720.00),
(6, 'SP000006', 'Mixxi Váng sữa vị kem vani 75g (lốc 4 hộp)', 'mixxi-vang-sua-kem-vani-75g', 4, 'Sữa, kem, đường, chất béo, vitamin và khoáng chất', 'Mixxi Váng sữa vị kem vani 75g (lốc 4 hộp) là sản phẩm dinh dưỡng thơm ngon được làm từ lớp váng sữa giàu dinh dưỡng, cung cấp năng lượng, canxi và vitamin cần thiết cho trẻ nhỏ giúp phát triển hệ xương, răng và hỗ trợ tiêu hóa khỏe mạnh.', 64000.00, 100, 'Việt Nam', 'Lốc', '6.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 51200.00),
(7, 'SP000007', 'Yakult Sữa chua uống lên men 65ml', 'yakult-sua-chua-uong-len-men-65ml', 4, '', 'Yakult Sữa chua uống lên men 65ml là sản phẩm sữa chua uống chứa hàng tỷ vi khuẩn có lợi Lactobacillus casei Shirota giúp cân bằng hệ vi sinh đường ruột, hỗ trợ tiêu hóa, tăng cường miễn dịch và tốt cho sức khỏe tổng thể. Sản phẩm có vị chua nhẹ, dễ uống, phù hợp sử dụng hàng ngày.', 4500.00, 100, 'Việt Nam', 'Chai', '7.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 3600.00),
(8, 'SP000008', 'Yomost Sữa chua uống hương cam 170ml', 'yomost-sua-chua-uong-cam-170ml', 4, 'Sữa chua uống, đường, hương cam tự nhiên, chất ổn định, vitamin', 'Yomost Sữa chua uống hương cam 170ml là sản phẩm dinh dưỡng thơm ngon, kết hợp giữa sữa chua lên men tự nhiên và hương cam tươi mát. Sản phẩm chứa lợi khuẩn có lợi cho hệ tiêu hóa, hỗ trợ cân bằng hệ vi sinh đường ruột và tăng cường hấp thu dưỡng chất. Với hương cam tự nhiên dễ uống, Yomost mang đến trải nghiệm tươi mát, bổ dưỡng, phù hợp dùng hàng ngày cho cả trẻ em và người lớn.\r\n\r\nSữa chua uống Yomost bổ sung canxi và vitamin thiết yếu giúp hỗ trợ phát triển hệ xương chắc khỏe, đồng thời giúp tăng sức đề kháng. Sản phẩm tiện lợi, dễ thưởng thức mọi lúc mọi nơi, là lựa chọn tuyệt vời cho bữa phụ hoặc sau khi vận động.', 18000.00, 100, 'Việt Nam', 'Chai', '8.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 14400.00),
(9, 'SP000009', 'Bắp cải trái tim L1', 'bap-cai-trai-tim-l1', 6, 'Bắp cải trái tim L1 tươi sạch', 'Bắp cải trái tim L1 là sản phẩm rau củ tươi sạch được trồng và thu hoạch theo quy trình kiểm soát chất lượng nghiêm ngặt, đảm bảo độ tươi ngon và an toàn cho người tiêu dùng. Bắp cải trái tim có hình dáng nhỏ gọn, phần lá cuộn chặt, mềm và ngọt tự nhiên, lõi nhỏ nên dễ chế biến và hạn chế lãng phí.\r\nSản phẩm giàu chất xơ, vitamin C, vitamin K cùng nhiều khoáng chất thiết yếu giúp hỗ trợ hệ tiêu hóa, tăng cường sức đề kháng và góp phần bảo vệ tim mạch. Bắp cải trái tim rất thích hợp để chế biến đa dạng các món ăn như luộc, xào, nấu canh, làm salad hoặc cuốn thịt, mang lại bữa ăn thanh mát và giàu dinh dưỡng cho gia đình.\r\nBắp cải trái tim L1 được bảo quản và vận chuyển trong điều kiện phù hợp nhằm giữ nguyên độ giòn, màu sắc tươi xanh và hương vị tự nhiên. Đây là lựa chọn lý tưởng cho những ai ưu tiên sử dụng thực phẩm sạch, tươi ngon và tốt cho sức khỏe trong bữa ăn hàng ngày.', 22000.00, 39, 'Việt Nam', 'Kg', '9.png', 'active', '2025-12-31 06:22:44', '2026-01-11 16:15:34', 17600.00),
(10, 'SP000010', 'Súp lơ bông cải trắng L1', 'sup-lo-bong-cai-trang-l1', 6, 'Súp lơ bông cải trắng tươi sạch', 'Súp lơ bông cải trắng L1 là sản phẩm rau củ tươi ngon được tuyển chọn kỹ lưỡng, đảm bảo chất lượng từ khâu trồng trọt đến thu hoạch. Bông cải trắng L1 có phần bông chắc, màu trắng ngà và lá xanh tươi, chứa nhiều dưỡng chất thiết yếu như vitamin C, vitamin K, folate và chất xơ – giúp hỗ trợ hệ miễn dịch, tốt cho tiêu hóa và sức khỏe tổng thể.\r\nSúp lơ bông cải trắng dễ chế biến, có thể dùng để luộc, hấp, xào, nấu canh hay làm salad đều rất ngon và giữ được hương vị tự nhiên. Sản phẩm được bảo quản và vận chuyển trong điều kiện lạnh phù hợp để giữ trọn độ giòn, tươi mới và hàm lượng dinh dưỡng cao nhất.\r\nSúp lơ bông cải trắng L1 là lựa chọn tuyệt vời cho bữa ăn hàng ngày của gia đình, đặc biệt phù hợp với những ai quan tâm đến thực phẩm sạch, giàu dinh dưỡng và tốt cho sức khỏe.', 47920.00, 47, 'Việt Nam', 'Kg', '10.png', 'active', '2025-12-31 06:22:44', '2026-01-11 16:18:38', 38336.00),
(11, 'SP000011', 'Cải bó xôi 300g', 'cai-bo-xoi-300g', 6, 'Cải bó xôi tươi sạch', 'Cải bó xôi 300g là sản phẩm rau xanh tươi ngon, được chọn lựa kỹ lưỡng và bảo quản đúng tiêu chuẩn để giữ nguyên độ tươi, màu sắc và hàm lượng dinh dưỡng. Cải bó xôi chứa nhiều vitamin A, C, K, chất sắt và chất xơ – giúp hỗ trợ tiêu hóa, tăng cường hệ miễn dịch và tốt cho sức khỏe tổng thể.\r\nCải bó xôi dễ chế biến và phù hợp với nhiều món ăn như xào tỏi, luộc, nấu canh hoặc dùng làm salad dinh dưỡng. Sản phẩm có hương vị tự nhiên, giòn ngon và thích hợp dùng hàng ngày trong bữa ăn gia đình.\r\nCải bó xôi 300g là lựa chọn lý tưởng cho những ai yêu thích thực phẩm sạch, giàu dinh dưỡng và tiện lợi trong chế biến.', 12000.00, 46, 'Việt Nam', 'Gói', '11.png', 'active', '2025-12-31 06:22:44', '2026-01-10 18:15:01', 9600.00),
(12, 'SP000012', 'Kim bôi măng trúc Quân Tử 300g', 'kim-boi-mang-truc-quan-tu-300g', 6, 'Măng trúc tươi, gia vị, dầu ăn', 'Kim bôi măng trúc Quân Tử 300g là sản phẩm măng trúc đã được sơ chế sẵn, giữ nguyên độ tươi giòn và hương vị đặc trưng của măng. Sản phẩm được lựa chọn kỹ lưỡng, đảm bảo tiêu chuẩn an toàn thực phẩm và cung cấp nhiều dinh dưỡng như chất xơ, vitamin và khoáng chất tự nhiên từ măng trúc.\r\nMăng trúc có vị giòn, tươi, phù hợp để chế biến nhiều món ăn như xào, nấu canh hoặc dùng trong các món kho, mang lại hương vị thơm ngon và giàu dinh dưỡng cho bữa ăn gia đình. Kim bôi măng trúc Quân Tử 300g tiện lợi, dễ sử dụng và bảo quản, phù hợp cho người nội trợ bận rộn nhưng vẫn muốn mang đến bữa ăn ngon và lành mạnh.\r\nSản phẩm phù hợp với chế độ ăn hàng ngày và là lựa chọn tuyệt vời cho những ai yêu thích thực phẩm dễ chế biến, giàu chất xơ và tốt cho sức khỏe tiêu hóa.', 29000.00, 46, 'Việt Nam', 'Gói', '12.png', 'active', '2025-12-31 06:22:44', '2026-01-10 08:57:00', 23200.00),
(13, 'SP000013', 'BioVegi Nấm BúnApi gói 125g', 'biovegi-nam-bunapi-goi-125g', 6, 'Nấm BúnApi tươi 100%', 'BioVegi Nấm BúnApi gói 125g là sản phẩm nấm tươi sạch với thành phần 100% nấm BúnApi chất lượng cao, được trồng và thu hoạch theo tiêu chuẩn an toàn thực phẩm. Nấm BúnApi có hương vị tự nhiên, giòn, ngon, giàu dinh dưỡng như protein thực vật, chất xơ, vitamin và khoáng chất.\r\nSản phẩm rất phù hợp để chế biến đa dạng món ăn như xào, nấu lẩu, nấu canh hay kết hợp với các loại rau củ khác, mang đến hương vị đậm đà và giá trị dinh dưỡng cao cho bữa ăn gia đình. BioVegi Nấm BúnApi gói 125g tiện lợi, dễ bảo quản và sử dụng, lý tưởng cho chế độ ăn lành mạnh và dinh dưỡng.', 35000.00, 50, 'Việt Nam', 'Gói', '13.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 28000.00),
(14, 'SP000014', 'Cà rốt', 'ca-rot', 7, 'Cà rốt tươi', 'Cà rốt là loại rau củ giàu dinh dưỡng, có màu cam bắt mắt và vị ngọt tự nhiên. Cà rốt chứa nhiều beta-carotene (tiền tố của vitamin A), vitamin C, chất xơ và các khoáng chất thiết yếu giúp hỗ trợ thị lực, tăng cường hệ miễn dịch, tốt cho tiêu hóa và góp phần giữ làn da khỏe mạnh.\r\nCà rốt tươi có thể chế biến thành nhiều món ăn đa dạng như xào, luộc, hấp, nộm hay dùng để làm nước ép thơm ngon. Sản phẩm thích hợp cho bữa ăn hàng ngày của gia đình, dễ chế biến và phù hợp với nhiều lứa tuổi.\r\nCà rốt được lựa chọn kỹ, bảo quản lạnh để giữ độ tươi ngon, màu sắc tự nhiên và giá trị dinh dưỡng cao nhất. Đây là lựa chọn lý tưởng cho những ai ưu tiên thực phẩm sạch, tốt cho sức khỏe và giàu chất xơ.', 12000.00, 77, 'Việt Nam', 'Kg', '14.png', 'active', '2025-12-31 06:22:44', '2026-01-10 21:25:46', 9600.00),
(15, 'SP000015', 'Bí đỏ tròn', 'bi-do-tron', 7, 'Bí đỏ tròn tươi', 'Bí đỏ tròn là loại rau củ có màu cam rực rỡ, vị ngọt dịu và độ mềm mịn khi chín. Bí đỏ chứa nhiều beta-carotene, vitamin A, vitamin C, chất xơ và khoáng chất thiết yếu giúp tăng cường hệ miễn dịch, hỗ trợ tiêu hóa, tốt cho da và thị lực. Ngoài ra, bí đỏ còn chứa chất chống oxy hóa giúp bảo vệ tế bào khỏi gốc tự do.\r\nBí đỏ tròn có thể chế biến thành rất nhiều món ngon như canh bí đỏ, bí đỏ hấp, bí đỏ nấu với đậu, cháo bí đỏ cho bé… mang đến bữa ăn giàu dinh dưỡng cho cả gia đình. Sản phẩm được lựa chọn kỹ lưỡng và bảo quản lạnh để giữ nguyên độ tươi và hàm lượng dinh dưỡng tối đa.\r\nBí đỏ tròn là lựa chọn lý tưởng cho thực đơn lành mạnh, giàu chất xơ và vitamin, phù hợp với mọi lứa tuổi và chế độ ăn uống cân đối.', 17000.00, 80, 'Việt Nam', 'Kg', '15.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 13600.00),
(16, 'SP000016', 'Khoai tây', 'khoai-tay', 7, 'Khoai tây tươi', 'Khoai tây là loại rau củ giàu dinh dưỡng, có vị bùi và mềm sau khi nấu, phù hợp với nhiều món ăn phong phú như chiên, luộc, nướng, nghiền hay hầm canh. Khoai tây chứa nhiều carbohydrate dạng tinh bột, vitamin C, kali và chất xơ, giúp cung cấp năng lượng, hỗ trợ tiêu hóa và tốt cho sức khỏe tim mạch.\r\nSản phẩm được lựa chọn kỹ lưỡng để đảm bảo độ tươi ngon, không bị hư hỏng, có màu sắc tự nhiên và giá trị dinh dưỡng cao. Khoai tây là lựa chọn phổ biến trong bữa ăn hàng ngày, dễ chế biến và phù hợp với mọi lứa tuổi.\r\nKhoai tây tươi thích hợp cho các món ăn từ đơn giản đến cầu kỳ, mang đến trải nghiệm ẩm thực đa dạng và hấp dẫn cho gia đình.', 11000.00, 80, 'Việt Nam', 'Kg', '16.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 8800.00),
(17, 'SP000017', 'Chanh có hạt 250g', 'chanh-co-hat-250g', 7, 'Chanh có hạt tươi', 'Chanh có hạt 250g là sản phẩm trái cây tươi giòn, mọng nước và giàu vitamin C – giúp tăng cường hệ miễn dịch, hỗ trợ tiêu hóa, thanh lọc cơ thể và làm đẹp da. Sản phẩm được lựa chọn kỹ lưỡng, bảo quản lạnh để giữ trọn độ tươi ngon, màu sắc tự nhiên và hương vị chua ngọt đặc trưng.\r\nChanh có hạt phù hợp dùng trực tiếp, pha nước giải khát, làm gia vị chế biến món ăn hoặc trang trí thức uống. Với trọng lượng khoảng 250g mỗi túi, chanh dễ dàng bảo quản trong tủ lạnh, tiện lợi cho sử dụng hàng ngày. Đây là lựa chọn tuyệt vời cho những ai yêu thích trái cây giàu dinh dưỡng và hương vị tự nhiên.', 17000.00, 80, 'Việt Nam', 'Túi', '17.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 13600.00),
(18, 'SP000018', 'Ớt chuông màu Baby HFG 250g', 'ot-chuong-mau-baby-hfg-250g', 7, 'Ớt chuông màu baby tươi', 'Ớt chuông màu Baby HFG 250g là sản phẩm rau củ tươi ngon, được lựa chọn kỹ lưỡng để đảm bảo độ giòn, màu sắc tươi sáng và giá trị dinh dưỡng. Ớt chuông màu baby chứa nhiều vitamin C, chất xơ và các khoáng chất thiết yếu giúp tăng cường sức đề kháng, hỗ trợ tiêu hóa và góp phần duy trì sức khỏe tổng thể.\r\nỚt chuông baby HFG có vị ngọt nhẹ, ít hạt và dễ chế biến, phù hợp với nhiều món ăn như xào, nướng, nấu soup, làm salad tươi mát hoặc dùng trực tiếp. Sản phẩm được bảo quản lạnh để giữ trọn hương vị tự nhiên và độ giòn tươi, là lựa chọn lý tưởng cho bữa ăn lành mạnh của gia đình.\r\nỚt chuông màu Baby HFG 250g thích hợp cho những ai yêu thích thực phẩm sạch, giàu dưỡng chất và dễ chế biến trong nhiều món ăn khác nhau.', 26000.00, 80, 'Việt Nam', 'Gói', '18.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 20800.00),
(19, 'SP000019', 'Dưa hấu ruột vàng', 'dua-hau-ruot-vang', 8, 'Dưa hấu ruột vàng tươi', 'Dưa hấu ruột vàng là sản phẩm trái cây tươi ngon với lớp vỏ xanh đặc trưng và ruột màu vàng tươi bắt mắt. Trái cây chứa nhiều nước, vitamin A, vitamin C và chất chống oxy hóa giúp bổ sung năng lượng, hỗ trợ hệ miễn dịch và tăng cường sức khỏe tổng thể. Dưa hấu ruột vàng còn giàu chất xơ giúp hỗ trợ tiêu hóa và thanh lọc cơ thể.\r\nSản phẩm thích hợp dùng giải khát vào những ngày hè nóng bức, làm salad trái cây, nước ép thơm mát hoặc kết hợp cùng các món tráng miệng. Dưa hấu ruột vàng được lựa chọn kỹ càng, bảo quản lạnh để giữ trọn độ tươi ngon, vị ngọt tự nhiên và hàm lượng dinh dưỡng cao nhất. Đây là lựa chọn tuyệt vời cho những ai yêu thích trái cây tươi, giàu dưỡng chất và phù hợp với chế độ dinh dưỡng lành mạnh.', 14900.00, 59, 'Việt Nam', 'Kg', '19.png', 'active', '2025-12-31 06:22:44', '2026-01-10 08:57:31', 11920.00),
(20, 'SP000020', 'Táo đỏ Mỹ', 'tao-do-my', 8, 'Táo đỏ Mỹ tươi', 'Táo đỏ Mỹ là trái cây nhập khẩu có màu đỏ rực rỡ, vị ngọt giòn đặc trưng và hương thơm hấp dẫn. Táo đỏ chứa nhiều vitamin C, chất xơ, các chất chống oxy hóa giúp hỗ trợ hệ miễn dịch, bảo vệ tế bào khỏi gốc tự do, thúc đẩy tiêu hóa và duy trì sức khỏe tổng thể.\r\nTáo đỏ Mỹ rất phù hợp để ăn trực tiếp, làm salad trái cây, nước ép hoặc dùng làm món tráng miệng bổ dưỡng. Sản phẩm được lựa chọn kỹ càng, bảo quản lạnh để giữ trọn độ tươi ngon, màu sắc tự nhiên và hương vị thơm mát. Đây là lựa chọn tuyệt vời cho những ai yêu thích trái cây giàu dinh dưỡng và tiện lợi cho bữa ăn hàng ngày.', 65000.00, 60, 'Mỹ', 'Kg', '20.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 52000.00),
(21, 'SP000021', 'Bưởi Diễn quả', 'buoi-dien-qua', 8, 'Bưởi Diễn tươi', 'Bưởi Diễn quả là loại trái cây đặc sản nổi tiếng của miền Bắc với hương thơm dịu, vị ngọt thanh và tép bưởi mọng nước. Bưởi chứa nhiều vitamin C, chất xơ và các chất chống oxy hóa giúp hỗ trợ hệ miễn dịch, cải thiện tiêu hóa và thanh lọc cơ thể. Với lớp vỏ xanh mỏng và hương thơm đặc trưng, bưởi Diễn là lựa chọn tuyệt vời cho những ai yêu thích trái cây tươi ngon.\r\nSản phẩm có thể dùng trực tiếp, làm đồ tráng miệng hoặc kết hợp với salad trái cây mang đến hương vị tươi mát và giàu dinh dưỡng. Bưởi Diễn được chọn lựa kỹ lưỡng, bảo quản lạnh để giữ nguyên độ tươi và hương vị tự nhiên, phù hợp sử dụng hàng ngày cho gia đình.', 42000.00, 60, 'Việt Nam', 'Kg', '21.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 33600.00),
(22, 'SP000022', 'Dâu tây Sơn La 500g', 'dau-tay-son-la-500g', 8, 'Dâu tây tươi (Sơn La)', 'Dâu tây Sơn La 500g là sản phẩm trái cây tươi được trồng và thu hoạch tại vùng đất Sơn La – nổi tiếng với khí hậu và thổ nhưỡng phù hợp cho cây dâu phát triển. Dâu tây có màu đỏ bắt mắt, vị ngọt nhẹ pha chút chua thanh tự nhiên và hương thơm đặc trưng. Trái cây giàu vitamin C, chất chống oxy hóa và chất xơ giúp tăng cường hệ miễn dịch, hỗ trợ tiêu hóa và làm đẹp da.\r\nSản phẩm được lựa chọn kỹ lưỡng, bảo quản lạnh để đảm bảo giữ trọn độ tươi và giá trị dinh dưỡng cao nhất. Dâu tây Sơn La rất thích hợp dùng trực tiếp, làm salad trái cây, smoothie hoặc trang trí các món tráng miệng hấp dẫn. Đây là lựa chọn tuyệt vời cho bữa ăn lành mạnh và giàu dinh dưỡng cho cả gia đình.', 125000.00, 0, 'Việt Nam', 'Gói', '22.png', 'out_of_stock', '2025-12-31 06:22:44', '2026-01-04 11:34:59', 100000.00),
(23, 'SP000023', 'Nho xanh Shine Muscat Hàn Quốc 450g', 'nho-xanh-shine-muscat-han-quoc-450g', 8, 'Nho xanh Shine Muscat 100%', 'Nho xanh Shine Muscat Hàn Quốc 450g là sản phẩm trái cây cao cấp được nhập khẩu trực tiếp từ Hàn Quốc, nổi bật với trái nho lớn, vỏ mỏng, ruột giòn sần sật và vị ngọt thanh tự nhiên đặc trưng của giống nho Shine Muscat. Nho chứa nhiều vitamin C, chất chống oxy hóa và chất xơ giúp hỗ trợ hệ miễn dịch, tốt cho hệ tiêu hóa và tăng cường sức khỏe tổng thể.\r\nSản phẩm được tuyển chọn kỹ lưỡng, bảo quản lạnh để giữ trọn độ tươi ngon, hương thơm tự nhiên và độ mọng nước của từng trái. Nho xanh Shine Muscat rất phù hợp để ăn ngay, làm salad trái cây hoặc dùng trong các món tráng miệng thanh mát. Đây là lựa chọn tuyệt vời cho những ai yêu thích trái cây nhập khẩu chất lượng cao, giàu dinh dưỡng và tiện lợi cho bữa ăn hàng ngày.', 169000.00, 60, 'Hàn Quốc', 'Gói', '23.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 135200.00),
(24, 'SP000024', 'Comfort Nước xả vải thời thượng 3.8kg', 'comfort-nuoc-xa-vai-thoi-thuong-38kg', 10, 'Hợp chất làm mềm vải, hương thơm đặc trưng Comfort', 'Comfort Nước xả vải thời thượng 3.8kg là sản phẩm nước xả vải cao cấp mang đến cho quần áo hương thơm tinh tế, mềm mại và lâu phai. Công thức đặc biệt giúp làm mềm sợi vải, giảm tĩnh điện, bảo vệ chất liệu sau mỗi lần giặt và giữ hương thơm dễ chịu suốt cả ngày.\r\nSản phẩm Comfort thích hợp với nhiều loại vải khác nhau, từ quần áo thường ngày đến đồ mềm, khăn trải giường và vải nhạy cảm. Với dung tích lớn 3.8kg, sản phẩm rất tiết kiệm cho những gia đình sử dụng thường xuyên. Nước xả vải Comfort mang đến sự kết hợp hoàn hảo giữa hiệu quả làm mềm và hương thơm thời thượng, giúp quần áo luôn thơm mát và mềm mại sau mỗi lần giặt.', 220000.00, 149, 'Việt Nam', 'Chai', '24.png', 'active', '2025-12-31 06:22:44', '2026-01-10 17:47:56', 176000.00),
(25, 'SP000025', 'Downy Nước xả nắng mải tươi 1.5L', 'downy-nuoc-xa-nang-mai-tuoi-15l', 10, 'Hợp chất làm mềm vải, hương thơm Downy nắng mải tươi', 'Downy Nước xả nắng mải tươi 1.5L là sản phẩm nước xả vải cao cấp mang đến cho quần áo hương thơm tươi mát, mềm mại và lâu phai sau mỗi lần giặt. Công thức đặc biệt giúp làm mềm sợi vải, giảm tĩnh điện, tăng độ bền cho sợi vải và mang lại cảm giác dễ chịu mỗi khi mặc.\r\nSản phẩm Downy thích hợp với nhiều loại vải khác nhau, từ quần áo thường ngày đến đồ mềm, khăn trải giường và vải nhạy cảm. Với dung tích 1.5L tiện lợi, sản phẩm giúp tiết kiệm và phù hợp sử dụng hàng ngày cho gia đình. Downy Nước xả nắng mải tươi kết hợp hiệu quả làm mềm và hương thơm tự nhiên, mang đến trải nghiệm chăm sóc quần áo hoàn hảo.', 122800.00, 150, 'Việt Nam', 'Chai', '25.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 98240.00),
(26, 'SP000026', 'Sunlight Nước rửa chén thiên nhiên túi 2.1kg + 2kg', 'sunlight-nuoc-rua-chen-thien-nhien-tui-21kg-2kg', 11, 'Hợp chất hoạt động bề mặt từ thiên nhiên, hương chanh', 'Sunlight Nước rửa chén thiên nhiên túi 2.1kg + 2kg là sản phẩm nước rửa chén hiệu quả cao với thành phần chiết xuất từ thiên nhiên giúp làm sạch dầu mỡ, vết bẩn và mảng bám trên chén dĩa một cách nhanh chóng mà vẫn giữ tay mềm mại. Công thức dịu nhẹ không gây khô da, an toàn cho người sử dụng và thân thiện với môi trường.\r\n\r\nSản phẩm có hương chanh tươi mát, mang đến cảm giác sạch sẽ và thơm nhẹ sau mỗi lần rửa. Túi lớn 2.1kg + 2kg rất tiết kiệm và phù hợp cho gia đình sử dụng thường xuyên hoặc trong các bữa tiệc lớn. Sunlight nước rửa chén thiên nhiên là lựa chọn lý tưởng cho những ai quan tâm đến hiệu quả làm sạch và bảo vệ da tay.', 49000.00, 149, 'Việt Nam', 'Túi', '26.png', 'active', '2025-12-31 06:22:44', '2026-01-10 21:25:46', 39200.00),
(27, 'SP000027', 'Good Care Nước rửa cao cấp thiên nhiên 3.6kg', 'good-care-nuoc-rua-cao-cap-thien-nhien-36kg', 11, 'Chiết xuất từ thiên nhiên, hương dịu nhẹ', 'Good Care Nước rửa cao cấp thiên nhiên 3.6kg là sản phẩm nước rửa chất lượng cao với thành phần chiết xuất từ thiên nhiên, giúp làm sạch dầu mỡ và vết bẩn trên chén đĩa, xoong nồi một cách hiệu quả mà vẫn dịu nhẹ với da tay. Công thức đặc biệt không gây khô da, an toàn cho người dùng và thân thiện với môi trường.\r\nSản phẩm có hương thơm nhẹ nhàng từ các chiết xuất tự nhiên, mang lại cảm giác dễ chịu sau mỗi lần rửa. Dung tích lớn 3.6kg phù hợp cho gia đình sử dụng thường xuyên và tiết kiệm. Good Care Nước rửa cao cấp thiên nhiên là sự lựa chọn tuyệt vời cho những ai quan tâm đến hiệu quả làm sạch, bảo vệ da tay và chăm sóc bát đĩa sạch bóng.', 199000.00, 150, 'Việt Nam', 'Túi', '27.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 159200.00),
(28, 'SP000028', 'GIFT Nước lau sàn Pink Sakura 3.8kg', 'gift-nuoc-lau-san-pink-sakura-38kg', 12, 'Hợp chất làm sạch sàn, hương hoa anh đào Pink Sakura', 'GIFT Nước lau sàn Pink Sakura 3.8kg là sản phẩm nước lau sàn cao cấp với công thức làm sạch hiệu quả, giúp loại bỏ bụi bẩn, vết bám và mảng bám trên bề mặt sàn mà vẫn giữ độ bóng tự nhiên cho mặt sàn. Sản phẩm mang hương hoa anh đào Pink Sakura ngọt ngào và dễ chịu, tạo không gian thơm mát sau khi lau.\r\nCông thức dịu nhẹ, không gây hại cho da tay và thân thiện với môi trường, giúp bề mặt sàn luôn sạch sẽ, khô nhanh và không để lại vệt. GIFT Nước lau sàn Pink Sakura 3.8kg phù hợp với nhiều loại sàn như gạch men, đá tự nhiên, sàn gỗ… mang đến bữa không gian sống sạch đẹp và thơm mát cho gia đình.', 922000.00, 149, 'Việt Nam', 'Chai', '28.png', 'active', '2025-12-31 06:22:44', '2026-01-04 15:41:40', 737600.00),
(29, 'SP000029', 'GIFT Nước lau kính 800ml', 'gift-nuoc-lau-kinh-800ml', 12, 'Hợp chất làm sạch kính, hương thơm tự nhiên', 'GIFT Nước lau kính 800ml là sản phẩm chuyên dụng giúp làm sạch bề mặt kính, gương và các bề mặt bóng mượt khác trong nhà một cách nhanh chóng và hiệu quả. Công thức đặc biệt giúp loại bỏ vết bẩn, dấu vân tay và bụi mịn mà không để lại vệt mờ, giữ cho kính luôn trong suốt và sáng bóng.\r\n\r\nSản phẩm có hương thơm dễ chịu, mang đến không gian sạch sẽ, thoáng mát sau khi lau. GIFT Nước lau kính 800ml phù hợp sử dụng cho cửa kính, vách kính, gương soi trong phòng tắm, cửa sổ, bàn kính và các bề mặt kính khác. Đây là lựa chọn lý tưởng cho gia đình và không gian sống sạch đẹp, gọn gàng.', 27500.00, 150, 'Việt Nam', 'Chai', '29.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 22000.00),
(30, 'SP000030', 'Lock&Lock Máy tăm nước không dây 200ml ENR156BLU', 'locklock-may-tam-nuoc-khong-day-200ml-enr156blu', 14, 'Nhựa ABS, Nhựa PC, Hệ thống bơm nước, Pin Lithium', 'Lock&Lock Máy tăm nước không dây 200ml ENR156BLU là thiết bị chăm sóc răng miệng hiện đại với 4 chế độ phun phù hợp nhu cầu làm sạch sâu và nhẹ nhàng cho răng và nướu. Bình chứa nước dung tích 200ml dễ tháo rời để vệ sinh, tia nước mảnh giúp loại bỏ thức ăn thừa và mảng bám, đầu tăm xoay 360° tiện lợi cho việc làm sạch toàn diện. Máy có chuẩn chống nước IPX7 và có thể sử dụng lâu dài đến khoảng 20 ngày chỉ với 1 lần sạc qua cổng USB. Sản phẩm phù hợp sử dụng tại nhà hoặc mang theo trong các chuyến đi.', 689000.00, 120, 'Trung Quốc', 'Cái', '30.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 551200.00),
(31, 'SP000031', 'Listerine Natural Green Tea 750ml', 'listerine-natural-green-tea-750ml', 14, 'Nước, Sorbitol, Propylene Glycol, Sodium Lauryl Sulfate, Poloxamer 407, Flavour, Eucalyptol, Thymol, Sodium Fluoride, Methyl Salicylate, Menthol, Sodium Saccharin, Sucralose, Chiết xuất lá trà xanh', 'Nước súc miệng Listerine Natural Green Tea 750ml với hương trà xanh tự nhiên giúp bảo vệ răng miệng toàn diện, ngăn ngừa vi khuẩn gây mảng bám và sâu răng, hỗ trợ hơi thở thơm mát và răng chắc khỏe hơn. Công thức không cay, không chứa cồn cùng với fluoride giúp hình thành lớp bảo vệ răng, chăm sóc miệng suốt 24 giờ. Phù hợp sử dụng hàng ngày sau khi đánh răng để làm sạch sâu và ngăn ngừa mảng bám ở những nơi bàn chải khó tiếp cận.', 185500.00, 120, 'Thái Lan', 'Chai', '31.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 148400.00),
(32, 'SP000032', 'P/S Muối hồng & Hoa cúc 230g', 'ps-muoi-hong-hoa-cuc-230g', 14, 'Water, Sorbitol, Hydrated Silica, Zinc Citrate, Sodium Lauryl Sulfate, Flavour, Cellulose Gum, Sodium Fluoride, Sodium Chloride', 'P/S Muối hồng & Hoa cúc 230g là kem đánh răng với công thức kết hợp muối hồng giúp làm trắng răng tự nhiên và tinh dầu hoa cúc giúp làm sạch sâu, mang lại hơi thở thơm mát và bảo vệ răng miệng hiệu quả. Sản phẩm bổ sung khoáng kẽm giúp kháng khuẩn, an toàn và lành tính cho cả gia đình trong việc chăm sóc răng hằng ngày.', 49000.00, 120, 'Việt Nam', 'Hộp', '32.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 39200.00),
(33, 'SP000033', 'NIVEA Dưỡng thể săn da & trắng ban đêm 400ml', 'nivea-duong-the-san-da-trang-dem-400ml', 15, 'Aqua, Ethylhexyl Methoxycinnamate, Isopropyl Palmitate, Paraffinum Liquidum, Glycerin, Cetyl Alcohol, Dimethicone, Malpighia Glabra Fruit Juice, Myrciaria Dubia Fruit Juice, Glycyrrhiza Glabra Root Extract, Persea Gratissima Oil...', 'NIVEA Dưỡng thể săn da & trắng ban đêm 400ml là sản phẩm chăm sóc da toàn thân giúp dưỡng ẩm sâu, làm săn chắc da và hỗ trợ làm sáng màu da vào ban đêm. Công thức giàu dưỡng chất giúp nuôi dưỡng làn da mềm mịn, rạng rỡ sau khi ngủ dậy. Sản phẩm dịu nhẹ, thẩm thấu nhanh mà không gây nhờn rít, phù hợp dùng hàng ngày sau tắm để duy trì độ ẩm và cải thiện độ săn chắc làn da.', 154000.00, 120, 'Thái Lan', 'Chai', '33.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 123200.00),
(34, 'SP000034', 'Aiken Xà phòng sạch khuẩn Protection 90g', 'aiken-xa-phong-sach-khuan-protection-90g', 15, 'Sodium Palmate, Sodium Palm Kernelate, Fragrance, Glycerin, Phenoxyethanol, Imidazolidinyl Urea, Tetrasodium EDTA, Titanium Dioxide, Lactobacillus Ferment, Camellia Sinensis Leaf Extract, Backhousia Citriodora Leaf Extract', 'Aiken Xà phòng sạch khuẩn Protection 90g là xà phòng cục giúp vệ sinh cơ thể sạch khuẩn với hệ kháng khuẩn thảo mộc từ cam thảo, lá trà xanh lên men và lemon myrtle, giúp loại bỏ vi khuẩn và mùi cơ thể hiệu quả. Sản phẩm phù hợp với mọi loại da, mang lại cảm giác thư giãn, dễ chịu sau khi sử dụng và bảo vệ da khỏi các tác nhân gây hại. Thích hợp dùng cho cả gia đình trong các hoạt động hàng ngày.', 12300.00, 120, 'Việt Nam', 'Hộp', '34.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 9840.00),
(35, 'SP000035', 'Romano Sữa tắm giữ ẩm Classic 650g', 'romano-sua-tam-giu-am-classic-650g', 15, 'Water, Sodium Laureth Sulfate, Cocamidopropyl Betaine, Glycerin, Sodium PCA, Fragrance (hương nước hoa nam tính), Piroctone Olamine, Sodium Chloride', 'Romano Sữa tắm giữ ẩm Classic 650g là sản phẩm sữa tắm/gội 2 trong 1 hương nước hoa nam tính dành riêng cho nam giới, giúp làm sạch da và tóc, giữ ẩm da hiệu quả và mang lại mùi thơm lịch lãm, nam tính. Công thức với Sodium PCA giúp dưỡng ẩm da, làm sạch bã nhờn và khử mùi cơ thể, mang lại cảm giác sảng khoái sau khi sử dụng. Phù hợp dùng hàng ngày cho mọi loại da và tóc.', 192400.00, 120, 'Việt Nam', 'Chai', '35.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 153920.00),
(36, 'SP000036', 'Kotex BVS Style Lưới Siêu Thấm SMC 8 miếng', 'kotex-bvs-style-luoi-sieu-tham-smc-8-mieng', 16, 'Màng lưới, màng vải không dệt, bông cellulose, hạt siêu thấm, PE, giấy không dính, Polymer kết dính, mùi hương', 'Kotex BVS Style Lưới Siêu Thấm SMC 8 miếng là băng vệ sinh dành cho phụ nữ với thiết kế siêu mỏng và mặt lưới siêu thấm giúp thấm hút nhanh, chống tràn hiệu quả và mang lại cảm giác khô thoáng suốt ngày. Sản phẩm có lõi 3D siêu thấm hút, êm mềm thoải mái và phù hợp sử dụng hàng ngày trong những ngày nhạy cảm, giúp bạn tự tin và thoải mái hoạt động cả ngày dài.', 22600.00, 120, 'Việt Nam', 'Gói', '36.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 18080.00),
(37, 'SP000037', 'Laurier BVS Fresh & Free 22cm 20 miếng', 'laurier-bvs-fresh-free-22cm-20-mieng', 16, 'Bột giấy, vải không dệt, màng đáy, hạt siêu thấm, keo dính, polymer, giấy không dính', 'Laurier BVS Fresh & Free 22cm 20 miếng là băng vệ sinh thiết kế siêu thấm, có lớp rãnh caro giúp hút chất lỏng nhanh và khóa chặt dịch, mang lại cảm giác khô thoáng, thoải mái cả ngày. Thiết kế có cánh giúp cố định chắc chắn khi vận động và phù hợp dùng trong những ngày kinh nguyệt nhiều hoặc vừa. Sản phẩm mang đến cảm giác mềm mại, ôm sát cơ thể và ngăn ngừa tràn hiệu quả, giúp bạn tự tin suốt ngày dài.', 45000.00, 120, 'Nhật Bản', 'Gói', '37.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 36000.00),
(38, 'SP000038', 'Diana BVS Super Night 29cm 4 miếng', 'diana-bvs-super-night-29cm-4-mieng', 16, 'Bông Cellulose, hạt siêu thấm, PE, giấy tráng silicone, polymer kết dính, vải không dệt, có cánh', 'Diana BVS Super Night 29cm 4 miếng là băng vệ sinh ban đêm có cánh được thiết kế đặc biệt để cung cấp khả năng thấm hút vượt trội và chống tràn hiệu quả trong suốt đêm dài. Sản phẩm có lõi thấm kép và lớp rãnh thông minh giúp khóa chặt chất lỏng và giữ bề mặt luôn khô thoáng, đồng thời bề mặt mềm mại đem lại cảm giác êm ái và thoải mái khi sử dụng. Sản phẩm phù hợp cho những ngày lượng kinh nhiều hoặc sử dụng ban đêm để yên tâm và tự tin hơn.', 19800.00, 120, 'Việt Nam', 'Gói', '38.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 15840.00),
(39, 'SP000039', 'Pacow Bắp Shank khay 250g', 'pacow-bap-shank-khay-250g', 18, 'Thịt bắp bò mát Pacow 250g, 100% thịt bò tươi, không chất bảo quản, gân phân bố đều', 'Pacow Bắp Shank khay 250g là phần thịt bắp bò cao cấp được tuyển chọn từ bò khỏe mạnh, xử lý và bảo quản theo công nghệ thịt mát hiện đại của Pacow, giúp giữ trọn độ tươi ngon và giá trị dinh dưỡng tự nhiên. Thịt bắp có thớ thịt săn chắc, gân phân bố đều tạo độ dai giòn đặc trưng, vị ngọt tự nhiên khi chế biến. Sản phẩm rất phù hợp để chế biến các món ăn truyền thống như phở bò, bún bò, lẩu, bò hầm, súp hoặc kho tiêu. Thịt khi nấu chín mềm vừa, không bị bở, nước dùng trong và đậm vị. Pacow Bắp Shank được đóng gói khay tiện lợi, đảm bảo vệ sinh an toàn thực phẩm, dễ bảo quản trong ngăn mát tủ lạnh và thích hợp sử dụng cho bữa ăn gia đình hằng ngày cũng như các dịp sum họp.', 110000.00, 30, 'Việt Nam', 'Khay', '39.png', 'active', '2025-12-31 06:22:44', '2026-01-10 17:00:57', 95120.00),
(40, 'SP000040', 'Thịt Ba Rọi có da_MH Giun Quế', 'thit-ba-roi-co-da-mh-giun-que', 18, 'Thịt ba rọi heo có da từ heo nuôi giun quế, 100% thịt tươi, không chất bảo quản', 'Thịt Ba Rọi có da MH Giun Quế là phần thịt bụng heo tươi ngon, giàu nạc xen kẽ mỡ và da, mang đến hương vị thơm, béo ngọt tự nhiên khi chế biến. Sản phẩm được lấy từ heo nuôi bằng giun quế, giúp thịt chắc, ngọt và giàu dưỡng chất hơn so với thịt ba rọi thông thường. Thịt ba rọi có da là nguyên liệu cực kỳ linh hoạt trong ẩm thực: thích hợp để nấu canh, kho tàu, chiên xào, nướng BBQ hay làm các món miền Tây như thịt kho trứng,… Mỡ xen kẽ với nạc tạo độ mềm, béo vừa phải khi nấu chín mà không bị bở, nước dùng trong và đậm vị. Sản phẩm được đóng gói khay tiện lợi, đảm bảo vệ sinh an toàn thực phẩm và dễ bảo quản trong ngăn mát tủ lạnh.', 234900.00, 30, 'Việt Nam', 'Khay', '40.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 187920.00),
(41, 'SP000041', 'KiaOra Ba chỉ bò Úc cắt lát khay 200g', 'kiaora-ba-chi-bo-uc-cat-lat-khay-200g', 18, 'Thịt ba chỉ bò Úc tươi 200g, cắt lát mỏng, không chất bảo quản', 'KiaOra Ba chỉ bò Úc cắt lát khay 200g là phần thịt bò cao cấp nhập khẩu từ Úc, với thớ thịt xen kẽ mỡ và nạc, mang lại hương vị mềm ngọt tự nhiên và giàu chất dinh dưỡng. Thịt được cắt lát sẵn tiện lợi cho đa dạng cách chế biến như nấu lẩu, xào, bò cuộn, nướng BBQ hoặc làm món fajita, phở bò tại gia. Sản phẩm giữ được độ tươi ngon nhờ bảo quản lạnh, dễ dàng sử dụng trực tiếp hoặc tẩm ướp gia vị phục vụ bữa ăn nhanh cho cả gia đình. KiaOra cam kết thịt bò đạt tiêu chuẩn an toàn vệ sinh thực phẩm, không chứa chất bảo quản, mang đến lựa chọn uy tín cho bữa ăn hàng ngày.', 75000.00, 30, 'Úc', 'Khay', '41.png', 'active', '2025-12-31 06:22:44', '2026-01-10 17:00:22', 63920.00),
(42, 'SP000042', 'AceFoods Lõi Bắp Rùa Bò Tây Ban Nha 500g', 'acefoods-loi-bap-rua-bo-tay-ban-nha-500g', 18, 'Thịt lõi bắp rùa bò Tây Ban Nha 100% tươi, không chất bảo quản', 'AceFoods Lõi Bắp Rùa Bò Tây Ban Nha 500g là phần thịt lõi đặc biệt của bắp bò nhập khẩu từ Tây Ban Nha, có đường gân tự nhiên chạy dọc thớ thịt tạo độ giòn mềm rất riêng. Phần lõi bắp rùa được cắt lát mỏng, giữ trọn vị ngọt tự nhiên và độ mềm dẻo khi chế biến. Đây là loại thịt cao cấp, thích hợp cho các món lẩu, xào, hầm, nấu phở hoặc mì bò nhúng. Sản phẩm được đóng khay 500g tiện lợi, bảo quản lạnh giúp giữ độ tươi ngon lâu dài, phù hợp cho cả bữa ăn gia đình hay dịp tụ họp cuối tuần. Với chất lượng thịt tuyệt hảo từ Tây Ban Nha và quy trình phân phối đảm bảo an toàn vệ sinh thực phẩm, AceFoods mang đến lựa chọn lý tưởng cho những ai yêu thích các món thịt bò thơm ngon.', 209900.00, 30, 'Tây Ban Nha', 'Khay', '42.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 167920.00),
(43, 'SP000043', 'Gà ta nguyên con 3F', 'ga-ta-nguyen-con-3f', 18, 'Gà ta nguyên con 3F tươi 100%, không chất bảo quản, không hormone tăng trưởng, không dư lượng kháng sinh', 'Gà ta nguyên con 3F là sản phẩm thịt gà tươi sạch đến từ thương hiệu 3F – được nuôi theo tiêu chuẩn nghiêm ngặt, từ giống gà chọn lọc đến quy trình chăn nuôi, giết mổ và đóng gói khép kín hiện đại. Sản phẩm nổi bật với thịt chắc, dai ngon, vị ngọt tự nhiên và giàu đạm, cực kỳ phù hợp để chế biến đa dạng các món ăn truyền thống như gà luộc, gà hấp bia, nướng, kho tiêu hay nấu canh chua thơm ngon cho bữa cơm gia đình. Sản phẩm được đóng gói nguyên con, giữ nguyên độ tươi ngon và đảm bảo an toàn vệ sinh thực phẩm để bạn yên tâm sử dụng hàng ngày. Hơn nữa, bạn có thể quét mã QR trên bao bì để truy xuất nguồn gốc sản phẩm và thông tin quy trình sản xuất theo chuẩn quốc tế.', 145000.00, 30, 'Việt Nam', 'Con', '43.png', 'active', '2025-12-31 06:22:44', '2026-01-11 07:33:47', 124704.00),
(44, 'SP000044', 'Cá hồi Nauy nguyên con 6–8kg', 'ca-hoi-nauy-nguyen-con-6-8kg', 19, 'Cá hồi Nauy nguyên con nhập khẩu, thịt đỏ giàu dinh dưỡng, bảo quản lạnh tươi nguyên', 'Cá hồi Nauy nguyên con 6–8kg là sản phẩm hải sản cao cấp nhập khẩu trực tiếp từ Nauy — nổi tiếng với chất lượng cá hồi tươi ngon, thịt đỏ mọng, giàu axit béo Omega-3, vitamin D, B12 và khoáng chất thiết yếu cho sức khỏe. Cá nguyên con giữ nguyên toàn vẹn thịt và da, giúp bạn linh hoạt trong chế biến: từ sashimi tươi sống, gỏi cá hồi, áp chảo, nướng BBQ, hấp bia đến kho tiêu hay nấu lẩu đều rất thơm ngon. Sản phẩm được bảo quản lạnh nghiêm ngặt ngay từ thu hoạch đến khi đến tay người tiêu dùng, đảm bảo độ tươi, màu sắc đẹp mắt và an toàn vệ sinh thực phẩm. Cá hồi Nauy nguyên con là nguồn thực phẩm giàu dinh dưỡng phù hợp cho bữa ăn gia đình, bữa tiệc cuối tuần hay món đãi khách thanh lịch.', 3499000.00, 201, 'Na Uy', 'Con', '44.png', 'active', '2025-12-31 06:22:44', '2026-01-11 18:05:11', 3421631.28),
(45, 'SP000045', 'HDC Tôm thẻ nõn tự nhiên 200g', 'hdc-tom-the-non-tu-nhien-200g', 19, 'Tôm thẻ nõn tự nhiên HDC 200g, tôm sạch tươi, giàu protein, không chất bảo quản', 'HDC Tôm thẻ nõn tự nhiên 200g là sản phẩm hải sản tươi ngon được sơ chế sạch, bóc nõn tiện lợi, phù hợp chế biến nhiều món ăn hấp dẫn như tôm chiên giòn, hấp sả, nướng bơ tỏi, xào thập cẩm hoặc nấu canh chua thanh mát. Tôm thẻ nõn giữ nguyên độ tươi tự nhiên, thịt chắc, ngọt và giàu dinh dưỡng với hàm lượng protein cao – rất tốt cho cơ bắp, xương và hệ miễn dịch. Sản phẩm không chứa chất bảo quản, được bảo quản lạnh nghiêm ngặt từ khâu chọn nguyên liệu đến đóng gói, đảm bảo an toàn vệ sinh thực phẩm. HDC Tôm thẻ nõn tự nhiên là lựa chọn lý tưởng cho mọi bữa ăn gia đình, từ bữa chính đến món nhậu cuối tuần, mang đến hương vị tươi ngon và dinh dưỡng cân đối.', 125000.00, 0, 'Việt Nam', 'Hộp', '45.png', 'out_of_stock', '2025-12-31 06:22:44', '2026-01-11 07:15:49', 106400.00),
(46, 'SP000046', 'Lenger Ngao (nghêu) trắng sạch hộp 1200g', 'lenger-ngao-ngheu-trang-sach-hop-1200g', 19, 'Ngao trắng sạch Lenger 1200g, ngao đã được làm sạch cát và tạp chất', 'Lenger Ngao (nghêu) trắng sạch hộp 1200g là sản phẩm hải sản tươi sống được tuyển chọn và làm sạch kỹ lưỡng theo công nghệ hiện đại, loại bỏ cát, đất và các tạp chất bẩn, giúp thịt ngao giữ nguyên hương vị tự nhiên, thơm ngon và an toàn cho sức khỏe. Ngao trắng có vị ngọt, thịt chắc, giàu dinh dưỡng như protein, sắt, kẽm và nhiều khoáng chất quan trọng khác, phù hợp để chế biến đa dạng các món ăn hấp dẫn như ngao hấp sả, ngao nướng mỡ hành, ngao xào bơ tỏi, lẩu ngao chua cay hay canh ngao mồng tơi. Sản phẩm được đóng gói trong hộp tiện lợi 1200g, dễ bảo quản lạnh và sử dụng cho bữa ăn gia đình hoặc dịp tụ họp cuối tuần. Lenger là thương hiệu thủy sản có quy trình sản xuất & chế biến đạt chuẩn chất lượng an toàn thực phẩm cao, mang đến lựa chọn đáng tin cậy cho thực khách yêu hải sản.', 75000.00, 0, 'Việt Nam', 'Hộp', '46.png', 'out_of_stock', '2025-12-31 06:22:44', '2026-01-10 16:35:01', 60000.00),
(47, 'SP000047', 'NTF Râu bạch tuộc đông lạnh 300g', 'ntf-rau-bach-tuoc-dong-lanh-300g', 19, 'Râu bạch tuộc đông lạnh NTF 300g, đã làm sạch, không chất bảo quản', 'NTF Râu bạch tuộc đông lạnh 300g là sản phẩm hải sản chất lượng cao được sơ chế, làm sạch tỉ mỉ và cấp đông nhanh giúp giữ nguyên độ tươi, vị ngọt tự nhiên và giá trị dinh dưỡng của thịt bạch tuộc. Sản phẩm râu bạch tuộc chắc thịt, dai ngon, rất thích hợp để chế biến các món hấp dẫn như ngao hấp sả, nướng bơ tỏi, xào chua ngọt, salad hải sản hay nấu lẩu, đem lại hương vị biển cả đậm đà cho bữa ăn gia đình. Bạch tuộc chứa nhiều protein, vitamin và khoáng chất như selenium, kẽm và omega-3 tốt cho sức khỏe, hỗ trợ hệ miễn dịch và trao đổi chất. NTF Râu bạch tuộc đông lạnh 300g được đóng gói tiện lợi, dễ bảo quản trong ngăn đá tủ lạnh và rã đông nhanh khi cần sử dụng, đảm bảo an toàn vệ sinh thực phẩm và chất lượng đồng đều cho mỗi bữa ăn.', 195000.00, 0, 'Việt Nam', 'Hộp', '47.png', 'out_of_stock', '2025-12-31 06:22:44', '2026-01-10 16:17:39', 156000.00),
(48, 'SP000048', 'Hàu sữa sống 18–20 con/kg', 'hau-sua-song-18-20-con-kg', 19, 'Hàu sữa sống nguyên con còn vỏ, tự nhiên, giàu protein và khoáng chất', 'Hàu sữa sống 18–20 con/kg là sản phẩm hải sản tươi sống được chọn lọc kỹ và bảo quản lạnh đúng tiêu chuẩn, giữ nguyên vị ngọt tự nhiên và giá trị dinh dưỡng đặc trưng của hàu. Hàu sữa là một loại nhuyễn thể hai mảnh vỏ với thịt mềm, mọng nước và lớp “sữa” ngọt béo, chứa hàm lượng protein cao cùng các vitamin và khoáng chất như kẽm, sắt, vitamin B12, omega-3 giúp bổ sung dinh dưỡng, hỗ trợ tăng cường sức khỏe và miễn dịch. Sản phẩm rất phù hợp để chế biến các món ăn hấp dẫn như hàu nướng mỡ hành, hàu nướng phô mai, hàu hấp sả gừng, cháo hàu thanh mát hoặc đơn giản là ăn kèm chanh mù tạt để giữ nguyên độ tươi ngon của thịt hàu. Với quy cách khoảng 18–20 con mỗi kg, hàu sữa sống mang đến trải nghiệm hải sản tươi ngon, giàu dinh dưỡng cho những bữa ăn gia đình hoặc dịp tụ họp cuối tuần.', 66900.00, 25, 'Việt Nam', 'Kg', '48.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 53520.00),
(49, 'SP000049', 'Danisa Bánh quy bơ hộp 908g', 'danisa-banh-quy-bo-hop-908g', 21, 'Bột mì, bơ, đường, trứng, muối, hương vani', 'Danisa Bánh quy bơ hộp 908g là dòng bánh quy bơ cao cấp nổi tiếng được sản xuất theo công thức truyền thống của Đan Mạch, mang đến hương vị thơm ngon đặc trưng khó quên. Bánh được làm từ bơ nguyên chất kết hợp cùng bột mì, trứng và các nguyên liệu chọn lọc kỹ lưỡng, tạo nên lớp bánh giòn tan, béo nhẹ, tan chậm trong miệng và có hậu vị ngọt dịu tự nhiên.\r\nSản phẩm bao gồm nhiều loại bánh quy bơ khác nhau với hình dáng đa dạng, giúp người dùng có trải nghiệm thưởng thức phong phú. Danisa bánh quy bơ thích hợp dùng kèm với trà nóng, cà phê hoặc sữa, mang lại cảm giác thư giãn và tinh tế trong những buổi sum họp gia đình hay tiếp khách.\r\nHộp bánh được thiết kế bằng kim loại sang trọng, chắc chắn, giúp bảo quản bánh tốt hơn, giữ trọn độ giòn và hương vị trong suốt thời gian sử dụng. Với trọng lượng lớn 908g, sản phẩm phù hợp cho gia đình đông người hoặc làm quà biếu cao cấp trong các dịp lễ, Tết, sinh nhật và các sự kiện quan trọng. Danisa Bánh quy bơ hộp 908g không chỉ là món ăn ngon mà còn thể hiện sự tinh tế và trân trọng dành cho người nhận.', 281000.00, 200, 'Singapore', 'Hộp', '49.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 224800.00),
(50, 'SP000050', 'Chocopie Bánh Orion hộp 396g', 'chocopie-banh-orion-hop-396g', 21, 'Bột mì, cacao, đường, bơ thực vật, sữa, lòng trắng trứng, hương vani', 'Chocopie Bánh Orion hộp 396g/435.6g là sản phẩm bánh quy kẹp marshmallow phủ socola nổi tiếng của thương hiệu Orion – một lựa chọn hấp dẫn cho người yêu hảo ngọt. Bánh có lớp vỏ mềm mịn được bao phủ bởi lớp socola thơm đậm, bên trong là nhân marshmallow ngọt ngào và bánh quy giòn nhẹ, tạo nên sự kết hợp hoàn hảo giữa các tầng hương vị.\r\nSản phẩm được đóng gói trong hộp tiện lợi, giữ trọn hương vị và độ tươi ngon, thích hợp dùng cho bữa xế, đi học, đi làm hoặc làm món ăn kèm với trà, cà phê. Chocopie Orion không chỉ là món ăn vặt yêu thích của trẻ em mà còn được nhiều người lớn ưa chuộng nhờ vị ngọt dịu và độ mềm xốp đặc trưng.\r\nVới trọng lượng hộp 396g/435.6g, sản phẩm phù hợp để chia sẻ trong gia đình, bạn bè hoặc dùng làm quà biếu trong các dịp lễ, Tết và sự kiện đặc biệt. Chocopie Bánh Orion là sự lựa chọn tuyệt vời cho những ai yêu thích đồ ngọt chất lượng, thơm ngon và tiện lợi.', 58200.00, 196, 'Việt Nam', 'Hộp', '50.png', 'active', '2025-12-31 06:22:44', '2026-01-04 16:30:10', 46560.00),
(51, 'SP000051', 'Tipo Bánh Butter Cookies Matcha hộp 75g', 'tipo-banh-butter-cookies-matcha-hop-75g', 21, 'Bột mì, bơ, đường, bột matcha, trứng, muối', 'Tipo Bánh Butter Cookies Matcha hộp 75g là sản phẩm bánh quy vị Matcha thơm ngon, hòa quyện hương trà xanh Nhật Bản với vị bơ béo ngậy đặc trưng. Bánh được làm từ những nguyên liệu chất lượng với bột mì chọn lọc, bơ nguyên chất và bột matcha tinh khiết, mang đến lớp bánh giòn nhẹ, tan mềm trong miệng, thơm lừng hương trà xanh dịu mát.\r\nSản phẩm thích hợp dùng làm món ăn nhẹ trong ngày, kèm với trà hoặc cà phê, và cũng là lựa chọn tuyệt vời để chia sẻ với bạn bè, người thân trong những buổi họp mặt. Với trọng lượng 75g gọn nhẹ, Tipo Butter Cookies Matcha rất tiện lợi để bỏ túi đem theo khi đi học, đi làm hoặc dùng làm quà tặng nhỏ trong các dịp đặc biệt.\r\nCaja Tipo Butter Cookies Matcha không chỉ đem đến vị ngon độc đáo mà còn là trải nghiệm ẩm thực tinh tế cho người yêu hảo ngọt. Sản phẩm được đóng gói chắc chắn, giữ trọn độ giòn và hương vị tự nhiên, phù hợp với mọi đối tượng yêu thích bánh quy hương trà xanh.', 21900.00, 200, 'Việt Nam', 'Hộp', '51.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 17520.00),
(52, 'SP000052', 'KitKat Trà xanh 8 thanh 136g', 'kitkat-tra-xanh-8-thanh-136g', 21, 'Bánh xốp, socola sữa, bột trà xanh Matcha', 'KitKat Trà xanh 8 thanh 136g là dòng sản phẩm bánh socola thanh giòn nổi tiếng của thương hiệu KitKat, với hương vị trà xanh Matcha Nhật Bản đặc trưng hòa quyện cùng socola mượt mà. Mỗi thanh KitKat gồm nhiều lớp bánh xốp giòn tan bên trong, được bao phủ bởi lớp socola trà xanh thơm lừng, tạo nên sự kết hợp hấp dẫn giữa vị ngọt dịu và hương trà xanh thanh mát.\r\nSản phẩm được đóng gói tiện lợi gồm 8 thanh, thích hợp dùng làm món ăn nhẹ, tráng miệng hoặc thưởng thức cùng với trà/cà phê vào bất kỳ thời điểm nào trong ngày. KitKat Trà xanh không chỉ đem lại trải nghiệm vị giác tinh tế mà còn là lựa chọn quà tặng hấp dẫn cho bạn bè, người thân trong các dịp lễ, sinh nhật hoặc các sự kiện đặc biệt.\r\nVới chất lượng đảm bảo từ nguyên liệu chọn lọc và quy trình sản xuất hiện đại, KitKat Trà xanh mang đến cảm giác giòn tan, hương trà xanh dịu nhẹ và độ ngọt cân bằng – phù hợp với người yêu thích đồ ngọt có hương vị tinh tế.', 114000.00, 200, 'Nhật Bản', 'Hộp', '52.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 91200.00),
(53, 'SP000053', 'Xylitol Kẹo gum Fresh Mint 58g', 'xylitol-keo-gum-fresh-mint-58g', 21, 'Xylitol, gum base, hương Fresh Mint, chất làm ngọt, chất nhũ hóa', 'Xylitol Kẹo gum Fresh Mint 58g là sản phẩm kẹo cao su không đường được làm từ đường thay thế Xylitol giúp giữ thơm lâu và bảo vệ răng miệng. Sản phẩm mang đến vị Fresh Mint the mát, đem lại cảm giác sảng khoái và hơi thở thơm mát suốt thời gian dài.\r\nKẹo gum Xylitol Fresh Mint chứa Xylitol giúp giảm nguy cơ sâu răng, hỗ trợ vệ sinh răng miệng hiệu quả hơn so với kẹo gum truyền thống. Với thiết kế gọn nhẹ và tiện lợi, sản phẩm thích hợp mang theo khi đi học, đi làm hay sau bữa ăn để làm sạch răng miệng và giúp hơi thở luôn thơm mát.\r\nXylitol Kẹo gum Fresh Mint 58g là lựa chọn tuyệt vời cho những ai yêu thích kẹo gum hương bạc hà tươi mát, giúp thư giãn tinh thần và chăm sóc răng miệng hàng ngày.', 28800.00, 200, 'Việt Nam', 'Hộp', '53.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 23040.00),
(54, 'SP000054', 'Koreno Mì vị tôm Jumbo 1kg', 'koreno-mi-vi-tom-jumbo-1kg', 22, 'Mì sợi, hương vị tôm, gia vị, dầu mè, muối', 'Koreno Mì vị tôm Jumbo 1kg là sản phẩm mì ăn liền cao cấp từ thương hiệu Koreno, nổi bật với sợi mì to bản, dai ngon và hương vị tôm đậm đà, hấp dẫn. Mì được chế biến từ nguyên liệu chọn lọc, kết hợp cùng gói gia vị tôm thơm lừng, mang đến hương vị đậm đà và cân bằng dinh dưỡng, phù hợp cho bữa ăn nhanh, tiện lợi.\r\nSản phẩm Koreno Jumbo 1kg gồm nhiều gói mì nhỏ, thích hợp sử dụng trong gia đình, văn phòng hoặc khi cần một bữa ăn nhanh mà vẫn đảm bảo ngon miệng và đầy đủ hương vị. Mì sợi to giúp giữ độ dai tốt sau khi nấu, hòa quyện cùng nước dùng vị tôm đậm đà sẽ tạo nên trải nghiệm ẩm thực hấp dẫn ngay tại nhà.\r\nKoreno Mì vị tôm Jumbo 1kg không chỉ dễ chế biến mà còn là lựa chọn tuyệt vời cho những ai yêu thích mì ăn liền hương vị đặc trưng, tiện lợi và đậm đà, phù hợp dùng hằng ngày hoặc trong những chuyến dã ngoại, du lịch.', 79900.00, 300, 'Hàn Quốc', 'Gói', '54.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 63920.00),
(55, 'SP000055', 'Hảo Hảo Mì tôm chua cay 75g', 'hao-hao-mi-tom-chua-cay-75g', 22, 'Sợi mì, gia vị chua cay, dầu ớt, muối, chất điều vị', 'Hảo Hảo Mì tôm chua cay 75g là sản phẩm mì ăn liền quen thuộc, được yêu thích bởi vị chua cay đặc trưng hòa quyện cùng sợi mì dai mềm. Mì được sản xuất bởi thương hiệu Hảo Hảo – nổi tiếng với chất lượng ổn định và hương vị hấp dẫn, phù hợp với khẩu vị của nhiều lứa tuổi.\r\nSản phẩm dễ chế biến trong vài phút với nước sôi nóng, mang đến bữa ăn nhanh gọn, đậm đà hương vị chua cay kích thích vị giác. Gói gia vị được cân chỉnh kỹ lưỡng giúp tạo ra nước dùng thơm ngon, cay nồng vừa phải, phù hợp dùng trong những ngày lạnh hoặc khi cần một bữa ăn ấm áp, tràn đầy năng lượng.\r\nHảo Hảo Mì tôm chua cay 75g phù hợp dùng riêng lẻ, kết hợp với rau củ, trứng hoặc thịt để tạo bữa ăn đầy đủ dinh dưỡng hơn. Đây là lựa chọn tiện lợi cho sinh viên, dân văn phòng hoặc khi đi du lịch, dã ngoại. Sản phẩm thể hiện phong cách ẩm thực nhanh gọn nhưng vẫn giàu hương vị, là món mì mì ăn liền không thể thiếu trong tủ thực phẩm của mỗi gia đình.', 4200.00, 300, 'Việt Nam', 'Gói', '55.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 3360.00);
INSERT INTO `san_pham` (`ID_sp`, `Ma_hien_thi`, `Ten`, `Slug`, `ID_danh_muc`, `Thanh_phan`, `Mo_ta_sp`, `Gia_tien`, `So_luong_ton`, `Xuat_xu`, `Don_vi_tinh`, `Hinh_anh`, `Trang_thai`, `Ngay_tao`, `Ngay_cap_nhat`, `Gia_nhap`) VALUES
(56, 'SP000056', 'Modern Mì ly vị lẩu Thái tôm 67g', 'modern-mi-ly-vi-lau-thai-tom-67g', 22, 'Sợi mì, gói gia vị lẩu Thái tôm, dầu ớt, muối, chất điều vị', 'Modern Mì ly vị lẩu Thái tôm 67g là sản phẩm mì ăn liền dạng ly tiện lợi với hương vị lẩu Thái tôm đặc trưng, mang đến sự hòa quyện giữa vị chua cay đậm đà và hương tôm thơm ngon. Sản phẩm được đóng gói trong ly chắc chắn, dễ mở và dễ chế biến – chỉ cần thêm nước sôi là có thể thưởng thức ngay một bữa ăn nóng hổi, đầy hương vị.\r\nHương vị lẩu Thái với sự kết hợp của chanh, ớt, sả và nguyên liệu tôm tạo nên trải nghiệm ẩm thực hấp dẫn, thích hợp cho những ngày cần năng lượng nhanh hoặc khi muốn đổi vị cho bữa ăn. Modern Mì ly vị lẩu Thái tôm không chỉ ngon mà còn rất tiện lợi, phù hợp cho sinh viên, dân văn phòng, người bận rộn hoặc khi đi du lịch, dã ngoại.\r\nSản phẩm chứa các thành phần được lựa chọn kỹ càng, giúp tạo ra nước dùng đậm đà, cay nhẹ vừa miệng và hài hòa cùng sợi mì mềm mại. Modern Mì ly vị lẩu Thái tôm 67g là lựa chọn tuyệt vời cho những ai yêu thích món mì ăn liền thơm ngon, nhanh gọn và giàu hương vị.', 8600.00, 300, 'Việt Nam', 'Ly', '56.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 6880.00),
(57, 'SP000057', 'Omachi Mì đậm đà vị sườn ngũ quả 80g', 'omachi-mi-dam-da-vi-suon-ngu-qua-80g', 22, 'Sợi mì, gói gia vị vị sườn ngũ quả, dầu ớt, muối, chất điều vị', 'Omachi Mì đậm đà vị sườn ngũ quả 80g là sản phẩm mì ăn liền thơm ngon, được chế biến theo công thức đặc trưng của thương hiệu Omachi, mang đến trải nghiệm ẩm thực đậm đà và hấp dẫn. Sản phẩm gây ấn tượng với hương vị sườn ngũ quả hòa quyện cùng nước dùng đậm đà, cân bằng giữa vị mặn, cay nhẹ và hương thơm đặc trưng từ các gia vị tự nhiên.\r\nMì Omachi được sản xuất từ những thành phần chất lượng, giúp giữ nguyên hương vị vốn có và tạo độ mềm mại cho sợi mì sau khi nấu. Gói mì 80g tiện lợi, dễ chế biến – chỉ cần thêm nước sôi trong vài phút là có thể tận hưởng bữa ăn nóng hổi, đầy hương vị, phù hợp cho những ngày bận rộn hoặc khi cần bữa ăn nhanh.\r\nOmachi Mì đậm đà vị sườn ngũ quả 80g thích hợp dùng riêng hoặc kết hợp với trứng, rau xanh và thịt để tăng thêm giá trị dinh dưỡng. Đây là lựa chọn không thể thiếu trong tủ thực phẩm của sinh viên, dân văn phòng và mọi gia đình yêu thích mì ăn liền với hương vị mới lạ, hấp dẫn và tiện lợi.', 8500.00, 300, 'Việt Nam', 'Gói', '57.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 6800.00),
(58, 'SP000058', 'Indomie Mì xào khô vị bò cay 91g', 'indomie-mi-xao-kho-vi-bo-cay-91g', 22, 'Sợi mì, gói gia vị vị bò cay, dầu ớt, muối, chất điều vị', 'Indomie Mì xào khô vị bò cay 91g là sản phẩm mì xào nổi tiếng từ thương hiệu Indomie, mang đến hương vị đặc trưng với sợi mì dai, thấm đều gia vị đậm đà vị bò cay nồng. Mì xào khô khác với mì nước ở chỗ nước dùng đã được trộn cùng gói gia vị dầu ớt và gia vị bò cay, tạo ra món mì xào thơm ngon ngay sau khi chế biến mà không cần nước dùng nhiều.\r\nSản phẩm sử dụng những thành phần được lựa chọn chất lượng, với gói gia vị cân đối giúp mang lại vị cay quyện vị bò thơm lừng, thích hợp cho những ai yêu thích vị cay nồng và hương vị đậm đà. Indomie Mì xào khô vị bò cay rất tiện lợi, chỉ cần thêm nước sôi, trộn đều và thưởng thức ngay trong vài phút – phù hợp với sinh viên, dân văn phòng hoặc khi cần bữa ăn nhanh gọn.\r\nMì Indomie xào khô vị bò cay có thể kết hợp thêm trứng, rau củ và thịt để tăng giá trị dinh dưỡng, tạo nên bữa ăn cân đối hơn. Đây là lựa chọn lý tưởng cho những ai tìm kiếm món mì ăn liền thơm ngon, có vị cay hấp dẫn và tiện lợi để dùng trong nhiều hoàn cảnh khác nhau.', 6000.00, 300, 'Indonesia', 'Gói', '58.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 4800.00),
(59, 'SP000059', 'Bao Minh Gạo ST25 Lúa Ruộng Ruội 3kg', 'bao-minh-gao-st25-lua-ruong-ruoi-3kg', 23, 'Gạo ST25 nguyên chất', 'Bao Minh Gạo ST25 Lúa Ruộng Ruội 3kg là sản phẩm gạo đặc sản cao cấp được sản xuất từ giống lúa ST25 – vốn nổi tiếng với hạt dài, cơm dẻo, thơm mềm và giữ hương tự nhiên ngay cả khi nguội. Gạo ST25 được trồng trên những cánh đồng lúa giàu phù sa, chăm sóc theo quy trình kiểm soát chất lượng nghiêm ngặt để đảm bảo tiêu chuẩn ngon nhất cho người tiêu dùng.\r\nSản phẩm gạo ST25 mang đến trải nghiệm ẩm thực tuyệt vời với hạt cơm trong, mềm dẻo và mùi thơm nhẹ đặc trưng, phù hợp cho bữa ăn gia đình mỗi ngày. Gạo rất dễ nấu, chín đều và giữ được hương vị tự nhiên, giúp món cơm thêm phần hấp dẫn mà vẫn đảm bảo dinh dưỡng.\r\nVới trọng lượng 3kg, Bao Minh Gạo ST25 Lúa Ruộng Ruội 3kg là lựa chọn lý tưởng cho gia đình nhỏ hoặc dùng làm quà biếu sang trọng trong các dịp lễ, tết hay thăm hỏi người thân. Đây là sản phẩm gạo cao cấp, mang đến bữa cơm thơm ngon, chất lượng và trọn vị cho mọi bữa ăn.', 147600.00, 150, 'Việt Nam', 'Túi', '59.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 118080.00),
(60, 'SP000060', 'Xuân An Yến mạch mật ong 384g', 'xuan-an-yen-mach-mat-ong-384g', 23, 'Yến mạch cán dẹt, mật ong tự nhiên, chất ổn định, vitamin', 'Xuân An Yến mạch mật ong 384g là sản phẩm dinh dưỡng tiện lợi được kết hợp tinh tế giữa yến mạch cán dẹt giàu dinh dưỡng và mật ong nguyên chất ngọt dịu. Yến mạch chứa hàm lượng cao chất xơ beta-glucan, giúp hỗ trợ hệ tiêu hóa, cân bằng đường huyết và góp phần giảm cholesterol xấu, trong khi mật ong cung cấp năng lượng tự nhiên và các chất chống oxy hóa tốt cho sức khỏe.\r\nSản phẩm phù hợp dùng cho bữa sáng nhanh gọn, bữa phụ dinh dưỡng hoặc đồ ăn nhẹ trước/sau khi tập luyện. Bạn có thể kết hợp yến mạch mật ong với sữa tươi, trái cây tươi hoặc sữa chua để tạo thành bữa ăn phong phú, tăng cường năng lượng và bổ sung vitamin thiết yếu. Xuân An Yến mạch mật ong 384g được đóng gói tiện lợi, dễ bảo quản và mang theo khi đi làm, đi học hay du lịch.\r\nVới thành phần nguyên liệu tự nhiên, sản phẩm không chỉ mang đến hương vị thơm ngon mà còn là lựa chọn lý tưởng cho những ai quan tâm đến sức khỏe, giảm cân lành mạnh, hỗ trợ tim mạch và duy trì lối sống năng động. Đây là lựa chọn tuyệt vời cho cả gia đình, từ trẻ em đến người lớn, mong muốn một bữa ăn nhanh gọn nhưng vẫn giàu dưỡng chất.', 85000.00, 150, 'Việt Nam', 'Hộp', '60.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 68000.00),
(61, 'SP000061', 'An Vinh Khô cá sặc 200g', 'an-vinh-kho-ca-sac-200g', 23, 'Khô cá sặc, muối, gia vị tự nhiên', 'An Vinh Khô cá sặc 200g là sản phẩm khô cá thơm ngon đặc sản được chế biến từ cá sặc tươi, tuyển chọn kỹ lưỡng, mang đến hương vị đậm đà, mềm ngon và giàu dinh dưỡng. Cá sặc sau khi làm sạch được ướp gia vị tự nhiên, phơi khô theo tiêu chuẩn truyền thống để giữ trọn vẹn độ ngọt của thịt cá và mùi thơm hấp dẫn.\r\nSản phẩm khô cá sặc có thể chế biến theo nhiều cách khác nhau như chiên giòn, nướng than hồng, rim mặn ngọt hoặc kết hợp với rau sống, bún, cơm trắng tạo thành bữa ăn thơm ngon bổ dưỡng. Khô cá sặc giàu protein, omega-3 và các khoáng chất thiết yếu hỗ trợ sự phát triển cơ bắp và tăng cường sức khỏe tổng thể.\r\nAn Vinh Khô cá sặc 200g được đóng gói gọn nhẹ, tiện lợi, dễ bảo quản và thích hợp mang theo khi đi dã ngoại, du lịch hay làm quà biếu cho người thân và bạn bè trong các dịp lễ tết. Đây là lựa chọn tuyệt vời cho những ai yêu thích hương vị truyền thống, đậm đà và chất lượng cao trong từng miếng khô cá.', 91000.00, 150, 'Việt Nam', 'Gói', '61.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 72800.00),
(62, 'SP000062', 'Lốc 3 Miwon lá kim tảo biển cao cấp 5g', 'loc-3-miwon-la-kim-tao-bien-cao-cap-5g', 23, 'Lá kim tảo biển 100%', 'Miwon Lá kim tảo biển cấp 1 5g × 3 là sản phẩm tảo biển tự nhiên được tuyển chọn kỹ lưỡng, mang đến hương vị đậm đà và trải nghiệm dinh dưỡng phong phú. Kim tảo biển là loại thực phẩm giàu vitamin, khoáng chất, chất xơ và các dưỡng chất thiết yếu giúp tăng cường sức khỏe tổng thể, hỗ trợ tiêu hóa và cung cấp năng lượng cho cơ thể.\r\nSản phẩm được chế biến và đóng gói theo tiêu chuẩn vệ sinh an toàn thực phẩm, giữ trọn độ tươi ngon và các giá trị dinh dưỡng tự nhiên của tảo biển. Với trọng lượng nhỏ gọn 5g × 3 túi, Miwon Lá kim tảo biển rất tiện lợi để bảo quản, mang theo khi đi làm, đi học hoặc dùng trong các bữa ăn dưỡng chất.\r\nKim tảo biển có thể dùng kèm với cơm, salad, súp, sushi hoặc làm gia vị bổ sung cho các món ăn để tăng hương vị và độ dinh dưỡng. Đây là lựa chọn tuyệt vời cho những ai yêu thích thực phẩm từ biển, giàu dưỡng chất và tốt cho sức khỏe tim mạch, hệ miễn dịch và chức năng đường ruột.', 41300.00, 148, 'Việt Nam', 'Hộp', '62.png', 'active', '2025-12-31 06:22:44', '2026-01-10 21:25:46', 33040.00),
(63, 'SP000063', 'Meizan Bột mì đa dụng 500g MB', 'meizan-bot-my-da-dung-500g-mb', 23, 'Bột mì đa dụng 100%', 'Meizan Bột mì đa dụng 500g MB là sản phẩm bột mì chất lượng cao, được sản xuất từ nguyên liệu lúa mì chọn lọc kỹ lưỡng và chế biến theo công nghệ hiện đại để đạt được độ mịn, tinh khiết và tơi xốp đồng nhất. Bột mì đa dụng Meizan phù hợp cho nhiều mục đích nấu nướng và làm bánh khác nhau, từ bánh ngọt, bánh mì, bánh bông lan đến làm vỏ bánh, bột chiên giòn hay các món chiên xào khác.\r\nSản phẩm giúp ciết tạo độ xốp, mềm mượt và giữ cấu trúc tốt khi nướng, đồng thời dễ hòa tan và trộn đều với các nguyên liệu khác, mang đến kết quả thơm ngon, hấp dẫn. Bột mì Meizan đa dụng 500g là lựa chọn tối ưu cho cả người nội trợ gia đình và những ai yêu thích làm bánh tại nhà.\r\nVới trọng lượng 500g tiện lợi, sản phẩm thích hợp sử dụng hàng ngày trong gian bếp, đồng thời dễ bảo quản và bảo đảm chất lượng lâu dài. Meizan Bột mì đa dụng 500g MB không chỉ giúp bạn chế biến được những món ăn ngon mà còn mang lại trải nghiệm ẩm thực tinh tế và đa dạng.', 16100.00, 146, 'Việt Nam', 'Gói', '63.png', 'active', '2025-12-31 06:22:44', '2026-01-10 21:25:46', 12880.00),
(64, 'SP000064', 'SGM Trà sữa Thái 300ml', 'sgm-tra-sua-thai-300ml', 24, 'Sữa, đường, trà, hương Thái, chất ổn định, vitamin', 'SGM Trà sữa Thái 300ml là sản phẩm đồ uống tiện lợi với sự kết hợp tinh tế giữa vị trà đặc trưng và hương sữa béo mịn, mang lại cảm giác thơm ngon và dễ uống ngay từ ngụm đầu tiên. Thành phần được pha chế đặc biệt với sữa tươi và hương liệu tự nhiên, tạo nên vị trà sữa Thái đậm đà, cân bằng giữa ngọt và thanh, phù hợp với mọi đối tượng từ trẻ em đến người lớn.\r\nSản phẩm được đóng trong chai 300ml gọn nhẹ, dễ mang theo khi đi học, đi làm hoặc thư giãn dã ngoại cùng bạn bè và người thân. Trà sữa SGM thích hợp dùng trực tiếp, không cần pha chế thêm, mang đến trải nghiệm thoải mái, nhanh chóng cho những ai yêu thích hương vị trà sữa Thái truyền thống.\r\nVới giá trị dinh dưỡng cân đối cùng hương vị thơm ngon, SGM Trà sữa Thái 300ml không chỉ là lựa chọn giải khát tuyệt vời cho mùa hè mà còn là thức uống bổ sung năng lượng cho ngày dài năng động. Sản phẩm phù hợp dùng hàng ngày và có thể kết hợp với các món ăn nhẹ khác để tăng vị giác.', 16800.00, 40, 'Việt Nam', 'Chai', '64.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 13440.00),
(65, 'SP000065', 'Đức Việt Xúc xích Roma gói 500g', 'duc-viet-xuc-xich-roma-goi-500g', 24, 'Thịt heo, thịt bò, muối, gia vị, chất ổn định, phụ gia thực phẩm tiêu chuẩn', 'Đức Việt Xúc xích Roma gói 500g là sản phẩm xúc xích cao cấp được chế biến từ hỗn hợp thịt heo và thịt bò tươi, pha trộn cùng gia vị đặc trưng và các thành phần phụ gia đảm bảo an toàn thực phẩm, mang đến hương vị thơm ngon, đậm đà và hấp dẫn ngay từ miếng đầu tiên.\r\nXúc xích Roma có kết cấu dai giòn vừa phải, giữ trọn vị thịt tự nhiên, thích hợp dùng cho bữa sáng, bữa phụ hoặc kết hợp trong các món ăn như khoai tây chiên xúc xích, mì xào xúc xích, bánh mì kẹp xúc xích hoặc dùng trực tiếp với nước sốt yêu thích. Sản phẩm được đóng gói 500g tiện lợi, phù hợp cho gia đình hoặc những buổi tụ họp bạn bè, dã ngoại.\r\nĐức Việt Xúc xích Roma là lựa chọn lý tưởng cho những ai yêu thích món xúc xích truyền thống thơm ngon, đầy đủ năng lượng và dễ chế biến. Với tiêu chuẩn sản xuất nghiêm ngặt, sản phẩm đảm bảo chất lượng, phù hợp với khẩu vị người tiêu dùng Việt Nam và các yêu cầu khắt khe về an toàn thực phẩm.', 60400.00, 40, 'Việt Nam', 'Gói', '65.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 48320.00),
(66, 'SP000066', 'Phú Mỹ Bakery Bánh bao nhân xíu mại 300g', 'phu-my-bakery-banh-bao-nhan-xiu-mai-300g', 24, 'Bột mì, nhân xíu mại, đường, muối, men nở, gia vị', 'Phú Mỹ Bakery Bánh bao nhân xíu mại 300g là sản phẩm bánh bao chế biến sẵn thơm ngon, được làm từ bột mì tươi và nhân xíu mại đậm đà, mang đến hương vị hấp dẫn ngay từ miếng đầu tiên. Nhân xíu mại mềm mại, thấm vị cùng lớp vỏ bánh mềm xốp giúp bạn tận hưởng trọn vẹn cảm giác ngon miệng và đầy đủ dinh dưỡng.\r\nSản phẩm được chế biến theo tiêu chuẩn an toàn vệ sinh thực phẩm, phù hợp dùng cho bữa sáng, bữa phụ hoặc các bữa ăn nhanh tiện lợi. Bánh bao nhân xíu mại dễ dàng kết hợp với nước sốt, rau sống hoặc dùng kèm trà nóng để tăng trải nghiệm ẩm thực.\r\nVới trọng lượng 300g, Phú Mỹ Bakery Bánh bao nhân xíu mại 300g là lựa chọn lý tưởng cho những ai yêu thích món bánh truyền thống pha chút phong cách hiện đại, thích hợp dùng trong gia đình, văn phòng hoặc đem theo khi di chuyển. Đây là món ăn tiện lợi, thơm ngon và giàu năng lượng – đáp ứng nhu cầu dinh dưỡng cho mọi lứa tuổi.', 29950.00, 40, 'Việt Nam', 'Gói', '66.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 23960.00),
(67, 'SP000067', 'Bibigo Kim chi cải thảo cắt lát 100g', 'bibigo-kim-chi-cai-thao-cat-lat-100g', 24, 'Cải thảo, ớt, tỏi, gừng, muối, gia vị lên men', 'Bibigo Kim chi cải thảo cắt lát 100g là sản phẩm kim chi Hàn Quốc nổi tiếng đến từ thương hiệu Bibigo, mang đến vị chua cay đặc trưng, vị giòn tươi của cải thảo cùng hương thơm tự nhiên hấp dẫn. Kim chi được làm từ cải thảo tươi nguyên chất, lên men tự nhiên theo công thức truyền thống, giữ nguyên dưỡng chất và hương vị đặc sắc của món ăn Hàn Quốc.\r\nSản phẩm thích hợp dùng trực tiếp như món khai vị, ăn kèm với cơm nóng, mì cay, cơm trộn hoặc trong các món nướng, lẩu giúp tăng hương vị đậm đà và cân bằng dinh dưỡng. Với kích thước gọn nhẹ 100g, kim chi Bibigo tiện lợi để sử dụng mỗi ngày, mang đến bữa ăn đa dạng và ngon miệng cho gia đình.\r\nBibigo Kim chi không chỉ hấp dẫn bởi vị chua cay hài hòa mà còn là lựa chọn thực phẩm lên men tốt cho hệ tiêu hóa nhờ các lợi khuẩn tự nhiên. Sản phẩm phù hợp với mọi đối tượng yêu thích ẩm thực Hàn Quốc, đặc biệt là những ai muốn trải nghiệm món kim chi truyền thống ngay tại nhà.', 14200.00, 40, 'Hàn Quốc', 'Gói', '67.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 11360.00),
(68, 'SP000068', 'Sa Giang Bánh phồng tôm mini 15 – 100g', 'sa-giang-banh-phong-tom-mini-15-100g', 24, 'Bột mì, bột tôm, dầu thực vật, muối, gia vị', 'Sa Giang Bánh phồng tôm mini 15 – 100g là sản phẩm bánh phồng tôm truyền thống giòn tan, thơm ngon đặc trưng đến từ thương hiệu Sa Giang – một trong những tên tuổi nổi tiếng với các sản phẩm bánh snack chất lượng. Bánh được làm từ bột mì chọn lọc kết hợp bột tôm tươi cùng các gia vị, mang đến hương vị biển đậm đà và độ giòn nhẹ khó cưỡng.\r\nSản phẩm có kích thước mini, tiện lợi, dễ sử dụng cho mọi lứa tuổi, phù hợp làm món ăn vặt, khai vị trong các buổi tiệc, tụ họp bạn bè hoặc dùng kèm với nước chấm yêu thích. Bánh phồng tôm Sa Giang giữ được độ giòn lâu, hương vị thơm đặc trưng và mang lại cảm giác ngon miệng ngay từ miếng đầu tiên.\r\nVới trọng lượng 100g gọn nhẹ, Sa Giang Bánh phồng tôm mini 15 – 100g là lựa chọn tuyệt vời cho những ai yêu thích đồ ăn nhẹ giàu hương vị, dễ ăn và tiện lợi. Đây cũng là món quà nhỏ ý nghĩa để chia sẻ cùng gia đình hoặc người thân trong những dịp nhỏ thường ngày.', 12200.00, 40, 'Việt Nam', 'Gói', '68.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 9760.00),
(69, 'SP000069', 'Đồi Dừa Vàng Tôm Thịt 3×140g (Khay 252g)', 'doi-dua-vang-tom-thit-3x140g-khay-252g', 25, 'Tôm, thịt, dừa, gia vị, tinh bột, chất ổn định', 'Đồi Dừa Vàng Tôm Thịt 3×140g (Khay 252g) là sản phẩm chế biến sẵn thơm ngon, được làm từ nguyên liệu tôm và thịt chất lượng cao kết hợp cùng dừa tươi theo công thức truyền thống mang hương vị đặc trưng Việt Nam. Sản phẩm được sản xuất bởi thương hiệu Đồi Dừa Vàng – nổi bật với các loại thực phẩm chế biến tiện lợi, giàu dinh dưỡng và phù hợp cho bữa ăn nhanh hoặc làm món chính bổ sung năng lượng.\r\nTừng miếng tôm thịt hòa quyện cùng dừa tươi tạo nên vị ngọt thanh, đậm đà hấp dẫn, đồng thời giữ được độ mềm tự nhiên và mùi thơm dễ chịu. Khay 252g gồm 3 gói nhỏ 140g mỗi gói, rất tiện lợi để chia sử dụng trong gia đình, mang theo đi làm hoặc dùng khi đi dã ngoại. Sản phẩm phù hợp dùng trực tiếp sau khi hâm nóng hoặc kết hợp với cơm trắng, bánh mì, salad để tạo thành bữa ăn đầy đủ dinh dưỡng và ngon miệng.\r\nĐồi Dừa Vàng Tôm Thịt mang đến sự tiện lợi và hương vị hài hòa giữa hải sản và thịt, phù hợp cả người lớn và trẻ em. Đây là lựa chọn lý tưởng cho những ai yêu thích món ăn chế biến sẵn nhưng vẫn đảm bảo hương vị tự nhiên, giàu protein và cung cấp năng lượng cho cả ngày.', 167000.00, 50, 'Việt Nam', 'Khay', '69.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 133600.00),
(70, 'SP000070', 'Orifood Ba chỉ bò Mỹ 500g sốt sukiyaki', 'orifood-ba-chi-bo-my-500g-sot-sukiyaki', 25, 'Ba chỉ bò Mỹ, sốt sukiyaki, muối, tiêu, phụ gia thực phẩm', 'Orifood Ba chỉ bò Mỹ 500g sốt sukiyaki là sản phẩm thịt bò nhập khẩu chất lượng cao được chế biến sẵn với sốt sukiyaki đậm đà, hòa quyện vị ngọt tự nhiên của thịt bò Mỹ cùng hương vị Nhật Bản tinh tế. Ba chỉ bò Mỹ có lớp mỡ và nạc xen kẽ giúp thịt mềm, thơm và ngọt, dễ dàng hấp thu gia vị sốt sukiyaki tạo nên hương vị hấp dẫn khó quên.\r\nSản phẩm thích hợp để nấu lẩu, xào nhanh hoặc nướng cùng rau củ, mì và cơm nóng, mang đến bữa ăn đậm đà, giàu dinh dưỡng và tinh tế. Sốt sukiyaki thấm sâu vào từng thớ thịt, tạo nước sốt sánh mịn, hòa quyện cùng vị béo nhẹ của thịt mà không bị ngán. Đây là lựa chọn lý tưởng cho những buổi tụ họp gia đình, bạn bè, dịp cuối tuần hoặc khi bạn muốn đổi vị cho bữa ăn gia đình.\r\nOrifood Ba chỉ bò Mỹ 500g sốt sukiyaki được đóng gói tiện lợi, dễ dàng bảo quản trong ngăn mát tủ lạnh và nhanh chóng chế biến theo nhiều cách khác nhau. Sản phẩm không chỉ mang lại hương vị ngon miệng mà còn cung cấp nguồn protein chất lượng, vitamin và khoáng chất thiết yếu cho cơ thể.', 195300.00, 50, 'Mỹ', 'Gói', '70.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 156240.00),
(71, 'SP000071', 'Meat Deli Giò lụa Hảo Hạng 300g', 'meat-deli-gio-lua-hao-hang-300g', 25, 'Thịt heo xay, mỡ heo, gia vị, muối, chất ổn định', 'Meat Deli Giò lụa Hảo Hạng 300 gr là sản phẩm giò lụa cao cấp được chế biến từ thịt heo tươi chất lượng, xay nhuyễn và phối trộn cùng gia vị đặc trưng, mang đến vị giòn dai hoàn hảo, hương thơm tinh tế và độ ngọt nhẹ tự nhiên. Sản phẩm thuộc dòng thực phẩm chế biến sẵn chất lượng cao của Meat Deli – thương hiệu uy tín với tiêu chuẩn an toàn vệ sinh thực phẩm nghiêm ngặt.\r\nGiò lụa Hảo Hạng có thể dùng trực tiếp trong các bữa ăn gia đình hoặc là nguyên liệu cho các món ăn hấp dẫn như  bánh mì giò lụa, bún, phở, ăn kèm cơm trắng và rau sống, thậm chí xào, chiên tùy theo sở thích. Với lớp vỏ mịn màng, độ đàn hồi tốt và hương vị thơm ngon, sản phẩm đem lại trải nghiệm ẩm thực trọn vẹn cho mọi thành viên trong gia đình.\r\nĐóng gói 300 gr gọn nhẹ, Meat Deli Giò lụa Hảo Hạng rất tiện lợi cho việc bảo quản trong tủ lạnh và dễ dàng chế biến mọi lúc. Đây là lựa chọn lý tưởng cho bữa sáng nhanh gọn, bữa phụ giàu protein hoặc món ăn kèm trong bữa tối, đảm bảo cung cấp đầy đủ dinh dưỡng và thơm ngon.', 55000.00, 50, 'Việt Nam', 'Gói', '71.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 44000.00),
(72, 'SP000072', 'LC Food Viên thả lẩu phô mai trứng muối 500g', 'lc-food-vien-tha-lau-pho-mai-trung-muoi-500g', 25, 'Thịt/lõi cá, phô mai, trứng muối, gia vị, bột năng, chất ổn định', 'LC Food Viên thả lẩu phô mai trứng muối 500g là sản phẩm thực phẩm chế biến cao cấp, được làm từ nguyên liệu chất lượng kết hợp giữa thịt (hoặc lõi cá), phô mai béo ngậy và trứng muối thơm đặc trưng. Sản phẩm được tạo hình tròn đều và đóng gói tiện lợi, rất phù hợp để sử dụng làm viên thả lẩu hoặc chế biến các món hấp, chiên, nướng trong bữa ăn gia đình.\r\nViên thả lẩu phô mai trứng muối có phần nhân bên trong mềm mịn với vị phô mai kéo sợi kết hợp cùng vị béo đặc trưng của trứng muối, mang đến trải nghiệm ẩm thực hấp dẫn từ từng miếng. Sản phẩm giàu protein và hương vị đậm đà, thích hợp dùng trong nhiều món ăn như lẩu, nướng, chiên giòn kèm nước sốt yêu thích hoặc ăn kèm rau củ, mì, bún.\r\nLC Food Viên thả lẩu phô mai trứng muối 500g rất tiện lợi cho người nội trợ hiện đại — chỉ cần chế biến nhanh trong vài phút để có bữa ăn ngon miệng, bổ dưỡng cho cả gia đình. Đây là lựa chọn hoàn hảo cho bữa ăn cuối tuần, tiệc nhỏ tại nhà hoặc làm món chính trong các dịp tụ họp bạn bè.', 84200.00, 50, 'Việt Nam', 'Gói', '72.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 67360.00),
(73, 'SP000073', 'Há cảo Thực Phẩm Cầu Tre gói 500g', 'ha-cao-thuc-pham-cau-tre-goi-500g', 25, 'Há cảo (cá basa), muối, gia vị, chất bảo quản thực phẩm', 'Há cảo Thực Phẩm Cầu Tre gói 500g là sản phẩm thực phẩm chế biến sẵn thơm ngon, được làm từ phần thịt cá basa (hà cao) tươi chất lượng cao, phối trộn cùng gia vị truyền thống và bảo quản an toàn theo tiêu chuẩn vệ sinh thực phẩm. Sản phẩm mang đến hương vị tự nhiên, độ ngọt thịt hài hòa và phù hợp khẩu vị người Việt.\r\nSản phẩm thích hợp dùng trong nhiều món ăn phong phú như chiên giòn, hấp, nấu canh hoặc kết hợp với rau củ và gia vị để tạo ra những bữa ăn giàu protein, bổ dưỡng cho gia đình. Cá basa trong sản phẩm chứa nhiều protein, omega-3 và các acid amin thiết yếu giúp hỗ trợ sức khỏe tim mạch, tăng cường miễn dịch và cung cấp năng lượng hiệu quả.\r\nHá cảo Thực Phẩm Cầu Tre gói 500g được đóng gói gọn nhẹ và tiện lợi, dễ bảo quản trong ngăn mát tủ lạnh và nhanh chóng chế biến trong vài phút. Sản phẩm phù hợp với mọi đối tượng, từ trẻ em đến người lớn tuổi, đặc biệt là những ai yêu thích món cá chế biến sẵn thơm ngon, giàu dinh dưỡng và tiện lợi cho bữa ăn hàng ngày.', 78800.00, 50, 'Việt Nam', 'Gói', '73.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 63040.00),
(74, 'SP000074', 'Bia Hà Nội Premium lon Sleek 330ml', 'bia-ha-noi-premium-lon-sleek-330ml', 27, 'Nước, malt lúa mạch, hoa bia, men bia', 'Bia Hà Nội Premium lon Sleek 330ml là sản phẩm bia cao cấp thuộc dòng Premium được sản xuất bởi thương hiệu Bia Hà Nội danh tiếng, nổi bật với hương vị tinh tế, cân bằng giữa vị mạch nha dịu ngọt và hương hoa bia thanh lịch. Lon Sleek 330ml gọn nhẹ, thiết kế hiện đại mang lại cảm giác sang trọng và thích hợp cho mọi dịp tụ họp, tiệc tùng hoặc dùng giải khát sau giờ làm việc.\r\nSản phẩm được lên men truyền thống từ malt lúa mạch chọn lọc cùng hoa bia chất lượng tạo nên mùi thơm dịu, vị bia thanh mát, sủi nhẹ tự nhiên và hậu vị kéo dài dễ chịu trên vòm miệng. Bia Hà Nội Premium phù hợp dùng khi ướp lạnh để tăng trải nghiệm thưởng thức, đặc biệt kết hợp tốt với các món nướng, hải sản, món chiên giòn hay các món Âu – Á phong phú.\r\nBia Hà Nội Premium lon Sleek 330ml không chỉ mang đến cảm giác sảng khoái mà còn là lựa chọn lý tưởng cho những ai yêu thích bia có độ cân bằng tốt, hương vị tinh tế và chất lượng ổn định. Sản phẩm phù hợp với các buổi tiệc gia đình, gặp gỡ bạn bè hoặc dùng hàng ngày trong không khí thoải mái.', 298300.00, 200, 'Việt Nam', 'Thùng', '74.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 238640.00),
(75, 'SP000075', 'Bia Sài Gòn Special Sleek lon 330ml', 'bia-sai-gon-special-sleek-lon-330ml', 27, 'Nước, malt lúa mạch, hoa bia, men bia', 'Bia Sài Gòn Special Sleek lon 330ml là sản phẩm bia cao cấp đến từ thương hiệu Bia Sài Gòn, mang hương vị thanh mát và cân bằng vị malt và hoa bia truyền thống. Lon Sleek 330ml với thiết kế hiện đại, nhỏ gọn rất tiện lợi cho những dịp giải trí, tụ họp bạn bè hay dùng làm đồ uống trong bữa ăn hàng ngày.\r\nSản phẩm được lên men từ malt lúa mạch chọn lọc và hoa bia chất lượng, mang đến hương thơm dịu nhẹ và vị bia tươi mát dễ chịu, cùng với độ sủi bọt tự nhiên giúp tăng trải nghiệm thưởng thức. Bia Sài Gòn Special đặc trưng với vị bia mạch nha rõ rệt nhưng không quá nồng, phù hợp với nhiều món ăn như hải sản, đồ nướng hoặc các món Âu – Á phong phú.\r\nVới dung tích lon 330ml, Bia Sài Gòn Special Sleek là lựa chọn tuyệt vời cho những ai yêu thích bia cao cấp với phong cách hiện đại, mang đến cảm giác sảng khoái sau những giờ làm việc căng thẳng. Sản phẩm phù hợp sử dụng trong mọi hoàn cảnh từ bữa ăn gia đình đến buổi gặp gỡ bạn bè, tiệc nhỏ hay dã ngoại ngoài trời.', 333000.00, 192, 'Việt Nam', 'Lon', '75.png', 'active', '2025-12-31 06:22:44', '2026-01-10 17:47:56', 266400.00),
(76, 'SP000076', 'Bia Hà Nội lon 330ml', 'bia-ha-noi-lon-330ml', 27, 'Nước, malt lúa mạch, hoa bia, men bia', 'Bia Hà Nội lon 330ml là sản phẩm bia truyền thống đến từ thương hiệu Bia Hà Nội – một trong những thương hiệu bia được yêu thích tại Việt Nam với hương vị đậm đà, cân bằng và sảng khoái. Lon 330ml gọn nhẹ, dễ sử dụng, phù hợp cho nhiều hoàn cảnh từ giải khát hàng ngày đến các buổi tụ họp bạn bè, gia đình hay dùng kèm với các món ăn mặn, nướng, hải sản.\r\nSản phẩm được lên men từ malt lúa mạch chọn lọc kết hợp cùng hoa bia chất lượng, mang đến hương thơm nhẹ, vị bia mát và hậu vị dễ chịu. Bia Hà Nội với độ sủi bọt tự nhiên, vị thanh mát và cân đối sẽ là lựa chọn lý tưởng để thưởng thức khi được ướp lạnh, đặc biệt trong những ngày thời tiết nóng bức hoặc sau giờ làm việc căng thẳng.\r\nBia Hà Nội lon 330ml không chỉ là thức uống giải khát mà còn là biểu tượng của phong cách thưởng thức bia truyền thống Việt Nam, phù hợp với cả người mới bắt đầu và người sành bia. Sản phẩm đem lại cảm giác sảng khoái, hương vị hài hòa và trải nghiệm thú vị cho người dùng trong từng ngụm.', 27900.00, 400, 'Việt Nam', 'Lon', '76.png', 'active', '2025-12-31 06:22:44', '2026-01-11 18:03:44', 25110.00),
(77, 'SP000077', 'Tiger Bia Crystal lon 330ml', 'tiger-bia-crystal-lon-330ml', 27, 'Nước, malt lúa mạch, hoa bia, men bia', 'Tiger Bia Crystal lon 330ml là sản phẩm bia cao cấp mang hương vị tươi mát và thanh lịch từ thương hiệu Tiger – một trong những thương hiệu bia quốc tế được ưa chuộng. Với thiết kế lon 330ml gọn nhẹ, sản phẩm phù hợp cho nhiều hoàn cảnh từ giải khát hàng ngày, tụ họp bạn bè, đến các buổi tiệc nhỏ hay dịp cuối tuần thư giãn.\r\nBia Tiger Crystal được sản xuất từ malt lúa mạch và hoa bia chọn lọc, mang đến hương thơm dịu nhẹ, vị bia mát lạnh và độ sủi tự nhiên cân bằng, tạo cảm giác sảng khoái ngay từ ngụm đầu tiên. Đặc điểm nổi bật của bia Tiger Crystal là vị bia tinh khiết, ít đắng và hậu vị mềm, phù hợp với nhiều món ăn từ hải sản, nướng BBQ đến các món Âu – Á phong phú.\r\nVới dung tích 330ml, Tiger Bia Crystal rất dễ bảo quản, uống lạnh để tăng trải nghiệm thưởng thức. Sản phẩm không chỉ là thức uống giải khát mà còn thể hiện phong cách thưởng thức bia tinh tế, là lựa chọn lý tưởng cho những ai yêu thích hương vị bia nhẹ nhàng, tinh khiết và sảng khoái trong từng ngụm.', 410000.00, 200, 'Việt Nam', 'Thùng', '77.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 328000.00),
(78, 'SP000078', 'Heineken Bia lon cao 330ml', 'heineken-bia-lon-cao-330ml', 27, 'Nước, malt lúa mạch, hoa bia, men bia', 'Heineken Bia lon cao 330ml là sản phẩm bia cao cấp đến từ thương hiệu Heineken – một trong những thương hiệu bia nổi tiếng toàn cầu với lịch sử hơn một thế kỷ. Lon cao 330ml mang lại trải nghiệm thưởng thức bia tươi mát, tinh khiết với hương thơm nhẹ của malt và hoa bia chất lượng, giúp người uống cảm nhận được sự cân bằng hoàn hảo giữa vị đắng nhẹ, độ sủi bọt mịn và hậu vị êm ái.\r\nSản phẩm được lên men từ nguyên liệu chọn lọc như malt lúa mạch và hoa bia thượng hạng, tuân thủ quy trình sản xuất hiện đại giúp giữ trọn hương vị tự nhiên và chất lượng ổn định. Heineken Bia lon cao 330ml phù hợp sử dụng trong nhiều hoàn cảnh từ giải khát hàng ngày, tụ họp bạn bè, đến các buổi tiệc, BBQ, picnic hoặc dùng kèm nhiều món ăn như hải sản, món nướng và đồ Âu – Á.\r\nVới dung tích 330ml thuận tiện, sản phẩm dễ dàng bảo quản và dùng lạnh để tăng trải nghiệm thưởng thức. Heineken Bia lon cao 330ml không chỉ là thức uống mang đến cảm giác sảng khoái mà còn là biểu tượng của phong cách thưởng thức bia hiện đại, phù hợp với những người yêu bia tinh tế và đam mê trải nghiệm hương vị quốc tế.', 451000.00, 200, 'Việt Nam', 'Thùng', '78.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 360800.00),
(79, 'SP000079', 'Coca-Cola Nước ngọt Coca 2.25L', 'coca-cola-nuoc-ngot-coca-225l', 28, 'Nước có ga, đường, hương liệu tự nhiên, caffeine', 'Coca-Cola Nước ngọt Coca 2.25L là sản phẩm đồ uống giải khát nổi tiếng của thương hiệu Coca-Cola – biểu tượng của phong cách sống năng động và tươi trẻ trên toàn thế giới. Với hương vị đặc trưng hòa quyện giữa ngọt dịu và vị chua nhẹ, cùng với độ sủi bọt tự nhiên hấp dẫn, Coca-Cola mang đến cảm giác sảng khoái tức thì trong những ngày nắng nóng hoặc những khoảnh khắc thư giãn cùng gia đình và bạn bè.\r\nThùng nước ngọt Coca 2.25L có thiết kế lớn, tiện lợi để dùng cho bữa tiệc gia đình, buổi dã ngoại hay các dịp tụ họp đông người. Sản phẩm chứa nước có ga và hương liệu đặc trưng, giúp kích thích vị giác và đem lại cảm giác tươi mát mỗi khi thưởng thức. Coca-Cola không chỉ là đồ uống giải khát mà còn là phần không thể thiếu trong các bữa ăn nhanh, sự kiện hoặc những khoảnh khắc chia sẻ niềm vui.\r\nSản phẩm được sản xuất theo tiêu chuẩn chất lượng toàn cầu, an toàn vệ sinh thực phẩm và phù hợp với nhu cầu sử dụng hàng ngày. Coca-Cola Nước ngọt Coca 2.25L là lựa chọn tuyệt vời cho cả gia đình, giúp bạn tận hưởng những phút giây thư giãn sảng khoái và trọn vẹn bên những người thân yêu.', 25900.00, 150, 'Việt Nam', 'Chai', '79.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 20720.00),
(80, 'SP000080', 'Nutri Boost Nước ngọt cam sữa 297ml', 'nutri-boost-nuoc-ngot-cam-sua-297ml', 28, 'Nước, đường, nước cam cô đặc, sữa bột, chất ổn định, hương liệu tự nhiên', 'Nutri Boost Nước ngọt cam sữa 297ml là sản phẩm đồ uống giải khát kết hợp giữa hương vị cam tươi mát và vị sữa béo ngọt, mang đến trải nghiệm vị giác độc đáo và thơm ngon. Sản phẩm được pha chế với nước cam cô đặc cùng sữa bột cao cấp, tạo nên vị cam ngọt dịu hòa quyện cùng sự mềm mịn của sữa, phù hợp cho mọi lứa tuổi và các hoàn cảnh sử dụng như giải khát hàng ngày, đi học, đi làm hay dịp dã ngoại.\r\nĐồ uống Nutri Boost chứa các thành phần được lựa chọn kỹ lưỡng và sản xuất theo quy trình đảm bảo vệ sinh an toàn thực phẩm. Nước ngọt cam sữa giúp bổ sung năng lượng nhanh, mang lại cảm giác sảng khoái và thư giãn sau những giờ làm việc hoặc học tập căng thẳng. Với dung tích 297ml gọn nhẹ, sản phẩm tiện lợi mang theo khi di chuyển, dùng ngay mà không cần chuẩn bị thêm.\r\nNutri Boost Nước ngọt cam sữa 297ml là lựa chọn tuyệt vời cho những ai yêu thích hương vị trái cây pha sữa tươi mát, giúp cân bằng độ ngọt và chua nhẹ tự nhiên. Đây là sản phẩm phù hợp với lối sống năng động, mang đến nguồn năng lượng tươi mới và trải nghiệm ẩm thực thú vị trong từng ngụm.', 10600.00, 150, 'Việt Nam', 'Chai', '80.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 8480.00),
(81, 'SP000081', 'La Vie Nước khoáng 500ml', 'la-vie-nuoc-khoang-500ml', 28, 'Nước khoáng tự nhiên', 'La Vie Nước khoáng 500ml là sản phẩm nước uống tinh khiết được khai thác từ nguồn nước khoáng tự nhiên sâu dưới lòng đất, giữ nguyên các khoáng chất thiết yếu như canxi, magiê và các nguyên tố vi lượng tốt cho sức khỏe. Sản phẩm được đóng chai 500ml tiện lợi, dễ mang theo khi đi học, đi làm, du lịch hay tập thể dục, mang đến cảm giác sảng khoái và bù nước hiệu quả sau những hoạt động dài.\r\nNước khoáng La Vie có hương vị thanh mát, không mùi, không vị lạ, giúp giải nhiệt cơ thể và hỗ trợ cân bằng điện giải một cách tự nhiên. Sản phẩm phù hợp sử dụng hàng ngày cho cả người lớn và trẻ em, kể cả những người hoạt động thể thao hoặc làm việc ngoài trời. Nhờ quy trình đóng gói nghiêm ngặt đạt chuẩn an toàn vệ sinh, La Vie đảm bảo chất lượng nước tinh khiết, an toàn cho sức khỏe người tiêu dùng.\r\nVới chai 500ml tiện dụng, La Vie Nước khoáng là lựa chọn lý tưởng cho gia đình, văn phòng, trường học hoặc các hoạt động ngoài trời. Đây là sản phẩm nước uống giải khát tuyệt vời, giúp cơ thể luôn tươi mới, tràn đầy năng lượng và cân bằng dưỡng chất cần thiết mỗi ngày.', 5800.00, 141, 'Việt Nam', 'Chai', '81.png', 'active', '2025-12-31 06:22:44', '2026-01-10 19:06:50', 4640.00),
(82, 'SP000082', 'Nescafé Café 3in1 Đậm Đà Hài Hòa 20 × 16g', 'nescafe-cafe-3in1-dam-da-hai-hoa-20x16g', 28, 'Cà phê hòa tan, đường, kem thực vật', 'Nescafé Café 3in1 Đậm Đà Hài Hòa 20 × 16g là sản phẩm đồ uống cà phê hòa tan tiện lợi, được phối trộn hoàn hảo giữa cà phê nguyên chất, đường và kem thực vật để tạo nên hương vị đậm đà nhưng vẫn cân bằng, thơm ngon và dễ thưởng thức. Sản phẩm phù hợp với những ai yêu thích một tách cà phê nhanh gọn nhưng vẫn giữ được dư vị tinh tế, hài hòa.\r\nMỗi gói 16g được đóng gói riêng biệt trong hộp 20 gói, giúp bạn dễ dàng pha chế chỉ trong vài giây bằng cách thêm nước nóng mà không cần các thiết bị phức tạp. Nescafé Café 3in1 mang tới vị cà phê vừa đủ, không quá đắng mà vẫn đậm đà, hòa quyện với kem và đường tạo cảm giác mịn màng, ngọt nhẹ – phù hợp cho bữa sáng hay những lúc cần năng lượng nhanh.\r\nSản phẩm được sản xuất theo tiêu chuẩn chất lượng quốc tế của thương hiệu Nescafé, đảm bảo an toàn vệ sinh và trải nghiệm hương vị đồng nhất. Với thiết kế gọn nhẹ, Nescafé Café 3in1 là lựa chọn lý tưởng cho gia đình, văn phòng, du lịch hoặc mỗi khi bạn muốn thưởng thức một ly cà phê thơm ngon mà không mất nhiều thời gian chuẩn bị.', 80500.00, 150, 'Việt Nam', 'Hộp', '82.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 64400.00),
(83, 'SP000083', 'Nestea TPBS Trà vị chanh hộp 195g', 'nestea-tpbs-tra-vi-chanh-hop-195g', 28, 'Nước, đường, chiết xuất trà, hương chanh, chất điều chỉnh độ axit, chất bảo quản', 'Nestea TPBS Trà vị chanh hộp 195g là sản phẩm đồ uống giải khát được yêu thích với hương vị trà mát dịu kết hợp cùng vị chanh tươi sảng khoái. Sản phẩm được pha chế từ chiết xuất trà tự nhiên cùng hương chanh tươi, mang đến hương vị cân bằng giữa vị ngọt nhẹ và vị chua thanh mát, giúp bạn giải nhiệt nhanh chóng trong những ngày nắng nóng hay sau khi vận động.\r\nTrà Nestea được đóng gói trong hộp 195g gọn nhẹ, dễ mang theo khi đi học, đi làm hay dã ngoại. Với công thức trà kết hợp chanh và các thành phần chất lượng cao, sản phẩm không chỉ giúp giải khát mà còn mang lại trải nghiệm thưởng thức thú vị cho người dùng ở mọi lứa tuổi.\r\nSản phẩm phù hợp dùng trực tiếp, không cần pha chế thêm, rất tiện lợi cho những ai bận rộn nhưng vẫn muốn thưởng thức một thức uống thơm ngon, mát lạnh. Nestea TPBS Trà vị chanh hộp 195g là lựa chọn lý tưởng cho gia đình, bạn bè hoặc các dịp tụ họp, mang đến hương vị tươi mới và bổ sung năng lượng tức thì vào ngày dài năng động.', 35500.00, 150, 'Việt Nam', 'Hộp', '83.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 28400.00),
(84, 'SP000084', 'Lock&amp;Lock Nồi chiên không dầu 5.5L EJF179IVY', 'lockandlock-noi-chien-khong-dau-55l-ejf179ivy', 29, 'Thân nồi, khay chiên, vỉ nướng, phụ kiện', 'Lock&amp;Lock Nồi chiên không dầu 5.5L là thiết bị nhà bếp cao cấp giúp bạn chế biến món chiên nướng thơm ngon mà không cần dùng nhiều dầu mỡ, phù hợp với lối sống hiện đại và ăn uống lành mạnh. Với dung tích lớn 5.5L, nồi chiên Lock&amp;Lock phù hợp cho gia đình từ 4–6 người, dễ dàng chiên giòn, nướng thịt, rau củ, khoai tây hay hạt ngũ cốc mà vẫn giữ được độ giòn và hương vị tự nhiên.\r\nSản phẩm ứng dụng công nghệ đối lưu khí nóng Rapid Air cho phép nhiệt lan tỏa đều trong lòng nồi, giúp thực phẩm chín nhanh, giòn đều mà không bị khô. Thiết kế sang trọng với lớp vỏ ngoài bền đẹp, dễ vệ sinh và thao tác điều khiển đơn giản thông qua bảng điều khiển điện tử hiện đại. Nồi chiên không dầu Lock&amp;Lock còn đi kèm khay chiên chống dính và vỉ nướng tiện lợi, giúp bạn chế biến nhiều món ngon ngay tại nhà.\r\nLock&amp;Lock Nồi chiên không dầu 5.5L không chỉ giúp giảm lượng dầu mỡ trong món ăn mà còn tiết kiệm thời gian nấu nướng; là lựa chọn hoàn hảo cho những ai yêu thích món chiên giòn, bánh ngọt tự làm hay thực đơn ăn kiêng lành mạnh. Sản phẩm phù hợp dùng hằng ngày, mang lại trải nghiệm nấu nướng tiện lợi và chất lượng cho cả gia đình.', 3400000.00, 19, 'Hàn Quốc', 'Cái', '84.png', 'active', '2025-12-31 06:22:44', '2026-01-11 14:59:21', 2749600.00),
(85, 'SP000085', 'Nồi cơm điện tử cao tần Lock & Lock 1.5L', 'noi-com-dien-tu-cao-tan-lock-lock-15l', 29, 'Thân nồi, ruột hợp kim chống dính, mâm nhiệt cao tần, linh kiện điện tử', 'Nồi cơm điện tử cao tần Lock & Lock 1.5L là thiết bị nhà bếp hiện đại và tiện nghi, giúp bạn nấu cơm thơm ngon, mềm dẻo và giữ trọn dưỡng chất trong từng hạt gạo. Với công nghệ nấu cao tần hiện đại, sản phẩm phân phối nhiệt đều khắp lòng nồi, giúp cơm chín nhanh, đều và giữ được hương thơm tự nhiên.\r\nSản phẩm có dung tích 1.5L phù hợp cho gia đình 3–5 người, có nhiều chế độ nấu đa dạng như nấu cơm trắng, nấu cháo, nấu cơm nhanh, hấp và giữ ấm tự động, đáp ứng đầy đủ nhu cầu bữa ăn hàng ngày. Bảng điều khiển điện tử với màn hình hiển thị rõ ràng giúp người dùng dễ dàng lựa chọn chế độ phù hợp.\r\nNồi cơm điện tử cao tần Lock & Lock sở hữu thiết kế sang trọng, vỏ ngoài bền bỉ, dễ lau chùi và an toàn khi sử dụng. Sản phẩm còn được trang bị chức năng giữ ấm lâu, giúp cơm luôn nóng hổi suốt nhiều giờ sau khi nấu. Đây là lựa chọn hoàn hảo cho gia đình hiện đại, vừa tiện lợi, vừa nâng cao chất lượng bữa ăn mỗi ngày.', 6176000.00, 20, 'Việt Nam', 'Cái', '85.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 4940800.00),
(86, 'SP000086', 'Máy xay thịt 1L Lock&Lock 400W màu đen', 'may-xay-thit-1l-lock-lock-400w-mau-den', 29, 'Động cơ 400W, cối xay 1L, lưỡi inox, vỏ nhựa ABS', 'Máy xay thịt 1L Lock&Lock – 400W màu đen là thiết bị nhà bếp tiện dụng, được thiết kế để giúp bạn xay thịt, rau củ, hạt, gia vị và nhiều nguyên liệu khác một cách nhanh chóng và hiệu quả. Máy sở hữu động cơ mạnh mẽ 400W kết hợp với lưỡi dao bằng thép không gỉ bền bỉ, giúp xay mịn nguyên liệu trong thời gian ngắn mà vẫn giữ được độ tươi ngon và chất lượng dinh dưỡng.\r\nSản phẩm có cối xay dung tích 1L phù hợp cho gia đình từ 2–4 người, thiết kế gọn nhẹ, màu đen sang trọng và dễ vệ sinh sau mỗi lần sử dụng. Máy xay thịt Lock&Lock vận hành êm ái, an toàn với khóa nắp chắc chắn và các tính năng bảo vệ quá tải, giúp bạn yên tâm sử dụng lâu dài.\r\nMáy xay thịt Lock & Lock không chỉ hỗ trợ xay thịt mà còn có thể xay các loại rau củ, hạt ngũ cốc và nguyên liệu khác, giúp bạn chế biến món ăn đa dạng hơn như nem, chả, giò, bột nhuyễn và nhiều món hấp dẫn khác. Đây là lựa chọn lý tưởng cho người nội trợ hiện đại – vừa tiện lợi, vừa tiết kiệm thời gian nấu nướng mà vẫn mang đến kết quả chất lượng cao cho bữa ăn gia đình.', 972200.00, 18, 'Việt Nam', 'Cái', '86.png', 'active', '2025-12-31 06:22:44', '2026-01-11 14:59:21', 777760.00),
(87, 'SP000087', 'Bếp nướng điện Lock&Lock', 'bep-nuong-dien-lock-lock', 29, 'Thiết bị bếp nướng điện, khay nướng, vỉ, điều khiển nhiệt', 'Bếp nướng điện Lock&Lock là thiết bị nhà bếp tiện lợi giúp bạn nướng thịt, cá, rau củ và nhiều nguyên liệu khác ngay tại nhà mà không cần bếp than hay lò nướng cồng kềnh. Sản phẩm sở hữu công suất mạnh mẽ và bề mặt nướng lớn, cho phép thực phẩm chín đều, giữ được độ ngọt tự nhiên và hương vị thơm ngon.\r\nBếp nướng điện Lock&Lock thiết kế hiện đại với khay nướng chống dính và vỉ nướng chắc chắn, giúp bạn dễ dàng lật và kiểm soát quá trình nướng mà không lo thực phẩm bị dính vào bề mặt. Sản phẩm có điều khiển nhiệt độ linh hoạt, phù hợp với nhiều món nướng như thịt nướng, hải sản, rau củ, giúp bữa ăn gia đình thêm hấp dẫn và đa dạng.\r\nBếp nướng điện Lock&Lock không chỉ phù hợp cho gia đình mà còn lý tưởng cho các buổi tụ họp, BBQ tại nhà mà không cần dùng than. Thiết kế dễ sử dụng, dễ vệ sinh và an toàn khi vận hành, mang đến trải nghiệm nấu nướng tiện ích cho người nội trợ hiện đại.', 1472000.00, 20, 'Việt Nam', 'Cái', '87.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 1177600.00),
(88, 'SP000088', 'Hokkaido Hộp Thực phẩm trộn 750ml', 'hokkaido-hop-thuc-pham-tron-750ml', 29, 'Hỗn hợp thực phẩm (rau, thịt, sốt, gia vị)', 'Hokkaido Hộp Thực phẩm trộn 750ml là sản phẩm tiện lợi được chế biến sẵn từ thương hiệu Hokkaido, kết hợp nhiều thành phần tươi ngon như rau củ, thịt và sốt trộn đặc trưng, mang đến bữa ăn nhanh gọn, giàu dinh dưỡng và hương vị thơm ngon hấp dẫn. Sản phẩm được đóng gói trong hộp 750ml tiện lợi, phù hợp cho bữa trưa văn phòng, bữa tối nhanh hoặc các buổi dã ngoại.\r\nThực phẩm trộn Hokkaido có sự cân bằng giữa lượng rau xanh tươi mát, protein từ thịt và nước sốt đậm đà giúp kích thích vị giác ngay từ miếng đầu tiên. Hương vị được điều chỉnh hài hòa, không quá nồng, phù hợp với khẩu vị của nhiều lứa tuổi từ trẻ em đến người lớn. Sản phẩm thích hợp dùng trực tiếp sau khi lắc đều hoặc trộn cùng một ít gia vị thêm nếu cần để tăng hương vị theo ý thích.\r\nHokkaido Hộp TP trộn 750ml đảm bảo tiêu chuẩn vệ sinh an toàn thực phẩm, giữ trọn chất lượng nguyên liệu và giá trị dinh dưỡng. Đây là lựa chọn lý tưởng cho người bận rộn, sinh viên, dân văn phòng hoặc bất cứ ai muốn thưởng thức món ăn nhanh, chất lượng mà không mất quá nhiều thời gian chuẩn bị.', 31000.00, 20, 'Việt Nam', 'Hộp', '88.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 24800.00),
(89, 'SP000089', 'Meizan Dầu đậu nành chai 2L', 'meizan-dau-dau-nanh-chai-2l', 30, 'Dầu đậu nành tinh luyện 100%', 'Meizan Dầu đậu nành chai 2L là sản phẩm dầu ăn cao cấp được chế biến từ 100% đậu nành tinh luyện, mang đến hàm lượng dinh dưỡng cao, thơm nhẹ và không bị oxy hóa nhanh trong quá trình nấu nướng. Dầu có điểm khói phù hợp với nhiều phương pháp chế biến như xào, chiên, rán, nướng và nấu, giúp giữ nguyên hương vị tự nhiên của thực phẩm mà vẫn đảm bảo an toàn cho sức khỏe.\r\nDầu đậu nành Meizan được sản xuất theo quy trình hiện đại, giữ lại các acid béo thiết yếu như omega-3 và omega-6, góp phần hỗ trợ sức khỏe tim mạch và tiêu hóa. Dầu có màu vàng nhạt, mùi thơm nhẹ đặc trưng, phù hợp với khẩu vị gia đình Việt.\r\nChai dầu 2L lớn và tiện lợi, phù hợp cho sử dụng hàng ngày trong các gia đình, nhà hàng nhỏ hoặc căn tin. Meizan Dầu đậu nành chai 2L không chỉ mang tới hiệu quả nấu nướng cao mà còn là lựa chọn lành mạnh cho bữa ăn gia đình, giúp món xào ngon, thức chiên giòn đều và hấp dẫn hơn.', 80900.00, 100, 'Việt Nam', 'Chai', '89.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 64720.00),
(90, 'SP000090', 'Nước mắm Nam Ngư 900ml', 'nuoc-mam-nam-ngu-900ml', 30, 'Nấm ngư, nước mắm, đường, gia vị, chất bảo quản thực phẩm', 'Nước mắm Nam Ngư 900ml là sản phẩm thực phẩm chế biến sẵn thơm ngon với nấm ngư – loại nấm giàu dinh dưỡng, phối trộn cùng nước mắm nguyên chất và các gia vị truyền thống. Sản phẩm mang đến sự kết hợp giữa vị ngọt tự nhiên của nấm và hương vị đậm đà, mặn ngọt hài hòa của nước mắm, tạo nên món ăn hấp dẫn, tiện lợi và phù hợp với khẩu vị của nhiều đối tượng.\r\nSản phẩm được chế biến theo quy trình an toàn vệ sinh thực phẩm nghiêm ngặt, giữ được hương vị tự nhiên và chất lượng nguyên liệu. Nấm ngư chứa nhiều protein thực vật, chất xơ và các vitamin thiết yếu; kết hợp cùng nước mắm với hương vị đậm đà sẽ giúp bạn sáng tạo nhiều món ăn hấp dẫn như xào nấm nước mắm, nấu canh, hầm cùng thịt, hoặc ăn kèm cơm trắng nóng hổi.\r\nVới dung tích 900ml, Nấm ngư nước mắm rất tiện lợi cho việc sử dụng hàng ngày trong gia đình, nhà hàng nhỏ hoặc khi cần chuẩn bị bữa ăn nhanh. Sản phẩm là lựa chọn tuyệt vời đem lại trải nghiệm vị giác đậm đà, phong phú và giàu dinh dưỡng, phù hợp cho bữa cơm gia đình ấm áp.', 60200.00, 100, 'Việt Nam', 'Chai', '90.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 48160.00),
(91, 'SP000091', 'Biên Hòa Đường mía Thượng hạng gói 500g', 'bien-hoa-duong-mia-thuong-hang-goi-500g', 30, 'Đường mía tinh luyện 100%', 'Biên Hòa Đường mía Thượng hạng gói 500g là sản phẩm đường tinh luyện cao cấp từ mía nguyên chất, mang đến vị ngọt dịu tự nhiên và độ tan nhanh trong nước nóng hoặc lạnh. Sản phẩm được sản xuất bởi thương hiệu Biên Hòa – uy tín lâu năm trong ngành thực phẩm, đảm bảo chất lượng, an toàn vệ sinh và tiêu chuẩn dinh dưỡng cho người sử dụng.\r\nĐường mía Biên Hòa phù hợp cho nhiều mục đích sử dụng trong gia đình như pha trà, cà phê, nấu chè, làm bánh và chế biến các món tráng miệng thơm ngon. Với gói 500g tiện lợi, sản phẩm dễ bảo quản, dùng dần và tiết kiệm không gian bếp. Đường mía Thượng hạng có độ tinh khiết cao, không lẫn tạp chất, giúp giữ nguyên hương vị tự nhiên của món ăn, đồng thời làm tăng hương vị và màu sắc hấp dẫn của thức uống hoặc món tráng miệng.\r\nSản phẩm phù hợp với mọi lứa tuổi và nhu cầu ẩm thực trong gia đình, đặc biệt là những ai yêu thích món ngọt được làm tại nhà với nguyên liệu đảm bảo chất lượng. Đây là lựa chọn lý tưởng để thêm vị ngọt tự nhiên cho thực đơn hàng ngày và làm phong phú trải nghiệm ẩm thực của bạn.', 18600.00, 98, 'Việt Nam', 'Gói', '91.png', 'active', '2025-12-31 06:22:44', '2026-01-10 17:43:43', 14880.00),
(92, 'SP000092', 'Kikkoman Nước tương chai 150ml T24', 'kikkoman-nuoc-tuong-chai-150ml-t24', 30, 'Nước tương lên men từ đậu nành, muối, nước', 'Kikkoman Nước tương chai 150ml T24 là sản phẩm nước tương cao cấp đến từ thương hiệu Kikkoman – một trong những thương hiệu nước tương lâu đời và được tin dùng trên toàn thế giới. Sản phẩm được sản xuất từ đậu nành lên men tự nhiên, mang đến hương vị đậm đà, mặn ngọt cân bằng và hương thơm tinh tế, phù hợp dùng trong nhiều món ăn từ Âu đến Á.\r\nNước tương Kikkoman rất thích hợp dùng để chấm sushi, sashimi, gỏi cuốn, dimsum hay làm gia vị trong các món xào, nấu, ướp thịt, marinades và sốt salad. Hương vị lên men tự nhiên giúp làm nổi bật hương vị nguyên liệu mà không lấn át vị gốc của thực phẩm. Với chai 150ml nhỏ gọn, sản phẩm dễ dàng bảo quản trong tủ lạnh và mang theo khi đi dã ngoại hoặc dùng tại bàn ăn gia đình.\r\nKikkoman Nước tương không chỉ đem lại hương vị thơm ngon chuẩn chất Nhật Bản mà còn là lựa chọn lành mạnh cho bữa ăn, giúp tăng độ đậm đà cho món ăn mà không cần dùng nhiều muối. Đây là sản phẩm lý tưởng cho những người yêu ẩm thực, đặc biệt yêu thích hương vị truyền thống và độ tinh tế trong từng món ăn.', 100900.00, 100, 'Nhật Bản', 'Chai', '92.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 80720.00),
(93, 'SP000093', 'Chin-Su Tương ớt 250g', 'chin-su-tuong-ot-250g', 30, 'Ớt, tỏi, giấm, muối, đường, chất bảo quản thực phẩm', 'Chin-Su Tương ớt 250g là sản phẩm nước sốt cay đặc trưng đến từ thương hiệu Chin-Su, nổi tiếng với hương vị đậm đà và chất lượng ổn định. Sản phẩm được pha chế từ ớt đỏ tươi, tỏi, giấm và các gia vị truyền thống, mang đến hương cay nồng tinh tế, phù hợp cho nhiều món ăn như phở, bún, cơm, mì xào, hải sản nướng và nhiều món Âu Á khác.\r\nVới dung tích 250g gọn nhẹ, Chin-Su Tương ớt dễ dàng bảo quản và sử dụng hàng ngày, giúp tăng hương vị cay nồng cho món ăn mà không làm mất cân bằng hương vị nguyên bản. Sản phẩm còn chứa các thành phần tự nhiên được lựa chọn kỹ càng, đảm bảo an toàn vệ sinh thực phẩm và phù hợp khẩu vị của nhiều lứa tuổi.\r\nTương ớt Chin-Su không chỉ là loại nước chấm quen thuộc trong bữa ăn gia đình mà còn là lựa chọn tuyệt vời cho các món ăn sáng, trưa, tối, từ món Âu đến món Á. Hương vị cay vừa phải, hòa quyện cùng vị ngọt dịu và chút chua thanh khiến món ăn thêm hấp dẫn và trọn vị hơn mỗi ngày.', 18300.00, 100, 'Việt Nam', 'Chai', '93.png', 'active', '2025-12-31 06:22:44', '2026-01-04 10:45:29', 14640.00),
(108, 'SP000094', 'Kẹo socola Snickers 240g', NULL, 21, NULL, 'Kẹo socola thơm ngon', 65000.00, 100, NULL, 'Cái', 'img_6962b00113b7d_1768075265.jpg', 'inactive', '2026-01-10 19:59:25', '2026-01-10 20:36:07', 65000.00),
(109, 'SP000109', 'Hạt điều rang muối Minh Việt vỏ hạt to 400g', NULL, 21, NULL, '', 230000.00, 98, NULL, 'Hộp', 'img_6962b7ed59175_1768077293.jpg', 'active', '2026-01-10 20:30:15', '2026-01-11 14:59:21', 195000.00),
(110, 'SP000110', 'Hạt Macadamia Nữ Hoàng Hạt 250g', NULL, 21, NULL, '', 150000.00, 48, NULL, 'Hộp', 'img_6962b7dfe8985_1768077279.jpg', 'active', '2026-01-10 20:30:50', '2026-01-11 14:59:21', 140000.00),
(111, 'SP000111', 'Hạt dẻ Tuấn Đạt 110g', NULL, 21, NULL, '', 90000.00, 80, NULL, 'Hộp', 'img_6962b7cfa5c84_1768077263.jpg', 'active', '2026-01-10 20:31:47', '2026-01-10 20:34:23', 70000.00);

--
-- Bẫy `san_pham`
--
DELIMITER $$
CREATE TRIGGER `trg_ghi_lich_su_san_pham` AFTER UPDATE ON `san_pham` FOR EACH ROW BEGIN
    IF @current_user_id IS NOT NULL THEN
        IF OLD.Gia_tien != NEW.Gia_tien THEN
            INSERT INTO lich_su_san_pham 
            (ID_sp, Ten_sp, Gia_cu, Gia_moi, Nguoi_sua, Loai_thao_tac, Ghi_chu)
            VALUES 
            (NEW.ID_sp, NEW.Ten, OLD.Gia_tien, NEW.Gia_tien, 
             @current_user_id, 'sua_gia', 
             CONCAT('Gia thay doi tu ', OLD.Gia_tien, ' thanh ', NEW.Gia_tien));
        END IF;
        
        IF OLD.So_luong_ton != NEW.So_luong_ton THEN
            INSERT INTO lich_su_san_pham 
            (ID_sp, Ten_sp, So_luong_cu, So_luong_moi, Nguoi_sua, Loai_thao_tac)
            VALUES 
            (NEW.ID_sp, NEW.Ten, OLD.So_luong_ton, NEW.So_luong_ton, 
             @current_user_id, 'sua_so_luong');
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_san_pham_ma_hien_thi` BEFORE INSERT ON `san_pham` FOR EACH ROW BEGIN
    -- Chỉ tạo mã nếu chưa có
    IF NEW.Ma_hien_thi IS NULL OR NEW.Ma_hien_thi = '' OR NEW.Ma_hien_thi = 'PENDING' THEN
        -- Lấy ID tiếp theo (không dùng AUTO_INCREMENT vì chưa commit)
        SET @next_id = (SELECT IFNULL(MAX(ID_sp), 0) + 1 FROM san_pham);
        SET NEW.Ma_hien_thi = CONCAT('SP', LPAD(@next_id, 6, '0'));
    END IF;
    
    -- Đảm bảo số lượng không âm
    IF NEW.So_luong_ton < 0 THEN 
        SET NEW.So_luong_ton = 0; 
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_tu_dong_out_of_stock` BEFORE UPDATE ON `san_pham` FOR EACH ROW BEGIN
    IF NEW.So_luong_ton <= 0 AND OLD.So_luong_ton > 0 THEN
        SET NEW.Trang_thai = 'out_of_stock';
    END IF;
    
    IF NEW.So_luong_ton > 0 AND OLD.Trang_thai = 'out_of_stock' THEN
        SET NEW.Trang_thai = 'active';
    END IF;
    
    IF NEW.So_luong_ton < 0 THEN 
        SET NEW.So_luong_ton = 0; 
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tai_khoan`
--

CREATE TABLE `tai_khoan` (
  `ID` int(11) NOT NULL,
  `Tai_khoan` varchar(100) NOT NULL,
  `Mat_khau` varchar(255) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `Sdt` varchar(15) DEFAULT NULL,
  `Ho_ten` varchar(200) DEFAULT NULL,
  `Dia_chi` text DEFAULT NULL,
  `Phan_quyen` enum('KH','ADMIN','QUAN_LY_KHO') DEFAULT 'KH',
  `Trang_thai` enum('active','inactive','banned') DEFAULT 'active',
  `Ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tai_khoan`
--

INSERT INTO `tai_khoan` (`ID`, `Tai_khoan`, `Mat_khau`, `Email`, `Sdt`, `Ho_ten`, `Dia_chi`, `Phan_quyen`, `Trang_thai`, `Ngay_tao`) VALUES
(1, 'Kim Nè', '$2y$10$h07k50PIaUrB1IX1C4gaFuCFzKlp8I0IudANpsPbb5vwLT8J6RtEi', 'kim@gmail.com', NULL, 'Kim', NULL, 'ADMIN', 'active', '2025-12-31 06:24:24'),
(2, 'Hắn ', '$2y$10$lxL4OJTGry5Y2Idq5RBA4OuNvPDtB2QG/nPk78Us4xybA1nI0dcKW', 'han@gmail.com', NULL, 'Hắn Nè', NULL, 'QUAN_LY_KHO', 'active', '2025-12-31 19:43:30'),
(999999, 'POS_SYSTEM', 'disabled', 'pos@system.local', '0000000000', 'Khßch vÒng lai (T?i qu?y)', 'Mua t?i qu?y', 'KH', 'active', '2025-12-31 21:12:19');

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_danh_muc_cay`
-- (See below for the actual view)
--
CREATE TABLE `v_danh_muc_cay` (
`ID_cha` int(11)
,`Ten_cha` varchar(100)
,`ID_con` int(11)
,`Ten_con` varchar(100)
,`Thu_tu_hien_thi` int(11)
,`So_san_pham` bigint(21)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_dashboard_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_dashboard_summary` (
`Doanh_thu_hom_nay` decimal(37,2)
,`Don_hang_hom_nay` bigint(21)
,`Don_cho_xu_ly` bigint(21)
,`SP_ton_thap` bigint(21)
,`Lo_sap_het_han` bigint(21)
,`Phieu_huy_cho_duyet` bigint(21)
,`Loi_nhuan_hom_nay` decimal(48,2)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_gio_hang_chi_tiet`
-- (See below for the actual view)
--
CREATE TABLE `v_gio_hang_chi_tiet` (
`ID_gio` int(11)
,`ID_tk` int(11)
,`Ho_ten` varchar(200)
,`ID_sp` int(11)
,`Ma_hien_thi` varchar(20)
,`Ten` varchar(200)
,`Gia_tien` decimal(15,2)
,`Hinh_anh` varchar(255)
,`Don_vi_tinh` varchar(50)
,`So_luong` int(11)
,`Thanh_tien` decimal(25,2)
,`So_luong_ton` int(11)
,`Tinh_trang` varchar(13)
,`Ngay_them` timestamp
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_lich_su_gia_san_pham`
-- (See below for the actual view)
--
CREATE TABLE `v_lich_su_gia_san_pham` (
`ID_lich_su` int(11)
,`ID_sp` int(11)
,`Ma_hien_thi` varchar(20)
,`Ten_sp` varchar(200)
,`Gia_cu` decimal(15,2)
,`Gia_moi` decimal(15,2)
,`Chenh_lech` decimal(16,2)
,`Phan_tram_thay_doi` decimal(22,2)
,`Nguoi_sua` varchar(200)
,`Ngay_sua` timestamp
,`Ghi_chu` text
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_loi_nhuan_san_pham`
-- (See below for the actual view)
--
CREATE TABLE `v_loi_nhuan_san_pham` (
`ID_sp` int(11)
,`Ma_hien_thi` varchar(20)
,`Ten` varchar(200)
,`Hinh_anh` varchar(255)
,`Gia_nhap` decimal(15,2)
,`Gia_ban` decimal(15,2)
,`LN_don_vi` decimal(16,2)
,`Ty_le_LN_du_kien` decimal(22,2)
,`Tong_ban` decimal(32,0)
,`Doanh_thu` decimal(37,2)
,`Gia_von` decimal(47,2)
,`LN_thuc` decimal(48,2)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_lo_hang_sap_het_han`
-- (See below for the actual view)
--
CREATE TABLE `v_lo_hang_sap_het_han` (
`ID_chi_tiet_nhap` int(11)
,`ID_sp` int(11)
,`Ma_SP` varchar(20)
,`Ten_SP` varchar(200)
,`Hinh_anh` varchar(255)
,`So_luong_nhap` int(11)
,`So_luong_con` int(11)
,`Don_gia_nhap` decimal(15,2)
,`Ngay_het_han` date
,`So_ngay_con` int(7)
,`ID_phieu_nhap` int(11)
,`Ma_phieu_nhap` varchar(50)
,`Ngay_nhap` date
,`Ten_danh_muc` varchar(100)
,`Muc_canh_bao` varchar(13)
,`Gia_tri_ton` decimal(25,2)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_phieu_huy_cho_duyet`
-- (See below for the actual view)
--
CREATE TABLE `v_phieu_huy_cho_duyet` (
`ID_phieu_huy` int(11)
,`Ma_hien_thi` varchar(50)
,`Loai_phieu` enum('huy','hong','het_han','dieu_chinh')
,`Ngay_huy` date
,`Ngay_tao` timestamp
,`Ly_do` text
,`Tong_tien_huy` decimal(15,2)
,`Trang_thai` enum('cho_duyet','da_duyet','tu_choi')
,`Nguoi_tao` int(11)
,`Ten_nguoi_tao` varchar(200)
,`So_san_pham` bigint(21)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_san_pham_ban_chay`
-- (See below for the actual view)
--
CREATE TABLE `v_san_pham_ban_chay` (
`ID_sp` int(11)
,`Ma_hien_thi` varchar(20)
,`Ten` varchar(200)
,`Gia_tien` decimal(15,2)
,`Hinh_anh` varchar(255)
,`Tong_ban` decimal(32,0)
,`Doanh_thu` decimal(37,2)
,`So_don_hang` bigint(21)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_san_pham_sap_het`
-- (See below for the actual view)
--
CREATE TABLE `v_san_pham_sap_het` (
`ID_sp` int(11)
,`Ma_hien_thi` varchar(20)
,`Ten` varchar(200)
,`So_luong_ton` int(11)
,`Gia_tien` decimal(15,2)
,`Don_vi_tinh` varchar(50)
,`Ten_danh_muc` varchar(100)
,`Muc_do_canh_bao` varchar(9)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_thong_ke_doanh_thu`
-- (See below for the actual view)
--
CREATE TABLE `v_thong_ke_doanh_thu` (
`Ngay` date
,`So_don_hang` bigint(21)
,`Tong_tien_hang` decimal(37,2)
,`Tong_phi_ship` decimal(37,2)
,`Tong_doanh_thu` decimal(37,2)
,`Gia_tri_trung_binh` decimal(19,6)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_thong_ke_hang_huy`
-- (See below for the actual view)
--
CREATE TABLE `v_thong_ke_hang_huy` (
`Loai_phieu` enum('huy','hong','het_han','dieu_chinh')
,`So_phieu` bigint(21)
,`Tong_so_luong` decimal(32,0)
,`Tong_gia_tri` decimal(37,2)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_thong_ke_lai_lo`
-- (See below for the actual view)
--
CREATE TABLE `v_thong_ke_lai_lo` (
`Ngay` date
,`So_don` bigint(21)
,`Doanh_thu` decimal(37,2)
,`Gia_von` decimal(47,2)
,`Loi_nhuan` decimal(48,2)
,`Ty_le_LN` decimal(54,2)
);

-- --------------------------------------------------------

--
-- Cấu trúc đóng vai cho view `v_tong_quan_don_hang`
-- (See below for the actual view)
--
CREATE TABLE `v_tong_quan_don_hang` (
`Trang_thai` enum('dang_xu_ly','dang_giao','da_giao','huy')
,`So_luong` bigint(21)
,`Tong_tien` decimal(37,2)
,`Gia_tri_trung_binh` decimal(19,6)
);

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_danh_muc_cay`
--
DROP TABLE IF EXISTS `v_danh_muc_cay`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_danh_muc_cay`  AS SELECT `dm_cha`.`ID_danh_muc` AS `ID_cha`, `dm_cha`.`Ten_danh_muc` AS `Ten_cha`, `dm_con`.`ID_danh_muc` AS `ID_con`, `dm_con`.`Ten_danh_muc` AS `Ten_con`, `dm_con`.`Thu_tu_hien_thi` AS `Thu_tu_hien_thi`, count(`sp`.`ID_sp`) AS `So_san_pham` FROM ((`danh_muc` `dm_cha` left join `danh_muc` `dm_con` on(`dm_cha`.`ID_danh_muc` = `dm_con`.`Danh_muc_cha`)) left join `san_pham` `sp` on(`dm_con`.`ID_danh_muc` = `sp`.`ID_danh_muc` and `sp`.`Trang_thai` = 'active')) WHERE `dm_cha`.`Danh_muc_cha` is null AND `dm_cha`.`Trang_thai` = 'active' GROUP BY `dm_cha`.`ID_danh_muc`, `dm_cha`.`Ten_danh_muc`, `dm_con`.`ID_danh_muc`, `dm_con`.`Ten_danh_muc`, `dm_con`.`Thu_tu_hien_thi` ORDER BY `dm_cha`.`Thu_tu_hien_thi` ASC, `dm_con`.`Thu_tu_hien_thi` ASC ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_dashboard_summary`
--
DROP TABLE IF EXISTS `v_dashboard_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_dashboard_summary`  AS SELECT (select coalesce(sum(`don_hang`.`Thanh_tien`),0) from `don_hang` where `don_hang`.`Trang_thai` = 'da_giao' and cast(`don_hang`.`Ngay_dat` as date) = curdate()) AS `Doanh_thu_hom_nay`, (select count(0) from `don_hang` where cast(`don_hang`.`Ngay_dat` as date) = curdate()) AS `Don_hang_hom_nay`, (select count(0) from `don_hang` where `don_hang`.`Trang_thai` = 'dang_xu_ly') AS `Don_cho_xu_ly`, (select count(0) from `san_pham` where `san_pham`.`So_luong_ton` <= 10 and `san_pham`.`Trang_thai` = 'active') AS `SP_ton_thap`, (select count(0) from `v_lo_hang_sap_het_han` where `v_lo_hang_sap_het_han`.`Muc_canh_bao` in ('DA_HET_HAN','TRONG_7_NGAY','TRONG_30_NGAY')) AS `Lo_sap_het_han`, (select count(0) from `phieu_huy` where `phieu_huy`.`Trang_thai` = 'cho_duyet') AS `Phieu_huy_cho_duyet`, (select coalesce(`v_thong_ke_lai_lo`.`Loi_nhuan`,0) from `v_thong_ke_lai_lo` where `v_thong_ke_lai_lo`.`Ngay` = curdate() limit 1) AS `Loi_nhuan_hom_nay` ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_gio_hang_chi_tiet`
--
DROP TABLE IF EXISTS `v_gio_hang_chi_tiet`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_gio_hang_chi_tiet`  AS SELECT `gh`.`ID_gio` AS `ID_gio`, `gh`.`ID_tk` AS `ID_tk`, `tk`.`Ho_ten` AS `Ho_ten`, `gh`.`ID_sp` AS `ID_sp`, `sp`.`Ma_hien_thi` AS `Ma_hien_thi`, `sp`.`Ten` AS `Ten`, `sp`.`Gia_tien` AS `Gia_tien`, `sp`.`Hinh_anh` AS `Hinh_anh`, `sp`.`Don_vi_tinh` AS `Don_vi_tinh`, `gh`.`So_luong` AS `So_luong`, `gh`.`So_luong`* `sp`.`Gia_tien` AS `Thanh_tien`, `sp`.`So_luong_ton` AS `So_luong_ton`, CASE WHEN `sp`.`So_luong_ton` = 0 THEN 'Het hang' WHEN `sp`.`So_luong_ton` < `gh`.`So_luong` THEN 'Khong du hang' ELSE 'Co san' END AS `Tinh_trang`, `gh`.`Ngay_them` AS `Ngay_them` FROM ((`gio_hang` `gh` join `tai_khoan` `tk` on(`gh`.`ID_tk` = `tk`.`ID`)) join `san_pham` `sp` on(`gh`.`ID_sp` = `sp`.`ID_sp`)) WHERE `sp`.`Trang_thai` = 'active' ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_lich_su_gia_san_pham`
--
DROP TABLE IF EXISTS `v_lich_su_gia_san_pham`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_lich_su_gia_san_pham`  AS SELECT `ls`.`ID_lich_su` AS `ID_lich_su`, `ls`.`ID_sp` AS `ID_sp`, `sp`.`Ma_hien_thi` AS `Ma_hien_thi`, `ls`.`Ten_sp` AS `Ten_sp`, `ls`.`Gia_cu` AS `Gia_cu`, `ls`.`Gia_moi` AS `Gia_moi`, `ls`.`Gia_moi`- `ls`.`Gia_cu` AS `Chenh_lech`, round((`ls`.`Gia_moi` - `ls`.`Gia_cu`) / `ls`.`Gia_cu` * 100,2) AS `Phan_tram_thay_doi`, `tk`.`Ho_ten` AS `Nguoi_sua`, `ls`.`Ngay_sua` AS `Ngay_sua`, `ls`.`Ghi_chu` AS `Ghi_chu` FROM ((`lich_su_san_pham` `ls` join `san_pham` `sp` on(`ls`.`ID_sp` = `sp`.`ID_sp`)) join `tai_khoan` `tk` on(`ls`.`Nguoi_sua` = `tk`.`ID`)) WHERE `ls`.`Loai_thao_tac` = 'sua_gia' ORDER BY `ls`.`Ngay_sua` DESC ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_loi_nhuan_san_pham`
--
DROP TABLE IF EXISTS `v_loi_nhuan_san_pham`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_loi_nhuan_san_pham`  AS SELECT `sp`.`ID_sp` AS `ID_sp`, `sp`.`Ma_hien_thi` AS `Ma_hien_thi`, `sp`.`Ten` AS `Ten`, `sp`.`Hinh_anh` AS `Hinh_anh`, `sp`.`Gia_nhap` AS `Gia_nhap`, `sp`.`Gia_tien` AS `Gia_ban`, `sp`.`Gia_tien`- `sp`.`Gia_nhap` AS `LN_don_vi`, CASE WHEN `sp`.`Gia_nhap` > 0 THEN round((`sp`.`Gia_tien` - `sp`.`Gia_nhap`) / `sp`.`Gia_nhap` * 100,2) ELSE 0 END AS `Ty_le_LN_du_kien`, coalesce(sum(`ct`.`So_luong`),0) AS `Tong_ban`, coalesce(sum(`ct`.`Thanh_tien`),0) AS `Doanh_thu`, coalesce(sum(`ct`.`So_luong` * `sp`.`Gia_nhap`),0) AS `Gia_von`, coalesce(sum(`ct`.`Thanh_tien`) - sum(`ct`.`So_luong` * `sp`.`Gia_nhap`),0) AS `LN_thuc` FROM ((`san_pham` `sp` left join `chi_tiet_don_hang` `ct` on(`sp`.`ID_sp` = `ct`.`ID_sp`)) left join `don_hang` `dh` on(`ct`.`ID_dh` = `dh`.`ID_dh` and `dh`.`Trang_thai` = 'da_giao')) GROUP BY `sp`.`ID_sp`, `sp`.`Ma_hien_thi`, `sp`.`Ten`, `sp`.`Hinh_anh`, `sp`.`Gia_nhap`, `sp`.`Gia_tien` ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_lo_hang_sap_het_han`
--
DROP TABLE IF EXISTS `v_lo_hang_sap_het_han`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_lo_hang_sap_het_han`  AS SELECT `ct`.`ID_chi_tiet_nhap` AS `ID_chi_tiet_nhap`, `ct`.`ID_sp` AS `ID_sp`, `sp`.`Ma_hien_thi` AS `Ma_SP`, `sp`.`Ten` AS `Ten_SP`, `sp`.`Hinh_anh` AS `Hinh_anh`, `ct`.`So_luong` AS `So_luong_nhap`, `ct`.`So_luong_con` AS `So_luong_con`, `ct`.`Don_gia_nhap` AS `Don_gia_nhap`, `ct`.`Ngay_het_han` AS `Ngay_het_han`, to_days(`ct`.`Ngay_het_han`) - to_days(curdate()) AS `So_ngay_con`, `pn`.`ID_phieu_nhap` AS `ID_phieu_nhap`, `pn`.`Ma_hien_thi` AS `Ma_phieu_nhap`, `pn`.`Ngay_nhap` AS `Ngay_nhap`, `dm`.`Ten_danh_muc` AS `Ten_danh_muc`, CASE WHEN `ct`.`Ngay_het_han` <= curdate() THEN 'DA_HET_HAN' WHEN to_days(`ct`.`Ngay_het_han`) - to_days(curdate()) <= 7 THEN 'TRONG_7_NGAY' WHEN to_days(`ct`.`Ngay_het_han`) - to_days(curdate()) <= 30 THEN 'TRONG_30_NGAY' ELSE 'BINH_THUONG' END AS `Muc_canh_bao`, `ct`.`So_luong_con`* `ct`.`Don_gia_nhap` AS `Gia_tri_ton` FROM (((`chi_tiet_phieu_nhap` `ct` join `san_pham` `sp` on(`ct`.`ID_sp` = `sp`.`ID_sp`)) join `phieu_nhap_kho` `pn` on(`ct`.`ID_phieu_nhap` = `pn`.`ID_phieu_nhap`)) left join `danh_muc` `dm` on(`sp`.`ID_danh_muc` = `dm`.`ID_danh_muc`)) WHERE `ct`.`Ngay_het_han` is not null AND `ct`.`Ngay_het_han` <= curdate() + interval 30 day AND `ct`.`So_luong_con` > 0 ORDER BY `ct`.`Ngay_het_han` ASC ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_phieu_huy_cho_duyet`
--
DROP TABLE IF EXISTS `v_phieu_huy_cho_duyet`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_phieu_huy_cho_duyet`  AS SELECT `ph`.`ID_phieu_huy` AS `ID_phieu_huy`, `ph`.`Ma_hien_thi` AS `Ma_hien_thi`, `ph`.`Loai_phieu` AS `Loai_phieu`, `ph`.`Ngay_huy` AS `Ngay_huy`, `ph`.`Ngay_tao` AS `Ngay_tao`, `ph`.`Ly_do` AS `Ly_do`, `ph`.`Tong_tien_huy` AS `Tong_tien_huy`, `ph`.`Trang_thai` AS `Trang_thai`, `ph`.`Nguoi_tao` AS `Nguoi_tao`, `tk`.`Ho_ten` AS `Ten_nguoi_tao`, (select count(0) from `chi_tiet_phieu_huy` where `chi_tiet_phieu_huy`.`ID_phieu_huy` = `ph`.`ID_phieu_huy`) AS `So_san_pham` FROM (`phieu_huy` `ph` join `tai_khoan` `tk` on(`ph`.`Nguoi_tao` = `tk`.`ID`)) WHERE `ph`.`Trang_thai` = 'cho_duyet' ORDER BY `ph`.`Ngay_tao` DESC ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_san_pham_ban_chay`
--
DROP TABLE IF EXISTS `v_san_pham_ban_chay`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_san_pham_ban_chay`  AS SELECT `sp`.`ID_sp` AS `ID_sp`, `sp`.`Ma_hien_thi` AS `Ma_hien_thi`, `sp`.`Ten` AS `Ten`, `sp`.`Gia_tien` AS `Gia_tien`, `sp`.`Hinh_anh` AS `Hinh_anh`, sum(`ct`.`So_luong`) AS `Tong_ban`, sum(`ct`.`Thanh_tien`) AS `Doanh_thu`, count(distinct `ct`.`ID_dh`) AS `So_don_hang` FROM ((`san_pham` `sp` join `chi_tiet_don_hang` `ct` on(`sp`.`ID_sp` = `ct`.`ID_sp`)) join `don_hang` `dh` on(`ct`.`ID_dh` = `dh`.`ID_dh`)) WHERE `dh`.`Trang_thai` <> 'huy' GROUP BY `sp`.`ID_sp`, `sp`.`Ma_hien_thi`, `sp`.`Ten`, `sp`.`Gia_tien`, `sp`.`Hinh_anh` ORDER BY sum(`ct`.`So_luong`) DESC ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_san_pham_sap_het`
--
DROP TABLE IF EXISTS `v_san_pham_sap_het`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_san_pham_sap_het`  AS SELECT `sp`.`ID_sp` AS `ID_sp`, `sp`.`Ma_hien_thi` AS `Ma_hien_thi`, `sp`.`Ten` AS `Ten`, `sp`.`So_luong_ton` AS `So_luong_ton`, `sp`.`Gia_tien` AS `Gia_tien`, `sp`.`Don_vi_tinh` AS `Don_vi_tinh`, `dm`.`Ten_danh_muc` AS `Ten_danh_muc`, CASE WHEN `sp`.`So_luong_ton` = 0 THEN 'HET HANG' WHEN `sp`.`So_luong_ton` <= 5 THEN 'NGUY HIEM' WHEN `sp`.`So_luong_ton` <= 10 THEN 'CANH BAO' END AS `Muc_do_canh_bao` FROM (`san_pham` `sp` join `danh_muc` `dm` on(`sp`.`ID_danh_muc` = `dm`.`ID_danh_muc`)) WHERE `sp`.`So_luong_ton` <= 10 AND `sp`.`Trang_thai` = 'active' ORDER BY `sp`.`So_luong_ton` ASC ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_thong_ke_doanh_thu`
--
DROP TABLE IF EXISTS `v_thong_ke_doanh_thu`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_thong_ke_doanh_thu`  AS SELECT cast(`dh`.`Ngay_dat` as date) AS `Ngay`, count(`dh`.`ID_dh`) AS `So_don_hang`, sum(`dh`.`Tong_tien`) AS `Tong_tien_hang`, sum(`dh`.`Phi_van_chuyen`) AS `Tong_phi_ship`, sum(`dh`.`Thanh_tien`) AS `Tong_doanh_thu`, avg(`dh`.`Thanh_tien`) AS `Gia_tri_trung_binh` FROM `don_hang` AS `dh` WHERE `dh`.`Trang_thai` <> 'huy' GROUP BY cast(`dh`.`Ngay_dat` as date) ORDER BY cast(`dh`.`Ngay_dat` as date) DESC ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_thong_ke_hang_huy`
--
DROP TABLE IF EXISTS `v_thong_ke_hang_huy`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_thong_ke_hang_huy`  AS SELECT `ph`.`Loai_phieu` AS `Loai_phieu`, count(distinct `ph`.`ID_phieu_huy`) AS `So_phieu`, coalesce(sum(`ct`.`So_luong`),0) AS `Tong_so_luong`, coalesce(sum(`ct`.`Thanh_tien`),0) AS `Tong_gia_tri` FROM (`phieu_huy` `ph` left join `chi_tiet_phieu_huy` `ct` on(`ph`.`ID_phieu_huy` = `ct`.`ID_phieu_huy`)) WHERE `ph`.`Trang_thai` = 'da_duyet' GROUP BY `ph`.`Loai_phieu` ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_thong_ke_lai_lo`
--
DROP TABLE IF EXISTS `v_thong_ke_lai_lo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_thong_ke_lai_lo`  AS SELECT cast(`dh`.`Ngay_dat` as date) AS `Ngay`, count(distinct `dh`.`ID_dh`) AS `So_don`, coalesce(sum(`ct`.`Thanh_tien`),0) AS `Doanh_thu`, coalesce(sum(`ct`.`So_luong` * `sp`.`Gia_nhap`),0) AS `Gia_von`, coalesce(sum(`ct`.`Thanh_tien`) - sum(`ct`.`So_luong` * `sp`.`Gia_nhap`),0) AS `Loi_nhuan`, CASE WHEN sum(`ct`.`Thanh_tien`) > 0 THEN round((sum(`ct`.`Thanh_tien`) - sum(`ct`.`So_luong` * `sp`.`Gia_nhap`)) / sum(`ct`.`Thanh_tien`) * 100,2) ELSE 0 END AS `Ty_le_LN` FROM ((`don_hang` `dh` join `chi_tiet_don_hang` `ct` on(`dh`.`ID_dh` = `ct`.`ID_dh`)) join `san_pham` `sp` on(`ct`.`ID_sp` = `sp`.`ID_sp`)) WHERE `dh`.`Trang_thai` = 'da_giao' GROUP BY cast(`dh`.`Ngay_dat` as date) ORDER BY cast(`dh`.`Ngay_dat` as date) DESC ;

-- --------------------------------------------------------

--
-- Cấu trúc cho view `v_tong_quan_don_hang`
--
DROP TABLE IF EXISTS `v_tong_quan_don_hang`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_tong_quan_don_hang`  AS SELECT `don_hang`.`Trang_thai` AS `Trang_thai`, count(0) AS `So_luong`, sum(`don_hang`.`Thanh_tien`) AS `Tong_tien`, avg(`don_hang`.`Thanh_tien`) AS `Gia_tri_trung_binh` FROM `don_hang` GROUP BY `don_hang`.`Trang_thai` ;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`ID_log`),
  ADD KEY `idx_user_action` (`User_id`,`Action_type`),
  ADD KEY `idx_table_record` (`Table_name`,`Record_id`),
  ADD KEY `idx_created_at` (`Created_at`),
  ADD KEY `idx_action_type` (`Action_type`);

--
-- Chỉ mục cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD PRIMARY KEY (`ID_ct_dh`),
  ADD KEY `ID_sp` (`ID_sp`),
  ADD KEY `idx_don_hang` (`ID_dh`),
  ADD KEY `idx_ctdh_lo` (`ID_chi_tiet_nhap`),
  ADD KEY `idx_ctdh_gia_von` (`Don_gia_von`);

--
-- Chỉ mục cho bảng `chi_tiet_phieu_huy`
--
ALTER TABLE `chi_tiet_phieu_huy`
  ADD PRIMARY KEY (`ID_chi_tiet`),
  ADD KEY `ID_sp` (`ID_sp`),
  ADD KEY `ID_lo_nhap` (`ID_lo_nhap`),
  ADD KEY `idx_phieu` (`ID_phieu_huy`);

--
-- Chỉ mục cho bảng `chi_tiet_phieu_nhap`
--
ALTER TABLE `chi_tiet_phieu_nhap`
  ADD PRIMARY KEY (`ID_chi_tiet_nhap`),
  ADD KEY `idx_phieu_sp` (`ID_phieu_nhap`,`ID_sp`),
  ADD KEY `idx_het_han` (`Ngay_het_han`),
  ADD KEY `idx_sp_con` (`ID_sp`,`So_luong_con`);

--
-- Chỉ mục cho bảng `danh_muc`
--
ALTER TABLE `danh_muc`
  ADD PRIMARY KEY (`ID_danh_muc`),
  ADD KEY `idx_cha_trang_thai` (`Danh_muc_cha`,`Trang_thai`);

--
-- Chỉ mục cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  ADD PRIMARY KEY (`ID_dh`),
  ADD KEY `idx_tk_trang_thai` (`ID_tk`,`Trang_thai`),
  ADD KEY `idx_ngay_trang_thai` (`Ngay_dat`,`Trang_thai`);

--
-- Chỉ mục cho bảng `gio_hang`
--
ALTER TABLE `gio_hang`
  ADD PRIMARY KEY (`ID_gio`),
  ADD UNIQUE KEY `unique_cart` (`ID_tk`,`ID_sp`),
  ADD KEY `ID_sp` (`ID_sp`);

--
-- Chỉ mục cho bảng `lich_su_san_pham`
--
ALTER TABLE `lich_su_san_pham`
  ADD PRIMARY KEY (`ID_lich_su`),
  ADD KEY `Nguoi_sua` (`Nguoi_sua`),
  ADD KEY `idx_sp_ngay` (`ID_sp`,`Ngay_sua`),
  ADD KEY `idx_loai_thao_tac` (`Loai_thao_tac`);

--
-- Chỉ mục cho bảng `ma_phieu_sequence`
--
ALTER TABLE `ma_phieu_sequence`
  ADD PRIMARY KEY (`Ngay`);

--
-- Chỉ mục cho bảng `nha_cung_cap`
--
ALTER TABLE `nha_cung_cap`
  ADD PRIMARY KEY (`ID_ncc`),
  ADD UNIQUE KEY `Ma_hien_thi` (`Ma_hien_thi`);

--
-- Chỉ mục cho bảng `phieu_huy`
--
ALTER TABLE `phieu_huy`
  ADD PRIMARY KEY (`ID_phieu_huy`),
  ADD UNIQUE KEY `Ma_hien_thi` (`Ma_hien_thi`),
  ADD KEY `Nguoi_tao` (`Nguoi_tao`),
  ADD KEY `Nguoi_duyet` (`Nguoi_duyet`),
  ADD KEY `idx_trang_thai` (`Trang_thai`),
  ADD KEY `idx_ngay_loai` (`Ngay_huy`,`Loai_phieu`);

--
-- Chỉ mục cho bảng `phieu_nhap_kho`
--
ALTER TABLE `phieu_nhap_kho`
  ADD PRIMARY KEY (`ID_phieu_nhap`),
  ADD UNIQUE KEY `Ma_hien_thi` (`Ma_hien_thi`),
  ADD KEY `idx_nguoi_tao_ngay` (`Nguoi_tao`,`Ngay_nhap`),
  ADD KEY `idx_pnk_ncc` (`ID_ncc`),
  ADD KEY `idx_pnk_trang_thai` (`Trang_thai`);

--
-- Chỉ mục cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  ADD PRIMARY KEY (`ID_sp`),
  ADD UNIQUE KEY `Ma_hien_thi` (`Ma_hien_thi`),
  ADD UNIQUE KEY `Slug` (`Slug`),
  ADD KEY `idx_danh_muc_trang_thai` (`ID_danh_muc`,`Trang_thai`),
  ADD KEY `idx_ton_kho` (`So_luong_ton`);
ALTER TABLE `san_pham` ADD FULLTEXT KEY `idx_tim_kiem` (`Ten`,`Mo_ta_sp`);

--
-- Chỉ mục cho bảng `tai_khoan`
--
ALTER TABLE `tai_khoan`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `Tai_khoan` (`Tai_khoan`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `idx_tai_khoan` (`Tai_khoan`),
  ADD KEY `idx_phan_quyen_trang_thai` (`Phan_quyen`,`Trang_thai`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `ID_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  MODIFY `ID_ct_dh` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

--
-- AUTO_INCREMENT cho bảng `chi_tiet_phieu_huy`
--
ALTER TABLE `chi_tiet_phieu_huy`
  MODIFY `ID_chi_tiet` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT cho bảng `chi_tiet_phieu_nhap`
--
ALTER TABLE `chi_tiet_phieu_nhap`
  MODIFY `ID_chi_tiet_nhap` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT cho bảng `danh_muc`
--
ALTER TABLE `danh_muc`
  MODIFY `ID_danh_muc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  MODIFY `ID_dh` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT cho bảng `gio_hang`
--
ALTER TABLE `gio_hang`
  MODIFY `ID_gio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT cho bảng `lich_su_san_pham`
--
ALTER TABLE `lich_su_san_pham`
  MODIFY `ID_lich_su` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT cho bảng `nha_cung_cap`
--
ALTER TABLE `nha_cung_cap`
  MODIFY `ID_ncc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `phieu_huy`
--
ALTER TABLE `phieu_huy`
  MODIFY `ID_phieu_huy` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT cho bảng `phieu_nhap_kho`
--
ALTER TABLE `phieu_nhap_kho`
  MODIFY `ID_phieu_nhap` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  MODIFY `ID_sp` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT cho bảng `tai_khoan`
--
ALTER TABLE `tai_khoan`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1000000;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD CONSTRAINT `chi_tiet_don_hang_ibfk_1` FOREIGN KEY (`ID_dh`) REFERENCES `don_hang` (`ID_dh`) ON DELETE CASCADE,
  ADD CONSTRAINT `chi_tiet_don_hang_ibfk_2` FOREIGN KEY (`ID_sp`) REFERENCES `san_pham` (`ID_sp`);

--
-- Các ràng buộc cho bảng `chi_tiet_phieu_huy`
--
ALTER TABLE `chi_tiet_phieu_huy`
  ADD CONSTRAINT `chi_tiet_phieu_huy_ibfk_1` FOREIGN KEY (`ID_phieu_huy`) REFERENCES `phieu_huy` (`ID_phieu_huy`) ON DELETE CASCADE,
  ADD CONSTRAINT `chi_tiet_phieu_huy_ibfk_2` FOREIGN KEY (`ID_sp`) REFERENCES `san_pham` (`ID_sp`),
  ADD CONSTRAINT `chi_tiet_phieu_huy_ibfk_3` FOREIGN KEY (`ID_lo_nhap`) REFERENCES `chi_tiet_phieu_nhap` (`ID_chi_tiet_nhap`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `chi_tiet_phieu_nhap`
--
ALTER TABLE `chi_tiet_phieu_nhap`
  ADD CONSTRAINT `chi_tiet_phieu_nhap_ibfk_1` FOREIGN KEY (`ID_phieu_nhap`) REFERENCES `phieu_nhap_kho` (`ID_phieu_nhap`) ON DELETE CASCADE,
  ADD CONSTRAINT `chi_tiet_phieu_nhap_ibfk_2` FOREIGN KEY (`ID_sp`) REFERENCES `san_pham` (`ID_sp`);

--
-- Các ràng buộc cho bảng `danh_muc`
--
ALTER TABLE `danh_muc`
  ADD CONSTRAINT `danh_muc_ibfk_1` FOREIGN KEY (`Danh_muc_cha`) REFERENCES `danh_muc` (`ID_danh_muc`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  ADD CONSTRAINT `don_hang_ibfk_1` FOREIGN KEY (`ID_tk`) REFERENCES `tai_khoan` (`ID`);

--
-- Các ràng buộc cho bảng `gio_hang`
--
ALTER TABLE `gio_hang`
  ADD CONSTRAINT `gio_hang_ibfk_1` FOREIGN KEY (`ID_tk`) REFERENCES `tai_khoan` (`ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `gio_hang_ibfk_2` FOREIGN KEY (`ID_sp`) REFERENCES `san_pham` (`ID_sp`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `lich_su_san_pham`
--
ALTER TABLE `lich_su_san_pham`
  ADD CONSTRAINT `lich_su_san_pham_ibfk_1` FOREIGN KEY (`ID_sp`) REFERENCES `san_pham` (`ID_sp`) ON DELETE CASCADE,
  ADD CONSTRAINT `lich_su_san_pham_ibfk_2` FOREIGN KEY (`Nguoi_sua`) REFERENCES `tai_khoan` (`ID`);

--
-- Các ràng buộc cho bảng `phieu_huy`
--
ALTER TABLE `phieu_huy`
  ADD CONSTRAINT `phieu_huy_ibfk_1` FOREIGN KEY (`Nguoi_tao`) REFERENCES `tai_khoan` (`ID`),
  ADD CONSTRAINT `phieu_huy_ibfk_2` FOREIGN KEY (`Nguoi_duyet`) REFERENCES `tai_khoan` (`ID`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `phieu_nhap_kho`
--
ALTER TABLE `phieu_nhap_kho`
  ADD CONSTRAINT `phieu_nhap_kho_ibfk_1` FOREIGN KEY (`Nguoi_tao`) REFERENCES `tai_khoan` (`ID`);

--
-- Các ràng buộc cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  ADD CONSTRAINT `san_pham_ibfk_1` FOREIGN KEY (`ID_danh_muc`) REFERENCES `danh_muc` (`ID_danh_muc`);
COMMIT;