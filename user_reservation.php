<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Fetch user's active reservations
$sql = "SELECT r.*, vt.Name AS VehicleType, vc.Name AS Category, 
        vt.Price, vt.Name AS VehicleTypeName, vc.Name AS CategoryName,
        r.PaymentMethod, r.PaymentStatus, r.PaymentReceipt, r.ReferenceNumber,
        r.CreatedAt, 'active' as Status
        FROM reservations r 
        LEFT JOIN vehicle_types vt ON r.TypeID = vt.VehicleTypeID 
        LEFT JOIN vehicle_categories vc ON r.CategoryID = vc.CategoryID 
        WHERE r.UserID = ? 
        ORDER BY STR_TO_DATE(r.Date, '%Y-%m-%d') DESC, 
                 STR_TO_DATE(r.Time, '%H:%i:%s') DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$active_reservations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $active_reservations[] = $row;
}
mysqli_stmt_close($stmt);

// Fetch rescheduled reservations
$reschedule_sql = "SELECT r.*, vt.Name AS VehicleType, vc.Name AS Category, 
        vt.Price, vt.Name AS VehicleTypeName, vc.Name AS CategoryName,
        r.PaymentMethod, r.PaymentStatus, r.PaymentReceipt, r.ReferenceNumber,
        r.CreatedAt, r.RescheduledAt, r.NewDate, r.NewTime, r.Reason, 'rescheduled' as Status
        FROM reschedule r
        LEFT JOIN vehicle_types vt ON r.TypeID = vt.VehicleTypeID 
        LEFT JOIN vehicle_categories vc ON r.CategoryID = vc.CategoryID 
        WHERE r.UserID = ? 
        ORDER BY r.RescheduledAt DESC";

