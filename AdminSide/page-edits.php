<?php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
$search_param = "%$search%";

if (!empty($search)) {
    $search_condition = "WHERE name LIKE ? OR action LIKE ? OR description LIKE ?";
}

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM audit_trail $search_condition";
if (!empty($search)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_records = $conn->query($count_sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $records_per_page);

// Fetch audit trail records
$sql = "SELECT * FROM audit_trail $search_condition ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

if (!empty($search)) {
    $stmt->bind_param("sssii", $search_param, $search_param, $search_param, $records_per_page, $offset);
} else {
    $stmt->bind_param("ii", $records_per_page, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - Activity Logs</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            background-color: #f5f5f5;
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
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
        }

        .content {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .search-bar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
            flex-wrap: wrap;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            min-width: 300px;
        }

        .search-box input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #a4133c;
            box-shadow: 0 0 0 3px rgba(164, 19, 60, 0.1);
        }

        .search-btn {
            padding: 12px 20px;
            background: linear-gradient(180deg, #a4133c 0%, #ff4d6d 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: linear-gradient(180deg, #ff4d6d 0%, #a4133c 100%);
            transform: translateY(-2px);
        }

        .stats-bar {
            display: flex;
            gap: 8px;
            align-items: center;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        .stats-badge {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            color: #334155;
        }

        .audit-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #a4133c;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .audit-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .id-badge {
            background: #a4133c;
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .timestamp {
            color: #64748b;
            font-size: 13px;
            text-align: right;
        }

        .card-body {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-section {
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .info-section h4 {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .info-row {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-item {
            display: flex;
            gap: 8px;
            font-size: 14px;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            min-width: 80px;
        }

        .info-value {
            color: #212529;
        }

        .action-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .action-login {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .action-logout {
            background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
            color: #9a3412;
        }

        .action-reservation {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }

        .action-update {
            background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 100%);
            color: #6b21a8;
        }

        .action-delete {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            color: #991b1b;
        }

        .action-default {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #475569;
        }

        .ip-badge {
            background: #f8fafc;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            color: #64748b;
            font-family: 'Courier New', monospace;
            border: 1px solid #e2e8f0;
        }

        .description-text {
            color: #64748b;
            font-size: 13px;
            line-height: 1.5;
        }

        .pagination {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: center;
        }

        .pagination a, .pagination span {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #ddd;
            background-color: white;
            cursor: pointer;
            border-radius: 8px;
            text-decoration: none;
            color: #555;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background-color: #f8f9fa;
            border-color: #a4133c;
            color: #a4133c;
        }

        .pagination .current {
            background: linear-gradient(180deg, #a4133c 0%, #ff4d6d 100%);
            color: #fff;
            border-color: #a4133c;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            color: #6c757d;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .no-results i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .card-body {
                grid-template-columns: 1fr;
            }

            .search-bar-container {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                width: 100%;
            }

            .pagination {
                flex-wrap: wrap;
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
                <i class="fas fa-clipboard-list"></i> Audit Trail - Activity Logs
            </div>
            <button class="logout-btn" onclick="window.location.href='../logout.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>

        <div class="content">
            <div class="search-bar-container">
                <form method="GET" action="" class="search-box">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search by name, action, or description..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>

                <div class="stats-bar">
                    <span>Total Records:</span>
                    <span class="stats-badge"><?php echo number_format($total_records); ?></span>
                </div>

                <div class="pagination">
                    <?php
                        $search_qs = !empty($search) ? '&search=' . urlencode($search) : '';
                        for ($i = 1; $i <= $total_pages; $i++):
                            if ($i === $page):
                    ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i . $search_qs; ?>"><?php echo $i; ?></a>
                            <?php endif;
                        endfor;
                    ?>
                </div>
            </div>

            <!-- Audit cards -->
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php
                        // Determine action badge class
                        $action_class = 'action-default';
                        $action_lower = strtolower($row['action']);
                        
                        if (stripos($action_lower, 'login') !== false) {
                            $action_class = 'action-login';
                        } elseif (stripos($action_lower, 'logout') !== false) {
                            $action_class = 'action-logout';
                        } elseif (stripos($action_lower, 'reservation') !== false || stripos($action_lower, 'booking') !== false) {
                            $action_class = 'action-reservation';
                        } elseif (stripos($action_lower, 'update') !== false || stripos($action_lower, 'edit') !== false) {
                            $action_class = 'action-update';
                        } elseif (stripos($action_lower, 'delete') !== false) {
                            $action_class = 'action-delete';
                        }
                    ?>
                    <div class="audit-card">
                        <div class="card-header">
                            <div class="id-badge">
                                <i class="fas fa-id-badge"></i> ID: <?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?>
                            </div>
                            <div class="timestamp">
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?><br>
                                <small style="color: #94a3b8;"><?php echo date('h:i A', strtotime($row['created_at'])); ?></small>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <div class="info-section">
                                <h4><i class="fas fa-user"></i> User Information</h4>
                                <div class="info-row">
                                    <div class="info-item">
                                        <span class="info-label">Name:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($row['name']); ?></span>
                                    </div>
                                    <?php if (!empty($row['ip_address'])): ?>
                                    <div class="info-item">
                                        <span class="info-label">IP Address:</span>
                                        <span class="ip-badge"><?php echo htmlspecialchars($row['ip_address']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="info-section">
                                <h4><i class="fas fa-bolt"></i> Action Details</h4>
                                <div class="info-row">
                                    <div class="info-item">
                                        <span class="info-label">Action:</span>
                                        <span class="action-badge <?php echo $action_class; ?>">
                                            <?php echo htmlspecialchars($row['action']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="info-section" style="grid-column: 1 / -1;">
                                <h4><i class="fas fa-file-alt"></i> Description</h4>
                                <div class="description-text">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-clipboard-list"></i>
                    <p>No audit trail records found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Keep the dropdown open if it contains an active item
        document.querySelectorAll('.submenu').forEach(menu => {
            if (menu.querySelector('.active')) {
                menu.style.display = 'block';
            }
        });
    </script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>