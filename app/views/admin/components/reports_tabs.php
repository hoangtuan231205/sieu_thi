<?php
/**
 * Reports Section Tabs Component
 * Include this at the top of: reports.php, report_profit.php, warehouse/history pages
 */

$currentUri = $_SERVER['REQUEST_URI'] ?? '';

if (!function_exists('isTabActive')) {
    function isTabActive($pattern) {
        global $currentUri;
        return strpos($currentUri, $pattern) !== false ? 'active' : '';
    }
}
?>

<style>
.section-tabs {
    display: flex;
    gap: 4px;
    background: #f8fafc;
    padding: 6px;
    border-radius: 12px;
    margin-bottom: 24px;
    border: 1px solid #e2e8f0;
}

.section-tabs .tab-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.section-tabs .tab-item:hover {
    background: rgba(123, 192, 67, 0.1);
    color: #7BC043;
}

.section-tabs .tab-item.active {
    background: linear-gradient(135deg, #7BC043 0%, #5a9a32 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(123, 192, 67, 0.3);
}

.section-tabs .tab-item i {
    font-size: 14px;
}
</style>

<div class="section-tabs">
    <a href="<?= BASE_URL ?>/admin/report-profit" class="tab-item <?= isTabActive('report-profit') ?>">
        <i class="fas fa-dollar-sign"></i> Doanh thu & Lợi nhuận
    </a>
    <a href="<?= BASE_URL ?>/admin/report-expiry" class="tab-item <?= isTabActive('report-expiry') ?>">
        <i class="fas fa-clock"></i> Hàng sắp hết hạn
    </a>
</div>
