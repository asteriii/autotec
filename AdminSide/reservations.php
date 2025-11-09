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
$count_sql = "SELECT COUNT(*) as total FROM reservations $search_condition";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch reservations with vehicle type information
$sql = "SELECT r.*, vt.Name as VehicleTypeName, vt.Price as VehiclePrice 
        FROM reservations r 
        LEFT JOIN vehicle_types vt ON r.TypeID = vt.VehicleTypeID 
        $search_condition 
        ORDER BY r.Date DESC, r.Time DESC 
        LIMIT $records_per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Reservations - AutoTec Admin</title>
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

        .reservation-card {
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

        .reservation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #c0392b, #a93226);
        }

        .reservation-card:hover {
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

        .reservation-id {
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

        .view-receipt-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(66, 153, 225, 0.4);
        }

        .view-receipt-btn:disabled {
            background: #e2e8f0;
            color: #a0aec0;
            cursor: not-allowed;
            box-shadow: none;
        }

        .view-receipt-btn:disabled:hover {
            transform: none;
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
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3e0;
            color: #ff9800;
        }

        .status-confirmed {
            background: #e8f5e9;
            color: #4caf50;
        }

        .no-reservations {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .no-reservations i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Receipt Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            position: relative;
            margin: 2% auto;
            padding: 20px;
            width: 90%;
            max-width: 800px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .modal-header h3 {
            color: #2d3748;
            font-size: 24px;
        }

        .close-modal {
            font-size: 32px;
            font-weight: bold;
            color: #718096;
            cursor: pointer;
            transition: color 0.3s;
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
        }

        .close-modal:hover {
            color: #c0392b;
        }

        .receipt-image-container {
            text-align: center;
            margin: 20px 0;
        }

        .receipt-image {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .payment-info {
            background: #f7fafc;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .payment-info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .payment-info-row:last-child {
            border-bottom: none;
        }

        .payment-info-label {
            font-weight: 600;
            color: #4a5568;
        }

        .payment-info-value {
            color: #2d3748;
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
            <button class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>

        <div class="content">
            <h2><i class="fas fa-calendar-check"></i> Reservations Management</h2>

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

            <?php if (empty($reservations)): ?>
                <div class="no-reservations">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No reservations found</h3>
                    <p>There are currently no reservations matching your criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reservations as $reservation): ?>
                    <div class="reservation-card">
                        <div class="card-header">
                            <div class="reservation-id">ID: <?php echo $reservation['ReservationID']; ?></div>
                            <div class="status-badge <?php echo $reservation['PaymentStatus'] === 'confirmed' ? 'status-confirmed' : 'status-pending'; ?>">
                                <?php echo ucfirst($reservation['PaymentStatus'] ?? 'Pending'); ?>
                            </div>
                        </div>
                        
                        <div class="card-content">
                            <div class="info-section">
                                <h4><i class="fas fa-user"></i> Customer Details</h4>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($reservation['Fname'] . ' ' . $reservation['Mname'] . ' ' . $reservation['Lname']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($reservation['PhoneNum']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($reservation['Email']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($reservation['Address']); ?></p>
                            </div>
                            
                            <div class="info-section">
                                <h4><i class="fas fa-car"></i> Vehicle Details</h4>
                                <p><strong>Brand:</strong> <?php echo htmlspecialchars($reservation['Brand']); ?></p>
                                <p><strong>Plate No:</strong> <?php echo htmlspecialchars($reservation['PlateNo']); ?></p>
                                <p><strong>Vehicle Type:</strong> <?php echo htmlspecialchars($reservation['VehicleTypeName'] ?? 'Unknown'); ?></p>
                                <p><strong>Category:</strong> <?php echo htmlspecialchars($categories[$reservation['CategoryID']] ?? 'Unknown'); ?></p>
                            </div>
                            
                            <div class="info-section">
                                <h4><i class="fas fa-calendar-alt"></i> Appointment Details</h4>
                                <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($reservation['Date'])); ?></p>
                                <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($reservation['Time'])); ?></p>
                                <p><strong>Payment Method:</strong> <?php echo strtoupper($reservation['PaymentMethod'] ?? 'N/A'); ?></p>
                                <div class="price-tag">
                                    ₱<?php echo number_format($reservation['VehiclePrice'] ?? 0, 2); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="buttons">
                            <?php if (!empty($reservation['PaymentReceipt']) && $reservation['PaymentMethod'] === 'gcash'): ?>
                                <button class="btn view-receipt-btn" onclick="viewReceipt('<?php echo htmlspecialchars($reservation['PaymentReceipt']); ?>', '<?php echo htmlspecialchars($reservation['ReferenceNumber'] ?? 'N/A'); ?>', '<?php echo htmlspecialchars($reservation['PaymentMethod']); ?>', '<?php echo htmlspecialchars($reservation['PaymentStatus'] ?? 'pending'); ?>')">
                                    <i class="fas fa-receipt"></i> View Receipt
                                </button>
                            <?php else: ?>
                                <button class="btn view-receipt-btn" disabled>
                                    <i class="fas fa-receipt"></i> No Receipt
                                </button>
                            <?php endif; ?>
                            <button class="btn confirm-btn" onclick="confirmReservation(<?php echo $reservation['ReservationID']; ?>)">
                                <i class="fas fa-check"></i> Confirm
                            </button>
                            <button class="btn cancel-btn" onclick="cancelReservation(<?php echo $reservation['ReservationID']; ?>)">
                                <i class="fas fa-times"></i> Cancel
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
                    <span class="payment-info-label">Reference Number:</span>
                    <span class="payment-info-value" id="modalReferenceNumber">-</span>
                </div>
                <div class="payment-info-row">
                    <span class="payment-info-label">Payment Method:</span>
                    <span class="payment-info-value" id="modalPaymentMethod">-</span>
                </div>
                <div class="payment-info-row">
                    <span class="payment-info-label">Payment Status:</span>
                    <span class="payment-info-value" id="modalPaymentStatus">-</span>
                </div>
            </div>
            
            <div class="receipt-image-container">
                <img id="receiptImage" class="receipt-image" src="" alt="Payment Receipt">
            </div>
        </div>
    </div>

    <script>
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

        function viewReceipt(receiptPath, referenceNumber, paymentMethod, paymentStatus) {
            // Set modal content
            document.getElementById('modalReferenceNumber').textContent = referenceNumber;
            document.getElementById('modalPaymentMethod').textContent = paymentMethod.toUpperCase();
            document.getElementById('modalPaymentStatus').textContent = paymentStatus.charAt(0).toUpperCase() + paymentStatus.slice(1);
            
            // Set image source - ensure path starts with /
            const imagePath = receiptPath.startsWith('/') ? receiptPath : '/' + receiptPath;
            document.getElementById('receiptImage').src = imagePath;
            
            // Show modal
            document.getElementById('receiptModal').style.display = 'block';
        }

        function closeReceiptModal() {
            document.getElementById('receiptModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('receiptModal');
            if (event.target === modal) {
                closeReceiptModal();
            }
        }

        function confirmReservation(id) {
            if (confirm('Are you sure you want to confirm this reservation?')) {
                window.location.href = 'confirm_reservation.php?id=' + id;
            }
        }

        function cancelReservation(id) {
            if (confirm('Are you sure you want to cancel this reservation?')) {
                window.location.href = 'cancel_reservation.php?id=' + id;
            }
        }

        // Mobile sidebar toggle
        function toggleMobileSidebar() {
            document.querySelector('.sidebar').classList.toggle('mobile-open');
        }
    </script>
</body>
</html>