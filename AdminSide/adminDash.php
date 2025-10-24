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
    <link rel="stylesheet<link rel="stylesheet" href="/autotec/AdminSide/css/admindash.css">
    <title>Admin Dashboard - AutoTec</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="section">
            <div class="section-title active">
                <span><i class="fas fa-tachometer-alt"></i> Dashboard</span>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title" onclick="toggleMenu('admin-controls')">
                <span><i class="fas fa-cogs"></i> Admin Controls</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <ul class="submenu" id="admin-controls">
                <li><a href="reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a></li>
                <li><a href="ongoing-list.php"><i class="fas fa-clock"></i> Ongoing List</a></li>
                <li><a href="completed-list.php"><i class="fas fa-check-circle"></i> Completed List</a></li>
            </ul>
        </div>
        
        <div class="section">
            <div class="section-title" onclick="toggleMenu('page-settings')">
                <span><i class="fas fa-edit"></i> Page Settings</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <ul class="submenu" id="page-settings">
                <li><a href="homepage-edit.php"><i class="fas fa-home"></i> Home Page</a></li>
                <li><a href="contact-edit.php"><i class="fas fa-envelope"></i> Contact Page</a></li>
                <li><a href="about-edit.php"><i class="fas fa-info-circle"></i> About Page</a></li>
            </ul>
        </div>

        <div class="section">
            <div class="section-title" onclick="toggleMenu('activity-logs')">
                <span><i class="fas fa-history"></i> Activity Logs</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <ul class="submenu" id="activity-logs">
                <li><i class="fas fa-edit"></i> Page Edits</li>
                <li><i class="fas fa-check"></i> Confirmed Logs</li>
                <li><i class="fas fa-clock"></i> Ongoing Logs</li>
            </ul>
        </div>
    </div>

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