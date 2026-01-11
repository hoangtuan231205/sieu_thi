<?php
/**
 * ADMIN - BÁO CÁO LÃI/LỖ
 * Thiết kế giao diện hiện đại - Theme Woodland
 */
?>
<?php include __DIR__ . '/layouts/header.php'; ?>
<link rel="stylesheet" href="<?= asset('css/admin-modern.css') ?>">

<div class="admin-modern">
    <div class="admin-modern-container">
        <!-- Breadcrumb -->
        <div class="admin-breadcrumb">
            <a href="<?= BASE_URL ?>/">Trang chủ</a>
            <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
            <span class="current">Báo cáo Lãi/Lỗ</span>
        </div>
        
        <!-- Reports Section Tabs -->
        <?php include __DIR__ . '/components/reports_tabs.php'; ?>
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Báo cáo Lãi/Lỗ</h1>
                <p class="admin-page-subtitle">Phân tích doanh thu, chi phí và lợi nhuận kinh doanh</p>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <form method="GET" class="admin-filter-bar">
            <div class="form-group">
                <label>Từ ngày</label>
                <input type="date" class="form-control" name="date_from" value="<?= $date_from ?>">
            </div>
            <div class="form-group">
                <label>Đến ngày</label>
                <input type="date" class="form-control" name="date_to" value="<?= $date_to ?>">
            </div>
            <button type="submit" class="btn-admin-secondary">
                <i class="fas fa-search"></i>
                <span>Xem báo cáo</span>
            </button>
            <div style="display: flex; gap: 8px; margin-left: auto;">
                <a href="?date_from=<?= date('Y-m-d', strtotime('-7 days')) ?>&date_to=<?= date('Y-m-d') ?>" class="btn-filter">7 ngày</a>
                <a href="?date_from=<?= date('Y-m-d', strtotime('-30 days')) ?>&date_to=<?= date('Y-m-d') ?>" class="btn-filter">30 ngày</a>
                <a href="?date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>" class="btn-filter">Tháng này</a>
            </div>
        </form>
        
        <!-- Stat Cards -->
        <div class="stat-cards-row">
            <!-- Doanh thu -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Tổng doanh thu</h4>
                        <p class="stat-card-value"><?= number_format($summary['doanh_thu'] ?? 0, 0, ',', '.') ?>đ</p>
                    </div>
                    <div class="stat-card-icon info">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge success">
                        <i class="fas fa-arrow-up"></i>
                        Doanh số bán
                    </span>
                </div>
            </div>
            
            <!-- Giá vốn -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Giá vốn hàng bán</h4>
                        <p class="stat-card-value"><?= number_format($summary['gia_von'] ?? 0, 0, ',', '.') ?>đ</p>
                    </div>
                    <div class="stat-card-icon warning">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span style="font-size: 13px; color: var(--admin-text-muted);">Chi phí nhập hàng</span>
                </div>
            </div>
            
            <!-- Lợi nhuận -->
            <div class="stat-card" style="<?= ($summary['loi_nhuan'] ?? 0) >= 0 ? 'border-left: 4px solid var(--admin-success);' : 'border-left: 4px solid var(--admin-danger);' ?>">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Lợi nhuận gộp</h4>
                        <?php $profit = $summary['loi_nhuan'] ?? 0; ?>
                        <p class="stat-card-value" style="color: <?= $profit >= 0 ? 'var(--admin-success)' : 'var(--admin-danger)' ?>;">
                            <?= number_format($profit, 0, ',', '.') ?>đ
                        </p>
                    </div>
                    <div class="stat-card-icon <?= $profit >= 0 ? 'success' : 'danger' ?>">
                        <i class="fas fa-<?= $profit >= 0 ? 'trending-up' : 'trending-down' ?>"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span class="stat-badge <?= $profit >= 0 ? 'success' : 'danger' ?>">
                        <i class="fas fa-<?= $profit >= 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                        <?= $profit >= 0 ? 'Có lãi' : 'Lỗ' ?>
                    </span>
                </div>
            </div>
            
            <!-- Số đơn -->
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-info">
                        <h4>Đơn hàng</h4>
                        <p class="stat-card-value"><?= number_format($summary['so_don'] ?? 0) ?></p>
                    </div>
                    <div class="stat-card-icon primary">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
                <div class="stat-card-footer">
                    <span style="font-size: 13px; color: var(--admin-text-muted);">Đơn hoàn thành</span>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Chart -->
            <div class="col-lg-8">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Tổng quan bán hàng</h3>
                        <span style="font-size: 13px; color: var(--admin-text-muted);">7 ngày gần nhất</span>
                    </div>
                    <div class="admin-card-body">
                        <div class="chart-container">
                            <canvas id="profitChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Daily Table -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Chi tiết theo ngày</h3>
                    </div>
                    <div class="admin-card-body no-padding">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th style="text-align: right;">Doanh thu</th>
                                    <th style="text-align: right;">Giá vốn</th>
                                    <th style="text-align: right;">Lợi nhuận</th>
                                    <th style="text-align: center;">Tỷ lệ LN</th>
                                    <th style="text-align: center;">Số đơn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($profit_data)): ?>
                                    <?php foreach ($profit_data as $row): ?>
                                    <tr>
                                        <td style="font-weight: 500;"><?= date('d/m/Y', strtotime($row['Ngay'])) ?></td>
                                        <td style="text-align: right;"><?= number_format($row['Doanh_thu'], 0, ',', '.') ?>đ</td>
                                        <td style="text-align: right; color: var(--admin-text-muted);"><?= number_format($row['Gia_von'], 0, ',', '.') ?>đ</td>
                                        <td style="text-align: right; font-weight: 600; color: <?= $row['Loi_nhuan'] >= 0 ? 'var(--admin-success)' : 'var(--admin-danger)' ?>;">
                                            <?= number_format($row['Loi_nhuan'], 0, ',', '.') ?>đ
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="stat-badge <?= $row['Ty_le_LN'] >= 20 ? 'success' : ($row['Ty_le_LN'] >= 10 ? 'warning' : 'danger') ?>">
                                                <?= $row['Ty_le_LN'] ?>%
                                            </span>
                                        </td>
                                        <td style="text-align: center;"><?= $row['So_don'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            <p style="color: var(--admin-text-muted);">Không có dữ liệu trong khoảng thời gian này</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Top Products -->
            <div class="col-lg-4">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3 class="admin-card-title">Top sản phẩm lợi nhuận</h3>
                    </div>
                    <div class="admin-card-body" style="padding: 0;">
                        <?php if (!empty($top_products)): ?>
                            <?php foreach ($top_products as $i => $p): ?>
                            <div style="display: flex; align-items: center; gap: 12px; padding: 16px 24px; border-bottom: 1px solid #f1f5f9;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: <?= $i < 3 ? 'var(--admin-primary)' : '#e2e8f0' ?>; color: <?= $i < 3 ? 'white' : 'var(--admin-text-muted)' ?>; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700;">
                                    <?= $i + 1 ?>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <p style="font-size: 14px; font-weight: 500; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($p['Ten']) ?>
                                    </p>
                                </div>
                                <div style="font-weight: 600; color: var(--admin-success);">
                                    <?= number_format($p['LN_thuc'] ?? 0, 0, ',', '.') ?>đ
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding: 40px; text-align: center;">
                                <p style="color: var(--admin-text-muted);">Chưa có dữ liệu</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('profitChart').getContext('2d');
const chartData = <?= json_encode($chart_data ?? []) ?>;

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: chartData.map(d => {
            const date = new Date(d.Ngay);
            return date.toLocaleDateString('vi-VN', {day: '2-digit', month: '2-digit'});
        }),
        datasets: [
            {
                label: 'Doanh thu',
                data: chartData.map(d => parseFloat(d.Doanh_thu) || 0),
                backgroundColor: 'rgba(73, 108, 44, 0.2)',
                borderColor: 'rgba(73, 108, 44, 1)',
                borderWidth: 2,
                borderRadius: 6,
                borderSkipped: false,
            },
            {
                label: 'Lợi nhuận',
                data: chartData.map(d => parseFloat(d.Loi_nhuan) || 0),
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 0,
                borderRadius: 6,
                borderSkipped: false,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                align: 'end',
                labels: {
                    usePointStyle: true,
                    padding: 20
                }
            }
        },
        scales: {
            x: {
                grid: { display: false }
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9' },
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString() + 'đ';
                    }
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/layouts/footer.php'; ?>
