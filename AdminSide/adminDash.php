<?php
 
require_once '../db.php';


// Get current date for filtering
$today = date('Y-m-d');
$thisMonth = date('Y-m');
$thisYear = date('Y');

// Total reservations
$total_reservations_sql = "SELECT COUNT(*) as total FROM reservations";
$total_reservations_stmt = $pdo->prepare($total_reservations_sql);
$total_reservations_stmt->execute();
$total_reservations = $total_reservations_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Today's reservations
$today_reservations_sql = "SELECT COUNT(*) as total FROM reservations WHERE Date = ?";
$today_reservations_stmt = $pdo->prepare($today_reservations_sql);
$today_reservations_stmt->execute([$today]);
$today_reservations = $today_reservations_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// This month's reservations
$month_reservations_sql = "SELECT COUNT(*) as total FROM reservations WHERE Date LIKE ?";
$month_reservations_stmt = $pdo->prepare($month_reservations_sql);
$month_reservations_stmt->execute([$thisMonth . '%']);
$month_reservations = $month_reservations_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending reservations (assuming you'll add a status column later)
$pending_reservations_sql = "SELECT COUNT(*) as total FROM reservations WHERE Date >= ?";
$pending_reservations_stmt = $pdo->prepare($pending_reservations_sql);
$pending_reservations_stmt->execute([$today]);
$pending_reservations = $pending_reservations_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Vehicle type distribution
$vehicle_types_sql = "SELECT vt.Name, COUNT(r.ReservationID) as count 
                      FROM reservations r 
                      LEFT JOIN vehicle_types vt ON r.TypeID = vt.VehicleTypeID 
                      GROUP BY r.TypeID, vt.Name 
                      ORDER BY count DESC";
$vehicle_types_stmt = $pdo->prepare($vehicle_types_sql);
$vehicle_types_stmt->execute();
$vehicle_types_data = $vehicle_types_stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent reservations (last 5)
$recent_reservations_sql = "SELECT r.*, vt.Name as VehicleTypeName 
                           FROM reservations r 
                           LEFT JOIN vehicle_types vt ON r.TypeID = vt.VehicleTypeID 
                           ORDER BY r.Date DESC, r.Time DESC 
                           LIMIT 5";
$recent_reservations_stmt = $pdo->prepare($recent_reservations_sql);
$recent_reservations_stmt->execute();
$recent_reservations = $recent_reservations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly data for chart (last 6 months)
$monthly_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthly_sql = "SELECT COUNT(*) as count FROM reservations WHERE Date LIKE ?";
    $monthly_stmt = $pdo->prepare($monthly_sql);
    $monthly_stmt->execute([$month . '%']);
    $count = $monthly_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    $monthly_data[] = [
        'month' => date('M Y', strtotime($month . '-01')),
        'count' => $count
    ];
}

// Calculate revenue (estimated based on vehicle types)
$revenue_sql = "SELECT SUM(vt.Price) as total_revenue 
                FROM reservations r 
                LEFT JOIN vehicle_types vt ON r.TypeID = vt.VehicleTypeID 
                WHERE r.Date LIKE ?";
