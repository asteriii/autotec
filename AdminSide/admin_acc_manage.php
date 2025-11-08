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
    // Note: mysqli requires the exact types in bind_param. 4s for search then ii for limit offset.
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
  <title>Admin Accounts manager</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet"/>
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
      background-color: #bd5d53ff;
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
      gap: 12px;
      flex-wrap:wrap;
    }

    .search-box input {
      padding: 8px 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      width: 260px;
    }

    .search-btn {
      padding: 9px 12px;
      background: #a93226;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .add-btn {
      padding: 9px 12px;
      background: #388e3c;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .pagination {
      display: flex;
      gap: 6px;
    }

    .pagination a, .pagination span {
      padding: 6px 10px;
      border: 1px solid #aaa;
      background-color: white;
      cursor: pointer;
      border-radius: 4px;
      text-decoration: none;
      color: #333;
    }

    .pagination .current {
      background-color: #922b20;
      color: #fff;
      border-color: #922b20;
    }

    .admin-card {
      display: flex;
      justify-content: space-between;
      background-color: white;
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 18px;
      margin-bottom: 16px;
      gap: 12px;
      align-items: flex-start;
    }

    .admin-details {
      flex: 1;
    }

    .admin-details h4 {
      margin-bottom: 8px;
      font-size: 18px;
    }

    .admin-details p {
      margin: 4px 0;
      color: #444;
      font-size: 14px;
    }

    .card-actions {
      display: flex;
      flex-direction: column;
      gap: 8px;
      align-items: flex-end;
    }

    .btn {
      padding: 8px 12px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      color: white;
      font-weight: 600;
    }

    .edit-btn { background: #3182ce; }
    .remove-btn { background: #e53e3e; }

    .modal {
      display: none;
      position: fixed;
      z-index: 999;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.45);
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .modal.show { display: flex; }

    .modal-content {
      background-color: #fff;
      width: 100%;
      max-width: 480px;
      border-radius: 10px;
      padding: 22px;
      box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    }

    .modal-header {
      font-weight: 700;
      margin-bottom: 12px;
      font-size: 18px;
    }

    .modal form label { display:block; margin:10px 0 6px; font-weight:600; }
    .modal form input[type="text"], .modal form input[type="email"], .modal form input[type="password"] {
      width:100%; padding:10px; border:1px solid #ddd; border-radius:6px;
    }

    .modal-actions { margin-top:18px; text-align:right; display:flex; gap:8px; justify-content:flex-end; }
    .save-btn { background:#38a169; color:white; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; }
    .cancel-btn { background:#a0aec0; color:white; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; }

    /* tiny modal styles for success/error */
    .small-modal-content { text-align:center; padding:24px; }
    .small-modal-content p { margin:12px 0; font-size:16px; color:#2d3748; }
    .ok-btn { padding:8px 14px; border-radius:6px; border:none; background:#3182ce; color:white; cursor:pointer; }

    @media (max-width: 800px) {
      .sidebar { display:none; }
      .search-box input { width:100%; }
      .admin-card { flex-direction: column; align-items:stretch; }
      .card-actions { flex-direction:row; justify-content:flex-end; }
    }

    /* ===== adminDash sidebar/topbar styles appended so red theme matches exactly ===== */
    /* these are the sidebar/topbar rules copied from adminDash.php (appended to take precedence) */
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

    /* end appended adminDash styles */
  </style>
</head>
<body>

  <?php include 'sidebar.php'; ?>

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
      <h2>Admin Accounts Manager</h2>

      <div class="search-pagination">
        <form method="GET" class="search-box" style="display:flex; align-items:center; gap:8px;">
          <label style="display:none">Search:</label>
          <input type="text" name="search" placeholder="Search by username, email, branch, or role..." value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>">
          <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
          <button type="button" class="add-btn" onclick="openModal('addModal')"><i class="fas fa-plus"></i> Add Admin</button>
        </form>

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

      <!-- NOTE: removed small inline success text; using modals below -->

      <!-- Admin cards -->
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <?php
            // ensure keys exist
            $row['BranchName'] = isset($row['BranchName']) ? $row['BranchName'] : '';
            $row['role'] = isset($row['role']) ? $row['role'] : '';
            // safe JSON for JS: escape
            $json_admin = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
          ?>
          <div class="admin-card">
            <div class="admin-details">
              <h4><?php echo htmlspecialchars($row['username']); ?></h4>
              <p><strong>Email:</strong> <?php echo htmlspecialchars($row['Email']); ?></p>
              <p><strong>Branch:</strong> <?php echo htmlspecialchars($row['BranchName']); ?></p>
              <p><strong>Role:</strong> <?php echo htmlspecialchars($row['role']); ?></p>
              <p style="font-size:12px; color:#666; margin-top:8px;">ID: <?php echo (int)$row['admin_id']; ?></p>
            </div>

            <div class="card-actions">
              <button class="btn edit-btn" onclick='openEditModal(<?php echo $json_admin; ?>)'><i class="fas fa-pen"></i> Edit</button>
              <button class="btn remove-btn" onclick="openDeleteModal(<?php echo (int)$row['admin_id']; ?>)"><i class="fas fa-trash"></i> Remove</button>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="reservation-card" style="padding:20px;">
          <div style="flex:1;">
            <p>No admins found.</p>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- ✅ Add Admin Modal -->
  <div class="modal" id="addModal" role="dialog" aria-modal="true">
    <div class="modal-content" role="document">
      <div class="modal-header">Add new admin account</div>
      <form id="addForm" method="POST" action="add_admin.php">
        <label>username:</label>
        <input type="text" name="username" required>

        <label>email:</label>
        <input type="email" name="Email" required>

        <label>password:</label>
        <input type="text" name="password" required>

        <label>Branch:</label>
        <input type="text" name="BranchName">

        <label>Role:</label>
        <input type="text" name="role">

        <div class="modal-actions">
          <button type="button" class="cancel-btn" onclick="closeModal('addModal')">Cancel</button>
          <button type="submit" class="save-btn">Add</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Modal -->
  <div class="modal" id="editModal" role="dialog" aria-modal="true">
    <div class="modal-content" role="document">
      <div class="modal-header">Edit Admin</div>
      <form id="editForm" method="POST" action="update_admin.php">
        <input type="hidden" name="admin_id" id="edit_admin_id">
        <label for="edit_username">username:</label>
        <input type="text" name="username" id="edit_username" required>

        <label for="edit_email">email:</label>
        <input type="email" name="Email" id="edit_email" required>

        <label for="edit_password">password:</label>
        <input type="text" name="password" id="edit_password" required>

        <label for="edit_branch">Branch:</label>
        <input type="text" name="BranchName" id="edit_branch">

        <label for="edit_role">Role:</label>
        <input type="text" name="role" id="edit_role">

        <div class="modal-actions">
          <button type="button" class="cancel-btn" onclick="closeModal('editModal')">Cancel</button>
          <button type="submit" class="save-btn">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Modal -->
  <div class="modal" id="deleteModal" role="dialog" aria-modal="true">
    <div class="modal-content" role="document">
      <div class="modal-header">Confirm Deletion</div>
      <p>Are you sure you want to remove this admin?</p>

      <form id="deleteForm" method="POST" action="delete_admin.php">
        <input type="hidden" name="admin_id" id="delete_admin_id">
        <div class="modal-actions">
          <button type="button" class="cancel-btn" onclick="closeModal('deleteModal')">Cancel</button>
          <button type="submit" class="save-btn">Yes, Delete</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Success Modal (auto-hide) -->
  <div class="modal" id="successModal" aria-modal="true">
    <div class="modal-content small-modal-content">
      <p id="successText">Success</p>
    </div>
  </div>

  <!-- Error Modal (requires OK) -->
  <div class="modal" id="errorModal" aria-modal="true">
    <div class="modal-content small-modal-content">
      <p id="errorText">An error occurred</p>
      <button class="ok-btn" onclick="closeModal('errorModal')">OK</button>
    </div>
  </div>

  <script>
    function toggleMenu(id) {
      const menu = document.getElementById(id);
      const isVisible = menu.style.display === 'block';
      menu.style.display = isVisible ? 'none' : 'block';
    }

    function openEditModal(admin) {
      document.getElementById('edit_admin_id').value = admin.admin_id || '';
      document.getElementById('edit_username').value = admin.username || '';
      document.getElementById('edit_email').value = admin.Email || '';
      document.getElementById('edit_password').value = admin.password || '';
      document.getElementById('edit_branch').value = admin.BranchName || '';
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

    // show success/error modals based on URL params
    (function() {
      const url = new URL(window.location.href);
      const success = url.searchParams.get('success');
      const added = url.searchParams.get('added');
      const deleted = url.searchParams.get('deleted');
      const error = url.searchParams.get('error'); // 'duplicate_add' or 'duplicate_edit' or others

      if (success === '1') {
        document.getElementById('successText').textContent = 'Admin updated successfully.';
        const m = document.getElementById('successModal');
        m.classList.add('show');
        setTimeout(()=>m.classList.remove('show'), 2500);
        // remove query param so refresh/back doesn't keep showing (optional but handy)
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

        // remove error param to avoid repeating on refresh
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
// cleanup
$stmt->close();
$conn->close();
?>
