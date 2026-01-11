<?php
/**
 * POS View - Bán hàng tại quầy
 * Redesign based on SmartMart template
 */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSP to allow necessary scripts and silence warnings -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' 'unsafe-eval' https: data:;">
    <title><?= $page_title ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary: #7BC043;
        --primary-dark: #5a9c30;
        --primary-light: #f0f9eb;
        --secondary: #291D51;
        --background: #f6f8f6;
        --surface: #ffffff;
        --text-dark: #1e293b;
        --text-medium: #64748b;
        --text-light: #94a3b8;
        --border: #e2e8f0;
        --danger: #ef4444;
        --success: #22c55e;
        --radius: 10px;
        --shadow: 0 1px 3px rgba(0,0,0,0.1);
        --shadow-lg: 0 10px 40px rgba(0,0,0,0.15);
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Inter', sans-serif;
        background: var(--background);
        color: var(--text-dark);
        height: 100vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    
    /* ===== HEADER ===== */
    .pos-header {
        background: var(--surface);
        border-bottom: 1px solid var(--border);
        padding: 16px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }
    
    .pos-header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .btn-back {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        background: var(--primary);
        color: white;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.2s;
    }
    
    .btn-back:hover {
        background: var(--primary-dark);
        transform: translateX(-2px);
    }
    
    .btn-back i {
        font-size: 20px;
    }
    
    .pos-logo {
        width: 48px;
        height: 48px;
        background: var(--primary-light);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 24px;
    }
    
    .pos-title h1 {
        font-size: 20px;
        font-weight: 800;
        color: var(--text-dark);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .pos-title p {
        font-size: 13px;
        color: var(--text-medium);
        font-weight: 500;
    }
    
    .pos-header-right {
        display: flex;
        align-items: center;
        gap: 24px;
        background: var(--background);
        padding: 10px 20px;
        border-radius: 12px;
        border: 1px solid var(--border);
    }
    
    .header-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .header-info i {
        color: var(--text-light);
        font-size: 16px;
    }
    
    .header-info-content .label {
        font-size: 10px;
        color: var(--text-light);
        text-transform: uppercase;
        font-weight: 600;
    }
    
    .header-info-content .value {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-dark);
    }
    
    .header-divider {
        width: 1px;
        height: 32px;
        background: var(--border);
    }
    
    /* ===== MAIN LAYOUT ===== */
    .pos-main {
        flex: 1;
        display: flex;
        overflow: hidden;
        height: calc(100vh - 80px);
    }
    
    /* ===== LEFT PANEL - Products ===== */
    .pos-left {
        flex: 3;
        display: flex;
        flex-direction: column;
        padding: 20px;
        overflow: hidden;
        gap: 16px;
        border-right: 1px solid var(--border);
        height: 100%;
    }
    
    /* Search Bar */
    .search-bar {
        position: relative;
    }
    
    .search-bar i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-light);
        font-size: 18px;
    }
    
    .search-bar input {
        width: 100%;
        padding: 14px 16px 14px 48px;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        font-size: 15px;
        background: var(--surface);
        transition: all 0.2s;
    }
    
    .search-bar input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-light);
    }
    
    .search-bar input::placeholder {
        color: var(--text-light);
    }
    
    /* Category Tabs */
    .category-tabs {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 4px;
    }
    
    .category-tabs::-webkit-scrollbar {
        height: 4px;
    }
    
    .category-tabs::-webkit-scrollbar-thumb {
        background: var(--border);
        border-radius: 4px;
    }
    
    .category-tab {
        padding: 10px 20px;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text-medium);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.2s;
    }
    
    .category-tab:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
    
    .category-tab.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    
    /* Product Grid */
    .product-grid {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 16px;
        overflow-y: auto;
        padding-right: 8px;
        padding-bottom: 20px;
        align-content: start;
        min-height: 0;
    }
    
    .product-grid::-webkit-scrollbar {
        width: 6px;
    }
    
    .product-grid::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 6px;
    }
    
    /* Product Card */
    .product-card {
        background: var(--surface);
        border-radius: 12px;
        border: 1px solid var(--border);
        padding: 12px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
    }
    
    .product-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary);
    }
    
    .product-image {
        aspect-ratio: 1;
        background: var(--background);
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 12px;
        position: relative;
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.2s;
    }
    
    .product-card:hover .product-image img {
        transform: scale(1.05);
    }
    
    .stock-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: rgba(0,0,0,0.7);
        color: white;
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 6px;
        backdrop-filter: blur(4px);
    }
    
    .product-name {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 4px;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .product-sku {
        font-size: 11px;
        color: var(--text-light);
        font-family: monospace;
        margin-bottom: 10px;
    }
    
    .product-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
    }
    
    .product-price {
        font-size: 15px;
        font-weight: 800;
        color: var(--primary);
    }
    
    .btn-add-product {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--primary-light);
        color: var(--primary);
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    
    .btn-add-product:hover {
        background: var(--primary);
        color: white;
    }
    
    /* ===== RIGHT PANEL - Cart ===== */
    .pos-right {
        flex: 2;
        min-width: 380px;
        max-width: 450px;
        display: flex;
        flex-direction: column;
        background: var(--surface);
        height: 100%;
        overflow: hidden;
    }
    
    /* Cart Header */
    .cart-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--background);
    }
    
    .cart-header h2 {
        font-size: 16px;
        font-weight: 700;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .cart-header h2 i {
        color: var(--primary);
    }
    
    .cart-count {
        background: var(--primary);
        color: white;
        font-size: 12px;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: 700;
    }
    
    .btn-clear-cart {
        background: none;
        border: none;
        color: var(--danger);
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 6px 12px;
        border-radius: 6px;
        transition: all 0.2s;
    }
    
    .btn-clear-cart:hover {
        background: #fef2f2;
    }
    
    /* Cart Table */
    .cart-items {
        flex: 1;
        overflow-y: auto;
        padding: 8px;
        min-height: 0;
        max-height: calc(100vh - 500px);
    }
    
    .cart-items::-webkit-scrollbar {
        width: 6px;
    }
    
    .cart-items::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 6px;
    }
    
    .cart-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .cart-table thead {
        position: sticky;
        top: 0;
        background: var(--surface);
        z-index: 5;
    }
    
    .cart-table th {
        text-align: left;
        padding: 10px 12px;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-light);
        text-transform: uppercase;
        border-bottom: 1px solid var(--border);
    }
    
    .cart-table td {
        padding: 12px;
        vertical-align: top;
        border-bottom: 1px solid var(--border);
    }
    
    .cart-table tr:hover {
        background: var(--background);
    }
    
    .cart-item-name {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 2px;
    }
    
    .cart-item-price {
        font-size: 12px;
        color: var(--text-light);
    }
    
    /* Quantity Control */
    .qty-control {
        display: flex;
        align-items: center;
        border: 1px solid var(--border);
        border-radius: 8px;
        overflow: hidden;
        background: var(--surface);
    }
    
    .qty-btn {
        width: 28px;
        height: 28px;
        border: none;
        background: none;
        color: var(--text-medium);
        cursor: pointer;
        font-size: 14px;
        transition: all 0.1s;
    }
    
    .qty-btn:hover {
        background: var(--background);
        color: var(--primary);
    }
    
    .qty-input {
        width: 32px;
        height: 28px;
        border: none;
        text-align: center;
        font-size: 13px;
        font-weight: 600;
        background: transparent;
    }
    
    .qty-input:focus {
        outline: none;
    }
    
    .item-total {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-dark);
        text-align: right;
    }
    
    .btn-remove-item {
        background: none;
        border: none;
        color: var(--border);
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        transition: all 0.2s;
    }
    
    .btn-remove-item:hover {
        color: var(--danger);
        background: #fef2f2;
    }
    
    .cart-empty {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--text-light);
        padding: 40px;
    }
    
    .cart-empty i {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.3;
    }
    
    /* Cart Summary & Payment */
    .cart-footer {
        background: var(--background);
        border-top: 1px solid var(--border);
        padding: 20px;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 14px;
        color: var(--text-medium);
    }
    
    .summary-row.total {
        font-size: 22px;
        font-weight: 800;
        color: var(--primary);
        margin-top: 16px;
        padding-top: 16px;
        border-top: 2px dashed var(--border);
    }
    
    /* Payment Section */
    .payment-section {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 16px;
        margin-top: 16px;
    }
    
    .payment-title {
        font-size: 12px;
        font-weight: 700;
        color: var(--text-medium);
        text-transform: uppercase;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .payment-title i {
        color: var(--text-light);
    }
    
    .payment-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }
    
    .payment-field label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-light);
        margin-bottom: 6px;
    }
    
    .payment-input-wrap {
        position: relative;
    }
    
    .payment-input-wrap input {
        width: 100%;
        padding: 10px 32px 10px 12px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 15px;
        font-weight: 700;
        text-align: right;
    }
    
    .payment-input-wrap input:focus {
        outline: none;
        border-color: var(--primary);
    }
    
    .payment-input-wrap input.readonly {
        background: var(--background);
        border-color: transparent;
    }
    
    .payment-input-wrap .currency {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 13px;
        color: var(--text-light);
        font-weight: 500;
    }
    
    /* Checkout Button */
    .btn-checkout {
        width: 100%;
        margin-top: 16px;
        padding: 16px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        box-shadow: 0 4px 14px rgba(73, 108, 44, 0.4);
        transition: all 0.2s;
    }
    
    .btn-checkout:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(73, 108, 44, 0.5);
    }
    
    .btn-checkout:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    
    /* Receipt Modal */
    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    
    .modal-backdrop.show {
        display: flex;
    }
    
    .receipt-modal {
        background: white;
        border-radius: 16px;
        width: 400px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: var(--shadow-lg);
    }
    
    .receipt-header {
        text-align: center;
        padding: 24px;
        border-bottom: 2px dashed var(--border);
    }
    
    .receipt-header h3 {
        font-size: 20px;
        color: var(--primary);
        margin-bottom: 8px;
    }
    
    .receipt-body {
        padding: 20px;
    }
    
    .receipt-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        font-size: 13px;
        border-bottom: 1px dotted var(--border);
    }
    
    .receipt-total {
        display: flex;
        justify-content: space-between;
        font-size: 18px;
        font-weight: 700;
        padding: 16px 0;
        border-top: 2px solid var(--border);
        margin-top: 8px;
    }
    
    .receipt-footer {
        text-align: center;
        padding: 20px;
        border-top: 2px dashed var(--border);
        color: var(--text-medium);
        font-size: 12px;
    }
    
    .receipt-actions {
        display: flex;
        gap: 12px;
        padding: 20px;
        border-top: 1px solid var(--border);
    }
    
    .receipt-actions button {
        flex: 1;
        padding: 12px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .btn-print {
        background: var(--primary);
        color: white;
        border: none;
    }
    
    .btn-close-modal {
        background: var(--background);
        color: var(--text-medium);
        border: 1px solid var(--border);
    }
    
    /* Toast */
    .toast {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 14px 20px;
        background: var(--text-dark);
        color: white;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        z-index: 2000;
        display: none;
        animation: slideIn 0.3s ease;
    }
    
    .toast.show {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .toast.success { background: var(--success); }
    .toast.error { background: var(--danger); }
    
    @keyframes slideIn {
        from { transform: translateY(100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    /* Responsive */
    @media (max-width: 900px) {
        .pos-main {
            flex-direction: column;
        }
        
        .pos-left {
            border-right: none;
            border-bottom: 1px solid var(--border);
        }
        
        .pos-right {
            max-width: 100%;
            min-width: 0;
        }
    }
    
    /* Print Styles - 80mm Thermal Receipt */
    @media print {
        @page {
            size: 80mm auto;
            margin: 0;
        }
        
        html, body {
            width: 80mm;
            margin: 0;
            padding: 0;
            background: white !important;
        }
        
        body * {
            visibility: hidden;
        }
        
        #receiptContent, #receiptContent * {
            visibility: visible;
        }
        
        #receiptContent {
            position: absolute;
            left: 0;
            top: 0;
            width: 80mm;
            padding: 3mm;
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.3;
            color: #000;
            background: white;
        }
        
        .receipt-actions, .modal-backdrop, .receipt-modal {
            display: contents !important;
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
        }
        
        /* Receipt specific print styles */
        #receiptContent .receipt-header-print {
            font-size: 11px;
        }
        
        #receiptContent .receipt-title-print {
            font-size: 14px;
            font-weight: bold;
        }
        
        #receiptContent .receipt-divider {
            border-top: 1px dashed #000;
            margin: 3px 0;
        }
        
        #receiptContent .receipt-total-print {
            font-size: 12px;
            font-weight: bold;
        }
    }
    </style>
