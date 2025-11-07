<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Completed Tests</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet"/>
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
      background-color: #f4f4f4;
    }

    .sidebar {
      width: 250px;
      background-color: #a93226;
      color: white;
      padding-top: 20px;
    }

    .sidebar .section {
      padding: 0 20px;
    }

    .sidebar .section-title {
      padding: 10px 0;
      cursor: pointer;
      font-weight: bold;
    }

    .sidebar .submenu {
      list-style: none;
      padding-left: 15px;
      display: none;
    }

    .sidebar .submenu li {
      padding: 8px 0;
      font-size: 14px;
      cursor: pointer;
    }

    .sidebar .submenu li:hover {
      text-decoration: underline;
    }

    .sidebar .active {
      background-color: #922b20;
    }

    .main {
      flex: 1;
      background-color: #f9f9f9;
    }

    .topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #fff;
      padding: 10px 20px;
      border-bottom: 1px solid #ccc;
    }

    .logout-btn {
      background-color: #e57373;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
    }

    .content {
      padding: 30px;
    }

    .content h2 {
      margin-bottom: 20px;
    }

    .search-pagination {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .search-box input {
      padding: 6px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    .pagination button {
      padding: 6px 10px;
      margin-left: 4px;
      border: 1px solid #aaa;
      background-color: white;
      cursor: pointer;
      border-radius: 4px;
    }

    .reservation-card {
      display: flex;
      justify-content: space-between;
      background-color: white;
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
    }

    .reservation-details {
      width: 45%;
    }

    .reservation-details h4 {
      margin-bottom: 10px;
    }

    .reservation-details p {
      margin: 4px 0;
    }

    .completion-heading {
      margin-top: 12px;
      font-weight: bold;
    }
  </style>
</head>
<body>

  <?php include 'sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main">
    <div class="topbar">
      <div class="logo">☰</div>
      <button class="logout-btn">Logout</button>
    </div>

    <div class="content">
      <h2>Completed Tests</h2>

      <div class="search-pagination">
        <div class="search-box">
          <label>Search: <input type="text" placeholder="Search completed reservations"></label>
        </div>
        <div class="pagination">
          <button>1</button>
          <button>2</button>
          <button>3</button>
          <button>4</button>
          <button>5</button>
        </div>
      </div>

      <!-- Completed Card -->
      <div class="reservation-card">
        <div class="reservation-details">
          <h4>Reservation Details</h4>
          <p><strong>Name:</strong> Ana Lopez</p>
          <p><strong>Phone No.:</strong> 09111222333</p>
          <p><strong>Email:</strong> ana@example.com</p>
          <br>
          <p><strong>Vehicle Type:</strong> Hatchback</p>
          <p><strong>Plate Num:</strong> TUV-1122</p>
        </div>
        <div class="reservation-details">
          <h4>Reservation Details</h4>
          <p><strong>Date:</strong> 2025-07-10</p>
          <p><strong>Time:</strong> 09:00 AM</p>
          <p><strong>Price:</strong> ₱1300</p>
          <p class="completion-heading">Completion Details</p>
          <p><strong>Date:</strong> 2025-07-10</p>
          <p><strong>Time:</strong> 10:30 AM</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Sidebar Dropdown Script -->
  <script>
    function toggleMenu(id) {
      const menu = document.getElementById(id);
      const isVisible = menu.style.display === 'block';
      menu.style.display = isVisible ? 'none' : 'block';
    }
  </script>
</body>
</html>