$reschedule_stmt = mysqli_prepare($conn, $reschedule_sql);
mysqli_stmt_bind_param($reschedule_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($reschedule_stmt);
$reschedule_result = mysqli_stmt_get_result($reschedule_stmt);
$rescheduled_reservations = [];
while ($row = mysqli_fetch_assoc($reschedule_result)) {
    $rescheduled_reservations[] = $row;
}
mysqli_stmt_close($reschedule_stmt);

// Fetch completed reservations
$completed_sql = "SELECT c.*, vt.Name AS VehicleType, vc.Name AS Category, 
        vt.Price, vt.Name AS VehicleTypeName, vc.Name AS CategoryName,
        c.PaymentMethod, c.PaymentStatus, c.PaymentReceipt, c.ReferenceNumber,
        c.CreatedAt, c.CompletedAt, 'completed' as Status
        FROM completed c
        LEFT JOIN vehicle_types vt ON c.TypeID = vt.VehicleTypeID 
        LEFT JOIN vehicle_categories vc ON c.CategoryID = vc.CategoryID 
        WHERE c.UserID = ? 
        ORDER BY c.CompletedAt DESC";

$completed_stmt = mysqli_prepare($conn, $completed_sql);
mysqli_stmt_bind_param($completed_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($completed_stmt);
$completed_result = mysqli_stmt_get_result($completed_stmt);
$completed_reservations = [];
while ($row = mysqli_fetch_assoc($completed_result)) {
    $completed_reservations[] = $row;
}
mysqli_stmt_close($completed_stmt);

// Calculate totals
$active_count = count($active_reservations);
$reschedule_count = count($rescheduled_reservations);
$completed_count = count($completed_reservations);
$total_count = $active_count + $reschedule_count + $completed_count;

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

        /* Status-specific colors */
        .reservation-card.rescheduled::before {
            background: linear-gradient(135deg, #ffc107, #ff9800);
        }

        .reservation-card.completed::before {
            background: linear-gradient(135deg, #28a745, #20c997);
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

        /* Status Badge */
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.active {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-badge.rescheduled {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.completed {
            background: #d4edda;
            color: #155724;
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

        .payment-info-box.rescheduled-info {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }

        .payment-info-box.completed-info {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
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
                <div class="stat-number"><?php echo $total_count; ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-card" onclick="filterReservations('pending')" data-filter="pending">
                <div class="stat-number"><?php echo $active_count; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card" onclick="filterReservations('rescheduled')" data-filter="rescheduled">
                <div class="stat-number"><?php echo $reschedule_count; ?></div>
                <div class="stat-label">Reschedule Request</div>
            </div>
            <div class="stat-card" onclick="filterReservations('completed')" data-filter="completed">
                <div class="stat-number"><?php echo $completed_count; ?></div>
                <div class="stat-label">Reserved</div>
            </div>
        </div>

        <!-- Filter Info -->
        <div class="filter-info" id="filterInfo">
            <span class="filter-text" id="filterText">Showing all reservations</span>
            <button class="clear-filter" onclick="clearFilter()">Show All</button>
        </div>

        <!-- Reservations List -->
        <div class="reservations-grid" id="reservationsGrid">
            <!-- Content will be populated by JavaScript -->
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

    <!-- Message Toast -->
    <div id="messageToast" class="message"></div>

    <?php include 'footer.php'; ?>

    <script>
        // Store all reservations data
        const allReservations = {
            active: <?php echo json_encode($active_reservations); ?>,
            rescheduled: <?php echo json_encode($rescheduled_reservations); ?>,
            completed: <?php echo json_encode($completed_reservations); ?>
        };

        let currentFilter = 'all';
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('All reservations:', allReservations);
            displayReservations('all');
        });

        // Function to display reservations based on filter
        function displayReservations(filter) {
            const grid = document.getElementById('reservationsGrid');
            grid.innerHTML = '';
            
            let reservationsToShow = [];
            
            switch(filter) {
                case 'all':
                    reservationsToShow = [
                        ...allReservations.active,
                        ...allReservations.rescheduled,
                        ...allReservations.completed
                    ];
                    break;
                case 'pending':
                    reservationsToShow = allReservations.active;
                    break;
                case 'rescheduled':
                    reservationsToShow = allReservations.rescheduled;
                    break;
                case 'completed':
                    reservationsToShow = allReservations.completed;
                    break;
            }
            
            console.log('Filter:', filter, 'Count:', reservationsToShow.length);
            
            if (reservationsToShow.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <h2 class="empty-title">No ${filter === 'all' ? '' : filter.charAt(0).toUpperCase() + filter.slice(1)} Reservations</h2>
                        <p class="empty-description">${filter === 'all' ? "You haven't made any reservations yet." : `You don't have any ${filter} reservations.`}</p>
                        ${filter === 'all' ? '<a href="registration.php" class="btn btn-primary">Make a Reservation</a>' : ''}
                    </div>
                `;
                return;
            }
            
            reservationsToShow.forEach(reservation => {
                const card = createReservationCard(reservation);
                grid.innerHTML += card;
            });
        }

        // Function to create reservation card HTML
        function createReservationCard(data) {
            const status = data.Status || 'active';
            const isPast = new Date(data.Date) < new Date();
            const canModify = !isPast && status === 'active' && 
                             new Date(data.Date + ' ' + data.Time) > new Date(Date.now() + 24*60*60*1000);
            const paymentStatus = data.PaymentStatus || 'pending';
            const paymentMethod = data.PaymentMethod || 'onsite';
            
            // Format date and time
            let displayDate = data.Date;
            let displayTime = data.Time;
            
            // For rescheduled, show both old and new dates
            let dateDisplay = '';
            if (status === 'rescheduled') {
                dateDisplay = `
                    <div class="card-date" style="background: #fff3cd; margin-bottom: 8px;">
                        <strong>Original:</strong> ${formatDate(data.Date)} • ${formatTime(data.Time)}
                    </div>
                    <div class="card-date" style="background: #d4edda;">
                        <strong>Rescheduled to:</strong> ${formatDate(data.NewDate)} • ${formatTime(data.NewTime)}
                    </div>
                `;
            } else {
                dateDisplay = `
                    <div class="card-date">
                        ${formatDate(displayDate)} • ${formatTime(displayTime)}
                    </div>
                `;
            }
            
            // Status info box
            let statusInfoBox = '';
            if (status === 'active') {
                if (paymentStatus === 'pending') {
                    if (paymentMethod === 'gcash') {
                        statusInfoBox = `
                            <div class="payment-info-box pending-gcash">
                                <strong>⏳ Payment Verification Pending</strong>
                                Your GCash payment receipt is being verified by our team. You will be notified once approved.
                            </div>
                        `;
                    } else {
                        statusInfoBox = `
                            <div class="payment-info-box pending-onsite">
                                <strong>💰 Payment Required</strong>
                                Please pay at the testing center before your appointment. Download your receipt below.
                            </div>
                        `;
                    }
                } else if (paymentStatus === 'verified' || paymentStatus === 'paid') {
                    statusInfoBox = `
                        <div class="payment-info-box" style="background: #d4edda; border-left-color: #28a745; color: #155724;">
                            <strong>✓ Payment Confirmed</strong>
                            Your payment has been verified. See you on your appointment date!
                        </div>
                    `;
                }
            } else if (status === 'rescheduled') {
                statusInfoBox = `
                    <div class="payment-info-box rescheduled-info">
                        <strong>📅 Reservation Rescheduled</strong>
                        ${data.Reason ? 'Reason: ' + data.Reason : 'This appointment has been rescheduled.'}
                    </div>
                `;
            } else if (status === 'completed') {
                statusInfoBox = `
                    <div class="payment-info-box completed-info">
                        <strong>✅ Service Completed</strong>
                        This emission test was completed successfully on ${formatDate(data.CompletedAt || data.Date)}.
                    </div>
                `;
            }
            
            // Action buttons
            let actionButtons = `
                <button class="action-btn primary" onclick='generatePDF(${JSON.stringify(data)})'>
                    📄 Receipt
                </button>
            `;
            
            if (canModify && paymentStatus === 'pending') {
                actionButtons += `
                    <button class="action-btn" onclick="openRescheduleModal(${data.ReservationID}, '${data.BranchName}')">
                        📅 Reschedule
                    </button>
                `;
            }
            
            return `
                <div class="reservation-card ${status}" data-status="${status}">
                    <div class="card-header">
                        <div>
                            <div class="reservation-id">
                                ${data.ReferenceNumber || 'NO-' + data.ReservationID}
                            </div>
                            <div class="payment-method">
                                ${paymentMethod === 'gcash' ? '💳 GCash' : '🏢 On-Site Payment'}
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 5px; align-items: flex-end;">
                            <span class="status-badge ${status}">
                                ${status === 'rescheduled' ? 'REQUESTING' : status.toUpperCase()}
                            </span>
                            ${status === 'active' ? `<span class="payment-badge ${paymentStatus.toLowerCase()}">${paymentStatus.toUpperCase()}</span>` : ''}
                        </div>
                    </div>

                    ${statusInfoBox}
                    
                    <div class="card-info">
                        <div class="info-item">
                            <div class="info-label">Customer</div>
                            <div class="info-value">
                                ${(data.Fname || '') + ' ' + (data.Lname || '')}
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Plate Number</div>
                            <div class="info-value">${data.PlateNo || 'N/A'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Vehicle Type</div>
                            <div class="info-value">${data.VehicleType || 'N/A'}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Branch</div>
                            <div class="info-value">${data.BranchName || 'N/A'}</div>
                        </div>
                    </div>
                    
                    ${dateDisplay}

                    <div class="action-buttons">
                        ${actionButtons}
                    </div>
                </div>
            `;
        }

        // Helper function to format date
        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr + 'T00:00:00');
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return date.toLocaleDateString('en-US', options);
        }

        // Helper function to format time
        function formatTime(timeString) {
            if (!timeString) return 'N/A';
            if (timeString.includes('AM') || timeString.includes('PM')) {
                return timeString;
            }
            
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours, 10);
            const minute = minutes || '00';
            
            if (hour === 0) {
                return `12:${minute} AM`;
            } else if (hour < 12) {
                return `${hour}:${minute} AM`;
            } else if (hour === 12) {
                return `12:${minute} PM`;
            } else {
                return `${hour - 12}:${minute} PM`;
            }
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
            
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            
            // Display reservations
            displayReservations(filter);
            
            // Update filter info
            if (filter === 'all') {
                filterInfo.classList.remove('active');
            } else {
                filterInfo.classList.add('active');
                const filterLabels = {
                    'pending': 'Showing pending reservations',
                    'rescheduled': 'Showing rescheduled reservations',
                    'completed': 'Showing completed reservations'
                };
                
                const filterKey = filter === 'pending' ? 'active' : filter;
                const count = allReservations[filterKey].length;
                filterText.textContent = `${filterLabels[filter]} (${count} found)`;
            }
        }

        // Function to clear filter
        function clearFilter() {
            filterReservations('all');
        }

        // Function to open reschedule modal
        function openRescheduleModal(reservationId, branchName) {
            document.getElementById('rescheduleReservationId').value = reservationId;
            document.getElementById('rescheduleBranch').value = branchName;
            
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
                        statusText = ` (${availableSlots} slots left)`;
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

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        });

        // PDF Generation Function
        function generatePDF(data) {
            showMessage('Generating receipt...', 'info');
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            const primaryColor = '#bd1e51';
            
            function addHorizontalLine(y, width = 170) {
                doc.setDrawColor(224, 224, 224);
                doc.line(20, y, 20 + width, y);
            }
            
            function addColoredRect(x, y, width, height, color) {
                const rgb = hexToRgb(color);
                doc.setFillColor(rgb.r, rgb.g, rgb.b);
                doc.rect(x, y, width, height, 'F');
            }
            
            function hexToRgb(hex) {
                const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
                return result ? {
                    r: parseInt(result[1], 16),
                    g: parseInt(result[2], 16),
                    b: parseInt(result[3], 16)
                } : null;
            }
            
            function formatTimePDF(timeString) {
                if (!timeString) return 'N/A';
                if (timeString.includes('AM') || timeString.includes('PM')) {
                    return timeString;
                }
                
                const [hours, minutes] = timeString.split(':');
                const hour = parseInt(hours, 10);
                const minute = minutes || '00';
                
                if (hour === 0) {
                    return `12:${minute} AM`;
                } else if (hour < 12) {
                    return `${hour}:${minute} AM`;
                } else if (hour === 12) {
                    return `12:${minute} PM`;
                } else {
                    return `${hour - 12}:${minute} PM`;
                }
            }
            
            function formatDatePDF(dateString) {
                const date = new Date(dateString + 'T00:00:00');
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                };
                return date.toLocaleDateString('en-US', options);
            }
            
            // Header
            addColoredRect(0, 0, 210, 35, primaryColor);
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(24);
            doc.setFont('helvetica', 'bold');
            doc.text('AutoTEC', 20, 20);
            
            doc.setFontSize(12);
            doc.setFont('helvetica', 'normal');
            doc.text('Emission Testing Center', 20, 28);
            
            // Receipt title
            doc.setTextColor(51, 51, 51);
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text('APPOINTMENT RECEIPT', 105, 48, { align: 'center' });
            
            // Reference number section
            addColoredRect(20, 55, 170, 12, '#f8f9fa');
            doc.setTextColor(51, 51, 51);
            doc.setFontSize(11);
            doc.setFont('helvetica', 'bold');
            doc.text('Reference Number:', 25, 63);
            doc.setTextColor(189, 30, 81);
            doc.setFontSize(12);
            const refNumber = data.ReferenceNumber || `NO-${data.ReservationID}`;
            doc.text(refNumber, 185, 63, { align: 'right' });
            
            // Payment status section
            let yPos = 72;
            const paymentMethod = data.PaymentMethod || 'onsite';
            const paymentStatus = data.PaymentStatus || 'pending';
            
            let statusBgColor = '#fff3cd';
            if (paymentStatus === 'paid' || paymentStatus === 'verified') {
                statusBgColor = '#d4edda';
            }
            
            addColoredRect(20, yPos, 170, 10, statusBgColor);
            doc.setTextColor(51, 51, 51);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.text('Payment Status:', 25, yPos + 6);
            
            let statusTextColor = '#856404';
            if (paymentStatus === 'paid' || paymentStatus === 'verified') {
                statusTextColor = '#155724';
            }
            
            const statusRgb = hexToRgb(statusTextColor);
            doc.setTextColor(statusRgb.r, statusRgb.g, statusRgb.b);
            doc.text(paymentStatus.toUpperCase(), 70, yPos + 6);
            
            doc.setTextColor(51, 51, 51);
            doc.text('Payment Method:', 120, yPos + 6);
            doc.text(paymentMethod === 'gcash' ? 'GCash' : 'On-Site', 165, yPos + 6);
            
            // Generated date
            yPos += 12;
            doc.setTextColor(102, 102, 102);
            doc.setFontSize(9);
            doc.setFont('helvetica', 'normal');
            const now = new Date();
            doc.text(`Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`, 185, yPos, { align: 'right' });
            
            // Vehicle Information
            yPos += 8;
            doc.setTextColor(189, 30, 81);
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text('VEHICLE INFORMATION', 20, yPos);
            
            yPos += 3;
            addHorizontalLine(yPos);
            yPos += 8;
            
            doc.setTextColor(51, 51, 51);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            
            const vehicleInfo = [
                ['Plate Number:', (data.PlateNo || 'N/A').toUpperCase()],
                ['Vehicle Type:', data.VehicleTypeName || data.VehicleType || 'N/A'],
                ['Brand:', data.Brand || 'N/A'],
                ['Category:', data.CategoryName || data.Category || 'N/A']
            ];
            
            vehicleInfo.forEach(([label, value]) => {
                doc.setFont('helvetica', 'bold');
                doc.text(label, 25, yPos);
                doc.setFont('helvetica', 'normal');
                doc.text(value, 80, yPos);
                yPos += 8;
            });
            
            // Owner Information
            yPos += 5;
            doc.setTextColor(189, 30, 81);
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text('OWNER INFORMATION', 20, yPos);
            
            yPos += 3;
            addHorizontalLine(yPos);
            yPos += 8;
            
            doc.setTextColor(51, 51, 51);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            
            const fullName = data.Mname ? 
                `${data.Fname} ${data.Mname} ${data.Lname}` : 
                `${data.Fname} ${data.Lname}`;
            
            const ownerInfo = [
                ['Full Name:', fullName],
                ['Contact Number:', data.PhoneNum || 'N/A'],
                ['Email Address:', data.Email || 'N/A'],
                ['Address:', data.Address || 'N/A']
            ];
            
            ownerInfo.forEach(([label, value]) => {
                doc.setFont('helvetica', 'bold');
                doc.text(label, 25, yPos);
                doc.setFont('helvetica', 'normal');
                
                if (label === 'Address:' && value.length > 40) {
                    const lines = doc.splitTextToSize(value, 110);
                    doc.text(lines, 80, yPos);
                    yPos += (lines.length - 1) * 4;
                } else {
                    doc.text(value, 80, yPos);
                }
                yPos += 8;
            });
            
            // Appointment Details
            yPos += 5;
            doc.setTextColor(189, 30, 81);
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text('APPOINTMENT DETAILS', 20, yPos);
            
            yPos += 3;
            addHorizontalLine(yPos);
            yPos += 8;
            
            doc.setTextColor(51, 51, 51);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            
            const formattedDate = formatDatePDF(data.Date);
            const formattedTime = formatTimePDF(data.Time);
            
            const appointmentInfo = [
                ['Branch:', data.BranchName || 'AutoTEC'],
                ['Date:', formattedDate],
                ['Time:', formattedTime],
                ['Duration:', 'Approximately 20 minutes']
            ];
            
            appointmentInfo.forEach(([label, value]) => {
                doc.setFont('helvetica', 'bold');
                doc.text(label, 25, yPos);
                doc.setFont('helvetica', 'normal');
                doc.text(value, 80, yPos);
                yPos += 8;
            });
            
            // Fee Summary
            yPos += 5;
            addColoredRect(20, yPos - 3, 170, 18, '#f8f9fa');
            
            doc.setTextColor(189, 30, 81);
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text('FEE SUMMARY', 25, yPos + 3);
            
            const amount = parseFloat(data.Price) || 0;
            const serviceName = `${data.VehicleTypeName || data.VehicleType || 'Vehicle'} Emission Testing`;
            
            doc.setTextColor(51, 51, 51);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text(serviceName, 25, yPos + 12);
            
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(12);
            doc.text(`Php ${amount.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`, 185, yPos + 12, { align: 'right' });
            
            // Payment Instructions
            if (paymentStatus === 'pending') {
                yPos += 25;
                
                if (paymentMethod === 'gcash') {
                    addColoredRect(20, yPos - 3, 170, 25, '#fff3cd');
                    
                    doc.setTextColor(133, 100, 4);
                    doc.setFontSize(10);
                    doc.setFont('helvetica', 'bold');
                    doc.text('PAYMENT VERIFICATION PENDING:', 25, yPos + 3);
                    
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(8);
                    doc.text('• Your GCash payment receipt is being verified by our team', 25, yPos + 10);
                    doc.text('• You will be notified once your payment is approved', 25, yPos + 15);
                    doc.text('• Please wait for confirmation before your appointment date', 25, yPos + 20);
                } else {
                    addColoredRect(20, yPos - 3, 170, 25, '#e7f3ff');
                    
                    doc.setTextColor(12, 84, 96);
                    doc.setFontSize(10);
                    doc.setFont('helvetica', 'bold');
                    doc.text('PAYMENT INSTRUCTIONS:', 25, yPos + 3);
                    
                    doc.setFont('helvetica', 'normal');
                    doc.setFontSize(8);
                    doc.text('• Payment must be made at the testing center before your appointment', 25, yPos + 10);
                    doc.text('• Please bring this receipt and exact amount', 25, yPos + 15);
                    doc.text('• Cash payment only at the testing center', 25, yPos + 20);
                }
            } else if (paymentStatus === 'paid' || paymentStatus === 'verified') {
                yPos += 25;
                addColoredRect(20, yPos - 3, 170, 20, '#d4edda');
                
                doc.setTextColor(21, 87, 36);
                doc.setFontSize(10);
                doc.setFont('helvetica', 'bold');
                doc.text('PAYMENT CONFIRMED:', 25, yPos + 3);
                
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(8);
                doc.text('• Your payment has been verified and confirmed', 25, yPos + 10);
                doc.text('• Please arrive on time for your scheduled appointment', 25, yPos + 15);
            }
            
            // Important Notes
            yPos += 25;
            addColoredRect(20, yPos - 3, 170, 25, '#e8f4f8');
            
            doc.setTextColor(23, 162, 184);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.text('IMPORTANT REMINDERS:', 25, yPos + 3);
            
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(8);
            doc.setTextColor(51, 51, 51);
            doc.text('• Please arrive 15 minutes before your scheduled time', 25, yPos + 10);
            doc.text('• Bring this receipt and your vehicle registration documents', 25, yPos + 15);
            doc.text('• Vehicle must be physically present for testing', 25, yPos + 20);
            
            // Footer
            yPos += 25;
            addHorizontalLine(yPos);
            yPos += 8;
            
            doc.setTextColor(102, 102, 102);
            doc.setFontSize(8);
            doc.setFont('helvetica', 'italic');
            doc.text('Thank you for choosing AutoTEC Emission Testing Center', 105, yPos, { align: 'center' });
            
            yPos += 6;
            doc.setFontSize(7);
            doc.text('For inquiries, contact us at autotec_mandaluyong@yahoo.com or call 286527257', 105, yPos, { align: 'center' });

            // Save PDF
            const fileName = `AutoTEC_Receipt_${refNumber.replace(/[^a-zA-Z0-9]/g, '_')}.pdf`;
            doc.save(fileName);
            
            showMessage('Receipt downloaded successfully!', 'success');
        }
    </script>
</body>
</html>