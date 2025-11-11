<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Fetch user's reservations with related data - UPDATED WITH PAYMENT INFO
$sql = "SELECT r.*, vt.Name AS VehicleType, vc.Name AS Category, 
        vt.Price, vt.Name AS VehicleTypeName, vc.Name AS CategoryName,
        r.PaymentMethod, r.PaymentStatus, r.PaymentReceipt, r.ReferenceNumber,
        r.CreatedAt
        FROM reservations r 
        LEFT JOIN vehicle_types vt ON r.TypeID = vt.VehicleTypeID 
        LEFT JOIN vehicle_categories vc ON r.CategoryID = vc.CategoryID 
        WHERE r.UserID = ? 
        ORDER BY STR_TO_DATE(r.Date, '%Y-%m-%d') DESC, 
                 STR_TO_DATE(r.Time, '%H:%i:%s') DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$reservations = [];

while ($row = mysqli_fetch_assoc($result)) {
    $reservations[] = $row;
}
mysqli_stmt_close($stmt);

// Get user name for display
$user_sql = "SELECT Fname FROM users WHERE UserID = ?";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stmt_bind_param($user_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user_data = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($user_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>My Reservations - AutoTEC</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #e8f4f8 0%, #d1e7dd 100%);
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .page-title {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .page-title .highlight {
            color: #bd1e51;
        }

        .breadcrumb {
            color: #666;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 1px;
        }

        /* Stats Cards */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .stat-card.active {
            border-color: #bd1e51;
            background: linear-gradient(135deg, #bd1e51, #d63969);
            color: white;
        }

        .stat-card.active .stat-number {
            color: white;
        }

        .stat-card.active .stat-label {
            color: rgba(255, 255, 255, 0.9);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: 600;
            color: #bd1e51;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Filter Info */
        .filter-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-info.active {
            display: flex;
        }

        .filter-text {
            color: #666;
            font-weight: 500;
        }

        .clear-filter {
            background: #bd1e51;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .clear-filter:hover {
            background: #a01a45;
        }

        /* Reservations Grid */
        .reservations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }

        .reservation-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 2px solid transparent;
            padding: 20px;
        }

        .reservation-card.hidden {
            display: none;
        }

        .reservation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #bd1e51, #d63969);
        }

        .reservation-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            border-color: #bd1e51;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .reservation-id {
            background: linear-gradient(135deg, #bd1e51, #d63969);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Payment Status Badge */
        .payment-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .payment-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .payment-badge.paid {
            background: #d4edda;
            color: #155724;
        }

        .payment-badge.verified {
            background: #d1ecf1;
            color: #0c5460;
        }

        .payment-method {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        /* Payment Info Box */
        .payment-info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 11px;
            color: #856404;
        }

        .payment-info-box.pending-gcash {
            background: #fff3cd;
            border-left-color: #ffc107;
        }

        .payment-info-box.pending-onsite {
            background: #e7f3ff;
            border-left-color: #2196F3;
            color: #0c5460;
        }

        .payment-info-box strong {
            display: block;
            margin-bottom: 3px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .action-btn {
            flex: 1;
            min-width: 100px;
            background: white;
            color: #bd1e51;
            border: 2px solid #bd1e51;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            text-decoration: none;
        }

        .action-btn:hover {
            background: #bd1e51;
            color: white;
            transform: translateY(-1px);
        }

        .action-btn.primary {
            background: #bd1e51;
            color: white;
        }

        .action-btn.primary:hover {
            background: #a01a45;
        }

        .action-btn.cancel {
            border-color: #dc3545;
            color: #dc3545;
        }

        .action-btn.cancel:hover {
            background: #dc3545;
            color: white;
        }

        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .card-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            color: #666;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .info-value {
            color: #333;
            font-size: 14px;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .card-date {
            background: #f8f9fa;
            color: #333;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            text-align: center;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #bd1e51;
        }

        /* Time slot styles */
        .time-slot-option {
            padding: 10px;
            position: relative;
        }

        .time-slot-option.full {
            color: #dc3545;
            background-color: #f8d7da;
        }

        .time-slot-option.available {
            color: #28a745;
        }

        .time-slot-option.limited {
            color: #ffc107;
        }

        .slot-indicator {
            font-size: 11px;
            margin-left: 5px;
        }

        .loading-slots {
            text-align: center;
            padding: 10px;
            color: #666;
            font-style: italic;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .btn-primary {
            background: #bd1e51;
            color: white;
        }

        .btn-primary:hover {
            background: #a01a45;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        /* Success/Error Messages */
        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            font-weight: 500;
            z-index: 2000;
            transform: translateX(400px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .message.show {
            transform: translateX(0);
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            grid-column: 1 / -1;
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e9ecef, #f8f9fa);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: #666;
        }

        .empty-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .empty-description {
            color: #666;
            font-size: 16px;
            margin-bottom: 25px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .hero {
                padding: 30px 20px;
            }

            .page-title {
                font-size: 2em;
            }

            .reservations-grid {
                grid-template-columns: 1fr;
            }

            .card-info {
                grid-template-columns: 1fr;
            }

            .stats-section {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .modal-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1 class="page-title">MY <span class="highlight">RESERVATIONS</span></h1>
            <div class="breadcrumb">HOME &gt; MY RESERVATIONS</div>
        </section>

        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stat-card active" onclick="filterReservations('all')" data-filter="all">
                <div class="stat-number"><?php echo count($reservations); ?></div>
                <div class="stat-label">Total Reservations</div>
            </div>
            <div class="stat-card" onclick="filterReservations('upcoming')" data-filter="upcoming">
                <div class="stat-number">
                    <?php 
                    $upcoming = 0;
                    foreach ($reservations as $reservation) {
                        if (strtotime($reservation['Date']) >= strtotime('today')) {
                            $upcoming++;
                        }
                    }
                    echo $upcoming;
                    ?>
                </div>
                <div class="stat-label">Upcoming</div>
            </div>
            <div class="stat-card" onclick="filterReservations('thisMonth')" data-filter="thisMonth">
                <div class="stat-number">
                    <?php 
                    $thisMonth = 0;
                    $currentMonth = date('Y-m');
                    foreach ($reservations as $reservation) {
                        if (date('Y-m', strtotime($reservation['Date'])) === $currentMonth) {
                            $thisMonth++;
                        }
                    }
                    echo $thisMonth;
                    ?>
                </div>
                <div class="stat-label">This Month</div>
            </div>
        </div>

        <!-- Filter Info -->
        <div class="filter-info" id="filterInfo">
            <span class="filter-text" id="filterText">Showing all reservations</span>
            <button class="clear-filter" onclick="clearFilter()">Show All</button>
        </div>

        <!-- Reservations List -->
        <div class="reservations-grid">
            <?php if (empty($reservations)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h2 class="empty-title">No Reservations Yet</h2>
                    <p class="empty-description">You haven't made any reservations yet. Start by booking your first service!</p>
                    <a href="registration.php" class="btn btn-primary">Make a Reservation</a>
                </div>
            <?php else: ?>
                <?php foreach ($reservations as $reservation): 
                    $isPast = strtotime($reservation['Date']) < strtotime('today');
                    $canModify = !$isPast && strtotime($reservation['Date'] . ' ' . $reservation['Time']) > strtotime('+24 hours');
                    $paymentStatus = $reservation['PaymentStatus'] ?? 'pending';
                    $paymentMethod = $reservation['PaymentMethod'] ?? 'onsite';
                ?>
                    <div class="reservation-card" 
                         data-date="<?php echo htmlspecialchars($reservation['Date']); ?>"
                         data-reservation-id="<?php echo htmlspecialchars($reservation['ReservationID']); ?>"
                         data-branch="<?php echo htmlspecialchars($reservation['BranchName']); ?>"
                         data-reservation-data="<?php echo htmlspecialchars(json_encode($reservation)); ?>">
                        
                        <div class="card-header">
                            <div>
                                <div class="reservation-id">
                                    <?php echo htmlspecialchars($reservation['ReferenceNumber'] ?? 'NO-' . $reservation['ReservationID']); ?>
                                </div>
                                <div class="payment-method">
                                    <?php 
                                    echo $paymentMethod === 'gcash' ? '💳 GCash' : '🏢 On-Site Payment';
                                    ?>
                                </div>
                            </div>
                            <span class="payment-badge <?php echo strtolower($paymentStatus); ?>">
                                <?php echo strtoupper($paymentStatus); ?>
                            </span>
                        </div>

                        <!-- Payment Status Info -->
                        <?php if ($paymentStatus === 'pending'): ?>
                            <div class="payment-info-box <?php echo $paymentMethod === 'gcash' ? 'pending-gcash' : 'pending-onsite'; ?>">
                                <?php if ($paymentMethod === 'gcash'): ?>
                                    <strong>⏳ Payment Verification Pending</strong>
                                    Your GCash payment receipt is being verified by our team. You will be notified once approved.
                                <?php else: ?>
                                    <strong>💰 Payment Required</strong>
                                    Please pay at the testing center before your appointment. Download your receipt below.
                                <?php endif; ?>
                            </div>
                        <?php elseif ($paymentStatus === 'verified' || $paymentStatus === 'paid'): ?>
                            <div class="payment-info-box" style="background: #d4edda; border-left-color: #28a745; color: #155724;">
                                <strong>✓ Payment Confirmed</strong>
                                Your payment has been verified. See you on your appointment date!
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-info">
                            <div class="info-item">
                                <div class="info-label">Customer</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars(trim(($reservation['Fname'] ?? '') . ' ' . ($reservation['Lname'] ?? ''))); ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Plate Number</div>
                                <div class="info-value"><?php echo htmlspecialchars($reservation['PlateNo'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Vehicle Type</div>
                                <div class="info-value"><?php echo htmlspecialchars($reservation['VehicleType'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Branch</div>
                                <div class="info-value"><?php echo htmlspecialchars($reservation['BranchName'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        
                        <div class="card-date">
                            <?php 
                            $date = $reservation['Date'] ?? '';
                            $time = $reservation['Time'] ?? '';
                            if ($date && $time) {
                                echo date('M d, Y • g:i A', strtotime($date . ' ' . $time));
                            } else {
                                echo 'Date/Time not available';
                            }
                            ?>
                        </div>

                        <div class="action-buttons">
                            <button class="action-btn primary" onclick="generatePDF(<?php echo $reservation['ReservationID']; ?>)">
                                📄 Receipt
                            </button>
                            <?php if ($canModify && $paymentStatus === 'pending'): ?>
                                <button class="action-btn" onclick="openRescheduleModal(<?php echo $reservation['ReservationID']; ?>)">
                                    📅 Reschedule
                                </button>
                                <button class="action-btn cancel" onclick="confirmCancel(<?php echo $reservation['ReservationID']; ?>)">
                                    ❌ Cancel
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Reschedule Appointment</h2>
                <p style="color: #666; font-size: 14px;">Select a new date and time for your appointment</p>
            </div>
            <div class="modal-body">
                <form id="rescheduleForm">
                    <input type="hidden" id="rescheduleReservationId" name="reservationId">
                    <input type="hidden" id="rescheduleBranch" name="branchName">
                    
                    <div class="form-group">
                        <label for="rescheduleDate">New Date</label>
                        <input type="date" id="rescheduleDate" name="newDate" required 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="rescheduleTime">New Time</label>
                        <select id="rescheduleTime" name="newTime" required>
                            <option value="">Select a date first...</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="rescheduleReason">Reason for Rescheduling (Optional)</label>
                        <textarea id="rescheduleReason" name="reason" rows="3" 
                                  placeholder="Let us know why you need to reschedule..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRescheduleModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitReschedule()">Confirm Reschedule</button>
            </div>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Cancel Appointment</h2>
                <p style="color: #dc3545; font-size: 14px;">⚠️ This action cannot be undone</p>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 20px;">Are you sure you want to cancel this appointment?</p>
                <form id="cancelForm">
                    <input type="hidden" id="cancelReservationId" name="reservationId">
                    
                    <div class="form-group">
                        <label for="cancelReason">Reason for Cancellation</label>
                        <textarea id="cancelReason" name="reason" rows="3" required
                                  placeholder="Please tell us why you're cancelling..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCancelModal()">Keep Appointment</button>
                <button type="button" class="btn btn-danger" onclick="submitCancel()">Yes, Cancel</button>
            </div>
        </div>
    </div>

    <!-- Message Toast -->
    <div id="messageToast" class="message"></div>

    <?php include 'footer.php'; ?>

    <script>
        let currentFilter = 'all';
        let currentBranch = '';
        
        // Define all
        // Add this script section at the end of your existing JavaScript (before closing)

// Function to open reschedule modal
function openRescheduleModal(reservationId) {
    const card = document.querySelector(`[data-reservation-id="${reservationId}"]`);
    const reservationData = JSON.parse(card.dataset.reservationData);
    
    document.getElementById('rescheduleReservationId').value = reservationId;
    document.getElementById('rescheduleBranch').value = reservationData.BranchName;
    
    // Reset form
    document.getElementById('rescheduleDate').value = '';
    document.getElementById('rescheduleTime').innerHTML = '<option value="">Select a date first...</option>';
    document.getElementById('rescheduleReason').value = '';
    
    document.getElementById('rescheduleModal').classList.add('active');
}

// Function to close reschedule modal
function closeRescheduleModal() {
    document.getElementById('rescheduleModal').classList.remove('active');
}

// Load available time slots when date is selected
// Load available time slots when date is selected
document.getElementById('rescheduleDate').addEventListener('change', function() {
    const selectedDate = this.value;
    const branchName = document.getElementById('rescheduleBranch').value;
    const timeSelect = document.getElementById('rescheduleTime');
    
    if (!selectedDate) return;
    
    // Show loading state
    timeSelect.innerHTML = '<option value="" class="loading-slots">Loading available times...</option>';
    timeSelect.disabled = true;
    
    // Fetch available time slots using GET parameters
    fetch(`check_availability.php?date=${encodeURIComponent(selectedDate)}&branchName=${encodeURIComponent(branchName)}`)
    .then(response => response.json())
    .then(data => {
        timeSelect.innerHTML = '';
        timeSelect.disabled = false;
        
        // Check if there's an error
        if (data.error || !data.success) {
            console.error('API Error:', data.error);
            timeSelect.innerHTML = '<option value="">Error: ' + (data.error || 'Unknown error') + '</option>';
            showMessage('Error loading time slots: ' + (data.error || 'Unknown error'), 'error');
            return;
        }
        
        // Use the slots returned from the API
        const slots = data.slots || [];
        let hasAvailableSlots = false;
        
        // Add default option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = 'Select a time slot';
        timeSelect.appendChild(defaultOption);
        
        // Process each time slot from API
        slots.forEach(slot => {
            const option = document.createElement('option');
            option.value = slot.time;
            
            const availableSlots = slot.available;
            const displayTime = slot.display;
            
            // Determine availability status
            let statusClass = '';
            let statusText = '';
            
            if (availableSlots <= 0) {
                statusClass = 'full';
                statusText = ' (FULL)';
                option.disabled = true;
            } else if (availableSlots <= 1) {
                statusClass = 'limited';
                statusText = ` (${availableSlots} slot left)`;
                hasAvailableSlots = true;
            } else if (availableSlots <= 2) {
                statusClass = 'limited';
                statusText = ` (${availableSlots} slot left)`;
                hasAvailableSlots = true;
            } else {
                statusClass = 'available';
                statusText = ` (${availableSlots} available)`;
                hasAvailableSlots = true;
            }
            
            option.className = `time-slot-option ${statusClass}`;
            option.textContent = `${displayTime}${statusText}`;
            
            timeSelect.appendChild(option);
        });
        
        // Show message if no slots available
        if (!hasAvailableSlots) {
            showMessage('No available time slots for the selected date', 'info');
        }
    })
    .catch(error => {
        console.error('Error fetching time slots:', error);
        timeSelect.innerHTML = '<option value="">Error loading time slots. Please try again.</option>';
        timeSelect.disabled = false;
        showMessage('Error loading time slots. Please try again.', 'error');
    });
});

// Function to submit reschedule
function submitReschedule() {
    const form = document.getElementById('rescheduleForm');
    const reservationId = document.getElementById('rescheduleReservationId').value;
    const newDate = document.getElementById('rescheduleDate').value;
    const newTime = document.getElementById('rescheduleTime').value;
    const reason = document.getElementById('rescheduleReason').value;
    
    // Validation
    if (!newDate || !newTime) {
        showMessage('Please select both date and time', 'error');
        return;
    }
    
    // Disable submit button to prevent double submission
    const submitBtn = event.target;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    
    // Create form data
    const formData = new FormData();
    formData.append('reservationId', reservationId);
    formData.append('newDate', newDate);
    formData.append('newTime', newTime);
    formData.append('reason', reason);
    
    // Submit reschedule request
    fetch('reschedule_reservation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Appointment rescheduled successfully!', 'success');
            closeRescheduleModal();
            
            // Reload page after short delay to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showMessage(data.message || 'Failed to reschedule appointment', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirm Reschedule';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred. Please try again.', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Confirm Reschedule';
    });
}

// Function to confirm cancel
function confirmCancel(reservationId) {
    document.getElementById('cancelReservationId').value = reservationId;
    document.getElementById('cancelReason').value = '';
    document.getElementById('cancelModal').classList.add('active');
}

// Function to close cancel modal
function closeCancelModal() {
    document.getElementById('cancelModal').classList.remove('active');
}

// Function to submit cancel
function submitCancel() {
    const reservationId = document.getElementById('cancelReservationId').value;
    const reason = document.getElementById('cancelReason').value;
    
    if (!reason.trim()) {
        showMessage('Please provide a reason for cancellation', 'error');
        return;
    }
    
    // Disable submit button
    const submitBtn = event.target;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Cancelling...';
    
    // Create form data
    const formData = new FormData();
    formData.append('reservationId', reservationId);
    formData.append('reason', reason);
    
    // Submit cancellation request
    fetch('cancel_reservation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Appointment cancelled successfully', 'success');
            closeCancelModal();
            
            // Reload page after short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showMessage(data.message || 'Failed to cancel appointment', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Yes, Cancel';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred. Please try again.', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Yes, Cancel';
    });
}

// Function to show message toast
function showMessage(message, type = 'info') {
    const toast = document.getElementById('messageToast');
    toast.textContent = message;
    toast.className = `message ${type}`;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

// Function to filter reservations
function filterReservations(filter) {
    currentFilter = filter;
    
    // Update active stat card
    document.querySelectorAll('.stat-card').forEach(card => {
        card.classList.remove('active');
        if (card.dataset.filter === filter) {
            card.classList.add('active');
        }
    });
    
    // Filter reservation cards
    const cards = document.querySelectorAll('.reservation-card');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();
    
    let visibleCount = 0;
    
    cards.forEach(card => {
        const dateStr = card.dataset.date;
        const cardDate = new Date(dateStr);
        cardDate.setHours(0, 0, 0, 0);
        
        let show = false;
        
        switch(filter) {
            case 'all':
                show = true;
                break;
            case 'upcoming':
                show = cardDate >= today;
                break;
            case 'thisMonth':
                show = cardDate.getMonth() === currentMonth && 
                       cardDate.getFullYear() === currentYear;
                break;
        }
        
        if (show) {
            card.classList.remove('hidden');
            visibleCount++;
        } else {
            card.classList.add('hidden');
        }
    });
    
    // Update filter info
    const filterInfo = document.getElementById('filterInfo');
    const filterText = document.getElementById('filterText');
    
    if (filter === 'all') {
        filterInfo.classList.remove('active');
    } else {
        filterInfo.classList.add('active');
        const filterLabels = {
            'upcoming': 'Showing upcoming reservations',
            'thisMonth': 'Showing reservations for this month'
        };
        filterText.textContent = `${filterLabels[filter]} (${visibleCount} found)`;
    }
}

// Function to clear filter
function clearFilter() {
    filterReservations('all');
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
});

// Function to generate PDF receipt (placeholder - implement based on your needs)
function generatePDF(reservationId) {
    const card = document.querySelector(`[data-reservation-id="${reservationId}"]`);
    const reservationData = JSON.parse(card.dataset.reservationData);
    
    // You can implement PDF generation here using jsPDF
    // For now, we'll redirect to a receipt page or download endpoint
    window.open(`receipt.php?id=${reservationId}`, '_blank');
}
</script>