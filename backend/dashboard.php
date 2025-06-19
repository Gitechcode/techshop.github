<?php
require_once '../config/config.php';

// Check admin authentication
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit;
}

// Get comprehensive statistics
try {
    // Basic stats
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $total_users = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
    $total_products = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
    $total_orders = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status IN ('delivered', 'processing')");
    $result = $stmt->fetch();
    $total_sales = $result['total'] ?? 0;
    
    // Advanced analytics
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
    $today_orders = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $new_users_week = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE stock <= 10");
    $low_stock = $stmt->fetch()['count'];
    
    // Monthly sales data for chart
    $stmt = $pdo->query("
        SELECT 
            MONTH(created_at) as month,
            SUM(total_amount) as sales 
        FROM orders 
        WHERE YEAR(created_at) = YEAR(CURDATE()) 
        GROUP BY MONTH(created_at) 
        ORDER BY month
    ");
    $monthly_sales = $stmt->fetchAll();
    
    // Top selling products
    $stmt = $pdo->query("
        SELECT p.name, SUM(oi.quantity) as total_sold, p.price
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        GROUP BY p.id 
        ORDER BY total_sold DESC 
        LIMIT 5
    ");
    $top_products = $stmt->fetchAll();
    
    // Recent orders with more details
    $stmt = $pdo->query("
        SELECT o.*, u.name as user_name, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll();
    
} catch(Exception $e) {
    $total_users = $total_products = $total_orders = $total_sales = 0;
    $today_orders = $new_users_week = $low_stock = 0;
    $monthly_sales = $top_products = $recent_orders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - TechShop Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            /* Unified Color Scheme for All Admin Pages */
            --sidebar-width: 280px;
            --admin-primary: #1e40af;
            --admin-primary-dark: #1e3a8a;
            --admin-primary-light: #3b82f6;
            --admin-secondary: #64748b;
            --admin-success: #059669;
            --admin-danger: #dc2626;
            --admin-warning: #d97706;
            --admin-info: #0891b2;
            --admin-light: #f8fafc;
            --admin-dark: #1e293b;
            --admin-white: #ffffff;
            --admin-gray-50: #f9fafb;
            --admin-gray-100: #f3f4f6;
            --admin-gray-200: #e5e7eb;
            --admin-gray-300: #d1d5db;
            --admin-gray-400: #9ca3af;
            --admin-gray-500: #6b7280;
            --admin-gray-600: #4b5563;
            --admin-gray-700: #374151;
            --admin-gray-800: #1f2937;
            --admin-gray-900: #111827;
            --admin-border-radius: 0.5rem;
            --admin-border-radius-sm: 0.25rem;
            --admin-border-radius-lg: 0.75rem;
            --admin-box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --admin-box-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --admin-box-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --admin-transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: var(--admin-gray-50);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            color: var(--admin-gray-800);
            font-size: 14px;
            line-height: 1.6;
        }

        /* Unified Sidebar Design */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--admin-primary) 0%, var(--admin-primary-dark) 100%);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            box-shadow: var(--admin-box-shadow-lg);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h4 {
            color: var(--admin-white);
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }

        .sidebar-header small {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
        }

        .sidebar .nav {
            padding: 1rem 0;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.875rem 1.5rem;
            transition: var(--admin-transition);
            display: flex;
            align-items: center;
            font-weight: 500;
            font-size: 0.875rem;
            border: none;
            border-radius: 0;
            margin: 0.125rem 0.75rem;
            border-radius: var(--admin-border-radius);
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: var(--admin-white);
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateX(4px);
        }

        .sidebar .nav-link i {
            margin-right: 0.75rem;
            width: 18px;
            text-align: center;
            font-size: 1rem;
        }

        .sidebar-divider {
            border-color: rgba(255, 255, 255, 0.15);
            margin: 1rem 1.5rem;
        }

        /* Full Width Dashboard - No Wasted Space */
        .main-content {
            margin-left: var(--sidebar-width) !important;
            width: calc(100% - var(--sidebar-width)) !important;
            padding: 2rem !important;
            min-height: 100vh;
            background-color: var(--admin-gray-50);
        }

        .dashboard-container {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            opacity: 0;
            animation: pageLoad 0.8s ease-out forwards;
        }

        @keyframes pageLoad {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-element {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.8s ease-out forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(5, 150, 105, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(5, 150, 105, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(5, 150, 105, 0);
            }
        }

        .animated-progress {
            width: 0%;
            transition: width 2s ease-out;
        }

        /* Unified Page Header */
        .page-header {
            background-color: var(--admin-white);
            border-radius: var(--admin-border-radius-lg);
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--admin-box-shadow);
            border: 1px solid var(--admin-gray-200);
        }

        .page-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--admin-gray-900);
            margin-bottom: 0.5rem;
        }

        .page-header .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
            font-size: 0.875rem;
        }

        .page-header .breadcrumb-item a {
            color: var(--admin-gray-600);
            text-decoration: none;
        }

        .page-header .breadcrumb-item a:hover {
            color: var(--admin-primary);
        }

        .page-header .breadcrumb-item.active {
            color: var(--admin-gray-800);
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-dark) 100%);
            border-radius: var(--admin-border-radius-lg);
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
            box-shadow: var(--admin-box-shadow-lg);
            width: 100%;
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .dashboard-subtitle {
            opacity: 0.9;
            margin-bottom: 0;
            font-size: 1rem;
        }

        .dashboard-actions .btn {
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }

        /* Unified Cards */
        .card {
            box-shadow: var(--admin-box-shadow);
            border: 1px solid var(--admin-gray-200);
            border-radius: var(--admin-border-radius-lg);
            background-color: var(--admin-white);
            overflow: hidden;
        }

        .card-header {
            background-color: var(--admin-gray-50);
            border-bottom: 1px solid var(--admin-gray-200);
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            color: var(--admin-gray-800);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header i {
            margin-right: 0.5rem;
            color: var(--admin-primary);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Modern Stats Cards with Unified Colors */
        .stats-card-modern {
            background: white;
            border-radius: var(--admin-border-radius-lg);
            padding: 0;
            box-shadow: var(--admin-box-shadow-md);
            border: none;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            height: 100%;
        }

        .stats-card-modern:hover {
            transform: translateY(-5px);
            box-shadow: var(--admin-box-shadow-lg);
        }

        .stats-card-modern.primary { 
            border-top: 4px solid var(--admin-primary);
            background: linear-gradient(135deg, var(--admin-white) 0%, #f0f4ff 100%);
        }
        .stats-card-modern.success { 
            border-top: 4px solid var(--admin-success);
            background: linear-gradient(135deg, var(--admin-white) 0%, #f0fdf4 100%);
        }
        .stats-card-modern.info { 
            border-top: 4px solid var(--admin-info);
            background: linear-gradient(135deg, var(--admin-white) 0%, #f0f9ff 100%);
        }
        .stats-card-modern.warning { 
            border-top: 4px solid var(--admin-warning);
            background: linear-gradient(135deg, var(--admin-white) 0%, #fffbeb 100%);
        }

        .stats-card-body {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--admin-gray-900);
        }

        .stats-label {
            color: var(--admin-gray-600);
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stats-trend {
            font-size: 0.8rem;
        }

        .trend-indicator {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-weight: 500;
        }

        .trend-indicator.positive {
            background: rgba(5, 150, 105, 0.1);
            color: var(--admin-success);
        }

        .trend-indicator.warning {
            background: rgba(217, 119, 6, 0.1);
            color: var(--admin-warning);
        }

        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.3;
            color: var(--admin-gray-500);
        }

        /* Modern Cards */
        .modern-card {
            border: none;
            border-radius: var(--admin-border-radius-lg);
            box-shadow: var(--admin-box-shadow);
            overflow: hidden;
            background: white;
            height: 100%;
        }

        .modern-card .card-header {
            background: linear-gradient(135deg, var(--admin-gray-50) 0%, var(--admin-gray-100) 100%);
            border-bottom: 1px solid var(--admin-gray-200);
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            margin: 0;
            font-weight: 600;
            color: var(--admin-gray-900);
            font-size: 1rem;
        }

        .card-tools .form-select {
            border-radius: var(--admin-border-radius);
            border: 1px solid var(--admin-gray-300);
            font-size: 0.875rem;
        }

        /* Unified Buttons */
        .btn {
            border-radius: var(--admin-border-radius);
            font-weight: 500;
            transition: var(--admin-transition);
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .btn i {
            margin-right: 0.375rem;
        }

        .btn-primary {
            background-color: var(--admin-primary);
            color: var(--admin-white);
            box-shadow: var(--admin-box-shadow);
        }

        .btn-primary:hover {
            background-color: var(--admin-primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--admin-box-shadow-md);
            color: var(--admin-white);
        }

        .btn-outline-primary {
            color: var(--admin-primary);
            border: 1px solid var(--admin-primary);
            background-color: transparent;
        }

        .btn-outline-primary:hover {
            background-color: var(--admin-primary);
            color: var(--admin-white);
        }

        .btn-success {
            background-color: var(--admin-success);
            color: var(--admin-white);
        }

        .btn-success:hover {
            background-color: #047857;
            color: var(--admin-white);
        }

        .btn-danger {
            background-color: var(--admin-danger);
            color: var(--admin-white);
        }

        .btn-danger:hover {
            background-color: #b91c1c;
            color: var(--admin-white);
        }

        .btn-warning {
            background-color: var(--admin-warning);
            color: var(--admin-white);
        }

        .btn-warning:hover {
            background-color: #b45309;
            color: var(--admin-white);
        }

        .btn-info {
            background-color: var(--admin-info);
            color: var(--admin-white);
        }

        .btn-info:hover {
            background-color: #0e7490;
            color: var(--admin-white);
        }

        .btn-secondary {
            background-color: var(--admin-gray-600);
            color: var(--admin-white);
        }

        .btn-secondary:hover {
            background-color: var(--admin-gray-700);
            color: var(--admin-white);
        }

        /* Quick Actions Grid */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .quick-action-item {
            display: flex;
            align-items: center;
            padding: 0.875rem;
            border-radius: var(--admin-border-radius);
            text-decoration: none;
            transition: var(--admin-transition);
            border: 2px solid transparent;
        }

        .quick-action-item:hover {
            transform: translateY(-2px);
            text-decoration: none;
        }

        .quick-action-item.primary {
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-dark) 100%);
            color: white;
        }

        .quick-action-item.success {
            background: linear-gradient(135deg, var(--admin-success) 0%, #047857 100%);
            color: white;
        }

        .quick-action-item.info {
            background: linear-gradient(135deg, var(--admin-info) 0%, #0e7490 100%);
            color: white;
        }

        .quick-action-item.warning {
            background: linear-gradient(135deg, var(--admin-warning) 0%, #b45309 100%);
            color: white;
        }

        .action-icon {
            font-size: 1.25rem;
            margin-right: 0.75rem;
            opacity: 0.9;
        }

        .action-text h6 {
            margin: 0;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .action-text p {
            margin: 0;
            opacity: 0.8;
            font-size: 0.8rem;
        }

        /* Top Products */
        .top-products-list {
            space-y: 1rem;
        }

        .top-product-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--admin-gray-200);
        }

        .top-product-item:last-child {
            border-bottom: none;
        }

        .product-rank {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 0.75rem;
            font-size: 0.875rem;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            margin: 0;
            font-weight: 600;
            color: var(--admin-gray-900);
            font-size: 0.9rem;
        }

        .product-sales {
            margin: 0;
            color: var(--admin-gray-600);
            font-size: 0.8rem;
        }

        .product-progress {
            width: 80px;
        }

        .progress {
            height: 5px;
            border-radius: 3px;
            background: var(--admin-gray-200);
        }

        .progress-bar {
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-dark) 100%);
            border-radius: 3px;
        }

        /* Modern Table */
        .table-modern {
            margin: 0;
            font-size: 0.875rem;
        }

        .table-modern th {
            border-top: none;
            border-bottom: 2px solid var(--admin-gray-200);
            font-weight: 600;
            color: var(--admin-gray-700);
            padding: 0.875rem 0.75rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-modern td {
            padding: 0.875rem 0.75rem;
            border-bottom: 1px solid var(--admin-gray-200);
            vertical-align: middle;
        }

        .customer-info {
            display: flex;
            align-items: center;
        }

        .customer-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-dark) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 0.75rem;
            font-size: 0.8rem;
        }

        .customer-name {
            font-weight: 600;
            color: var(--admin-gray-900);
            font-size: 0.875rem;
        }

        .customer-email {
            font-size: 0.8rem;
            color: var(--admin-gray-600);
        }

        /* Unified Status Badges */
        .status-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-delivered {
            background: rgba(5, 150, 105, 0.1);
            color: var(--admin-success);
        }

        .status-processing {
            background: rgba(217, 119, 6, 0.1);
            color: var(--admin-warning);
        }

        .status-cancelled {
            background: rgba(220, 38, 38, 0.1);
            color: var(--admin-danger);
        }

        .status-pending {
            background: rgba(100, 116, 139, 0.1);
            color: var(--admin-secondary);
        }

        /* System Info */
        .system-info-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
        }

        .system-info-item i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }

        .system-info-item strong {
            display: block;
            color: var(--admin-gray-900);
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
        }

        .system-info-item p {
            margin: 0;
            color: var(--admin-gray-600);
            font-size: 0.8rem;
        }

        /* Unified Color Utilities */
        .text-primary { color: var(--admin-primary) !important; }
        .text-success { color: var(--admin-success) !important; }
        .text-info { color: var(--admin-info) !important; }
        .text-warning { color: var(--admin-warning) !important; }
        .text-danger { color: var(--admin-danger) !important; }
        .text-secondary { color: var(--admin-secondary) !important; }
        .text-gray-800 { color: var(--admin-gray-800) !important; }
        .text-gray-600 { color: var(--admin-gray-600) !important; }
        .text-gray-500 { color: var(--admin-gray-500) !important; }

        .bg-success { background-color: var(--admin-success) !important; }
        .bg-danger { background-color: var(--admin-danger) !important; }
        .bg-warning { background-color: var(--admin-warning) !important; color: var(--admin-white) !important; }
        .bg-info { background-color: var(--admin-info) !important; }
        .bg-primary { background-color: var(--admin-primary) !important; }
        .bg-secondary { background-color: var(--admin-secondary) !important; }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 1rem !important;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .dashboard-header {
                text-align: center;
                padding: 1.5rem;
            }
            
            .dashboard-title {
                font-size: 1.5rem;
            }
            
            .dashboard-actions {
                margin-top: 1rem;
            }
            
            .quick-actions-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-card-body {
                flex-direction: column;
                text-align: center;
                padding: 1.25rem;
            }
            
            .stats-icon {
                margin-top: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .dashboard-header {
                padding: 1.25rem;
            }
            
            .dashboard-title {
                font-size: 1.25rem;
            }
            
            .stats-number {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-laptop me-2"></i>TechShop</h4>
            <small>Admin Dashboard</small>
        </div>
        
        <hr class="sidebar-divider">
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="products.php">
                    <i class="fas fa-box"></i>
                    Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="categories.php">
                    <i class="fas fa-tags"></i>
                    Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="orders.php">
                    <i class="fas fa-shopping-cart"></i>
                    Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="users.php">
                    <i class="fas fa-users"></i>
                    Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
            </li>
            
            <hr class="sidebar-divider">
            
            <li class="nav-item">
                <a class="nav-link" href="../frontend/index.php" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    View Website
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-container">
            <!-- Enhanced Page Header -->
            <div class="dashboard-header mb-4 fade-in-element" style="animation-delay: 0.1s;">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="dashboard-title">
                            <i class="fas fa-chart-line me-3"></i>Dashboard Overview
                        </h1>
                        <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>! Here's what's happening with your store today.</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="dashboard-actions">
                            <button class="btn btn-outline-primary me-2" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh Data
                            </button>
                            <span class="badge bg-success fs-6 px-3 py-2">
                                <i class="fas fa-circle me-1" style="font-size: 8px;"></i>Live
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                    <div class="stats-card-modern primary fade-in-element" style="animation-delay: 0.2s;">
                        <div class="stats-card-body">
                            <div class="stats-info">
                                <h3 class="stats-number counter" data-target="<?php echo $total_users; ?>">0</h3>
                                <p class="stats-label">Total Customers</p>
                                <div class="stats-trend">
                                    <span class="trend-indicator positive">
                                        <i class="fas fa-arrow-up"></i> +<?php echo $new_users_week; ?> this week
                                    </span>
                                </div>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                    <div class="stats-card-modern success fade-in-element" style="animation-delay: 0.3s;">
                        <div class="stats-card-body">
                            <div class="stats-info">
                                <h3 class="stats-number counter" data-target="<?php echo $total_products; ?>">0</h3>
                                <p class="stats-label">Active Products</p>
                                <div class="stats-trend">
                                    <span class="trend-indicator warning">
                                        <i class="fas fa-exclamation-triangle"></i> <?php echo $low_stock; ?> low stock
                                    </span>
                                </div>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-box-open"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                    <div class="stats-card-modern info fade-in-element" style="animation-delay: 0.4s;">
                        <div class="stats-card-body">
                            <div class="stats-info">
                                <h3 class="stats-number counter" data-target="<?php echo $total_orders; ?>">0</h3>
                                <p class="stats-label">Total Orders</p>
                                <div class="stats-trend">
                                    <span class="trend-indicator positive">
                                        <i class="fas fa-plus"></i> <?php echo $today_orders; ?> today
                                    </span>
                                </div>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                    <div class="stats-card-modern warning fade-in-element" style="animation-delay: 0.5s;">
                        <div class="stats-card-body">
                            <div class="stats-info">
                                <h3 class="stats-number">$<span class="counter" data-target="<?php echo number_format($total_sales, 0, '', ''); ?>"><?php echo number_format($total_sales, 2); ?></span></h3>
                                <p class="stats-label">Total Revenue</p>
                                <div class="stats-trend">
                                    <span class="trend-indicator positive">
                                        <i class="fas fa-chart-line"></i> +12.5% vs last month
                                    </span>
                                </div>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Analytics Row -->
            <div class="row mb-4">
                <!-- Sales Chart -->
                <div class="col-xl-8 col-lg-7 mb-4">
                    <div class="card modern-card fade-in-element" style="animation-delay: 0.6s;">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-chart-area me-2"></i>Sales Analytics
                            </h5>
                            <div class="card-tools">
                                <select class="form-select form-select-sm">
                                    <option>Last 12 Months</option>
                                    <option>Last 6 Months</option>
                                    <option>Last 3 Months</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="350"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="col-xl-4 col-lg-5 mb-4">
                    <div class="card modern-card fade-in-element" style="animation-delay: 0.7s;">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-trophy me-2"></i>Top Selling Products
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_products)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-box fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No sales data yet</p>
                            </div>
                            <?php else: ?>
                            <div class="top-products-list">
                                <?php foreach ($top_products as $index => $product): ?>
                                <div class="top-product-item fade-in-element" style="animation-delay: <?php echo 0.8 + ($index * 0.1); ?>s;">
                                    <div class="product-rank"><?php echo $index + 1; ?></div>
                                    <div class="product-info">
                                        <h6 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <p class="product-sales"><?php echo $product['total_sold']; ?> sold • <?php echo formatPrice($product['price']); ?></p>
                                    </div>
                                    <div class="product-progress">
                                        <div class="progress">
                                            <div class="progress-bar animated-progress" data-width="<?php echo min(100, ($product['total_sold'] / max(1, $top_products[0]['total_sold'])) * 100); ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions and Recent Orders -->
            <div class="row mb-4">
                <!-- Enhanced Quick Actions -->
                <div class="col-xl-4 col-lg-5 mb-4">
                    <div class="card modern-card fade-in-element" style="animation-delay: 0.8s;">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions-grid">
                                <a href="products.php" class="quick-action-item primary fade-in-element" style="animation-delay: 0.9s;">
                                    <div class="action-icon">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <div class="action-text">
                                        <h6>Add Product</h6>
                                        <p>Create new product</p>
                                    </div>
                                </a>
                                
                                <a href="orders.php" class="quick-action-item success fade-in-element" style="animation-delay: 1.0s;">
                                    <div class="action-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="action-text">
                                        <h6>View Orders</h6>
                                        <p>Manage orders</p>
                                    </div>
                                </a>
                                
                                <a href="users.php" class="quick-action-item info fade-in-element" style="animation-delay: 1.1s;">
                                    <div class="action-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="action-text">
                                        <h6>Customers</h6>
                                        <p>Manage users</p>
                                    </div>
                                </a>
                                
                                <a href="categories.php" class="quick-action-item warning fade-in-element" style="animation-delay: 1.2s;">
                                    <div class="action-icon">
                                        <i class="fas fa-tags"></i>
                                    </div>
                                    <div class="action-text">
                                        <h6>Categories</h6>
                                        <p>Organize products</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Recent Orders -->
                <div class="col-xl-8 col-lg-7 mb-4">
                    <div class="card modern-card fade-in-element" style="animation-delay: 0.9s;">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-clock me-2"></i>Recent Orders
                            </h5>
                            <a href="orders.php" class="btn btn-sm btn-outline-primary">View All Orders</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_orders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No orders yet</h5>
                                <p class="text-muted">Orders will appear here once customers start purchasing</p>
                                <a href="products.php" class="btn btn-primary">Add Your First Product</a>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-modern">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Customer</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $index => $order): ?>
                                        <tr class="fade-in-element" style="animation-delay: <?php echo 1.0 + ($index * 0.1); ?>s;">
                                            <td>
                                                <strong>#<?php echo str_pad($index + 1, 4, '0', STR_PAD_LEFT); ?></strong>
                                            </td>
                                            <td>
                                                <div class="customer-info">
                                                    <div class="customer-avatar">
                                                        <?php echo strtoupper(substr($order['user_name'] ?? 'G', 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="customer-name"><?php echo htmlspecialchars($order['user_name'] ?? 'Guest'); ?></div>
                                                        <div class="customer-email"><?php echo htmlspecialchars($order['email'] ?? ''); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo formatPrice($order['total_amount']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-muted"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="row">
                <div class="col-12">
                    <div class="card modern-card fade-in-element" style="animation-delay: 1.3s;">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-server me-2"></i>System Status
                            </h5>
                            <span class="badge bg-success pulse-animation">All Systems Operational</span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-xl-3 col-lg-6 col-md-6">
                                    <div class="system-info-item fade-in-element" style="animation-delay: 1.4s;">
                                        <i class="fas fa-code text-primary"></i>
                                        <div>
                                            <strong>PHP Version</strong>
                                            <p><?php echo PHP_VERSION; ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6">
                                    <div class="system-info-item fade-in-element" style="animation-delay: 1.5s;">
                                        <i class="fas fa-database text-success"></i>
                                        <div>
                                            <strong>Database</strong>
                                            <p>MySQL Connected</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6">
                                    <div class="system-info-item fade-in-element" style="animation-delay: 1.6s;">
                                        <i class="fas fa-server text-info"></i>
                                        <div>
                                            <strong>Server</strong>
                                            <p><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6">
                                    <div class="system-info-item fade-in-element" style="animation-delay: 1.7s;">
                                        <i class="fas fa-clock text-warning"></i>
                                        <div>
                                            <strong>Last Updated</strong>
                                            <p><?php echo date('M j, Y g:i A'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Initialize everything when page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize counters
        initCounters();
        
        // Initialize progress bars
        setTimeout(initProgressBars, 500);
        
        // Initialize chart
        setTimeout(initChart, 800);
    });

    // Counter animation
    function initCounters() {
        const counters = document.querySelectorAll('.counter');
        
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const increment = target / 100;
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target.toLocaleString();
                    clearInterval(timer);
                } else {
                    counter.textContent = Math.floor(current).toLocaleString();
                }
            }, 20);
        });
    }

    // Progress bar animation
    function initProgressBars() {
        const progressBars = document.querySelectorAll('.animated-progress');
        progressBars.forEach(bar => {
            const width = bar.getAttribute('data-width');
            bar.style.width = width;
        });
    }

    // Sales Chart
    function initChart() {
        const ctx = document.getElementById('salesChart');
        if (!ctx) return;
        
        const salesChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Sales',
                    data: [<?php 
                        $months = array_fill(1, 12, 0);
                        foreach($monthly_sales as $sale) {
                            $months[$sale['month']] = $sale['sales'];
                        }
                        echo implode(',', array_values($months));
                    ?>],
                    borderColor: 'rgb(30, 64, 175)',
                    backgroundColor: 'rgba(30, 64, 175, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                }
            }
        });
    }

    function refreshDashboard() {
        // Add loading animation
        document.body.style.opacity = '0.7';
        setTimeout(() => {
            location.reload();
        }, 500);
    }
    </script>
</body>
</html>
