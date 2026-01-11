<?php
/**
 * Modern Admin Dashboard
 * Standardized UI - Theme Woodland
 * Reference: admin-modern.css
 */
include __DIR__ . '/layouts/header.php';

// Safe data extraction with defaults
$stats = $stats ?? [];
$low_stock_products = $low_stock_products ?? [];
$recent_orders = $recent_orders ?? [];
$category_stats = $category_stats ?? [];
$expiring_products = $expiring_products ?? [];
$chart_data = $chart_data ?? [];
$profit_data = $profit_data ?? [];

// Extract stats with defaults
$todayRevenue = $stats['doanh_thu'] ?? 0;
$todayOrders = $stats['don_hang'] ?? 0;
$totalProducts = $stats['san_pham'] ?? 0;
$lowStockCount = count($low_stock_products);
$expiringCount = count($expiring_products);
$warningCount = $lowStockCount + $expiringCount;

// Format currency
function formatVND($amount)
{
    return number_format($amount, 0, ',', '.') . ' ₫';
}

// Today's date
$today = date('d/m/Y');
?>

<link rel="stylesheet" href="<?= asset('css/admin-modern.css') ?>">

<style>
/* Custom dashboard styles extension */
.charts-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}
@media (max-width: 1024px) {
    .charts-layout { grid-template-columns: 1fr; }
}