</head>
<body>

<!-- Header -->
<header class="pos-header">
    <div class="pos-header-left">
        <a href="<?= BASE_URL ?>" class="btn-back" title="Quay lại">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="pos-logo">
            <i class="fas fa-store"></i>
        </div>
        <div class="pos-title">
            <h1>Bán hàng tại quầy</h1>
            <p>Hệ thống quản lý siêu thị FreshMart</p>
        </div>
    </div>
    <div class="pos-header-right">
        <div class="header-info">
            <i class="fas fa-user"></i>
            <div class="header-info-content">
                <div class="label">Thu ngân</div>
                <div class="value"><?= htmlspecialchars($cashier_name) ?></div>
            </div>
        </div>
        <div class="header-divider"></div>
        <div class="header-info">
            <i class="fas fa-calendar"></i>
            <div class="header-info-content">
                <div class="label">Ngày</div>
                <div class="value"><?= $current_date ?></div>
            </div>
        </div>
    </div>
</header>

<!-- Main Content -->
<main class="pos-main">
    <!-- Left Panel: Products -->
    <section class="pos-left">
        <!-- Search Bar -->
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Tìm kiếm tên sản phẩm hoặc nhập mã sản phẩm..." autofocus>
        </div>
        
        <!-- Category Tabs -->
        <div class="category-tabs">
            <button class="category-tab active" data-id="0">Tất cả</button>
            <?php foreach ($categories as $cat): ?>
                <button class="category-tab" data-id="<?= $cat['ID_dm'] ?>">
                    <?= htmlspecialchars($cat['Ten']) ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Product Grid -->
        <div class="product-grid" id="productGrid">
            <?php foreach ($products as $product): ?>
                <?php 
                $imgSrc = !empty($product['Hinh_anh']) 
                    ? (strpos($product['Hinh_anh'], 'http') === 0 ? $product['Hinh_anh'] : asset('img/products/' . $product['Hinh_anh']))
                    : asset('img/placeholder-product.png');
                ?>
                <div class="product-card" data-id="<?= $product['ID_sp'] ?>">
                    <div class="product-image">
                        <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($product['Ten']) ?>" loading="lazy" onerror="this.src='<?= asset('img/placeholder-product.png') ?>'">
                        <span class="stock-badge">Kho: <?= $product['So_luong_ton'] ?></span>
                    </div>
                    <div class="product-name"><?= htmlspecialchars($product['Ten']) ?></div>
                    <div class="product-sku">MSP: <?= $product['Ma_hien_thi'] ?></div>
                    <div class="product-footer">
                        <span class="product-price"><?= number_format($product['Gia_tien'], 0, ',', '.') ?>đ</span>
                        <button class="btn-add-product" onclick="addToCart(<?= $product['ID_sp'] ?>)">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Right Panel: Cart -->
    <section class="pos-right">
        <!-- Cart Header -->
        <div class="cart-header">
            <h2>
                <i class="fas fa-shopping-cart"></i>
                GIỎ HÀNG (<span id="cartCount"><?= count($cart) ?></span>)
            </h2>
            <button class="btn-clear-cart" onclick="clearCart()">
                <i class="fas fa-trash-alt"></i> XÓA GIỎ
            </button>
        </div>
        
        <!-- Cart Items -->
        <div class="cart-items" id="cartItems">
            <?php if (empty($cart)): ?>
                <div class="cart-empty">
                    <i class="fas fa-shopping-basket"></i>
                    <p>Giỏ hàng trống</p>
                </div>
            <?php else: ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th style="text-align: center;">SL</th>
                            <th style="text-align: right;">Thành tiền</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $item): ?>
                        <tr data-id="<?= $item['ID_sp'] ?>">
                            <td>
                                <div class="cart-item-name"><?= htmlspecialchars($item['Ten']) ?></div>
                                <div class="cart-item-price"><?= number_format($item['Gia_tien'], 0, ',', '.') ?>đ</div>
                            </td>
                            <td>
                                <div class="qty-control">
                                    <button class="qty-btn" onclick="updateQty(<?= $item['ID_sp'] ?>, -1)">−</button>
                                    <input type="text" class="qty-input" value="<?= $item['So_luong'] ?>" readonly>
                                    <button class="qty-btn" onclick="updateQty(<?= $item['ID_sp'] ?>, 1)">+</button>
                                </div>
                            </td>
                            <td class="item-total"><?= number_format($item['Thanh_tien'], 0, ',', '.') ?>đ</td>
                            <td>
                                <button class="btn-remove-item" onclick="removeItem(<?= $item['ID_sp'] ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Cart Footer -->
        <div class="cart-footer">
            <div class="summary-row">
                <span>Tạm tính</span>
                <span id="subtotal"><?= number_format($total, 0, ',', '.') ?>đ</span>
            </div>
            <div class="summary-row total">
                <span>TỔNG CỘNG</span>
                <span id="cartTotal"><?= number_format($total, 0, ',', '.') ?>đ</span>
            </div>
            
            <!-- Payment Section -->
            <div class="payment-section">
                <div class="payment-title">
                    <i class="fas fa-money-bill-wave"></i>
                    Thanh toán tiền mặt
                </div>
                <div class="payment-grid">
                    <div class="payment-field">
                        <label>Khách đưa</label>
                        <div class="payment-input-wrap" style="position: relative;">
                            <input type="text" id="cashReceived" placeholder="" inputmode="numeric" style="color: transparent; caret-color: #1e293b;">
                            <div id="cashDisplay" style="position: absolute; top: 0; left: 0; right: 32px; bottom: 0; display: flex; align-items: center; justify-content: flex-end; font-size: 15px; font-weight: 700; pointer-events: none; color: #1e293b;">0</div>
                            <span class="currency">đ</span>
                        </div>
                    </div>
                    <div class="payment-field">
                        <label>Tiền thừa</label>
                        <div class="payment-input-wrap">
                            <input type="text" id="changeAmount" class="readonly" value="0" readonly>
                            <span class="currency">đ</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Checkout Button -->
            <button class="btn-checkout" id="btnCheckout" onclick="checkout()">
                <i class="fas fa-receipt"></i>
                THANH TOÁN & IN HÓA ĐƠN
            </button>
        </div>
    </section>
