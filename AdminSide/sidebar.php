<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
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

    /* --- Sidebar (copied from your dashboard) --- */
    .sidebar {
        width: 280px;
        background: linear-gradient(180deg, #a4133c 0%, #ff4d6d 100%);
        color: white;
        padding-top: 20px;
        box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
        flex-shrink: 0;
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

    /* --- Main area --- */
    .main {
        flex: 1;
        background-color: #f8fafc;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* --- Topbar (copied look from your dashboard) --- */
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

    /* --- Content (kept styling from your homepage file but adjusted layout) --- */
    .content {
        padding: 30px;
        width: 100%;
    }

    h2 { margin-bottom: 20px; color: #2d3748; font-size: 28px; }

    .section-box {
      background-color: white;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 30px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    }

    .button-row { margin-top: 15px; }
    .btn {
      background-color: #58d68d;
      border: none;
      color: white;
      padding: 8px 14px;
      margin-right: 10px;
      border-radius: 4px;
      cursor: pointer;
    }
    .btn-danger { background-color: #e74c3c; }

    .announcement-preview {
      max-width: 100%;
      margin-top: 10px;
      border: 1px solid #e2e8f0;
    }

    /* modal styles (kept from your previous file) */
    .modal {
      display: none;
      position: fixed;
      z-index: 10;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.5);
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: #fff;
      padding: 30px 50px;
      border-radius: 10px;
      width: 700px;
      max-width: 95%;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
    }

    .modal-content h3 {
      margin-bottom: 20px;
      font-size: 24px;
      color: #333;
      border-bottom: 1px solid #ddd;
      padding-bottom: 10px;
    }

    .modal-content label {
      margin: 15px 0 6px;
      font-weight: 600;
      display: block;
      color: #444;
    }

    .modal-content input[type="text"] {
      padding: 10px 14px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #bbb;
      width: 100%;
      margin-bottom: 10px;
      box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
    }

    .modal-content img.announcement-preview {
      max-width: 100%;
      max-height: 300px;
      object-fit: contain;
    }

    .service-card img {
      width: 100%;
      max-width: 400px;
      border: 1px solid #aaa;
      margin-top: 10px;
    }

    @media (max-width: 768px) {
      .modal-content {
        width: 90%;
        padding: 20px 25px;
      }

      .modal-content h3 {
        font-size: 20px;
      }

      .modal-content input[type="text"] {
        font-size: 15px;
      }
    }
        
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="section">
            <div class="section-title active">
                <a href="adminDash.php" style="color: white; text-decoration: none; display: flex; align-items: center; width: 100%;">
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

    <script>
        function toggleMenu(menuId) {
            const menu = document.getElementById(menuId);
            menu.classList.toggle('show');
        }
    </script>
</body>
</html>