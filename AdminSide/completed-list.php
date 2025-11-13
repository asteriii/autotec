<?php
require_once '../db.php';

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 5;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
$params = [];

if (!empty($search)) {
    $search_condition = "WHERE Fname LIKE ? OR Lname LIKE ? OR PlateNo LIKE ? OR Email LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
}

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM completed $search_condition";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch completed with vehicle type information
$sql = "SELECT r.*, vt.Name as VehicleTypeName, vt.Price as VehiclePrice 
        FROM completed r 
        LEFT JOIN vehicle_types vt ON r.TypeID = vt.VehicleTypeID 
        $search_condition 
        ORDER BY r.Date DESC, r.Time DESC 
        LIMIT $records_per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$completeds = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch vehicle types from database
$vehicle_types_sql = "SELECT VehicleTypeID, Name, Price FROM vehicle_types";
$vehicle_types_stmt = $pdo->prepare($vehicle_types_sql);
$vehicle_types_stmt->execute();
$vehicle_types_data = $vehicle_types_stmt->fetchAll(PDO::FETCH_ASSOC);

$vehicle_types = [];
$vehicle_prices = [];
foreach ($vehicle_types_data as $type) {
    $vehicle_types[$type['VehicleTypeID']] = $type['Name'];
    $vehicle_prices[$type['VehicleTypeID']] = $type['Price'];
}

$categories = [
    1 => 'Basic',
    2 => 'Standard', 
    3 => 'Premium'
];