</main>

<!-- Receipt Modal -->
<div class="modal-backdrop" id="receiptModal">
    <div class="receipt-modal" style="font-family: 'Courier New', Consolas, monospace; width: 380px; max-width: 95vw;">
        <!-- Printable Receipt Content - A6 paper format -->
        <div id="receiptContent" style="padding: 15px 12px; background: white; font-size: 14px; line-height: 1.6;">
            <!-- Header -->
            <div style="text-align: center; margin-bottom: 12px;">
                <div style="font-size: 12px; letter-spacing: 1px;">================================</div>
                <div style="font-size: 18px; font-weight: bold; margin: 6px 0;">HÓA ĐƠN BÁN HÀNG</div>
                <div style="font-size: 16px; font-weight: bold;">FRESHMART</div>
                <div style="font-size: 11px; margin-top: 4px;">Siêu thị thực phẩm tươi sống</div>
                <div style="font-size: 12px; letter-spacing: 1px;">================================</div>
            </div>
            
            <!-- Info -->
            <div style="font-size: 13px; margin-bottom: 10px;">
                <div>Khách hàng: Khách lẻ</div>
                <div id="receiptDateTime">Ngày: --/--/---- --:--</div>
                <div id="receiptCashierInfo">Thu ngân: ---</div>
                <div id="receiptOrderId" style="display: none;">Mã HĐ: ---</div>
            </div>
            
            <div style="border-top: 1px dashed #000; margin: 8px 0;"></div>
            
            <!-- Products -->
            <div style="font-size: 13px;">
                <div style="font-weight: bold; margin-bottom: 6px;">SẢN PHẨM</div>
                <div style="border-bottom: 1px dashed #000; margin-bottom: 6px;"></div>
                <div id="receiptItemsList">
                    <!-- Items will be inserted here -->
                </div>
            </div>
            
            <div style="border-top: 1px dashed #000; margin: 8px 0;"></div>
            
            <!-- Summary -->
            <div style="font-size: 13px;">
                <div style="display: flex; justify-content: space-between;">
                    <span>Tạm tính:</span>
                    <span id="receiptSubtotal">0 đ</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Thuế (0%):</span>
                    <span>0 đ</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Giảm giá:</span>
                    <span>0 đ</span>
                </div>
            </div>
            
            <div style="border-top: 1px dashed #000; margin: 8px 0;"></div>
            
            <!-- Total -->
            <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: bold; margin: 6px 0;">
                <span>TỔNG CỘNG:</span>
                <span id="receiptTotal">0 đ</span>
            </div>
            
            <div style="border-top: 1px dashed #000; margin: 8px 0;"></div>
            
            <!-- Payment -->
            <div style="font-size: 13px;">
                <div style="display: flex; justify-content: space-between;">
                    <span>Tiền khách đưa:</span>
                    <span id="receiptCash">0 đ</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-weight: bold;">
                    <span>Tiền thừa:</span>
                    <span id="receiptChange">0 đ</span>
                </div>
            </div>
            
            <!-- Footer -->
            <div style="text-align: center; margin-top: 12px; padding-top: 8px; border-top: 1px dashed #000;">
                <div style="font-size: 12px; letter-spacing: 1px;">================================</div>
                <div style="font-size: 14px; margin: 6px 0;">Cảm ơn quý khách!</div>
                <div style="font-size: 14px;">Hẹn gặp lại!</div>
                <div style="font-size: 12px; letter-spacing: 1px;">================================</div>
            </div>
        </div>
        
        <!-- Action Buttons (not printed) -->
        <div class="receipt-actions" style="display: flex; gap: 12px; padding: 15px; border-top: 1px solid #e2e8f0;">
            <button class="btn-close-modal" onclick="closeReceipt()" style="flex: 1; padding: 12px; border-radius: 10px; background: #f6f8f6; color: #64748b; border: 1px solid #e2e8f0; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fas fa-times"></i> Đóng
            </button>
            <button class="btn-print" onclick="printReceipt()" style="flex: 1; padding: 12px; border-radius: 10px; background: #496C2C; color: white; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="fas fa-print"></i> In hóa đơn
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const CSRF_TOKEN = '<?= $csrf_token ?>';
const DEFAULT_IMG = '<?= asset('img/placeholder-product.png') ?>';

