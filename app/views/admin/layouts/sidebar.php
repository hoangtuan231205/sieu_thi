<?php
/**
 * Modern Admin Sidebar - Accordion Style
 * Design: Tree structure with collapsible groups
 * Theme: #7BC043 (Lime Green)
 */

$currentUri = $_SERVER['REQUEST_URI'] ?? '';

// Helper to check if current URL matches
function isActive($pattern) {
    global $currentUri;
    return strpos($currentUri, $pattern) !== false ? 'active' : '';
}

// Check for dashboard (exact match)
function isDashboard() {
    global $currentUri;
    return preg_match('#/admin/?$#', $currentUri) || strpos($currentUri, '/admin/dashboard') !== false;
}

// Check if any submenu item is active
function isGroupActive($patterns) {
    global $currentUri;
    foreach ($patterns as $pattern) {
        if (strpos($currentUri, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

// Get current user info
$userName = $_SESSION['user_name'] ?? 'Admin';
$userInitials = strtoupper(substr($userName, 0, 2));

// Define menu groups for auto-expand
$khoHangPatterns = ['warehouse', 'products', 'report-expiry', 'disposal'];
$quanLyPatterns = ['orders', 'categories', 'suppliers'];
?>

<aside class="sidebar-modern">
    <!-- Logo -->
    <a href="<?= BASE_URL ?>" class="sidebar-logo" style="text-decoration: none; color: inherit;">
        <div class="sidebar-logo-icon">
            <i class="fas fa-store"></i>
        </div>
        <h1>FreshMart</h1>
    </a>
    
    <!-- Navigation -->
    <nav class="sidebar-nav">
        <!-- Quay lại website -->
        <a href="<?= BASE_URL ?>" class="sidebar-nav-item" style="background: rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 15px;">
            <i class="fas fa-arrow-left"></i>
            <span>Quay lại website</span>
        </a>

        <!-- Trang chủ -->
        <a href="<?= BASE_URL ?>/admin" class="sidebar-nav-item <?= isDashboard() ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Trang quản lý</span>
        </a>
        
        <!-- Kho hàng (Accordion) -->
        <div class="menu-group <?= isGroupActive($khoHangPatterns) ? 'open' : '' ?>" data-group="kho-hang">
            <div class="menu-group-toggle">
                <i class="fas fa-boxes-stacked"></i>
                <span>Kho hàng</span>
                <i class="fas fa-chevron-right chevron"></i>
            </div>
            <div class="submenu">
                <a href="<?= BASE_URL ?>/warehouse" class="submenu-item <?= isActive('warehouse') ?>">
                    <i class="fas fa-truck-loading"></i>
                    <span>Phiếu nhập</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/products" class="submenu-item <?= isActive('products') ?>">
                    <i class="fas fa-box"></i>
                    <span>Sản phẩm</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/report-expiry" class="submenu-item <?= isActive('report-expiry') ?>">
                    <i class="fas fa-calendar-xmark"></i>
                    <span>Cảnh báo hết hạn</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/disposals" class="submenu-item <?= isActive('disposal') ?>">
                    <i class="fas fa-trash-can"></i>
                    <span>Phiếu hủy</span>
                </a>
            </div>
        </div>
        
        <!-- Quản lí (Accordion) -->
        <div class="menu-group <?= isGroupActive($quanLyPatterns) ? 'open' : '' ?>" data-group="quan-ly">
            <div class="menu-group-toggle">
                <i class="fas fa-cog"></i>
                <span>Quản lí</span>
                <i class="fas fa-chevron-right chevron"></i>
            </div>
            <div class="submenu">
                <a href="<?= BASE_URL ?>/admin/orders" class="submenu-item <?= isActive('orders') ?>">
                    <i class="fas fa-truck-fast"></i>
                    <span>Vận chuyển</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/categories" class="submenu-item <?= isActive('categories') ?>">
                    <i class="fas fa-layer-group"></i>
                    <span>Danh mục</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/suppliers" class="submenu-item <?= isActive('suppliers') ?>">
                    <i class="fas fa-handshake"></i>
                    <span>Nhà cung cấp</span>
                </a>
            </div>
        </div>
        
        <!-- Doanh thu -->
        <a href="<?= BASE_URL ?>/admin/report-profit" class="sidebar-nav-item <?= isActive('report-profit') ?>">
            <i class="fas fa-chart-line"></i>
            <span>Doanh thu</span>
        </a>
        
        <!-- Khách hàng -->
        <a href="<?= BASE_URL ?>/admin/users" class="sidebar-nav-item <?= isActive('users') ?>">
            <i class="fas fa-users"></i>
            <span>Khách hàng</span>
        </a>
        
    </nav>
    
    <!-- User Section -->
    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <?= $userInitials ?>
        </div>
        <div class="sidebar-user-info">
            <h4><?= htmlspecialchars($userName) ?></h4>
            <p>Quản lý cửa hàng</p>
        </div>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuGroups = document.querySelectorAll('.menu-group');
    const STORAGE_KEY = 'admin_sidebar_state';
    
    // Load saved state from localStorage
    function loadState() {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            try {
                return JSON.parse(saved);
            } catch (e) {
                return {};
            }
        }
        return {};
    }
    
    // Save state to localStorage
    function saveState(state) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    }
    
    // Initialize menu groups
    const state = loadState();
    
    menuGroups.forEach(group => {
        const groupId = group.dataset.group;
        const toggle = group.querySelector('.menu-group-toggle');
        
        // Initial state from PHP (class 'open' already added if active)
        const hasActiveItem = group.classList.contains('open');
        const savedOpen = state[groupId] === true;
        
        // Helper to check if any OTHER group is already open (to enforce strict accordion)
        // But for initialization, we prioritize PHP active state.
        
        if (hasActiveItem) {
            group.classList.add('open');
        } else if (savedOpen) {
             // Only open if no other group is currently open? 
             // Or just open it (and let user click to close others). 
             // For simplicity and user preference stability, let's open it.
            group.classList.add('open');
        }

        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const isOpen = group.classList.contains('open');
            
            // Close ALL groups first (Strict Accordion)
            menuGroups.forEach(g => {
                g.classList.remove('open');
            });
            
            // If it wasn't open, open it now
            if (!isOpen) {
                group.classList.add('open');
            }
            
            // Save state (only the currently open one)
            const newState = {};
            menuGroups.forEach(g => {
                if (g.classList.contains('open')) {
                    newState[g.dataset.group] = true;
                }
            });
            saveState(newState);
        });
    });
});
</script>