// Process receipt paths for each completed
foreach ($completeds as &$completed) {
    $receiptPath = $completed['PaymentReceipt'] ?? '';
    
    // Check if receipt exists in database (not 0, NULL, or empty)
    $hasReceipt = !empty($receiptPath) && $receiptPath !== '0' && $receiptPath !== 'NULL';
    
    if ($hasReceipt) {
        // Clean the path
        $cleanPath = ltrim($receiptPath, '/');
        $cleanPath = str_replace('//', '/', $cleanPath);
        
        // Build full server path to check if file exists
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $cleanPath;
        
        // Alternative path if DOCUMENT_ROOT doesn't work
        if (!file_exists($fullPath)) {
            $fullPath = __DIR__ . '/../' . $cleanPath;
        }
        
        $receiptExists = file_exists($fullPath);
        
        // Store processed data
        $completed['_hasReceipt'] = true;
        $completed['_receiptExists'] = $receiptExists;
        $completed['_receiptPath'] = $cleanPath;
        $completed['_fullPath'] = $fullPath;
        
        // Debug logging
        error_log("Completed ID {$completed['CompletedID']}: Receipt Path = {$receiptPath}, Clean Path = {$cleanPath}, Full Path = {$fullPath}, Exists = " . ($receiptExists ? 'YES' : 'NO'));
    } else {
        $completed['_hasReceipt'] = false;
        $completed['_receiptExists'] = false;
        $completed['_receiptPath'] = '';
        $completed['_fullPath'] = '';
    }
}
unset($completed); // Break reference
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Reserved List - AutoTec Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.03)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.03)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .sidebar .section {
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .section-title {
            padding: 15px 0;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            border-radius: 8px;
            margin: 5px 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-title:hover {
            background-color: rgba(255,255,255,0.1);
            padding-left: 10px;
        }

        .submenu {
            list-style: none;
            padding-left: 15px;
            display: none;
            animation: slideDown 0.3s ease;
        }

        .submenu.show {
            display: block;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .submenu li {
            padding: 12px 0;
            font-size: 14px;
            cursor: pointer;
            border-radius: 6px;
            margin: 2px 0;
            transition: all 0.3s ease;
        }

        .submenu li:hover {
            background-color: rgba(255,255,255,0.1);
            padding-left: 10px;
        }

        .submenu li a {
            color: white;
            text-decoration: none;
            display: block;
        }

        .active {
            background-color: rgba(255,255,255,0.15);
            font-weight: 500;
        }

        .main {
            flex: 1;
            background-color: #f8fafc;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-bottom: 1px solid #e2e8f0;
        }

        .logo {
            font-size: 24px;
            color: #c0392b;
            font-weight: 600;
        }

        .logout-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
        }

        .content {
            padding: 30px;
        }

        .content h2 {
            margin-bottom: 30px;
            color: #2d3748;
            font-weight: 700;
            font-size: 28px;
        }

        .search-pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-box input {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            width: 300px;
            transition: all 0.3s ease;
            background: white;
        }

        .search-box input:focus {
            outline: none;
            border-color: #c0392b;
            box-shadow: 0 0 0 3px rgba(192, 57, 43, 0.1);
        }

        .search-btn {
            background: linear-gradient(135deg, #c0392b, #a93226);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(192, 57, 43, 0.3);
        }

        .pagination {
            display: flex;
            gap: 5px;
        }

        .pagination a, .pagination span {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            background-color: white;
            cursor: pointer;
            border-radius: 8px;
            text-decoration: none;
            color: #4a5568;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            border-color: #c0392b;
            color: #c0392b;
            transform: translateY(-1px);
        }

        .pagination .current {
            background: linear-gradient(135deg, #c0392b, #a93226);
            color: white;
            border-color: #c0392b;
        }

        .completed-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .completed-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #c0392b, #a93226);
        }

        .completed-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .completed-id {
            background: linear-gradient(135deg, #c0392b, #a93226);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .card-content {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 25px;
            margin-bottom: 20px;
        }

        .info-section h4 {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-section p {
            margin: 8px 0;
            color: #4a5568;
            font-size: 14px;
        }

        .info-section strong {
            color: #2d3748;
            font-weight: 500;
        }

        .buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .confirm-btn {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            box-shadow: 0 2px 8px rgba(72, 187, 120, 0.3);
        }

        .confirm-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4);
        }

        .cancel-btn {
            background: linear-gradient(135deg, #f56565, #e53e3e);
            color: white;
            box-shadow: 0 2px 8px rgba(245, 101, 101, 0.3);
        }

        .cancel-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(245, 101, 101, 0.4);
        }

        .view-receipt-btn {
            background: linear-gradient(135deg, #4299e1, #3182ce);
            color: white;
            box-shadow: 0 2px 8px rgba(66, 153, 225, 0.3);
        }

        .view-receipt-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.4);
        }

        .view-receipt-btn:disabled {
            background: #cbd5e0;
            color: #718096;
            cursor: not-allowed;
            box-shadow: none;
            opacity: 0.6;
        }

        .price-tag {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #744210;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 16px;
            display: inline-block;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #d8ffcdff;
            color: #228504ff;
            border: 1px solid #adffa7ff;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .receipt-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 500;
            margin-top: 5px;
        }

        .receipt-uploaded {
            background: #d4edda;
            color: #155724;
        }

        .receipt-missing {
            background: #f8d7da;
            color: #721c24;
        }

        .receipt-not-required {
            background: #d1ecf1;
            color: #0c5460;
        }

        .no-completed {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .no-completed i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Receipt Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.85);
            animation: fadeIn 0.3s ease;
            backdrop-filter: blur(5px);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            position: relative;
            margin: 2% auto;
            padding: 30px;
            width: 90%;
            max-width: 900px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .modal-header h3 {
            color: #2d3748;
            font-size: 26px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .close-modal {
            font-size: 32px;
            font-weight: bold;
            color: #718096;
            cursor: pointer;
            transition: all 0.3s;
            background: none;
            border: none;
            padding: 5px 10px;
            line-height: 1;
            border-radius: 8px;
        }

        .close-modal:hover {
            color: #c0392b;
            background: #f7fafc;
        }

        .receipt-image-container {
            text-align: center;
            margin: 25px 0;
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
        }

        .receipt-image {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .receipt-image:hover {
            transform: scale(1.02);
        }

        .receipt-error {
            padding: 40px;
            text-align: center;
            color: #e53e3e;
        }

        .receipt-error i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .payment-info {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }

        .payment-info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .payment-info-row:last-child {
            border-bottom: none;
        }

        .payment-info-label {
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
        }

        .payment-info-value {
            color: #2d3748;
            font-weight: 500;
            font-size: 14px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .modal-actions a {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .download-btn {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1000;
                height: 100vh;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .card-content {
                grid-template-columns: 1fr;
            }

            .search-pagination {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box input {
                width: 100%;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
                padding: 20px;
            }

            .buttons {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main">
        <div class="topbar">
            <div class="logo">
                <i class="fas fa-car"></i> AutoTec Admin
            </div>
            <button class="logout-btn" onclick="window.location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>

        <div class="content">
            <h2><i class="fas fa-calendar-check"></i> Reserved List </h2>

            <div class="search-pagination">
                <form method="GET" class="search-box">
                    <input type="text" name="search" placeholder="Search by name, email, or plate number..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
                
                <div class="pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>

            <?php if (empty($completeds)): ?>
                <div class="no-completed">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No completed found</h3>
                    <p>There are currently no completed matching your criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($completeds as $completed): ?>
                    <?php
                    // Get processed receipt data
                    $hasReceipt = $completed['_hasReceipt'];
                    $receiptExists = $completed['_receiptExists'];
                    $receiptPath = $completed['_receiptPath'];
                    
                    // Payment method checks
                    $paymentMethod = strtolower($completed['PaymentMethod'] ?? '');
                    $isGcash = $paymentMethod === 'gcash';
                    $isOnsite = $paymentMethod === 'onsite' || $paymentMethod === 'cash';
                    ?>
                    <div class="completed-card">
                        <div class="card-header">
                            <div class="completed-id">ID: <?php echo $completed['CompletedID']; ?></div>
                            <div class="status-badge status-pending">
                                Canceled
                            </div>
                        </div>
                        
                        <div class="card-content">
                            <div class="info-section">
                                <h4><i class="fas fa-user"></i> Customer Details</h4>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($completed['Fname'] . ' ' . $completed['Mname'] . ' ' . $completed['Lname']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($completed['PhoneNum']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($completed['Email']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($completed['Address']); ?></p>
                            </div>
                            
                            <div class="info-section">
                                <h4><i class="fas fa-car"></i> Vehicle Details</h4>
                                <p><strong>Brand:</strong> <?php echo htmlspecialchars($completed['Brand']); ?></p>
                                <p><strong>Plate No:</strong> <?php echo htmlspecialchars($completed['PlateNo']); ?></p>
                                <p><strong>Vehicle Type:</strong> <?php echo htmlspecialchars($completed['VehicleTypeName'] ?? 'Unknown'); ?></p>
                                <p><strong>Category:</strong> <?php echo htmlspecialchars($categories[$completed['CategoryID']] ?? 'Unknown'); ?></p>
                            </div>
                            
                            <div class="info-section">
                                <h4><i class="fas fa-calendar-alt"></i> Appointment Details</h4>
                                <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($completed['Date'])); ?></p>
                                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($completed['Time'])); ?></p>
                                <p><strong>Branch:</strong> <?php echo htmlspecialchars($completed['BranchName'] ?? 'N/A'); ?></p>
                                <p><strong>Payment:</strong> <?php echo strtoupper($completed['PaymentMethod'] ?? 'N/A'); ?>
                                    <?php if ($isGcash): ?>
                                        <span class="receipt-status <?php echo $hasReceipt && $receiptExists ? 'receipt-uploaded' : 'receipt-missing'; ?>">
                                            <i class="fas fa-<?php echo $hasReceipt && $receiptExists ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                                            <?php echo $hasReceipt && $receiptExists ? 'Receipt Uploaded' : 'No Receipt'; ?>
                                        </span>
                                    <?php elseif ($isOnsite): ?>
                                        <span class="receipt-status receipt-not-required">
                                            <i class="fas fa-info-circle"></i>
                                            Pay on Site
                                        </span>
                                    <?php endif; ?>
                                </p>
                                <div class="price-tag">
                                    ₱<?php echo number_format($completed['VehiclePrice'] ?? 0, 2); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="buttons">
                            <?php if ($isGcash && $hasReceipt && $receiptExists): ?>
                                <button class="btn view-receipt-btn" 
                                        type="button"
                                        onclick="viewReceipt('/<?php echo addslashes($receiptPath); ?>', 
                                                             '<?php echo addslashes($completed['ReferenceNumber'] ?? 'N/A'); ?>', 
                                                             '<?php echo addslashes($completed['PaymentMethod']); ?>', 
                                                             '<?php echo addslashes($completed['PaymentStatus'] ?? 'pending'); ?>',
                                                             '<?php echo addslashes($completed['Fname'] . ' ' . $completed['Lname']); ?>',
                                                             '₱<?php echo number_format($completed['VehiclePrice'] ?? 0, 2); ?>')">
                                    <i class="fas fa-receipt"></i> View Receipt
                                </button>
                            <?php elseif ($isGcash && !$hasReceipt): ?>
                                <button class="btn view-receipt-btn" type="button" disabled title="Customer has not uploaded receipt yet">
                                    <i class="fas fa-exclamation-triangle"></i> No Receipt
                                </button>
                            <?php elseif ($isGcash && $hasReceipt && !$receiptExists): ?>
                                <button class="btn view-receipt-btn" type="button" disabled title="Receipt file not found: <?php echo htmlspecialchars($receiptPath); ?>">
                                    <i class="fas fa-times-circle"></i> File Missing
                                </button>
                            <?php elseif ($isOnsite): ?>
                                <button class="btn view-receipt-btn" type="button" disabled title="On-site payment - no receipt required">
                                    <i class="fas fa-money-bill"></i> Pay On-Site
                                </button>
                            <?php else: ?>
                                <button class="btn view-receipt-btn" type="button" disabled>
                                    <i class="fas fa-receipt"></i> N/A
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn confirm-btn" type="button" onclick="confirmCompleted(<?php echo $completed['CompletedID']; ?>)">
                                <i class="fas fa-check"></i> Mark as Done
                            </button>
                            <button class="btn cancel-btn" type="button" onclick="deleteCompleted(<?php echo $completed['CompletedID']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Payment Receipt</h3>
                <button class="close-modal" onclick="closeReceiptModal()">&times;</button>
            </div>
            
            <div class="payment-info">
                <div class="payment-info-row">
                    <span class="payment-info-label">Customer Name:</span>
                    <span class="payment-info-value" id="modalCustomerName">-</span>
                </div>
                <div class="payment-info-row">
                    <span class="payment-info-label">Reference Number:</span>
                    <span class="payment-info-value" id="modalReferenceNumber">-</span>
                </div>
                <div class="payment-info-row">
                    <span class="payment-info-label">Payment Method:</span>
                    <span class="payment-info-value" id="modalPaymentMethod">-</span>
                </div>
                <div class="payment-info-row">
                    <span class="payment-info-label">Amount:</span>
                    <span class="payment-info-value" id="modalAmount">-</span>
                </div>
                <div class="payment-info-row">
                    <span class="payment-info-label">Payment Status:</span>
                    <span class="payment-info-value" id="modalPaymentStatus">-</span>
                </div>
            </div>
            
            <div class="receipt-image-container" id="receiptImageContainer">
                <img id="receiptImage" class="receipt-image" src="" alt="Payment Receipt" 
                     onerror="showReceiptError()" style="display: none;">
                <div id="receiptError" class="receipt-error" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p><strong>Failed to Load Receipt</strong></p>
                    <p style="font-size: 14px; margin-top: 10px;">The receipt image could not be loaded. The file may have been moved or deleted.</p>
                </div>
            </div>
            
            <div class="modal-actions">
                <a id="downloadReceiptBtn" href="#" download class="download-btn">
                    <i class="fas fa-download"></i> Download Receipt
                </a>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Menu toggle function
        function toggleMenu(id) {
            const menu = document.getElementById(id);
            const isVisible = menu.classList.contains('show');
            
            // Hide all submenus first
            document.querySelectorAll('.submenu').forEach(submenu => {
                submenu.classList.remove('show');
            });
            
            // Show the clicked menu if it wasn't visible
            if (!isVisible) {
                menu.classList.add('show');
            }
        }

        // View Receipt Modal Function
        function viewReceipt(receiptPath, referenceNumber, paymentMethod, paymentStatus, customerName, amount) {
            console.log('=== Opening Receipt Modal ===');
            console.log('Receipt Path:', receiptPath);
            console.log('Reference:', referenceNumber);
            console.log('Customer:', customerName);
            
            // Set modal content
            document.getElementById('modalCustomerName').textContent = customerName || 'N/A';
            document.getElementById('modalReferenceNumber').textContent = referenceNumber || 'N/A';
            document.getElementById('modalPaymentMethod').textContent = paymentMethod.toUpperCase();
            document.getElementById('modalAmount').textContent = amount || 'N/A';
            document.getElementById('modalPaymentStatus').textContent = paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1);
            
            // Clean the path - ensure it starts with /
            let imagePath = receiptPath;
            if (!imagePath.startsWith('/')) {
                imagePath = '/' + imagePath;
            }
            
            console.log('Image URL:', imagePath);
            
            // Set image source
            const imgElement = document.getElementById('receiptImage');
            const errorElement = document.getElementById('receiptError');
            
            // Reset display states
            imgElement.style.display = 'none';
            errorElement.style.display = 'none';
            
            // Set image source
            imgElement.src = imagePath;
            imgElement.onload = function() {
                console.log('✓ Image loaded successfully');
                imgElement.style.display = 'block';
            };
            imgElement.onerror = function() {
                console.error('✗ Failed to load image:', imagePath);
                showReceiptError();
            };
            
            // Set download link
            const downloadBtn = document.getElementById('downloadReceiptBtn');
            downloadBtn.href = imagePath;
            downloadBtn.download = `receipt_${referenceNumber}.jpg`;
            
            // Show modal
            document.getElementById('receiptModal').style.display = 'block';
            console.log('Modal displayed');
        }

        function showReceiptError() {
            document.getElementById('receiptImage').style.display = 'none';
            document.getElementById('receiptError').style.display = 'block';
        }

        function closeReceiptModal() {
            const modal = document.getElementById('receiptModal');
            modal.style.display = 'none';
            
            // Reset image
            const imgElement = document.getElementById('receiptImage');
            imgElement.src = '';
            imgElement.style.display = 'none';
            
            // Hide error
            document.getElementById('receiptError').style.display = 'none';
        }

        // Completed management functions
        function confirmCompleted(completedID) {
            if (!confirm("Mark this appointment as fully completed and paid?")) {
                return;
            }

            fetch('mark_completed_done.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'completed_id=' + encodeURIComponent(completedID)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("✅ Marked as completed!");
                    location.reload();
                } else {
                    alert("❌ Error: " + data.message);
                }
            })
            .catch(err => {
                alert("⚠️ Request failed: " + err);
            });
        }

        function deleteCompleted(completedID) {
            if (!confirm("Are you sure you want to delete this completed record? This cannot be undone.")) {
                return;
            }

            fetch('delete_completed.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'completed_id=' + encodeURIComponent(completedID)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("✅ Record deleted!");
                    location.reload();
                } else {
                    alert("⚠️ Error: " + data.message);
                }
            })
            .catch(err => {
                alert("⚠️ Request failed: " + err);
            });
        }

        // Mobile sidebar toggle
        function toggleMobileSidebar() {
            document.querySelector('.sidebar').classList.toggle('mobile-open');
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('receiptModal');
            if (event.target === modal) {
                closeReceiptModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeReceiptModal();
            }
        });

        // Page load handler
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Completed page loaded');
            console.log('Total Completed on page:', <?php echo count($completeds); ?>);
            console.log('viewReceipt function defined:', typeof viewReceipt === 'function');
        });
    </script>

</body>
</html>