let currentTotal = <?= $total ?>;
let searchTimeout = null;
let currentCategory = 0;

// ===== LỌC DANH MỤC =====
document.querySelectorAll('.category-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        currentCategory = parseInt(this.dataset.id);
        filterByCategory(currentCategory);
    });
});

async function filterByCategory(categoryId) {
    try {
        const res = await fetch(`${BASE_URL}/pos/filterByCategory?category_id=${categoryId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        
        if (data.success) {
            renderProducts(data.products);
        }
    } catch (err) {
        console.error('Filter error:', err);
    }
}

// ===== TÌM KIẾM =====
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    if (query.length < 1) {
        filterByCategory(currentCategory);
        return;
    }
    
    searchTimeout = setTimeout(() => searchProducts(query), 300);
});

async function searchProducts(query) {
    try {
        const res = await fetch(`${BASE_URL}/pos/search?q=${encodeURIComponent(query)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        
        if (data.products && data.products.length > 0) {
            renderProducts(data.products);
        } else {
            document.getElementById('productGrid').innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: var(--text-light);">
                    <i class="fas fa-search" style="font-size: 40px; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>Không tìm thấy sản phẩm</p>
                </div>
            `;
        }
    } catch (err) {
        console.error('Search error:', err);
    }
}

function renderProducts(products) {
    const html = products.map(p => {
        let imgSrc = DEFAULT_IMG;
        if (p.Hinh_anh) {
            imgSrc = p.Hinh_anh.startsWith('http') ? p.Hinh_anh : BASE_URL + '/assets/img/products/' + p.Hinh_anh;
        }
        
        return `
        <div class="product-card" data-id="${p.ID_sp}">
            <div class="product-image">
                <img src="${imgSrc}" alt="${escapeHtml(p.Ten)}" onerror="this.src='${DEFAULT_IMG}'">
                <span class="stock-badge">Kho: ${p.So_luong_ton}</span>
            </div>
            <div class="product-name">${escapeHtml(p.Ten)}</div>
            <div class="product-sku">SKU: ${p.Ma_hien_thi || 'N/A'}</div>
            <div class="product-footer">
                <span class="product-price">${formatPrice(p.Gia_tien)}</span>
                <button class="btn-add-product" onclick="addToCart(${p.ID_sp})">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        `;
    }).join('');
    
    document.getElementById('productGrid').innerHTML = html;
}

// ===== CHỨC NĂNG GIỎ HÀNG =====
async function addToCart(productId) {
    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', 1);
        formData.append('csrf_token', CSRF_TOKEN);
        
        const res = await fetch(`${BASE_URL}/pos/addToCart`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        
        if (data.success) {
            renderCart(data.cart, data.total);
            showToast('Đã thêm vào giỏ', 'success');
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (err) {
        console.error('Add to cart error:', err);
        showToast('Có lỗi kết nối', 'error');
    }
}

async function updateQty(productId, change) {
    const row = document.querySelector(`tr[data-id="${productId}"]`);
    const qtyInput = row.querySelector('.qty-input');
    const newQty = parseInt(qtyInput.value) + change;
    
    if (newQty < 1) {
        removeItem(productId);
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', newQty);
        formData.append('csrf_token', CSRF_TOKEN);
        
        const res = await fetch(`${BASE_URL}/pos/updateQuantity`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        
        if (data.success) {
            renderCart(data.cart, data.total);
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (err) {
        console.error('Update qty error:', err);
    }
}

async function removeItem(productId) {
    try {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('csrf_token', CSRF_TOKEN);
        
        const res = await fetch(`${BASE_URL}/pos/removeFromCart`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        
        if (data.success) {
            renderCart(data.cart, data.total);
            showToast('Đã xóa sản phẩm', 'info');
        }
    } catch (err) {
        console.error('Remove item error:', err);
    }
}

async function clearCart() {
    if (!confirm('Xóa toàn bộ giỏ hàng?')) return;
    
    try {
        const formData = new FormData();
        formData.append('csrf_token', CSRF_TOKEN);
        
        const res = await fetch(`${BASE_URL}/pos/clearCart`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        
        if (data.success) {
            renderCart([], 0);
            showToast('Đã xóa giỏ hàng', 'info');
        }
    } catch (err) {
        console.error('Clear cart error:', err);
    }
}

function renderCart(cart, total) {
    currentTotal = total;
    document.getElementById('cartCount').textContent = cart.length;
    document.getElementById('subtotal').textContent = formatPrice(total);
    document.getElementById('cartTotal').textContent = formatPrice(total);
    
    if (cart.length === 0) {
        document.getElementById('cartItems').innerHTML = `
            <div class="cart-empty">
                <i class="fas fa-shopping-basket"></i>
                <p>Giỏ hàng trống</p>
            </div>
        `;
        document.getElementById('btnCheckout').disabled = true;
    } else {
        const html = `
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th style="text-align: center;">SL</th>
                        <th style="text-align: right;">Thành tiền</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    ${cart.map(item => `
                        <tr data-id="${item.ID_sp}">
                            <td>
                                <div class="cart-item-name">${escapeHtml(item.Ten)}</div>
                                <div class="cart-item-price">${formatPrice(item.Gia_tien)}</div>
                            </td>
                            <td>
                                <div class="qty-control">
                                    <button class="qty-btn" onclick="updateQty(${item.ID_sp}, -1)">−</button>
                                    <input type="text" class="qty-input" value="${item.So_luong}" readonly>
                                    <button class="qty-btn" onclick="updateQty(${item.ID_sp}, 1)">+</button>
                                </div>
                            </td>
                            <td class="item-total">${formatPrice(item.Thanh_tien)}</td>
                            <td>
                                <button class="btn-remove-item" onclick="removeItem(${item.ID_sp})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        
        document.getElementById('cartItems').innerHTML = html;
        updateCheckoutButton();
    }
    
    calculateChange();
}

function updateCheckoutButton() {
    const btn = document.getElementById('btnCheckout');
    const cartCount = parseInt(document.getElementById('cartCount').textContent || '0');
    btn.disabled = cartCount === 0;
}

// ===== THANH TOÁN =====
const cashInput = document.getElementById('cashReceived');
const cashDisplay = document.getElementById('cashDisplay');

// PHƯƠNG PHÁP KẾT HỢP - Input chỉ chứa số thuần, display overlay hiển thị format
cashInput.addEventListener('input', function(e) {
    // Chỉ giữ số trong input (không có dấu chấm)
    const raw = this.value.replace(/\D/g, '');
    this.value = raw;
    
    // Format và hiển thị riêng trên overlay
    const num = parseInt(raw) || 0;
    const formatted = num > 0 ? num.toLocaleString('vi-VN') : '0';
    cashDisplay.textContent = formatted;
    
    calculateChange();
});

function getCashValue() {
    const val = document.getElementById('cashReceived').value;
    return parseInt(val.replace(/\D/g, '')) || 0;
}

function calculateChange() {
    const cash = getCashValue();
    const change = cash - currentTotal;
    
    // Format tiền thừa với dấu chấm ngăn cách
    if (change >= 0) {
        document.getElementById('changeAmount').value = new Intl.NumberFormat('vi-VN').format(change);
    } else {
        document.getElementById('changeAmount').value = '0';
    }
}

async function checkout() {
    const cartCount = parseInt(document.getElementById('cartCount').textContent);
    
    if (cartCount === 0) {
        showToast('Giỏ hàng trống', 'error');
        return;
    }

    const cash = getCashValue();
    
    if (cash < currentTotal) {
        showToast('Số tiền khách đưa chưa đủ (Thiếu: ' + formatPriceRaw(currentTotal - cash) + 'đ)', 'error');
        return;
    }
    
    const btn = document.getElementById('btnCheckout');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Đang xử lý...';
    
    try {
        const formData = new FormData();
        formData.append('cash_received', cash);
        formData.append('csrf_token', CSRF_TOKEN);
        
        const res = await fetch(`${BASE_URL}/pos/checkout`, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        
        if (data.success) {
            showReceipt(data);
            renderCart([], 0);
            document.getElementById('cashReceived').value = '';
            document.getElementById('cashDisplay').textContent = '0';
            calculateChange();
            showToast('Thanh toán thành công!', 'success');
        } else {
            showToast(data.message || 'Có lỗi xảy ra', 'error');
        }
    } catch (err) {
        console.error('Checkout error:', err);
        showToast('Lỗi kết nối: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ===== HÓA ĐƠN =====
function showReceipt(data) {
    // Cài đặt ngày và thông tin thu ngân
    document.getElementById('receiptDateTime').textContent = 'Ngày: ' + (data.date || new Date().toLocaleString('vi-VN'));
    document.getElementById('receiptCashierInfo').textContent = 'Thu ngân: ' + (data.cashier_name || 'N/A');
    
    // Hiển thị mã đơn hàng nếu có
    if (data.order_id) {
        const orderIdEl = document.getElementById('receiptOrderId');
        orderIdEl.textContent = 'Mã HĐ: #' + data.order_id;
        orderIdEl.style.display = 'block';
    }
    
    // Tạo danh sách sản phẩm - định dạng gọn cho giấy 80mm
    let itemsHtml = '';
    if (data.items && data.items.length > 0) {
        data.items.forEach(item => {
            const qty = item.So_luong || 1;
            const price = item.Gia_tien || 0;
            const lineTotal = item.Thanh_tien || (qty * price);
            const name = item.Ten || 'Sản phẩm';
            
            // Định dạng gọn: Tên sản phẩm ở dòng 1, số lượng x giá = tổng ở dòng 2
            itemsHtml += `
                <div style="margin-bottom: 8px; font-size: 13px;">
                    <div style="font-weight: 600; word-wrap: break-word;">${escapeHtml(name)}</div>
                    <div style="display: flex; justify-content: space-between; padding-left: 10px; color: #333;">
                        <span>${qty} x ${formatPriceRaw(price)}</span>
                        <span>= ${formatPriceRaw(lineTotal)} đ</span>
                    </div>
                </div>
            `;
        });
    }
    document.getElementById('receiptItemsList').innerHTML = itemsHtml;
    
    // Cài đặt tổng
    document.getElementById('receiptSubtotal').textContent = formatPriceRaw(data.total) + ' đ';
    document.getElementById('receiptTotal').textContent = formatPriceRaw(data.total) + ' đ';
    document.getElementById('receiptCash').textContent = formatPriceRaw(data.cash_received) + ' đ';
    document.getElementById('receiptChange').textContent = formatPriceRaw(data.change) + ' đ';
    
    // Lưu lại để in
    window.lastReceipt = data;
    
    // Hiển thị modal
    document.getElementById('receiptModal').classList.add('show');
}

function closeReceipt() {
    document.getElementById('receiptModal').classList.remove('show');
}

function printReceipt() {
    // Lấy nội dung hóa đơn
    const receiptContent = document.getElementById('receiptContent').innerHTML;
    
    // Tạo cửa sổ mới kích thước giấy A6 (105mm x 148mm)
    // A6 = 105mm x 148mm ≈ 397px x 559px ở 96dpi
    const printWindow = window.open('', 'PRINT_RECEIPT', 'width=420,height=600,scrollbars=yes');
    
    // Tạo tài liệu in với style nhúng cho A6
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Hóa đơn</title>
            <style>
                @page {
                    size: 105mm auto;  /* Chiều rộng cố định, chiều cao TỰ ĐỘNG theo nội dung */
                    margin: 3mm;
                }
                
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                html, body {
                    width: 105mm;
                    font-family: 'Courier New', Consolas, monospace;
                    font-size: 14px;
                    line-height: 1.6;
                    color: #000;
                    background: #fff;
                }
                
                .receipt-wrapper {
                    width: 100%;
                    padding: 2mm;
                    background: white;
                }
                
                /* Override any flex that might cause issues */
                div[style*="display: flex"] {
                    display: flex !important;
                }
                
                @media print {
                    html, body {
                        width: 105mm;
                        margin: 0;
                        padding: 0;
                    }
                    
                    .receipt-wrapper {
                        width: 100%;
                    }
                    }
                    
                    .no-print {
                        display: none !important;
                    }
                }
            </style>
        </head>
        <body>
            <div class="receipt-wrapper">
                ${receiptContent}
            </div>
            <script>
                // Tự động in khi tải xong
                window.onload = function() {
                    window.print();
                    // Đóng cửa sổ sau khi in
                    window.onafterprint = function() {
                        window.close();
                    };
                    // Dự phòng: đóng sau 3 giây nếu onafterprint không hỗ trợ
                    setTimeout(function() {
                        window.close();
                    }, 3000);
                };
            <\/script>
        </body>
        </html>
    `);
    
    printWindow.document.close();
}

// ===== TIỆN ÍCH =====
function formatPrice(num) {
    return new Intl.NumberFormat('vi-VN').format(num) + 'đ';
}

function formatPriceRaw(num) {
    return new Intl.NumberFormat('vi-VN').format(num);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast show ' + type;
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}
</script>

</body>
</html>