.alert-banner {
    background: #fff;
    border-radius: var(--border-radius);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 24px;
    border: 1px solid var(--admin-border);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

.alert-banner.critical { border-left: 4px solid var(--admin-danger); background: #fef2f2; }
.alert-banner.warning { border-left: 4px solid var(--admin-warning); background: #fffbeb; }

.alert-icon-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.alert-banner.critical .alert-icon-circle { background: #fee2e2; color: #ef4444; }
.alert-banner.warning .alert-icon-circle { background: #fef3c7; color: #f59e0b; }

.chart-legend-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--admin-border);
}
.chart-legend-item:last-child { border-bottom: none; }
</style>

<div class="admin-modern">
    <div class="admin-modern-container">
        
        <!-- Header Section -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Dashboard Tổng Quan</h1>
                <p class="admin-page-subtitle">Hiệu suất kinh doanh ngày <strong><?= $today ?></strong></p>
            </div>
            <div class="admin-header-actions">
                <button class="btn-admin-secondary" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i> Làm mới
                </button>

            </div>
        </div>

        <?php
        // Count critical (expired or <= 7 days) and warning (8-30 days)
        $criticalCount = 0;
        $warningCount30 = 0;
        foreach ($expiring_products as $exp) {
            $daysLeft = $exp['Ngay_con_lai'] ?? 30;
            if ($daysLeft <= 7) {
                $criticalCount++;
            } else {
                $warningCount30++;
            }
        }
        $totalExpiryCount = count($expiring_products);
        ?>





        <!-- Stats Row -->
        <div class="stat-cards-row">
            <!-- Revenue Card -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Doanh thu hôm nay</h4>
                        <p class="stat-card-value"><?= formatVND($todayRevenue) ?></p>
                    </div>
                    <div class="stat-card-icon success">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge success">
                        <i class="fas fa-arrow-up"></i> Tăng trưởng
                    </span>
                </div>
            </div>

            <!-- Orders Card -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Đơn hàng mới</h4>
                        <p class="stat-card-value"><?= $todayOrders ?></p>
                    </div>
                    <div class="stat-card-icon info">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge normal">
                        <strong><?= $stats['don_cho_xu_ly'] ?? 0 ?></strong> đang chờ xử lý
                    </span>
                </div>
            </div>

            <!-- Products Card -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Sản phẩm Active</h4>
                        <p class="stat-card-value"><?= number_format($totalProducts) ?></p>
                    </div>
                    <div class="stat-card-icon warning">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge normal">
                        <strong><?= $stats['sp_moi_tuan'] ?? 0 ?></strong> mới tuần này
                    </span>
                </div>
            </div>

            <!-- Warnings Card -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Cảnh báo tồn kho</h4>
                        <p class="stat-card-value" style="color: var(--admin-danger);"><?= $warningCount ?></p>
                    </div>
                    <div class="stat-card-icon danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge danger">
                        <?= $lowStockCount ?> sắp hết hàng
                    </span>
                </div>
            </div>
        </div>

        <!-- Charts Layout -->
        <div class="charts-layout">
            <!-- Revenue Chart -->
            <div class="admin-card">
                <div class="admin-card-header" style="justify-content: space-between;">
                    <h3 class="admin-card-title">Xu hướng doanh thu</h3>
                    <a href="<?= BASE_URL ?>/admin/report-profit" style="font-size: 13px; color: var(--admin-primary); text-decoration: none; font-weight: 600;">Xem chi tiết</a>
                </div>
                <div class="admin-card-body">
                    <div style="height: 300px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Category Chart -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3 class="admin-card-title">Tỷ lệ tồn kho</h3>
                </div>
                <div class="admin-card-body">
                    <div style="height: 200px; margin-bottom: 20px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    
                    <!-- Legend -->
                     <div class="category-legend">
                        <?php
                        $colors = ['#7BC043', '#22d3ee', '#818cf8', '#f59e0b', '#ec4899'];
                        $i = 0;
                        foreach ($category_stats as $cat):
                            $percent = $cat['percent'] ?? 0;
                            ?>
                            <div class="chart-legend-item">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 10px; height: 10px; border-radius: 50%; background: <?= $colors[$i % 5] ?>"></div>
                                    <span style="font-size: 13px; color: var(--text-dark);">
                                        <?= htmlspecialchars($cat['Ten_danh_muc'] ?? 'Khác') ?>
                                    </span>
                                </div>
                                <span style="font-size: 13px; font-weight: 600; color: var(--text-dark);">
                                    <?= $percent ?>%
                                </span>
                            </div>
                            <?php $i++; endforeach; ?>

                        <?php if (empty($category_stats)): ?>
                            <div class="chart-legend-item">
                                <span style="font-size: 13px; color: var(--admin-text-muted);">Chưa có dữ liệu thống kê</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Chart colors
        const primaryColor = '#7BC043';
        const primaryBg = 'rgba(123, 192, 67, 0.1)';
        const gridColor = '#f1f5f9';
        const textColor = '#64748b';

        // Get chart data from PHP
        const revenueData = <?= json_encode(!empty($chart_data) ? $chart_data : [0, 0, 0, 0, 0, 0, 0]) ?>;
        const labels = <?= json_encode(!empty($chart_labels) ? $chart_labels : ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']) ?>;

        // Common chart options
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#ffffff',
                    titleColor: '#0f172a',
                    bodyColor: '#64748b',
                    borderColor: '#e2e8f0',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: textColor, font: { size: 12 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor, drawBorder: false },
                    ticks: { color: textColor, font: { size: 12 } }
                }
            }
        };

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx) {
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Doanh thu',
                        data: revenueData,
                        borderColor: primaryColor,
                        backgroundColor: primaryBg,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: commonOptions
            });
        }

        // Category Doughnut Chart
        const categoryCtx = document.getElementById('categoryChart');
        if (categoryCtx) {
            const categoryData = <?= json_encode(array_column($category_stats, 'percent') ?: []) ?>;
            const categoryLabels = <?= json_encode(array_column($category_stats, 'Ten_danh_muc') ?: []) ?>;
            const categoryColors = ['#7BC043', '#22d3ee', '#818cf8', '#f59e0b', '#ec4899'];

            if (categoryData.length > 0) {
                 new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            data: categoryData,
                            backgroundColor: categoryColors.slice(0, categoryData.length),
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            } else {
                 // Empty State for Chart
                 categoryCtx.parentElement.innerHTML = '<div style="height:100%; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:13px;">Chưa có dữ liệu</div>';
            }
        }
    });
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>