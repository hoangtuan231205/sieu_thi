<?php
/**
 * ADMIN - REPORTS TABS NAVIGATION COMPONENT
 * Reusable horizontal tab navigation for all report pages
 * 
 * Include this in report pages: <?php include __DIR__ . '/report_tabs.php'; ?>
 * 
 * Theme: Lime Green (#7BC043) + Navy (#2D3657)
 */

// Detect current page
$current_page = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);
$current_page = strtok($current_page, '?');

$report_tabs = [
    'report-profit' => ['icon' => 'fas fa-dollar-sign', 'label' => 'Doanh thu & Lợi nhuận', 'url' => BASE_URL . '/admin/report-profit'],
    'report-expiry' => ['icon' => 'fas fa-clock', 'label' => 'Hàng sắp hết hạn', 'url' => BASE_URL . '/admin/report-expiry'],
];
?>

<style>
/* ===== REPORT TABS NAVIGATION ===== */
.report-tabs-container {
    margin-bottom: 24px;
}

.report-tabs {
    display: flex;
    gap: 4px;
    background: #fff;
    padding: 6px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    overflow-x: auto;
}

.report-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    background: transparent;
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
    border-radius: 8px;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.2s ease;
    cursor: pointer;
}

.report-tab:hover {
    background: #f3f4f6;
    color: #374151;
    text-decoration: none;
}

.report-tab.active {
    background: var(--admin-primary, #7BC043);
    color: white;
    box-shadow: 0 2px 4px rgba(123, 192, 67, 0.3);
}

.report-tab.active:hover {
    background: #6aaa3a;
    color: white;
}

.report-tab i {
    font-size: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .report-tabs {
        padding: 4px;
        gap: 2px;
    }
    
    .report-tab {
        padding: 10px 14px;
        font-size: 13px;
    }
    
    .report-tab span {
        display: none;
    }
    
    .report-tab i {
        font-size: 18px;
    }
}
</style>

<div class="report-tabs-container">
    <nav class="report-tabs">
        <?php foreach ($report_tabs as $key => $tab): ?>
            <?php 
            $is_active = ($current_page === $key) || 
                         ($key === 'reports' && $current_page === 'reports') ||
                         (strpos($current_page, $key) !== false);
            ?>
            <a href="<?= $tab['url'] ?>" class="report-tab <?= $is_active ? 'active' : '' ?>">
                <i class="<?= $tab['icon'] ?>"></i>
                <span><?= $tab['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</div>
