<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../db.php';

// Get logged-in admin's branch
$loggedInBranch = $_SESSION['branch_filter'] ?? '';
$adminUsername = $_SESSION['admin_username'] ?? '';
$adminRole = $_SESSION['role'] ?? '';

// Fetch reservations and users for the logged-in admin's branch
try {
    $pdo = new PDO(
        "mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Query to get reservations with payment info for the specific branch
    $stmt = $pdo->prepare("
        SELECT 
            r.ReservationID,
            r.UserID,
            r.ReferenceNumber,
            r.Date,
            r.Time,
            r.Fname,
            r.Lname,
            r.PlateNo,
            r.Brand,
            r.BranchName,
            r.PaymentMethod,
            r.PaymentStatus,
            r.PaymentReceipt,
            r.Price,
            r.Email,
            r.CreatedAt,
            u.Username,
            u.Email as UserEmail,
            vt.Name as VehicleType
        FROM reservations r
        LEFT JOIN users u ON r.UserID = u.UserID
        LEFT JOIN vehicle_types vt ON r.TypeID = vt.VehicleTypeID
        WHERE r.BranchName = ?
        ORDER BY r.CreatedAt DESC, r.Date DESC
    ");
    
    $stmt->execute([$loggedInBranch]);
    $payments = $stmt->fetchAll();
    
    // Get unique users who have made reservations at this branch
    $userStmt = $pdo->prepare("
        SELECT DISTINCT 
            u.UserID,
            u.Username,
            u.Fname,
            u.Email
        FROM users u
        INNER JOIN reservations r ON u.UserID = r.UserID
        WHERE r.BranchName = ?
        ORDER BY u.Username
    ");
    $userStmt->execute([$loggedInBranch]);
    $users = $userStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $payments = [];
    $users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reports - <?php echo htmlspecialchars($loggedInBranch); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(180deg, #a4133c 0%, #ff4d6d 100%);
            min-height: 100vh;
            color: #1a1a1a;
            display: flex;
        }

        .main {
            flex: 1;
            background-color: #f5f5f5;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        .logo {
            font-size: 24px;
            color: #a4133c;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn {
            background: linear-gradient(135deg, #a4133c, #ff4d6d);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(164, 19, 60, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(164, 19, 60, 0.4);
        }

        .main-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Section */
        .page-header {
            background: white;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #a4133c 0%, #ff4d6d 100%);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #a4133c 0%, #ff4d6d 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .header-subtitle {
            color: #666;
            font-size: 15px;
            font-weight: 400;
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            color: #495057;
            border: 1px solid #dee2e6;
        }

        .admin-badge strong {
            color: #a4133c;
        }

        /* Filters Card */
        .filters-section {
            background: white;
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .filters-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f1f3f5;
        }

        .filters-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
        }

        .filter-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #a4133c 0%, #ff4d6d 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 600;
            font-size: 13px;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group select,
        .filter-group input {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: #f8f9fa;
            color: #495057;
        }

        .filter-group select:hover,
        .filter-group input:hover {
            border-color: #ff4d6d;
            background: white;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #a4133c;
            background: white;
            box-shadow: 0 0 0 4px rgba(164, 19, 60, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #a4133c 0%, #ff4d6d 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(164, 19, 60, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(164, 19, 60, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(108, 117, 125, 0.2);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(108, 117, 125, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #c9184a 0%, #ff758f 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(201, 24, 74, 0.2);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(201, 24, 74, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.green::before {
            background: linear-gradient(90deg, #a4133c 0%, #ff4d6d 100%);
        }

        .stat-card.yellow::before {
            background: linear-gradient(90deg, #ff758f 0%, #ffb3c1 100%);
        }

        .stat-card.blue::before {
            background: linear-gradient(90deg, #c9184a 0%, #ff4d6d 100%);
        }

        .stat-card.red::before {
            background: linear-gradient(90deg, #800f2f 0%, #a4133c 100%);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #ffd6e0 0%, #ffb3c1 100%);
            color: #a4133c;
        }

        .stat-icon.yellow {
            background: linear-gradient(135deg, #ffe5ec 0%, #ffd6e0 100%);
            color: #c9184a;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #ffc2d1 0%, #ff758f 100%);
            color: #a4133c;
        }

        .stat-icon.red {
            background: linear-gradient(135deg, #ffb3c1 0%, #ff8fa3 100%);
            color: #800f2f;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
        }

        .stat-value.green {
            background: linear-gradient(135deg, #a4133c 0%, #ff4d6d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-value.yellow {
            background: linear-gradient(135deg, #ff758f 0%, #ffb3c1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-value.blue {
            background: linear-gradient(135deg, #c9184a 0%, #ff4d6d 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Results Section */
        .results-section {
            background: white;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f1f3f5;
            flex-wrap: wrap;
            gap: 16px;
        }

        .results-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .record-count {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #ffd6e0 0%, #ffb3c1 100%);
            padding: 10px 20px;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 600;
            color: #800f2f;
            border: 1px solid #ffb3c1;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f1f3f5;
            color: #495057;
        }

        tbody tr {
            transition: all 0.2s ease;
        }

        tbody tr:hover {
            background: linear-gradient(135deg, #ffe5ec 0%, #fff0f3 100%);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%);
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .badge-paid {
            background: linear-gradient(135deg, #ffd6e0 0%, #ffb3c1 100%);
            color: #800f2f;
            border: 1px solid #ffb3c1;
        }

        .badge-verified {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .badge-gcash {
            background: linear-gradient(135deg, #cce5ff 0%, #b8daff 100%);
            color: #004085;
            border: 1px solid #b8daff;
        }

        .badge-onsite {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .no-data {
            text-align: center;
            padding: 80px 20px;
            color: #adb5bd;
        }

        .no-data-icon {
            font-size: 72px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .no-data-text {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #6c757d;
        }

        .no-data-subtext {
            font-size: 14px;
            color: #adb5bd;
        }

        /* Print Styles */
        .print-only {
            display: none;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            .print-only, .print-only * {
                visibility: visible;
            }

            .print-only {
                display: block;
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            body {
                background: white;
                padding: 0;
            }

            .print-header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 3px solid #a4133c;
            }

            .print-title {
                color: #a4133c;
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 8px;
            }

            .print-subtitle {
                color: #666;
                font-size: 14px;
            }

            .print-info-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                margin-bottom: 30px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
            }

            .print-info-item {
                text-align: center;
            }

            .print-info-label {
                font-size: 11px;
                color: #666;
                margin-bottom: 4px;
                text-transform: uppercase;
            }

            .print-info-value {
                font-size: 13px;
                font-weight: 600;
                color: #1a1a1a;
            }

            .print-stats {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
                margin-bottom: 30px;
            }

            .print-stat {
                text-align: center;
                padding: 15px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
            }

            .print-stat-label {
                font-size: 11px;
                color: #666;
                margin-bottom: 5px;
                text-transform: uppercase;
            }

            .print-stat-value {
                font-size: 20px;
                font-weight: 700;
                color: #a4133c;
            }

            table {
                page-break-inside: auto;
                font-size: 11px;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }

            th {
                background: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 12px;
            }

            .page-header {
                padding: 20px;
            }

            .filters-section,
            .results-section {
                padding: 20px;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                width: 100%;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main">
        <div class="topbar">
            <div class="logo">
                <i class="fas fa-file-invoice-dollar"></i> Payment Reports
            </div>
            <button class="logout-btn" onclick="window.location.href='../logout.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>

        <div class="main-container">
            <!-- Header -->
            <div class="page-header">
                <div class="header-content">
                    <div class="header-left">
                        <p class="header-subtitle">Track and analyze payment transactions</p>
                    </div>
                    <div class="admin-badge">
                        <strong><?php echo htmlspecialchars($adminUsername); ?></strong>
                        <span>(<?php echo htmlspecialchars($adminRole); ?>)</span>
                        <span>|</span>
                        <strong><?php echo htmlspecialchars($loggedInBranch); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section no-print">
                <div class="filters-header">
                    <h2>Filter Options</h2>
                </div>

                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="filterType">Time Period</label>
                        <select id="filterType">
                            <option value="all">All Time</option>
                            <option value="today">Today</option>
                            <option value="month">This Month</option>
                            <option value="year">This Year</option>
                            <option value="custom">Custom Date Range</option>
                            <option value="specific-month">Specific Month</option>
                        </select>
                    </div>

                    <div class="filter-group" id="monthGroup" style="display: none;">
                        <label for="monthSelect">Select Month</label>
                        <input type="month" id="monthSelect">
                    </div>

                    <div class="filter-group" id="startDateGroup" style="display: none;">
                        <label for="startDate">Start Date</label>
                        <input type="date" id="startDate">
                    </div>

                    <div class="filter-group" id="endDateGroup" style="display: none;">
                        <label for="endDate">End Date</label>
                        <input type="date" id="endDate">
                    </div>

                    <div class="filter-group">
                        <label for="userFilter">User Account</label>
                        <select id="userFilter">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['UserID']); ?>">
                                    <?php echo htmlspecialchars($user['Username']); ?>
                                    <?php if ($user['Fname']): ?>
                                        (<?php echo htmlspecialchars($user['Fname']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="paymentStatusFilter">Payment Status</label>
                        <select id="paymentStatusFilter">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="verified">Verified</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="paymentMethodFilter">Payment Method</label>
                        <select id="paymentMethodFilter">
                            <option value="">All Methods</option>
                            <option value="gcash">GCash</option>
                            <option value="onsite">On-site</option>
                        </select>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="applyFilters()">
                        Apply Filters
                    </button>
                    <button class="btn btn-secondary" onclick="resetFilters()">
                         Reset All
                    </button>
                    <button class="btn btn-success" onclick="window.print()">
                         Print Report
                    </button>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card green">
                    <div class="stat-header">
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-icon green">💵</div>
                    </div>
                    <div class="stat-value green" id="totalRevenue">₱0.00</div>
                </div>
                <div class="stat-card yellow">
                    <div class="stat-header">
                        <div class="stat-label">Pending Payments</div>
                        <div class="stat-icon yellow">⏳</div>
                    </div>
                    <div class="stat-value yellow" id="pendingAmount">₱0.00</div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-header">
                        <div class="stat-label">Paid Transactions</div>
                        <div class="stat-icon blue">✅</div>
                    </div>
                    <div class="stat-value blue" id="paidCount">0</div>
                </div>
                <div class="stat-card red">
                    <div class="stat-header">
                        <div class="stat-label">GCash Payments</div>
                        <div class="stat-icon red">📱</div>
                    </div>
                    <div class="stat-value green" id="gcashRevenue">₱0.00</div>
                </div>
            </div>

            <!-- Results Section -->
            <div class="results-section">
                <div class="results-header">
                    <h2 class="results-title">
                        Payment Transactions
                    </h2>
                    <div class="record-count">
                        <span>📊</span>
                        Total: <strong id="recordCount">0</strong>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Ref. Number</th>
                                <th>Date Created</th>
                                <th>Customer</th>
                                <th>User Account</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <tr>
                                <td colspan="8" class="no-data">
                                    <div class="no-data-icon">💰</div>
                                    <div class="no-data-text">No Payment Records</div>
                                    <div class="no-data-subtext">Apply filters to view payment data</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Print-only Content -->
    <div class="print-only">
        <div class="print-header">
            <h1 class="print-title">Payment Reports & Analytics</h1>
            <p class="print-subtitle"><?php echo htmlspecialchars($loggedInBranch); ?> - Automotive Testing Center</p>
        </div>

        <div class="print-info-grid">
            <div class="print-info-item">
                <div class="print-info-label">Report Generated</div>
                <div class="print-info-value" id="printDate"></div>
            </div>
            <div class="print-info-item">
                <div class="print-info-label">Generated By</div>
                <div class="print-info-value"><?php echo htmlspecialchars($adminUsername); ?> (<?php echo htmlspecialchars($adminRole); ?>)</div>
            </div>
            <div class="print-info-item">
                <div class="print-info-label">Filter Applied</div>
                <div class="print-info-value" id="printFilter"></div>
            </div>
        </div>

        <div class="print-stats">
            <div class="print-stat">
                <div class="print-stat-label">Total Revenue</div>
                <div class="print-stat-value" id="printTotalRevenue">₱0.00</div>
            </div>
            <div class="print-stat">
                <div class="print-stat-label">Pending Payments</div>
                <div class="print-stat-value" id="printPendingAmount">₱0.00</div>
            </div>
            <div class="print-stat">
                <div class="print-stat-label">Paid Transactions</div>
                <div class="print-stat-value" id="printPaidCount">0</div>
            </div>
            <div class="print-stat">
                <div class="print-stat-label">GCash Revenue</div>
                <div class="print-stat-value" id="printGcashRevenue">₱0.00</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Ref. Number</th>
                    <th>Date Created</th>
                    <th>Customer</th>
                    <th>User Account</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody id="printTableBody"></tbody>
        </table>
    </div>

    <script>
        const payments = <?php echo json_encode($payments); ?>;
        const loggedInBranch = "<?php echo htmlspecialchars($loggedInBranch); ?>";
        
        let filteredData = [...payments];

        // Filter type change handler
        document.getElementById('filterType').addEventListener('change', function() {
            const filterType = this.value;
            
            document.getElementById('monthGroup').style.display = 'none';
            document.getElementById('startDateGroup').style.display = 'none';
            document.getElementById('endDateGroup').style.display = 'none';

            if (filterType === 'specific-month') {
                document.getElementById('monthGroup').style.display = 'block';
            } else if (filterType === 'custom') {
                document.getElementById('startDateGroup').style.display = 'block';
                document.getElementById('endDateGroup').style.display = 'block';
            }
        });

        function applyFilters() {
            const filterType = document.getElementById('filterType').value;
            const userFilter = document.getElementById('userFilter').value;
            const paymentStatus = document.getElementById('paymentStatusFilter').value;
            const paymentMethod = document.getElementById('paymentMethodFilter').value;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            filteredData = payments.filter(payment => {
                const createdDate = new Date(payment.CreatedAt);
                let dateMatch = true;

                // Date filtering based on CreatedAt
                if (filterType === 'today') {
                    dateMatch = createdDate.toDateString() === today.toDateString();
                } else if (filterType === 'month') {
                    dateMatch = createdDate.getMonth() === today.getMonth() && 
                               createdDate.getFullYear() === today.getFullYear();
                } else if (filterType === 'year') {
                    dateMatch = createdDate.getFullYear() === today.getFullYear();
                } else if (filterType === 'specific-month') {
                    const selectedMonth = document.getElementById('monthSelect').value;
                    if (selectedMonth) {
                        const [year, month] = selectedMonth.split('-');
                        dateMatch = createdDate.getMonth() === parseInt(month) - 1 && 
                                   createdDate.getFullYear() === parseInt(year);
                    }
                } else if (filterType === 'custom') {
                    const startDate = document.getElementById('startDate').value;
                    const endDate = document.getElementById('endDate').value;
                    if (startDate && endDate) {
                        const start = new Date(startDate);
                        const end = new Date(endDate);
                        end.setHours(23, 59, 59, 999); // Include entire end date
                        dateMatch = createdDate >= start && createdDate <= end;
                    }
                }

                // User filtering - filter by UserID
                const userMatch = !userFilter || (payment.UserID && payment.UserID.toString() === userFilter);

                // Payment status filtering
                const statusMatch = !paymentStatus || payment.PaymentStatus === paymentStatus;

                // Payment method filtering
                const methodMatch = !paymentMethod || payment.PaymentMethod === paymentMethod;

                return dateMatch && userMatch && statusMatch && methodMatch;
            });

            renderTable();
            updateStats();
        }

        function renderTable() {
            const tbody = document.getElementById('tableBody');
            const printTbody = document.getElementById('printTableBody');
            
            if (filteredData.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="no-data">
                            <div class="no-data-icon">💰</div>
                            <div class="no-data-text">No Payment Records Found</div>
                            <div class="no-data-subtext">Try adjusting your filters to see more results</div>
                        </td>
                    </tr>
                `;
                printTbody.innerHTML = `
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 20px; color: #999;">No data to display</td>
                    </tr>
                `;
                return;
            }

            const tableRows = filteredData.map(payment => {
                const createdDate = new Date(payment.CreatedAt);
                const receiptStatus = payment.PaymentReceipt && payment.PaymentReceipt !== '0' ? ' Yes' : 'No';
                const username = payment.Username || 'Walk-in';
                
                return `
                    <tr>
                        <td><strong>${payment.ReferenceNumber}</strong></td>
                        <td>${createdDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}<br>
                            <small style="color: #6c757d;">${createdDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })}</small>
                        </td>
                        <td>${payment.Fname} ${payment.Lname}</td>
                        <td>${username}</td>
                        <td><span class="badge badge-${payment.PaymentMethod}">${payment.PaymentMethod.toUpperCase()}</span></td>
                        <td><span class="badge badge-${payment.PaymentStatus}">${payment.PaymentStatus.toUpperCase()}</span></td>
                        <td><strong>₱${parseFloat(payment.Price).toFixed(2)}</strong></td>
                        <td>${receiptStatus}</td>
                    </tr>
                `;
            }).join('');

            const printRows = filteredData.map(payment => {
                const createdDate = new Date(payment.CreatedAt);
                const username = payment.Username || 'Walk-in';
                
                return `
                    <tr>
                        <td><strong>${payment.ReferenceNumber}</strong></td>
                        <td>${createdDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                        <td>${payment.Fname} ${payment.Lname}</td>
                        <td>${username}</td>
                        <td><span class="badge badge-${payment.PaymentMethod}">${payment.PaymentMethod.toUpperCase()}</span></td>
                        <td><span class="badge badge-${payment.PaymentStatus}">${payment.PaymentStatus.toUpperCase()}</span></td>
                        <td><strong>₱${parseFloat(payment.Price).toFixed(2)}</strong></td>
                    </tr>
                `;
            }).join('');

            tbody.innerHTML = tableRows;
            printTbody.innerHTML = printRows;

            document.getElementById('recordCount').textContent = filteredData.length;
        }

        function updateStats() {
            const total = filteredData.length;
            
            // Calculate total revenue (all transactions)
            const totalRevenue = filteredData.reduce((sum, payment) => sum + parseFloat(payment.Price), 0);
            
            // Calculate pending amount
            const pendingAmount = filteredData
                .filter(payment => payment.PaymentStatus === 'pending')
                .reduce((sum, payment) => sum + parseFloat(payment.Price), 0);
            
            // Count paid transactions (paid + verified)
            const paidCount = filteredData.filter(payment => 
                payment.PaymentStatus === 'paid' || payment.PaymentStatus === 'verified'
            ).length;
            
            // Calculate GCash revenue (paid + verified GCash payments only)
            const gcashRevenue = filteredData
                .filter(payment => 
                    payment.PaymentMethod === 'gcash' && 
                    (payment.PaymentStatus === 'paid' || payment.PaymentStatus === 'verified')
                )
                .reduce((sum, payment) => sum + parseFloat(payment.Price), 0);

            // Update screen stats
            document.getElementById('totalRevenue').textContent = '₱' + totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('pendingAmount').textContent = '₱' + pendingAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('paidCount').textContent = paidCount;
            document.getElementById('gcashRevenue').textContent = '₱' + gcashRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Update print stats
            document.getElementById('printTotalRevenue').textContent = '₱' + totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('printPendingAmount').textContent = '₱' + pendingAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('printPaidCount').textContent = paidCount;
            document.getElementById('printGcashRevenue').textContent = '₱' + gcashRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Update print info
            const now = new Date();
            document.getElementById('printDate').textContent = now.toLocaleString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            const filterType = document.getElementById('filterType').value;
            const userFilter = document.getElementById('userFilter').value;
            const paymentStatus = document.getElementById('paymentStatusFilter').value;
            const paymentMethod = document.getElementById('paymentMethodFilter').value;
            
            let filterText = '';
            const filterLabels = {
                'all': 'All Time',
                'today': 'Today',
                'month': 'This Month',
                'year': 'This Year',
                'custom': 'Custom Date Range',
                'specific-month': 'Specific Month'
            };
            filterText = filterLabels[filterType] || 'All Time';
            
            if (userFilter) {
                const selectedOption = document.querySelector(`#userFilter option[value="${userFilter}"]`);
                if (selectedOption) {
                    filterText += ' | User: ' + selectedOption.textContent.trim();
                }
            }
            
            if (paymentStatus) {
                filterText += ' | Status: ' + paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1);
            }

            if (paymentMethod) {
                filterText += ' | Method: ' + paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1);
            }
            
            document.getElementById('printFilter').textContent = filterText;
        }

        function resetFilters() {
            document.getElementById('filterType').value = 'all';
            document.getElementById('userFilter').value = '';
            document.getElementById('paymentStatusFilter').value = '';
            document.getElementById('paymentMethodFilter').value = '';
            document.getElementById('monthSelect').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            
            document.getElementById('monthGroup').style.display = 'none';
            document.getElementById('startDateGroup').style.display = 'none';
            document.getElementById('endDateGroup').style.display = 'none';

            filteredData = [...payments];
            renderTable();
            updateStats();
        }

        // Keep the dropdown open if it contains an active item
        document.querySelectorAll('.submenu').forEach(menu => {
            if (menu.querySelector('.active')) {
                menu.style.display = 'block';
            }
        });

        // Initialize
        applyFilters();
    </script>
</body>
</html>