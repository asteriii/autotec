<?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "autotec";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Add status column if it doesn't exist
try {
    $check_column = $pdo->query("SHOW COLUMNS FROM contact_us LIKE 'status'");
    if ($check_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE contact_us ADD COLUMN status ENUM('unread', 'read') DEFAULT 'unread'");
    }
} catch(PDOException $e) {
    // Column might already exist, continue
}

// Handle status updates
if (isset($_POST['action']) && $_POST['action'] == 'mark_read' && isset($_POST['message_id'])) {
    $message_id = $_POST['message_id'];
    $update_sql = "UPDATE contact_us SET status = 'read' WHERE id = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([$message_id]);
}

if (isset($_POST['action']) && $_POST['action'] == 'mark_unread' && isset($_POST['message_id'])) {
    $message_id = $_POST['message_id'];
    $update_sql = "UPDATE contact_us SET status = 'unread' WHERE id = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([$message_id]);
}

// Get filter parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build SQL based on filter
$where_clause = "";
if ($filter == 'read') {
    $where_clause = "WHERE status = 'read'";
} elseif ($filter == 'unread') {
    $where_clause = "WHERE status = 'unread'";
}

// Get contact messages
$messages_sql = "SELECT * FROM contact_us $where_clause ORDER BY created_at DESC";
$messages_stmt = $pdo->prepare($messages_sql);
$messages_stmt->execute();
$messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for filter badges
$total_sql = "SELECT COUNT(*) as count FROM contact_us";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->execute();
$total_count = $total_stmt->fetch(PDO::FETCH_ASSOC)['count'];

$unread_sql = "SELECT COUNT(*) as count FROM contact_us WHERE status = 'unread'";
$unread_stmt = $pdo->prepare($unread_sql);
$unread_stmt->execute();
$unread_count = $unread_stmt->fetch(PDO::FETCH_ASSOC)['count'];

$read_sql = "SELECT COUNT(*) as count FROM contact_us WHERE status = 'read'";
$read_stmt = $pdo->prepare($read_sql);
$read_stmt->execute();
$read_count = $read_stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Contact Messages - AutoTec Admin</title>
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

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #c0392b 0%, #a93226 100%);
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

        .submenu li.active {
            background-color: rgba(255,255,255,0.15);
            padding-left: 10px;
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

        .filter-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }

        .filter-tab {
            padding: 10px 20px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            color: #4a5568;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-tab:hover {
            background: #f7fafc;
            border-color: #c0392b;
        }

        .filter-tab.active {
            background: #c0392b;
            color: white;
            border-color: #c0392b;
        }

        .badge {
            background: rgba(0,0,0,0.1);
            color: inherit;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .filter-tab.active .badge {
            background: rgba(255,255,255,0.2);
        }

        .messages-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .message-item {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-item:hover {
            background: #f7fafc;
        }

        .message-item.unread {
            background: #fff5f5;
            border-left: 4px solid #c0392b;
        }

        .message-item.unread::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 10px;
            width: 8px;
            height: 8px;
            background: #c0392b;
            border-radius: 50%;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .message-info {
            flex: 1;
        }

        .sender-name {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .sender-details {
            font-size: 14px;
            color: #718096;
            margin-bottom: 8px;
        }

        .message-date {
            font-size: 12px;
            color: #a0aec0;
        }

        .message-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            background: white;
            cursor: pointer;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            color: #4a5568;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn:hover {
            background: #f7fafc;
        }

        .action-btn.read {
            background: #38a169;
            color: white;
            border-color: #38a169;
        }

        .action-btn.read:hover {
            background: #2f855a;
        }

        .action-btn.unread {
            background: #d69e2e;
            color: white;
            border-color: #d69e2e;
        }

        .action-btn.unread:hover {
            background: #b7791f;
        }

        .message-content {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #c0392b;
            font-size: 14px;
            line-height: 1.6;
            color: #2d3748;
            margin-top: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.unread {
            background: #fed7d7;
            color: #c53030;
        }

        .status-badge.read {
            background: #c6f6d5;
            color: #22543d;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #e2e8f0;
        }

        .empty-state h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #4a5568;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1000;
                height: 100vh;
            }

            .content {
                padding: 20px;
            }

            .filter-tabs {
                flex-wrap: wrap;
                gap: 10px;
            }

            .message-header {
                flex-direction: column;
                gap: 10px;
            }

            .message-actions {
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="section">
            <div class="section-title">
                <span><i class="fas fa-tachometer-alt"></i> <a href="admindash.php" style="color: white; text-decoration: none;">Dashboard</a></span>
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
            <ul class="submenu show" id="page-settings">
                <li><a href="homepage-edit.php"><i class="fas fa-home"></i> Home Page</a></li>
                <li class="active"><a href="contact-edit.php"><i class="fas fa-envelope"></i> Contact Messages</a></li>
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
                <li><i class="fas fa-clock"></i> Audit Logs</li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="topbar">
            <div class="logo">
                <i class="fas fa-envelope"></i> Contact Messages
            </div>
            <button class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>

        <div class="content">
            <h2>Contact Messages</h2>
            <p class="subtitle">Manage and respond to customer inquiries and messages.</p>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-inbox"></i>
                    All Messages
                    <span class="badge"><?php echo $total_count; ?></span>
                </a>
                <a href="?filter=unread" class="filter-tab <?php echo $filter == 'unread' ? 'active' : ''; ?>">
                    <i class="fas fa-circle"></i>
                    Unread
                    <span class="badge"><?php echo $unread_count; ?></span>
                </a>
                <a href="?filter=read" class="filter-tab <?php echo $filter == 'read' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i>
                    Read
                    <span class="badge"><?php echo $read_count; ?></span>
                </a>
            </div>

            <!-- Messages Container -->
            <div class="messages-container">
                <?php if (count($messages) > 0): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message-item <?php echo $message['status']; ?>">
                            <div class="message-header">
                                <div class="message-info">
                                    <div class="sender-name">
                                        <?php echo htmlspecialchars($message['first_name'] . ' ' . $message['last_name']); ?>
                                        <span class="status-badge <?php echo $message['status']; ?>">
                                            <?php echo $message['status']; ?>
                                        </span>
                                    </div>
                                    <div class="sender-details">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($message['email']); ?>
                                        <?php if (!empty($message['phone_number'])): ?>
                                            <span style="margin-left: 15px;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($message['phone_number']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="message-date">
                                        <i class="fas fa-clock"></i> <?php echo date('F j, Y \a\t g:i A', strtotime($message['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="message-actions">
                                    <?php if ($message['status'] == 'unread'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <button type="submit" class="action-btn read">
                                                <i class="fas fa-check"></i> Mark as Read
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_unread">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <button type="submit" class="action-btn unread">
                                                <i class="fas fa-undo"></i> Mark as Unread
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="message-content">
                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No messages found</h3>
                        <p>
                            <?php if ($filter == 'unread'): ?>
                                No unread messages at the moment.
                            <?php elseif ($filter == 'read'): ?>
                                No read messages yet.
                            <?php else: ?>
                                No contact messages have been received yet.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
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

        // Auto-refresh page every 30 seconds to check for new messages
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>