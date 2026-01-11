<?php

if (!function_exists('getProductImagePath')) {
    function getProductImagePath($imageName) {
        // Tr\u1ea3 v\u1ec1 tr\u1ed1ng n\u1ebfu kh\u00f4ng c\u00f3 t\u00ean \u1ea3nh
        if (empty($imageName)) {
            return '';
        }
    
        return $imageName;
    }
}

/**
 * Upload h\u00ecnh \u1ea3nh
 * 
 * @param array $file $_FILES['field']
 * @param string $targetDir Th\u01b0 m\u1ee5c l\u01b0u
 * @return array ['success' => bool, 'filename' => string, 'message' => string]
 */
if (!function_exists('uploadImage')) {
    function uploadImage($file, $targetDir) {
        // 1. Validate c\u01a1 b\u1ea3n
        if (!isset($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'L\u1ed7i upload file (Code: ' . ($file['error'] ?? 'Unknown') . ')'];
        }

        // 2. Validate config
        if (!defined('ALLOWED_IMAGE_TYPES') || !defined('MAX_UPLOAD_SIZE')) {
            // Fallback config n\u1ebfu constants ch\u01b0a c\u00f3
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB
        } else {
            $allowedTypes = ALLOWED_IMAGE_TYPES; // T\u1eeb config
            $maxSize = MAX_UPLOAD_SIZE;
        }

        // 3. Check File Type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
             // Fallback check extension ch\u1ee7 y\u1ebfu cho local dev m\u00f4i tr\u01b0\u1eddng c\u0169
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $validExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $validExts)) {
                return ['success' => false, 'message' => 'Định dạng file không hỗ trợ (' . $mimeType . ')'];
            }
        }

        // 4. Check Size
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File quá lớn (Max: ' . ($maxSize/1024/1024) . 'MB)'];
        }

        // 5. Generate Safe Filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_') . '_' . time() . '.' . $ext;
        $targetPath = rtrim($targetDir, '/') . '/' . $filename;

        // 6. Ensure Dir Exists
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // 7. Move File
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => true, 'filename' => $filename, 'message' => 'Upload thành công'];
        } else {
            return ['success' => false, 'message' => 'Không thể lưu file vào đích'];
        }
    }
}