$revenue_stmt = $pdo->prepare($revenue_sql);
$revenue_stmt->execute([$thisMonth . '%']);
$monthly_revenue = $revenue_stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Dashboard - AutoTec</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #a4133c 0%, #ff4d6d 100%);
            color: white;
            padding-top: 20px;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
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

        .section-title.active {
            background-color: rgba(255,255,255,0.15);
            font-weight: 600;
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
            background: linear-gradient(135deg, #a4133c, #ff4d6d);
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
            margin-bottom: 10px;
            color: #2d3748;
            font-weight: 700;
            font-size: 32px;
        }

        .content .subtitle {
            color: #718096;
            margin-bottom: 30px;
            font-size: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .stat-card.reservations::before { background: linear-gradient(180deg, #3182ce, #2c5282); }
        .stat-card.today::before { background: linear-gradient(180deg, #38a169, #2f855a); }
        .stat-card.month::before { background: linear-gradient(180deg, #d69e2e, #b7791f); }
        .stat-card.revenue::before { background: linear-gradient(180deg, #c0392b, #a93226); }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-title {
            font-size: 14px;
            font-weight: 600;
            color: #718096;
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
            font-size: 18px;
            color: white;
        }

        .stat-icon.reservations { background: linear-gradient(135deg, #3182ce, #2c5282); }
        .stat-icon.today { background: linear-gradient(135deg, #38a169, #2f855a); }
        .stat-icon.month { background: linear-gradient(135deg, #d69e2e, #b7791f); }
        .stat-icon.revenue { background: linear-gradient(135deg, #c0392b, #a93226); }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-change {
            font-size: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .stat-change.positive { color: #38a169; }
        .stat-change.negative { color: #e53e3e; }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            height: 400px;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 15px;
            font-weight: 600;
            color: #2d3748;
        }

        .chart-filters {
            display: flex;
            gap: 10px;
        }

        .filter-btn {
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
            background: white;
            cursor: pointer;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
            color: #4a5568;
            transition: all 0.3s ease;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #a4133c;
            color: white;
            border-color: #a4133c;
        }

        .vehicle-types {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }

        .vehicle-type-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .vehicle-type-item:last-child {
            border-bottom: none;
        }

        .vehicle-name {
            font-weight: 500;
            color: #2d3748;
            font-size: 14px;
        }

        .vehicle-count {
            background: #f7fafc;
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            color: #4a5568;
        }

        .recent-reservations {
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }

        .reservation-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .reservation-item:last-child {
            border-bottom: none;
        }

        .reservation-info {
            flex: 1;
        }

        .customer-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .reservation-details {
            font-size: 11px;
            color: #718096;
        }

        .reservation-date {
            text-align: right;
            font-size: 11px;
            color: #718096;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1000;
                height: 100vh;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
       <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main">
    
        <div class="content">
            <h2>Dashboard</h2>
            <p class="subtitle">Welcome back! Here's what's happening with your business today.</p>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card reservations">
                    <div class="stat-header">
                        <span class="stat-title">Total Reservations</span>
                        <div class="stat-icon reservations">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($total_reservations); ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        All time reservations
                    </div>
                </div>

                <div class="stat-card today">
                    <div class="stat-header">
                        <span class="stat-title">Today's Reservations</span>
                        <div class="stat-icon today">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($today_reservations); ?></div>
                    <div class="stat-change">
                        <i class="fas fa-clock"></i>
                        <?php echo date('M d, Y'); ?>
                    </div>
                </div>

                <div class="stat-card month">
                    <div class="stat-header">
                        <span class="stat-title">This Month</span>
                        <div class="stat-icon month">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo number_format($month_reservations); ?></div>
                    <div class="stat-change">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('F Y'); ?>
                    </div>
                </div>

                <div class="stat-card revenue">
                    <div class="stat-header">
                        <span class="stat-title">Monthly Revenue</span>
                        <div class="stat-icon revenue">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="stat-value">₱<?php echo number_format($monthly_revenue, 2); ?></div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i>
                        Estimated revenue
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Chart -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h3 class="chart-title">Trends</h3>
                        <div class="chart-filters">
                            <button class="filter-btn active" onclick="updateChart('month')">Month</button>
                            <button class="filter-btn" onclick="updateChart('year')">Year</button>
                        </div>
                    </div>
                    <canvas id="reservationChart"></canvas>
                </div>

                <!-- Vehicle Types -->
                <div class="vehicle-types">
                    <h3 class="chart-title" style="margin-bottom: 15px;">Vehicle Types</h3>
                    <?php foreach ($vehicle_types_data as $type): ?>
                        <div class="vehicle-type-item">
                            <span class="vehicle-name"><?php echo htmlspecialchars($type['Name'] ?? 'Unknown'); ?></span>
                            <span class="vehicle-count"><?php echo $type['count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Recent Reservations -->
                <div class="recent-reservations">
                    <h3 class="chart-title" style="margin-bottom: 15px;">Recent Reservations</h3>
                    <?php foreach ($recent_reservations as $reservation): ?>
                        <div class="reservation-item">
                            <div class="reservation-info">
                                <div class="customer-name">
                                    <?php echo htmlspecialchars($reservation['Fname'] . ' ' . $reservation['Lname']); ?>
                                </div>
                                <div class="reservation-details">
                                    <?php echo htmlspecialchars($reservation['VehicleTypeName'] ?? 'Unknown'); ?> • 
                                    <?php echo htmlspecialchars($reservation['PlateNo']); ?>
                                </div>
                            </div>
                            <div class="reservation-date">
                                <?php echo date('M d, Y', strtotime($reservation['Date'])); ?><br>
                                <?php echo date('g:i A', strtotime($reservation['Time'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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

        // Chart setup
        const ctx = document.getElementById('reservationChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthly_data); ?>;
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month),
                datasets: [{
                    label: 'Reservations',
                    data: monthlyData.map(item => item.count),
                    borderColor: '#c0392b',
                    backgroundColor: 'rgba(192, 57, 43, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#c0392b',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        top: 5,
                        bottom: 5,
                        left: 5,
                        right: 5
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)',
                            lineWidth: 1
                        },
                        ticks: {
                            color: '#718096',
                            font: {
                                size: 9
                            },
                            maxTicksLimit: 4
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#718096',
                            font: {
                                size: 9
                            },
                            maxTicksLimit: 4
                        }
                    }
                },
                elements: {
                    point: {
                        hoverBackgroundColor: '#c0392b'
                    }
                }
            }
        });

        function updateChart(period) {
            // Update active filter button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // In a real implementation, you would fetch new data based on the period
            // For now, we'll just update the chart title
            console.log('Updating chart for period:', period);
        }

        // Set chart container height
        document.getElementById('reservationChart').style.height = '150px';
        document.getElementById('reservationChart').style.width = '100%';
    </script>
</body>
</html>