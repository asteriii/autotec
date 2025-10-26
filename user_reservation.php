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
                ?>
                    <div class="reservation-card" 
                         data-date="<?php echo htmlspecialchars($reservation['Date']); ?>"
                         data-reservation-id="<?php echo htmlspecialchars($reservation['ReservationID']); ?>"
                         data-reservation-data="<?php echo htmlspecialchars(json_encode($reservation)); ?>">
                        
                        <div class="card-header">
                            <div>
                                <div class="reservation-id">
                                    <?php echo htmlspecialchars($reservation['ReferenceNumber'] ?? 'NO-' . $reservation['ReservationID']); ?>
                                </div>
                                <div class="payment-method">
                                    <?php 
                                    $paymentMethod = $reservation['PaymentMethod'] ?? 'onsite';
                                    echo $paymentMethod === 'gcash' ? '💳 GCash' : '🏢 On-Site Payment';
                                    ?>
                                </div>
                            </div>
                            <span class="payment-badge <?php echo strtolower($reservation['PaymentStatus'] ?? 'pending'); ?>">
                                <?php echo strtoupper($reservation['PaymentStatus'] ?? 'PENDING'); ?>
                            </span>
                        </div>
                        
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
                                Receipt
                            </button>
                            <?php if ($canModify): ?>
                                <button class="action-btn" onclick="openRescheduleModal(<?php echo $reservation['ReservationID']; ?>)">
                                    Reschedule
                                </button>
                                <button class="action-btn cancel" onclick="confirmCancel(<?php echo $reservation['ReservationID']; ?>)">
                                    Cancel
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
                    
                    <div class="form-group">
                        <label for="rescheduleDate">New Date</label>
                        <input type="date" id="rescheduleDate" name="newDate" required 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="rescheduleTime">New Time</label>
                        <select id="rescheduleTime" name="newTime" required>
                            <option value="">Select Time</option>
                            <option value="09:00:00">9:00 AM</option>
                            <option value="09:20:00">9:20 AM</option>
                            <option value="09:40:00">9:40 AM</option>
                            <option value="10:00:00">10:00 AM</option>
                            <option value="10:20:00">10:20 AM</option>
                            <option value="10:40:00">10:40 AM</option>
                            <option value="11:00:00">11:00 AM</option>
                            <option value="11:20:00">11:20 AM</option>
                            <option value="11:40:00">11:40 AM</option>
                            <option value="13:00:00">1:00 PM</option>
                            <option value="13:20:00">1:20 PM</option>
                            <option value="13:40:00">1:40 PM</option>
                            <option value="14:00:00">2:00 PM</option>
                            <option value="14:20:00">2:20 PM</option>
                            <option value="14:40:00">2:40 PM</option>
                            <option value="15:00:00">3:00 PM</option>
                            <option value="15:20:00">3:20 PM</option>
                            <option value="15:40:00">3:40 PM</option>
                            <option value="16:00:00">4:00 PM</option>
                            <option value="16:20:00">4:20 PM</option>
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

        function showMessage(message, type = 'info') {
            const toast = document.getElementById('messageToast');
            toast.textContent = message;
            toast.className = `message ${type}`;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        function filterReservations(filterType) {
            const cards = document.querySelectorAll('.reservation-card');
            const statCards = document.querySelectorAll('.stat-card');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            
            currentFilter = filterType;
            
            statCards.forEach(card => {
                card.classList.remove('active');
                if (card.dataset.filter === filterType) {
                    card.classList.add('active');
                }
            });
            
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const currentMonth = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');
            
            let visibleCount = 0;
            let filterDescription = '';
            
            cards.forEach(card => {
                const reservationDate = card.dataset.date;
                let shouldShow = false;
                
                if (!reservationDate) {
                    shouldShow = filterType === 'all';
                } else {
                    const cardDate = new Date(reservationDate + 'T00:00:00');
                    const cardMonth = cardDate.getFullYear() + '-' + String(cardDate.getMonth() + 1).padStart(2, '0');
                    
                    switch (filterType) {
                        case 'all':
                            shouldShow = true;
                            filterDescription = 'Showing all reservations';
                            break;
                            
                        case 'upcoming':
                            shouldShow = cardDate >= today;
                            filterDescription = 'Showing upcoming reservations';
                            break;
                            
                        case 'thisMonth':
                            shouldShow = cardMonth === currentMonth;
                            filterDescription = 'Showing this month\'s reservations';
                            break;
                            
                        default:
                            shouldShow = true;
                    }
                }
                
                if (shouldShow) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });
            
            if (filterType === 'all') {
                filterInfo.classList.remove('active');
            } else {
                filterInfo.classList.add('active');
                filterText.textContent = `${filterDescription} (${visibleCount} found)`;
            }
        }

        function clearFilter() {
            filterReservations('all');
        }

        // Reschedule Modal Functions
        function openRescheduleModal(reservationId) {
            document.getElementById('rescheduleReservationId').value = reservationId;
            document.getElementById('rescheduleModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeRescheduleModal() {
            document.getElementById('rescheduleModal').classList.remove('active');
            document.getElementById('rescheduleForm').reset();
            document.body.style.overflow = 'auto';
        }

        function submitReschedule() {
            const form = document.getElementById('rescheduleForm');
            const formData = new FormData(form);
            
            if (!form.checkValidity()) {
                showMessage('Please fill in all required fields', 'error');
                return;
            }
            
            fetch('reschedule_reservation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Appointment rescheduled successfully!', 'success');
                    closeRescheduleModal();
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(data.message || 'Failed to reschedule appointment', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            });
        }

        // Cancel Modal Functions
        function confirmCancel(reservationId) {
            document.getElementById('cancelReservationId').value = reservationId;
            document.getElementById('cancelModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').classList.remove('active');
            document.getElementById('cancelForm').reset();
            document.body.style.overflow = 'auto';
        }

        function submitCancel() {
            const form = document.getElementById('cancelForm');
            const formData = new FormData(form);
            
            if (!form.checkValidity()) {
                showMessage('Please provide a reason for cancellation', 'error');
                return;
            }
            
            fetch('cancel_reservation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Appointment cancelled successfully', 'success');
                    closeCancelModal();
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(data.message || 'Failed to cancel appointment', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred. Please try again.', 'error');
            });
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const rescheduleModal = document.getElementById('rescheduleModal');
            const cancelModal = document.getElementById('cancelModal');
            
            if (event.target === rescheduleModal) {
                closeRescheduleModal();
            }
            if (event.target === cancelModal) {
                closeCancelModal();
            }
        });

        // PDF Generation Function with Payment Info
        function generatePDF(reservationId) {
            showMessage('Generating receipt...', 'info');
            
            const card = document.querySelector(`[data-reservation-id="${reservationId}"]`);
            if (!card) {
                showMessage('Reservation not found', 'error');
                return;
            }
            
            const reservationDataStr = card.getAttribute('data-reservation-data');
            let data;
            try {
                data = JSON.parse(reservationDataStr);
            } catch (error) {
                console.error('Failed to parse reservation data:', error);
                showMessage('Error loading reservation data', 'error');
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            const primaryColor = '#bd1e51';
            const darkGray = '#333333';
            const lightGray = '#666666';
            
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
            
            function formatDate(dateString) {
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
            const refNumber = data.ReferenceNumber || `NO-${reservationId}`;
            doc.text(refNumber, 185, 63, { align: 'right' });
            
            // Payment status section
            let yPos = 72;
            const paymentMethod = data.PaymentMethod || 'onsite';
            const paymentStatus = data.PaymentStatus || 'pending';
            
            addColoredRect(20, yPos, 170, 10, paymentStatus === 'paid' ? '#d4edda' : '#fff3cd');
            doc.setTextColor(51, 51, 51);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.text('Payment Status:', 25, yPos + 6);
            
            const statusColor = paymentStatus === 'paid' ? '#155724' : '#856404';
            const statusRgb = hexToRgb(statusColor);
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
            
            const formattedDate = formatDate(data.Date);
            const formattedTime = formatTime(data.Time);
            
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
            
            // Payment Instructions (only for on-site)
            if (paymentMethod === 'onsite' && paymentStatus === 'pending') {
                yPos += 25;
                addColoredRect(20, yPos - 3, 170, 20, '#fff3cd');
                
                doc.setTextColor(133, 100, 4);
                doc.setFontSize(10);
                doc.setFont('helvetica', 'bold');
                doc.text('PAYMENT INSTRUCTIONS:', 25, yPos + 3);
                
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(8);
                doc.text('• Payment must be made at the testing center before your appointment', 25, yPos + 10);
                doc.text('• Please bring this receipt and exact amount', 25, yPos + 15);
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