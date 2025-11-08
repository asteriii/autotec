<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ongoing Tests</title>
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

    .buttons {
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .complete-btn {
      background-color: #a5d6a7;
      border: none;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>


  <!-- Main Content -->
  <div class="main">
    <div class="topbar">
      <div class="logo">☰</div>
      <button class="logout-btn">Logout</button>
    </div>

    <div class="content">
      <h2>Ongoing Tests</h2>

      <div class="search-pagination">
        <div class="search-box">
          <label>Search: <input type="text" placeholder="Search ongoing reservations"></label>
        </div>
        <div class="pagination">
          <button>1</button>
          <button>2</button>
          <button>3</button>
          <button>4</button>
          <button>5</button>
        </div>
      </div>

      <!-- Ongoing Card -->
      <div class="reservation-card">
        <div class="reservation-details">
          <h4>Reservation Details</h4>
          <p><strong>Name:</strong> Michael Reyes</p>
          <p><strong>Phone No.:</strong> 09123456789</p>
          <p><strong>Email:</strong> michael@example.com</p>
          <br>
          <p><strong>Vehicle Type:</strong> Pickup</p>
          <p><strong>Plate Num:</strong> DEF-5678</p>
        </div>
        <div class="reservation-details">
          <h4>Reservation Details</h4>
          <p><strong>Date:</strong> 2025-07-17</p>
          <p><strong>Time:</strong> 2:30 PM</p>
          <p><strong>Price:</strong> ₱1800</p>
          <div class="buttons">
            <button class="complete-btn">Mark Completed</button>
          </div>
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
