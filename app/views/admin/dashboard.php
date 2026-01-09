<?php
/**
 * Modern Admin Dashboard
 * Design: SuperMart Template
 * Theme: #7BC043 (Lime Green) + #2D3657 (Navy)
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

<!-- Dashboard Container -->
<div class="dash-content">

    <!-- Header Section -->
    <div class="dash-header">
        <div class="dash-title">
            <h2>Dashboard Chính</h2>
            <p>Tổng quan hiệu suất kho hàng ngày <strong><?= $today ?></strong></p>
        </div>
        <div class="dash-actions">
            <button class="btn-outline" onclick="window.location.reload()">
                <i class="fas fa-sync-alt"></i>
                Làm mới
            </button>
            <button class="btn-outline" onclick="window.location.href='<?= BASE_URL ?>/public/admin/export-products'">
                <i class="fas fa-download"></i>
                Xuất dữ liệu
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

    <?php if ($totalExpiryCount > 0): ?>
        <!-- Expiry Alert Banner -->
        <div class="expiry-alert-banner <?= $criticalCount > 0 ? 'critical' : 'warning' ?>">
            <div class="alert-content">
                <div class="alert-icon <?= $criticalCount > 0 ? 'pulse' : '' ?>">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="alert-info">
                    <h4>
                        <?php if ($criticalCount > 0): ?>
                            ⚠️ <?= $criticalCount ?> lô hàng SẮP HẾT HẠN DƯỚI 7 NGÀY!
                        <?php else: ?>
                            📦 <?= $totalExpiryCount ?> lô hàng cần chú ý (hết hạn trong 30 ngày)
                        <?php endif; ?>
                    </h4>
                    <p>
                        <?php if ($criticalCount > 0): ?>
                            Khẩn cấp: <strong><?= $criticalCount ?></strong> lô cần xử lý ngay
                            <?php if ($warningCount30 > 0): ?>
                                &nbsp;|&nbsp; Cảnh báo: <strong><?= $warningCount30 ?></strong> lô trong 30 ngày
                            <?php endif; ?>
                        <?php else: ?>
                            Cần lên kế hoạch khuyến mãi hoặc sử dụng sớm
                        <?php endif; ?>
                    </p>
                </div>
                <div class="alert-actions">
                    <a href="<?= BASE_URL ?>/public/admin/report-expiry" class="btn-alert">
                        <i class="fas fa-eye"></i> Xem Chi Tiết
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($lowStockCount > 0): ?>
        <!-- Low Stock Alert Banner -->
        <div class="expiry-alert-banner low-stock">
            <div class="alert-content">
                <div class="alert-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="alert-info">
                    <h4>⚠️ <?= $lowStockCount ?> sản phẩm SẮP HẾT HÀNG!</h4>
                    <p>
                        Hiện có <strong><?= $lowStockCount ?></strong> sản phẩm có số lượng tồn kho thấp (≤ 10).
                        Cần nhập thêm hàng ngay.
                    </p>
                </div>
                <div class="alert-actions">
                    <a href="<?= BASE_URL ?>/public/admin/products" class="btn-alert">
                        <i class="fas fa-arrow-right"></i> Nhập Hàng
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div class="stats-grid">
        <!-- Revenue Card -->
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <p class="stat-card-label">Doanh thu (Hôm nay)</p>
                    <h3 class="stat-card-value"><?= formatVND($todayRevenue) ?></h3>
                </div>
                <div class="stat-card-icon green">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <span class="trend-up">
                    <i class="fas fa-arrow-up"></i> 12%
                </span>
                <span class="trend-neutral">so với hôm qua</span>
            </div>
        </div>

        <!-- Orders Card -->
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <p class="stat-card-label">Đơn hàng hôm nay</p>
                    <h3 class="stat-card-value"><?= $todayOrders ?></h3>
                </div>
                <div class="stat-card-icon blue">
                    <i class="fas fa-shopping-bag"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <strong><?= $stats['don_cho_xu_ly'] ?? 0 ?></strong>
                <span class="trend-neutral">Đang chờ xử lý</span>
            </div>
        </div>

        <!-- Products Card -->
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <p class="stat-card-label">SKU Đang hoạt động</p>
                    <h3 class="stat-card-value"><?= number_format($totalProducts) ?></h3>
                </div>
                <div class="stat-card-icon purple">
                    <i class="fas fa-box"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <strong><?= $stats['sp_moi_tuan'] ?? 0 ?></strong>
                <span class="trend-neutral">Mới thêm tuần này</span>
            </div>
        </div>

        <!-- Warnings Card -->
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <p class="stat-card-label">Cảnh báo tồn kho</p>
                    <h3 class="stat-card-value"><?= $warningCount ?></h3>
                </div>
                <div class="stat-card-icon orange">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <span class="trend-down"><?= $lowStockCount ?> Sắp hết</span>
                <span class="trend-neutral">Cần nhập kho</span>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-grid" style="grid-template-columns: 2fr 1fr;">
        <!-- Revenue Chart -->
        <div class="chart-card">
            <div class="chart-card-header">
                <h3 class="chart-card-title">Xu hướng Doanh thu</h3>
                <a href="<?= BASE_URL ?>/public/admin/report-profit" class="chart-card-link">Xem báo cáo</a>
            </div>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Category Chart -->
        <div class="chart-card">
            <div class="chart-card-header">
                <h3 class="chart-card-title">Tồn kho theo danh mục</h3>
            </div>
            <div class="chart-container" style="height: 220px;">
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
                    <div class="legend-item">
                        <div class="legend-item-left">
                            <div class="legend-dot" style="background: <?= $colors[$i % 5] ?>"></div>
                            <span class="legend-label">
                                <?= htmlspecialchars($cat['Ten_danh_muc'] ?? 'Khác') ?>
                            </span>
                        </div>
                        <span class="legend-value">
                            <?= $percent ?>%
                        </span>
                    </div>
                    <?php $i++; endforeach; ?>

                <?php if (empty($category_stats)): ?>
                    <div class="legend-item">
                        <div class="legend-item-left">
                            <div class="legend-dot" style="background: #7BC043"></div>
                            <span class="legend-label">Chưa có dữ liệu</span>
                        </div>
                        <span class="legend-value">-</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


</div>






<!-- Chart.js Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Chart colors
        const primaryColor = '#7BC043';
        const primaryBg = 'rgba(123, 192, 67, 0.1)';
        const profitColor = '#10b981';
        const profitBg = 'rgba(16, 185, 129, 0.1)';
        const gridColor = '#e7edf3';
        const textColor = '#64748b';

        // Get chart data from PHP
        const revenueData = <?= json_encode($chart_data ?: [0, 0, 0, 0, 0, 0, 0]) ?>;
        const profitData = <?= json_encode($profit_data ?: [0, 0, 0, 0, 0, 0, 0]) ?>;
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
                        label: 'Doanh thu (Triệu VNĐ)',
                        data: revenueData,
                        borderColor: primaryColor,
                        backgroundColor: primaryBg,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: primaryColor,
                        pointBorderWidth: 2,
                        pointHoverRadius: 6
                    }]
                },
                options: commonOptions
            });
        }

        // Profit Chart
        const profitCtx = document.getElementById('profitChart');
        if (profitCtx) {
            new Chart(profitCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Lợi nhuận (Triệu VNĐ)',
                        data: profitData,
                        borderColor: profitColor,
                        backgroundColor: profitBg,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: profitColor,
                        pointBorderWidth: 2,
                        pointHoverRadius: 6
                    }]
                },
                options: commonOptions
            });
        }

        // Category Doughnut Chart
        const categoryCtx = document.getElementById('categoryChart');
        if (categoryCtx) {
            const categoryData = <?= json_encode(array_column($category_stats, 'percent') ?: [45, 30, 25]) ?>;
            const categoryLabels = <?= json_encode(array_column($category_stats, 'Ten_danh_muc') ?: ['Rau củ', 'Sữa', 'Thịt']) ?>;
            const categoryColors = ['#7BC043', '#22d3ee', '#818cf8', '#f59e0b', '#ec4899'];

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
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#ffffff',
                            titleColor: '#000000',
                            bodyColor: '#000000',
                            borderColor: '#e2e8f0',
                            borderWidth: 1
                        }
                    }
                }
            });
        }
    });
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>