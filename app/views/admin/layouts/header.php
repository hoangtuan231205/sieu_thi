<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title><?= $page_title ?? 'Admin Dashboard' ?> - FreshMart</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/refactored-ui.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin-dashboard.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin-modern.css') ?>">
    
    <?php if (isset($additional_css)): ?>
    <!-- Additional CSS -->
    <link rel="stylesheet" href="<?= $additional_css ?>">
    <?php endif; ?>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <!-- Admin Wrapper -->
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="admin-main" style="margin-left:260px !important; height:100vh; overflow-y:auto; overflow-x:hidden; display:flex; flex-direction:column;">
            <!-- Header -->
            <?php include __DIR__ . '/admin_header.php'; ?>
            
            <!-- Content Area -->
            <div class="admin-content">
                <div class="admin-content-inner">

<script>
const baseUrl = '<?= BASE_URL ?>';
const csrfToken = '<?= Session::getCsrfToken() ?>';

// Notification helper
function showNotification(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
    toast.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>
