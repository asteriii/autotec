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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }

        .search-bar {
            display: flex;
            gap: 10px;
        }

        .search-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .search-btn {
            background-color: #b83232;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .search-btn:hover {
            background-color: #9a2828;
        }

        .audit-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #b83232;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .id-badge {
            background-color: #b83232;
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .timestamp {
            color: #666;
            font-size: 13px;
        }

        .card-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-section {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title::before {
            content: '';
            display: inline-block;
            width: 4px;
            height: 16px;
            background-color: #b83232;
            border-radius: 2px;
        }

        .detail-item {
            display: flex;
            gap: 8px;
            font-size: 14px;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
            min-width: 80px;
        }

        .detail-value {
            color: #333;
            word-break: break-word;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .page-btn {
            padding: 10px 18px;
            border: 1px solid #ddd;
            background: white;
            color: #333;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
        }

        .page-btn:hover {
            background-color: #f5f5f5;
            border-color: #b83232;
        }

        .page-btn.active {
            background-color: #b83232;
            color: white;
            border-color: #b83232;
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .no-records {
            background: white;
            padding: 40px;
            text-align: center;
            border-radius: 8px;
            color: #666;
            font-size: 16px;
        }

        .action-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }

        .action-login {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .action-logout {
            background-color: #fff3e0;
            color: #e65100;
        }

        .action-reservation {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        .action-default {
            background-color: #f5f5f5;
            color: #666;
        }

        .ip-badge {
            background-color: #f5f5f5;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            color: #666;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Audit Trail - Activity Logs</h1>
            <form method="GET" action="" class="search-bar">
                <input 
                    type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="Search by name, action, or description..."
                    value="<?php echo htmlspecialchars($search); ?>"
                >
                <button type="submit" class="search-btn">🔎 Search</button>
            </form>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    // Determine action badge class
                    $action_class = 'action-default';
                    if (stripos($row['action'], 'login') !== false) {
                        $action_class = 'action-login';
                    } elseif (stripos($row['action'], 'logout') !== false) {
                        $action_class = 'action-logout';
                    } elseif (stripos($row['action'], 'reservation') !== false) {
                        $action_class = 'action-reservation';
                    }
                ?>
                <div class="audit-card">
                    <div class="card-header">
                        <span class="id-badge">ID: <?php echo $row['id']; ?></span>
                        <span class="timestamp"><?php echo date('M d, Y - h:i A', strtotime($row['created_at'])); ?></span>
                    </div>
                    
                    <div class="card-content">
                        <div class="detail-section">
                            <div class="section-title">👤 User Information</div>
                            <div class="detail-item">
                                <span class="detail-label">Name:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($row['name']); ?></span>
                            </div>
                            <?php if (!empty($row['ip_address'])): ?>
                            <div class="detail-item">
                                <span class="detail-label">IP Address:</span>
                                <span class="ip-badge"><?php echo htmlspecialchars($row['ip_address']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="detail-section">
                            <div class="section-title">⚡ Action Details</div>
                            <div class="detail-item">
                                <span class="detail-label">Action:</span>
                                <span class="action-badge <?php echo $action_class; ?>">
                                    <?php echo htmlspecialchars($row['action']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="detail-section" style="grid-column: 1 / -1;">
                            <div class="section-title">📝 Description</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-btn">← Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-btn">Next →</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-records">
                <p>No audit trail records found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>