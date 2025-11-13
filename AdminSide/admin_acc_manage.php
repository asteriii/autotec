<?php
// admin_acc_manage.php
// Requires: db.php (must create $conn as mysqli connection)
include 'db.php';

// Pagination settings
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$records_per_page = 6;
$offset = ($page - 1) * $records_per_page;

// Search handling (prepared statements)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$params = [];
$search_sql = '';
if ($search !== '') {
    // search username, Email, BranchName, role
    $search_sql = "WHERE username LIKE ? OR Email LIKE ? OR BranchName LIKE ? OR role LIKE ?";
    $like = "%{$search}%";
    $params = [$like, $like, $like, $like];
}

// Get total records for pagination
if ($search_sql === '') {
    $count_sql = "SELECT COUNT(*) AS total FROM admin";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute();
} else {
    $count_sql = "SELECT COUNT(*) AS total FROM admin $search_sql";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("ssss", $params[0], $params[1], $params[2], $params[3]);
    $count_stmt->execute();
}
$count_result = $count_stmt->get_result()->fetch_assoc();
$total_records = (int)$count_result['total'];
$total_pages = max(1, (int)ceil($total_records / $records_per_page));
$count_stmt->close();

// Fetch admin records with limit/offset
if ($search_sql === '') {
    $sql = "SELECT * FROM admin ORDER BY admin_id ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $records_per_page, $offset);
} else {
    $sql = "SELECT * FROM admin $search_sql ORDER BY admin_id ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $params[0], $params[1], $params[2], $params[3], $records_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Accounts Manager</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
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
      background: linear-gradient(180deg, #a4133c 0%, #ff4d6d 100%);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .logout-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(164, 19, 60, 0.4);
    }

    .content {
      padding: 30px;
      max-width: 1400px;
      margin: 0 auto;
    }

    .page-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 30px;
    }

    .page-header h2 {
      font-size: 28px;
      font-weight: 700;
      color: #2c3e50;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .search-bar-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      gap: 15px;
      flex-wrap: wrap;
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
      border-color: #c0392b;
      box-shadow: 0 0 0 3px rgba(192, 57, 43, 0.1);
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

    .pagination {
      display: flex;
      gap: 8px;
      align-items: center;
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
      border-color: #c0392b;
      color: #c0392b;
    }

    .pagination .current {
      background: linear-gradient(180deg, #a4133c 0%, #ff4d6d 100%);
      color: #fff;
    }

    .admin-card {
      background: white;
      border: 1px solid #e5e7eb;
      border-left: 4px solid #c0392b;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      transition: all 0.3s ease;
    }

    .admin-card:hover {
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
      transform: translateY(-2px);
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 20px;
    }

    .admin-id-badge {
      background: #c0392b;
      color: white;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .card-body {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
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

    .card-actions {
      display: flex;
      gap: 10px;
      justify-content: flex-end;
      padding-top: 16px;
      border-top: 1px solid #e5e7eb;
    }

    .btn {
      padding: 10px 20px;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      color: white;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .edit-btn { 
      background: linear-gradient(135deg, #a4133c 0%, #ff4d6d 100%);
      color:#fff; 
    }

    .edit-btn:hover {
      background: linear-gradient(180deg, #ff4d6d 0%, #a4133c 100%);
      transform: translateY(-2px);
    }

    .remove-btn { 
      background: linear-gradient(180deg, #a4133c 0%, #ff4d6d 100%);
    }

    .remove-btn:hover {
      background: linear-gradient(180deg, #ff4d6d 0%, #a4133c 100%);
      transform: translateY(-2px);
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 999;
      top: 0; 
      left: 0;
      width: 100%; 
      height: 100%;
      background-color: rgba(0,0,0,0.6);
      backdrop-filter: blur(4px);
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .modal.show { display: flex; }

    .modal-content {
      background-color: #fff;
      width: 100%;
      max-width: 520px;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-30px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .modal-header {
      background: linear-gradient(180deg, #a4133c 0%, #ff4d6d 100%);
      color: white;
      padding: 24px 28px;
      font-weight: 700;
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 2px 8px rgba(164, 19, 60, 0.2);
    }
    
    .modal-header i {
      font-size: 24px;
    }
    
    .modal-body {
      padding: 28px;
    }

    .modal form label { 
      display: block; 
      margin: 18px 0 8px; 
      font-weight: 600;
      color: #2c3e50;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .modal form label:first-of-type {
      margin-top: 0;
    }

    .modal form input[type="text"], 
    .modal form input[type="email"], 
    .modal form input[type="password"],
    .modal form select {
      width: 100%; 
      padding: 12px 16px; 
      border: 2px solid #e5e7eb; 
      border-radius: 8px;
      font-size: 15px;
      transition: all 0.3s ease;
      background-color: #f8f9fa;
    }

    .modal form input:focus,
    .modal form select:focus {
      outline: none;
      border-color: #a4133c;
      box-shadow: 0 0 0 4px rgba(164, 19, 60, 0.1);
      background-color: white;
    }

    .modal-actions { 
      margin-top: 28px; 
      display: flex; 
      gap: 12px; 
      justify-content: flex-end;
      padding-top: 24px;
      border-top: 2px solid #f1f3f5;
    }

    .save-btn { 
      background: linear-gradient(180deg, #a4133c 0%, #ff4d6d 100%);
      color: white; 
      border: none; 
      padding: 12px 28px; 
      border-radius: 8px; 
      cursor: pointer;
      font-weight: 600;
      font-size: 15px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(164, 19, 60, 0.3);
    }

    .save-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(164, 19, 60, 0.4);
    }
    
    .save-btn:active {
      transform: translateY(0);
    }

    .cancel-btn { 
      background: white;
      color: #6c757d; 
      border: 2px solid #dee2e6; 
      padding: 12px 28px; 
      border-radius: 8px; 
      cursor: pointer;
      font-weight: 600;
      font-size: 15px;
      transition: all 0.3s ease;
    }

    .cancel-btn:hover {
      background: #f8f9fa;
      border-color: #adb5bd;
      color: #495057;
    }

    .small-modal-content { 
      text-align: center; 
      padding: 40px 32px;
    }

    .small-modal-content p { 
      margin: 20px 0; 
      font-size: 16px; 
      color: #495057;
      font-weight: 500;
      line-height: 1.6;
    }

    .ok-btn { 
      padding: 12px 32px; 
      border-radius: 8px; 
      border: none; 
      background: linear-gradient(180deg, #a4133c 0%, #ff4d6d 100%);
      color: white; 
      cursor: pointer;
      font-weight: 600;
      font-size: 15px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(164, 19, 60, 0.3);
      margin-top: 8px;
    }

    .ok-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(164, 19, 60, 0.4);
    }
    
    .delete-warning {
      background: #fff3cd;
      border-left: 4px solid #ffc107;
      padding: 16px;
      border-radius: 8px;
      margin: 16px 0;
      display: flex;
      align-items: start;
      gap: 12px;
    }
    
    .delete-warning i {
      color: #ff9800;
      font-size: 20px;
      margin-top: 2px;
    }
    
    .delete-warning p {
      margin: 0;
      color: #856404;
      font-size: 14px;
      text-align: left;
      line-height: 1.5;
    }

    .no-results {
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: 12px;
      color: #6c757d;
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

      .pagination {
        justify-content: center;
      }

      .card-actions {
        flex-direction: column;
      }

      .btn {
        width: 100%;
        justify-content: center;
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
        <i class="fas fa-user-shield"></i> Admin Accounts Manager
      </div>
      <button class="logout-btn" onclick="window.location.href='logout.php'">
        <i class="fas fa-sign-out-alt"></i> Logout
      </button>
    </div>

    <div class="content">
      <div class="page-header">
      </div>

      <div class="search-bar-container">
        <form method="GET" class="search-box">
          <input type="text" name="search" placeholder="Search by username, email, branch, or role..." value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
          <button type="submit" class="search-btn">
            <i class="fas fa-search"></i> Search
          </button>
        </form>

        <button class="btn edit-btn" onclick="openModal('addModal')" style="white-space: nowrap;">
          <i class="fas fa-user-plus"></i> Add Admin
        </button>

        <div class="pagination">
          <?php
            $search_qs = $search !== '' ? '&search=' . urlencode($search) : '';
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

      <!-- Admin cards -->
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <?php
            $row['BranchName'] = isset($row['BranchName']) ? $row['BranchName'] : '';
            $row['role'] = isset($row['role']) ? $row['role'] : '';
            $json_admin = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
          ?>
          <div class="admin-card">
            <div class="card-header">
              <div class="admin-id-badge">
                <i class="fas fa-id-badge"></i> ID: <?php echo (int)$row['admin_id']; ?>
              </div>
            </div>

            <div class="card-body">
              <div class="info-section">
                <h4><i class="fas fa-user"></i> Account Details</h4>
                <div class="info-row">
                  <div class="info-item">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?php echo htmlspecialchars($row['username']); ?></span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($row['Email']); ?></span>
                  </div>
                </div>
              </div>

              <div class="info-section">
                <h4><i class="fas fa-building"></i> Branch & Role</h4>
                <div class="info-row">
                  <div class="info-item">
                    <span class="info-label">Branch:</span>
                    <span class="info-value"><?php echo htmlspecialchars($row['BranchName']); ?></span>
                  </div>
                  <div class="info-item">
                    <span class="info-label">Role:</span>
                    <span class="info-value"><?php echo htmlspecialchars($row['role']); ?></span>
                  </div>
                </div>
              </div>
            </div>

            <div class="card-actions">
              <button class="btn edit-btn" onclick='openEditModal(<?php echo $json_admin; ?>)'>
                <i class="fas fa-pen"></i> Edit
              </button>
              <button class="btn remove-btn" onclick="openDeleteModal(<?php echo (int)$row['admin_id']; ?>)">
                <i class="fas fa-trash"></i> Delete
              </button>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="no-results">
          <i class="fas fa-users-slash"></i>
          <p>No admins found.</p>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- Add Admin Modal -->
  <div class="modal" id="addModal" role="dialog" aria-modal="true">
    <div class="modal-content" role="document">
      <div class="modal-header">
        <i class="fas fa-user-plus"></i> Add New Admin Account
      </div>
      <div class="modal-body">
        <form id="addForm" method="POST" action="add_admin.php">
          <label>Username:</label>
          <input type="text" name="username" required>

          <label>Email:</label>
          <input type="email" name="Email" required>

          <label>Password:</label>
          <input type="text" name="password" required>

          <label>Branch:</label>
          <select name="BranchName" required>
            <option value="">Select Branch</option>
            <option value="Autotec Shaw">Autotec Shaw</option>
            <option value="Autotec Subic">Autotec Subic</option>
          </select>

          <label>Role:</label>
          <input type="text" name="role">

          <div class="modal-actions">
            <button type="button" class="cancel-btn" onclick="closeModal('addModal')">Cancel</button>
            <button type="submit" class="save-btn">Add Admin</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal" id="editModal" role="dialog" aria-modal="true">
    <div class="modal-content" role="document">
      <div class="modal-header">
        <i class="fas fa-edit"></i> Edit Admin Account
      </div>
      <div class="modal-body">
        <form id="editForm" method="POST" action="update_admin.php">
          <input type="hidden" name="admin_id" id="edit_admin_id">
          
          <label for="edit_username">Username:</label>
          <input type="text" name="username" id="edit_username" required>

          <label for="edit_email">Email:</label>
          <input type="email" name="Email" id="edit_email" required>

          <label for="edit_password">Password:</label>
          <input type="text" name="password" id="edit_password" required>

          <label for="edit_branch">Branch:</label>
          <select name="BranchName" id="edit_branch" required>
            <option value="">Select Branch</option>
            <option value="Autotec Shaw">Autotec Shaw</option>
            <option value="Autotec Subic">Autotec Subic</option>
          </select>

          <label for="edit_role">Role:</label>
          <input type="text" name="role" id="edit_role">

          <div class="modal-actions">
            <button type="button" class="cancel-btn" onclick="closeModal('editModal')">Cancel</button>
            <button type="submit" class="save-btn">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Modal -->
  <div class="modal" id="deleteModal" role="dialog" aria-modal="true">
    <div class="modal-content" role="document">
      <div class="modal-header">
        <i class="fas fa-exclamation-triangle"></i> Confirm Deletion
      </div>
      <div class="modal-body">
        <div class="delete-warning">
          <i class="fas fa-exclamation-circle"></i>
          <p>Are you sure you want to remove this admin account? This action cannot be undone and will permanently delete all associated data.</p>
        </div>

        <form id="deleteForm" method="POST" action="delete_admin.php">
          <input type="hidden" name="admin_id" id="delete_admin_id">
          <div class="modal-actions">
            <button type="button" class="cancel-btn" onclick="closeModal('deleteModal')">Cancel</button>
            <button type="submit" class="save-btn" style="background: linear-gradient(180deg, #a4133c 0%, #ff4d6d 100%);">
              <i class="fas fa-trash"></i> Yes, Delete
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <div class="modal" id="successModal" aria-modal="true">
    <div class="modal-content">
      <div class="modal-header">
        <i class="fas fa-check-circle"></i> Success
      </div>
      <div class="modal-body small-modal-content">
        <i class="fas fa-check-circle" style="font-size: 56px; color: #27ae60; margin-bottom: 16px;"></i>
        <p id="successText">Success</p>
      </div>
    </div>
  </div>

  <!-- Error Modal -->
  <div class="modal" id="errorModal" aria-modal="true">
    <div class="modal-content">
      <div class="modal-header">
        <i class="fas fa-exclamation-circle"></i> Error
      </div>
      <div class="modal-body small-modal-content">
        <i class="fas fa-exclamation-circle" style="font-size: 56px; color: #e74c3c; margin-bottom: 16px;"></i>
        <p id="errorText">An error occurred</p>
        <button class="ok-btn" onclick="closeModal('errorModal')">OK</button>
      </div>
    </div>
  </div>

  <script>
    function openEditModal(admin) {
      document.getElementById('edit_admin_id').value = admin.admin_id || '';
      document.getElementById('edit_username').value = admin.username || '';
      document.getElementById('edit_email').value = admin.Email || '';
      document.getElementById('edit_password').value = admin.password || '';
      
      // Set the branch dropdown value
      const branchSelect = document.getElementById('edit_branch');
      branchSelect.value = admin.BranchName || '';
      
      document.getElementById('edit_role').value = admin.role || '';
      document.getElementById('editModal').classList.add('show');
    }

    function openDeleteModal(id) {
      document.getElementById('delete_admin_id').value = id;
      document.getElementById('deleteModal').classList.add('show');
    }

    function openModal(id) {
      document.getElementById(id).classList.add('show');
    }

    function closeModal(modalId) {
      var el = document.getElementById(modalId);
      if (el) el.classList.remove('show');
    }

    window.addEventListener('click', function(e) {
      ['editModal', 'deleteModal', 'addModal'].forEach(id => {
        const el = document.getElementById(id);
        if (e.target === el) closeModal(id);
      });
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        ['editModal', 'deleteModal', 'addModal'].forEach(closeModal);
      }
    });

    // Show success/error modals based on URL params
    (function() {
      const url = new URL(window.location.href);
      const success = url.searchParams.get('success');
      const added = url.searchParams.get('added');
      const deleted = url.searchParams.get('deleted');
      const error = url.searchParams.get('error');

      if (success === '1') {
        document.getElementById('successText').textContent = 'Admin updated successfully.';
        const m = document.getElementById('successModal');
        m.classList.add('show');
        setTimeout(()=>m.classList.remove('show'), 2500);
        url.searchParams.delete('success');
        window.history.replaceState({}, '', url.toString());
      }

      if (added === '1') {
        document.getElementById('successText').textContent = 'Admin added successfully.';
        const m = document.getElementById('successModal');
        m.classList.add('show');
        setTimeout(()=>m.classList.remove('show'), 2500);
        url.searchParams.delete('added');
        window.history.replaceState({}, '', url.toString());
      }

      if (deleted === '1') {
        document.getElementById('successText').textContent = 'Admin deleted successfully.';
        const m = document.getElementById('successModal');
        m.classList.add('show');
        setTimeout(()=>m.classList.remove('show'), 2500);
        url.searchParams.delete('deleted');
        window.history.replaceState({}, '', url.toString());
      }

      if (error) {
        let message = 'An error occurred.';
        if (error === 'duplicate_add') message = 'Username or Email already exists (add).';
        else if (error === 'duplicate_edit') message = 'Username or Email already exists (edit).';
        else if (error === 'db') message = 'Database error.';

        document.getElementById('errorText').textContent = message;
        document.getElementById('errorModal').classList.add('show');
        url.searchParams.delete('error');
        window.history.replaceState({}, '', url.toString());
      }
    })();
  </script>

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