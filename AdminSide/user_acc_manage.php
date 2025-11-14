<?php
include 'db.php';

// Pagination settings
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$records_per_page = 6;
$offset = ($page - 1) * $records_per_page;

// Search handling
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$params = [];
$search_sql = '';
if ($search !== '') {
    $search_sql = "WHERE Username LIKE ? OR Email LIKE ? OR Fname LIKE ? OR Address LIKE ?";
    $like = "%{$search}%";
    $params = [$like, $like, $like, $like];
}

// Get total records for pagination
if ($search_sql === '') {
    $count_sql = "SELECT COUNT(*) AS total FROM users";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute();
} else {
    $count_sql = "SELECT COUNT(*) AS total FROM users $search_sql";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("ssss", ...$params);
    $count_stmt->execute();
}
$count_result = $count_stmt->get_result()->fetch_assoc();
$total_records = (int)$count_result['total'];
$total_pages = max(1, (int)ceil($total_records / $records_per_page));
$count_stmt->close();

// Fetch user records
if ($search_sql === '') {
    $sql = "SELECT UserID, Fname, Username, password, Email, Address, PhoneNumber FROM users ORDER BY UserID ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $records_per_page, $offset);
} else {
    $sql = "SELECT UserID, Fname, Username, password, Email, Address, PhoneNumber FROM users $search_sql ORDER BY UserID ASC LIMIT ? OFFSET ?";
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
<title>User Accounts Manager</title>
<link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
<style>
  * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
  body { display:flex; min-height:100vh; background:#f4f4f4; }
  .main { flex:1; background:#f9f9f9; }
  .topbar { display:flex; justify-content:space-between; align-items:center; background:#fff; padding:15px 30px; box-shadow:0 2px 10px rgba(0,0,0,0.05); border-bottom:1px solid #e2e8f0; }
  .logo { font-size:24px; color:#c0392b; font-weight:600; }
  .logout-btn { background:linear-gradient(135deg, #a4133c, #ff4d6d); color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:500; transition:0.3s; box-shadow:0 2px 8px rgba(231,76,60,0.3); }
  .logout-btn:hover { transform:translateY(-2px); box-shadow:0 4px 15px rgba(231,76,60,0.4); }
  .content { padding:30px; }
  .content h2 { margin-bottom:20px; }
  .search-pagination { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:20px; }
  .search-box input { padding:8px 10px; border:1px solid #ccc; border-radius:4px; width:260px; }
  .search-btn { padding:9px 12px; background:#a93226; color:#fff; border:none; border-radius:4px; cursor:pointer; }
  .pagination { display:flex; gap:6px; }
  .pagination a, .pagination span { padding:6px 10px; border:1px solid #aaa; background:white; border-radius:4px; text-decoration:none; color:#333; }
  .pagination .current { background:#922b20; color:white; border-color:#922b20; }
  .user-card { display:flex; justify-content:space-between; background:white; border:1px solid #ccc; border-radius:8px; padding:18px; margin-bottom:16px; gap:12px; align-items:flex-start; }
  .user-details { flex:1; }
  .user-details h4 { margin-bottom:8px; font-size:18px; }
  .user-details p { margin:4px 0; color:#444; font-size:14px; }
  .card-actions { display:flex; flex-direction:column; gap:8px; align-items:flex-end; }
  .btn { padding:8px 12px; border-radius:6px; border:none; cursor:pointer; color:white; font-weight:600; }
  .edit-btn { background:#3182ce; }
  .remove-btn { background:#e53e3e; }
  .modal { display:none; position:fixed; z-index:999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.45); align-items:center; justify-content:center; padding:20px; }
  .modal.show { display:flex; }
  .modal-content { background:white; width:100%; max-width:480px; border-radius:10px; padding:22px; box-shadow:0 12px 30px rgba(0,0,0,0.15); }
  .modal-header { font-weight:700; margin-bottom:12px; font-size:18px; }
  .modal form label { display:block; margin:10px 0 6px; font-weight:600; }
  .modal form input { width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; }
  .modal-actions { margin-top:18px; text-align:right; display:flex; gap:8px; justify-content:flex-end; }
  .save-btn { background:#38a169; color:white; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; }
  .cancel-btn { background:#a0aec0; color:white; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; }
  .small-modal-content { text-align:center; padding:24px; }
  .small-modal-content p { margin:12px 0; font-size:16px; color:#2d3748; }
  .ok-btn { padding:8px 14px; border-radius:6px; border:none; background:#3182ce; color:white; cursor:pointer; }
</style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <div class="logo"><i class="fas fa-user"></i> AutoTec Admin</div>
    <button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
  </div>

  <div class="content">
    <h2>User Accounts Manager</h2>

    <div class="search-pagination">
      <form method="GET" class="search-box" style="display:flex;align-items:center;gap:8px;">
        <input type="text" name="search" placeholder="Search by name, username, email, or address..." value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>">
        <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
      </form>

      <div class="pagination">
        <?php
        $search_qs = $search !== '' ? '&search=' . urlencode($search) : '';
        for ($i = 1; $i <= $total_pages; $i++):
            if ($i === $page): ?>
              <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
              <a href="?page=<?php echo $i . $search_qs; ?>"><?php echo $i; ?></a>
            <?php endif;
        endfor;
        ?>
      </div>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
      <?php while ($row = $result->fetch_assoc()): 
        $json_user = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>
        <div class="user-card">
          <div class="user-details">
            <h4><?php echo htmlspecialchars($row['Fname']); ?></h4>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($row['Username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($row['Email']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($row['Address']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($row['PhoneNumber']); ?></p>
            <p style="font-size:12px; color:#666;">UserID: <?php echo (int)$row['UserID']; ?></p>
          </div>

          <div class="card-actions">
            <button class="btn edit-btn" onclick='openEditModal(<?php echo $json_user; ?>)'><i class="fas fa-pen"></i> Edit</button>
            <button class="btn remove-btn" onclick="openDeleteModal(<?php echo (int)$row['UserID']; ?>)"><i class="fas fa-trash"></i> Remove</button>
          </div>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div style="padding:20px;">No users found.</div>
    <?php endif; ?>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
  <div class="modal-content">
    <div class="modal-header">Edit User</div>
    <form id="editForm" method="POST" action="update_user.php">
      <input type="hidden" name="UserID" id="edit_UserID">
      <label>Full Name:</label>
      <input type="text" name="Fname" id="edit_Fname" required>

      <label>Username:</label>
      <input type="text" name="Username" id="edit_Username" required>

      <label>Password:</label>
      <input type="text" name="password" id="edit_password" required>

      <label>Email:</label>
      <input type="email" name="Email" id="edit_Email" required>

      <label>Address:</label>
      <input type="text" name="Address" id="edit_Address" required>

      <label>Phone Number:</label>
      <input type="text" name="PhoneNumber" id="edit_PhoneNumber">

      <div class="modal-actions">
        <button type="button" class="cancel-btn" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="save-btn">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal" id="deleteModal">
  <div class="modal-content">
    <div class="modal-header">Confirm Deletion</div>
    <p>Are you sure you want to remove this user?</p>
    <form id="deleteForm" method="POST" action="delete_user.php">
      <input type="hidden" name="UserID" id="delete_UserID">
      <div class="modal-actions">
        <button type="button" class="cancel-btn" onclick="closeModal('deleteModal')">Cancel</button>
        <button type="submit" class="save-btn">Yes, Delete</button>
      </div>
    </form>
  </div>
</div>

<!-- Success Modal -->
<div class="modal" id="successModal">
  <div class="modal-content small-modal-content">
    <p id="successText">Success</p>
  </div>
</div>

<!-- Error Modal -->
<div class="modal" id="errorModal">
  <div class="modal-content small-modal-content">
    <p id="errorText">Error occurred</p>
    <button class="ok-btn" onclick="closeModal('errorModal')">OK</button>
  </div>
</div>

<script>
function openEditModal(user) {
  document.getElementById('edit_UserID').value = user.UserID || '';
  document.getElementById('edit_Fname').value = user.Fname || '';
  document.getElementById('edit_Username').value = user.Username || '';
  document.getElementById('edit_password').value = user.password || '';
  document.getElementById('edit_Email').value = user.Email || '';
  document.getElementById('edit_Address').value = user.Address || '';
  document.getElementById('edit_PhoneNumber').value = user.PhoneNumber || '';
  document.getElementById('editModal').classList.add('show');
}

function openDeleteModal(id) {
  document.getElementById('delete_UserID').value = id;
  document.getElementById('deleteModal').classList.add('show');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('show');
}

window.addEventListener('click', e => {
  ['editModal', 'deleteModal'].forEach(id => {
    const el = document.getElementById(id);
    if (e.target === el) closeModal(id);
  });
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') ['editModal', 'deleteModal'].forEach(closeModal);
});

// Show modals based on URL params
(function() {
  const url = new URL(window.location.href);
  const success = url.searchParams.get('success');
  const deleted = url.searchParams.get('deleted');
  const error = url.searchParams.get('error');

  if (success === '1') {
    document.getElementById('successText').textContent = 'User updated successfully.';
    const m = document.getElementById('successModal');
    m.classList.add('show');
    setTimeout(() => m.classList.remove('show'), 2500);
  }

  if (deleted === '1') {
    document.getElementById('successText').textContent = 'User deleted successfully.';
    const m = document.getElementById('successModal');
    m.classList.add('show');
    setTimeout(() => m.classList.remove('show'), 2500);
  }

  if (error) {
    let msg = 'An error occurred.';
    if (error === 'duplicate') msg = 'Username or Email already exists.';
    if (error === 'db') msg = 'Database error.';
    document.getElementById('errorText').textContent = msg;
    document.getElementById('errorModal').classList.add('show');
  }
})();
</script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
