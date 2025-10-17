<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Fetch user's reservations with related data - CORRECTED QUERY
$sql = "SELECT r.*, vt.Name AS VehicleType, vc.Name AS Category, 
        vt.Price, vt.Name AS VehicleTypeName, vc.Name AS CategoryName
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
            cursor: pointer;
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
        }

        .reservation-id {
            background: linear-gradient(135deg, #bd1e51, #d63969);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .print-receipt-btn {
            background: #ff758f;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .print-receipt-btn:hover {
            background: #ff758f;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(167, 40, 85, 0.3);
        }

        .print-receipt-btn:active {
            transform: translateY(0);
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

        .btn-primary {
            background: linear-gradient(135deg, #bd1e51, #d63969);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #a01a45, #bd1e51);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(189, 30, 81, 0.3);
        }

        /* Print success message */
        .print-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ff758f;
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            font-weight: 500;
            z-index: 1000;
            transform: translateX(400px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(189, 30, 81, 0.3);
        }

        .print-message.show {
            transform: translateX(0);
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
        }

        @media (max-width: 480px) {
            .hero {
                padding: 25px 15px;
            }

            .page-title {
                font-size: 1.8em;
            }

            .reservation-card {
                padding: 15px;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .print-receipt-btn {
                align-self: flex-end;
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
            <div class="stat-card" onclick="filterReservations('all')" data-filter="all">
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
                    <a href="vehicleinfo.php" class="btn-primary">Make a Reservation</a>
                </div>
            <?php else: ?>
                <?php foreach ($reservations as $reservation): ?>
                    <div class="reservation-card" 
                         data-date="<?php echo htmlspecialchars($reservation['Date']); ?>"
                         data-reservation-id="<?php echo htmlspecialchars($reservation['ReservationID']); ?>"
                         data-reservation-data="<?php echo htmlspecialchars(json_encode($reservation)); ?>">
                        
                        <div class="card-header">
                            <div class="reservation-id">ID: #<?php echo str_pad($reservation['ReservationID'], 4, '0', STR_PAD_LEFT); ?></div>
                            <button class="print-receipt-btn" onclick="generatePDF(<?php echo $reservation['ReservationID']; ?>)">
                                Print Receipt
                            </button>
                        </div>
                        
                        <div class="card-info">
                            <div class="info-item">
                                <div class="info-label">Customer</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars(trim(($reservation['Fname'] ?? '') . ' ' . ($reservation['Lname'] ?? ''))); ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Vehicle Type</div>
                                <div class="info-value"><?php echo htmlspecialchars($reservation['VehicleType'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Category</div>
                                <div class="info-value"><?php echo htmlspecialchars($reservation['Category'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($reservation['Email'] ?? 'N/A'); ?></div>
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
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Print Success Message -->
    <div class="print-message" id="printMessage">
        Receipt is being generated and downloaded...
    </div>

    <?php include 'footer.php'; ?>

    <script>
        let currentFilter = 'all';

        function showPrintMessage() {
            const message = document.getElementById('printMessage');
            message.classList.add('show');
            
            setTimeout(() => {
                message.classList.remove('show');
            }, 3000);
        }

        function filterReservations(filterType) {
            const cards = document.querySelectorAll('.reservation-card');
            const statCards = document.querySelectorAll('.stat-card');
            const filterInfo = document.getElementById('filterInfo');
            const filterText = document.getElementById('filterText');
            
            // Update current filter
            currentFilter = filterType;
            
            // Update active state of stat cards
            statCards.forEach(card => {
                card.classList.remove('active');
                if (card.dataset.filter === filterType) {
                    card.classList.add('active');
                }
            });
            
            // Get current date for comparison
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Reset time to start of day
            
            const currentMonth = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0');
            
            let visibleCount = 0;
            let filterDescription = '';
            
            // Filter cards based on type
            cards.forEach(card => {
                const reservationDate = card.dataset.date;
                let shouldShow = false;
                
                if (!reservationDate) {
                    // Handle cards without date data (like empty state)
                    shouldShow = filterType === 'all';
                } else {
                    const cardDate = new Date(reservationDate + 'T00:00:00'); // Add time to avoid timezone issues
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
            
            // Update filter info display
            if (filterType === 'all') {
                filterInfo.classList.remove('active');
            } else {
                filterInfo.classList.add('active');
                filterText.textContent = `${filterDescription} (${visibleCount} found)`;
            }
            
            // Handle empty state when no reservations match filter
            const emptyState = document.querySelector('.empty-state');
            if (emptyState) {
                if (visibleCount === 0 && filterType !== 'all') {
                    // Create or show "no results" message for filters
                    showNoResultsState(filterType);
                } else {
                    hideNoResultsState();
                }
            }
        }

        function clearFilter() {
            filterReservations('all');
        }

        function showNoResultsState(filterType) {
            // Remove existing no-results state if any
            const existingNoResults = document.querySelector('.no-results-state');
            if (existingNoResults) {
                existingNoResults.remove();
            }
            
            // Create no-results state
            const noResultsDiv = document.createElement('div');
            noResultsDiv.className = 'no-results-state empty-state';
            noResultsDiv.style.gridColumn = '1 / -1';
            
            let title, description, icon;
            switch (filterType) {
                case 'upcoming':
                    icon = '📅';
                    title = 'No Upcoming Reservations';
                    description = 'You don\'t have any reservations scheduled for upcoming dates.';
                    break;
                case 'thisMonth':
                    icon = '📊';
                    title = 'No Reservations This Month';
                    description = 'You haven\'t made any reservations this month yet.';
                    break;
                default:
                    icon = '🔍';
                    title = 'No Results Found';
                    description = 'No reservations match your current filter.';
            }
            
            noResultsDiv.innerHTML = `
                <div class="empty-icon">${icon}</div>
                <h2 class="empty-title">${title}</h2>
                <p class="empty-description">${description}</p>
                <a href="vehicleinfo.php" class="btn-primary">Make a New Reservation</a>
            `;
            
            document.querySelector('.reservations-grid').appendChild(noResultsDiv);
        }

        function hideNoResultsState() {
            const noResultsState = document.querySelector('.no-results-state');
            if (noResultsState) {
                noResultsState.remove();
            }
        }

        // Initialize filter on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set default active state
            const defaultCard = document.querySelector('.stat-card[data-filter="all"]');
            if (defaultCard) {
                defaultCard.classList.add('active');
            }
        });

        function generatePDF(reservationId) {
    showPrintMessage();
    
    // Find the reservation data from the card
    const card = document.querySelector(`[data-reservation-id="${reservationId}"]`);
    if (!card) {
        console.error('Reservation card not found');
        return;
    }
    
    const reservationDataStr = card.getAttribute('data-reservation-data');
    let data;
    try {
        data = JSON.parse(reservationDataStr);
    } catch (error) {
        console.error('Failed to parse reservation data:', error);
        return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Colors matching the page design
    const primaryColor = '#bd1e51';
    const darkGray = '#333333';
    const lightGray = '#666666';
    const borderColor = '#e0e0e0';
    
    // Helper function to add a horizontal line
    function addHorizontalLine(y, width = 170) {
        doc.setDrawColor(224, 224, 224);
        doc.line(20, y, 20 + width, y);
    }
    
    // Helper function to add colored rectangle
    function addColoredRect(x, y, width, height, color) {
        const rgb = hexToRgb(color);
        doc.setFillColor(rgb.r, rgb.g, rgb.b);
        doc.rect(x, y, width, height, 'F');
    }
    
    // Convert hex to RGB
    function hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
    }
    
    // Helper function to format time
    function formatTime(timeString) {
        if (!timeString) return 'N/A';
        
        // If it's already in 12-hour format, return as is
        if (timeString.includes('AM') || timeString.includes('PM')) {
            return timeString;
        }
        
        // Convert 24-hour format to 12-hour format
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
    
    // Helper function to format date
    function formatDate(dateString) {
        const date = new Date(dateString + 'T00:00:00'); // Add time to avoid timezone issues
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        };
        return date.toLocaleDateString('en-US', options);
    }
    
    // Header section with colored background matching the hero gradient
    addColoredRect(0, 0, 210, 35, primaryColor);
    
    // Company logo/name
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(24);
    doc.setFont('helvetica', 'bold');
    doc.text('AutoTEC', 20, 20);
    
    // Subtitle
    doc.setFontSize(12);
    doc.setFont('helvetica', 'normal');
    doc.text('Emission Testing Center', 20, 28);
    
    // Receipt title
    doc.setTextColor(51, 51, 51);
    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text('APPOINTMENT RECEIPT', 105, 48, { align: 'center' });
    
    // Reference number section with card-like styling
    addColoredRect(20, 55, 170, 12, '#f8f9fa');
    doc.setTextColor(51, 51, 51);
    doc.setFontSize(11);
    doc.setFont('helvetica', 'bold');
    doc.text('Reference Number:', 25, 63);
    doc.setTextColor(189, 30, 81); // Primary color
    doc.setFontSize(12);
    doc.text(`NO-${reservationId}`, 140, 63, { align: 'right' });
    
    // Date and time of booking
    doc.setTextColor(102, 102, 102);
    doc.setFontSize(9);
    doc.setFont('helvetica', 'normal');
    const now = new Date();
    doc.text(`Generated: ${now.toLocaleDateString()} ${now.toLocaleTimeString()}`, 185, 75, { align: 'right' });
    
    // Vehicle Information Section
    let yPos = 85;
    doc.setTextColor(189, 30, 81); // Primary color
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text('VEHICLE INFORMATION', 20, yPos);
    
    yPos += 3;
    addHorizontalLine(yPos);
    yPos += 8;
    
    // Vehicle details
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
    
    // Owner Information Section
    yPos += 5;
    doc.setTextColor(189, 30, 81); // Primary color
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text('OWNER INFORMATION', 20, yPos);
    
    yPos += 3;
    addHorizontalLine(yPos);
    yPos += 8;
    
    // Owner details
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
        
        // Handle long text wrapping for address
        if (label === 'Address:' && value.length > 40) {
            const lines = doc.splitTextToSize(value, 110);
            doc.text(lines, 80, yPos);
            yPos += (lines.length - 1) * 4;
        } else {
            doc.text(value, 80, yPos);
        }
        yPos += 8;
    });
    
    // Appointment Details Section
    yPos += 5;
    doc.setTextColor(189, 30, 81); // Primary color
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text('APPOINTMENT DETAILS', 20, yPos);
    
    yPos += 3;
    addHorizontalLine(yPos);
    yPos += 8;
    
    // Schedule details
    doc.setTextColor(51, 51, 51);
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    
    const formattedDate = formatDate(data.Date);
    const formattedTime = formatTime(data.Time);
    
    const appointmentInfo = [
        ['Date:', formattedDate],
        ['Time:', formattedTime],
        ['Duration:', 'Approximately 20 minutes'],
        ['Location:', 'AutoTEC Emission Testing Center']
    ];
    
    appointmentInfo.forEach(([label, value]) => {
        doc.setFont('helvetica', 'bold');
        doc.text(label, 25, yPos);
        doc.setFont('helvetica', 'normal');
        doc.text(value, 80, yPos);
        yPos += 8;
    });
    
    // Fee Summary Section with card-like styling
    yPos += 5;
    addColoredRect(20, yPos - 3, 170, 18, '#f8f9fa');
    
    doc.setTextColor(189, 30, 81); // Primary color
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text('FEE SUMMARY', 25, yPos + 3);
    
    // Use price from data
    const amount = parseFloat(data.Price) || 300;
    const serviceName = `${data.VehicleTypeName || data.VehicleType || 'Vehicle'} Emission Testing`;
    
    doc.setTextColor(51, 51, 51);
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text(serviceName, 25, yPos + 12);
    
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(12);
    doc.text(`Php ${amount.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`, 185, yPos + 12, { align: 'right' });
    
    // Important Notes Section with warning styling
    yPos += 25;
    addColoredRect(20, yPos - 3, 170, 25, '#fff3cd');
    
    doc.setTextColor(133, 100, 4);
    doc.setFontSize(10);
    doc.setFont('helvetica', 'bold');
    doc.text('IMPORTANT REMINDERS:', 25, yPos + 3);
    
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8);
    doc.text('• Please arrive 15 minutes before your scheduled time', 25, yPos + 10);
    doc.text('• Bring this receipt and your vehicle registration documents', 25, yPos + 15);
    doc.text('• Vehicle must be physically present for testing', 25, yPos + 20);
    
    // Footer section
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

    
    // Save the PDF with proper filename
    const fileName = `AutoTEC_Receipt_NO-${String(reservationId).padStart(4, '0')}.pdf`;
    doc.save(fileName);
}
   </script>