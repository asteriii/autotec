<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
        exit;
    }
    header('Location: login.php');
    exit;
}

include 'db.php'; // uses mysqli $conn
require_once 'audit_trail.php';

// Get session variables
$username = $_SESSION['admin_username'] ?? 'Unknown Admin';
$admin_branch = $_SESSION['branch_filter'] ?? null;

// Define upload directory with absolute path for Railway
define('UPLOAD_DIR_QRCODES', '/var/www/html/uploads/qrcodes/');
define('UPLOAD_DIR_QRCODES_RELATIVE', 'uploads/qrcodes/');

// Create directory if it doesn't exist
if (!file_exists(UPLOAD_DIR_QRCODES)) {
    mkdir(UPLOAD_DIR_QRCODES, 0755, true);
}

// Handle QR code upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_qr') {
    header('Content-Type: application/json');
    
    try {
        $branchId = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        
        if ($branchId <= 0) {
            throw new Exception('Invalid branch ID');
        }
        
        // Get branch name from database
        $stmt = $conn->prepare("SELECT BranchName FROM about_us WHERE AboutID = ?");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $branchData = $result->fetch_assoc();
        $stmt->close();
        
        if (!$branchData) {
            throw new Exception('Branch not found');
        }
        
        $branchName = $branchData['BranchName'];
        
        // Check if admin has permission for this branch
        if ($admin_branch && $branchName !== $admin_branch) {
            logAction($username, 'Unauthorized Access', "Attempted to upload QR code for $branchName but assigned to $admin_branch");
            throw new Exception('You can only update QR codes for your assigned branch');
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['qr_image']) || $_FILES['qr_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }
        
        $file = $_FILES['qr_image'];
        
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size exceeds 5MB limit');
        }
        
        // Verify it's actually an image
        if (!getimagesize($file['tmp_name'])) {
            throw new Exception('Uploaded file is not a valid image');
        }
        
        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Get old QR code path to delete later
        $stmt = $conn->prepare("SELECT GcashQR FROM about_us WHERE AboutID = ?");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $oldQR = null;
        if ($row = $result->fetch_assoc()) {
            $oldQR = $row['GcashQR'];
        }
        $stmt->close();
        
        // Generate unique filename
        $newFileName = 'qr_branch_' . $branchId . '_' . time() . '.' . $extension;
        $destPath = UPLOAD_DIR_QRCODES . $newFileName;
        $dbPath = UPLOAD_DIR_QRCODES_RELATIVE . $newFileName;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new Exception('Failed to save QR code image');
        }
        
        // Set file permissions
        chmod($destPath, 0644);
        
        // Update database
        $stmt = $conn->prepare("UPDATE about_us SET GcashQR = ? WHERE AboutID = ?");
        $stmt->bind_param("si", $dbPath, $branchId);
        
        if (!$stmt->execute()) {
            // If database update fails, delete the uploaded file
            unlink($destPath);
            throw new Exception('Failed to update database');
        }
        
        $stmt->close();
        
        // Delete old QR code if it exists
        if ($oldQR && file_exists('/var/www/html/' . $oldQR)) {
            unlink('/var/www/html/' . $oldQR);
        }
        
        // 🧾 Log the audit trail
        logGcashQR($username, $branchName);
        
        echo json_encode([
            'success' => true,
            'message' => 'QR code updated successfully',
            'path' => $dbPath
        ]);
        
    } catch (Exception $e) {
        // Log error
        logAction($username, 'Error', "Failed to update GCash QR code: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Fetch vehicle types (initial server render)
$sql = "SELECT VehicleTypeID, Name, Price FROM vehicle_types ORDER BY VehicleTypeID ASC";
$result = $conn->query($sql);

$vehicle_types = [];
if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        $vehicle_types[] = $r;
    }
}

// Fetch branches with QR codes
$branchesSql = "SELECT AboutID, BranchName, GcashQR FROM about_us ORDER BY AboutID";
$branchesResult = $conn->query($branchesSql);
$branches = [];
if ($branchesResult && $branchesResult->num_rows > 0) {
    while ($b = $branchesResult->fetch_assoc()) {
        $branches[] = $b;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Reservation Details - AutoTec</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
  <style>
    * { box-sizing: border-box; font-family: 'Inter', sans-serif; margin:0; padding:0; }
    body { display:flex; min-height:100vh; background:#f5f7fa; color:#2d3748; }

    /* Main and topbar */
    .main { flex:1; display:flex; flex-direction:column; background:#f5f7fa; }
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
            color: #a4133c;
            font-weight: 600;
        }
      
    .logo i { font-size: 28px; }
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

    /* content */
    .content { padding:32px; flex:1; max-width: 1400px; margin: 0 auto; width: 100%; }
    .page-header {
      margin-bottom: 32px;
    }
    .page-header h2 { 
      font-size:32px; 
      font-weight:800; 
      color:#1a202c;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .page-header p {
      color: #718096;
      font-size: 15px;
    }

    /* cards */
    .reservation-card { 
      background:#fff; 
      border-radius:16px; 
      padding:32px; 
      box-shadow:0 1px 3px rgba(0,0,0,0.08); 
      border:1px solid #e2e8f0; 
      margin-bottom:28px;
      transition: box-shadow 0.3s ease;
    }
    .reservation-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      padding-bottom: 16px;
      border-bottom: 2px solid #f7fafc;
    }
    .card-header h3 { 
      color:#a4133c; 
      font-size:22px; 
      font-weight:700;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* price grid equal cards */
    .price-container { display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:20px; margin-bottom: 20px; }

    .price-item { 
      background: linear-gradient(135deg, #fff 0%, #f7fafc 100%);
      border-radius:12px; 
      border:2px solid #e2e8f0; 
      padding:24px; 
      display:flex; 
      flex-direction:column; 
      justify-content:center; 
      align-items:center; 
      min-height:150px; 
      text-align:center; 
      transition: all .3s ease; 
      position: relative;
      overflow: hidden;
    }
    .price-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, #a4133c 0%, #ff4d6d 100%);
      transform: scaleX(0);
      transition: transform 0.3s ease;
    }
    .price-item:hover::before {
      transform: scaleX(1);
    }
    .price-item:hover { 
      transform:translateY(-6px); 
      box-shadow:0 8px 24px rgba(164, 19, 60, 0.15);
      border-color: #ff4d6d;
    }
    .price-item h4 { 
      margin-bottom:12px; 
      font-size:17px; 
      color:#2d3748; 
      font-weight:600;
    }
    .price-value { 
      font-size:28px; 
      font-weight:800; 
      background: linear-gradient(135deg, #a4133c 0%, #ff4d6d 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* controls */
    .controls { display:flex; gap:12px; justify-content:flex-end; }
    .button-row { margin-top:18px; text-align:center; }
    .btn { 
      background: linear-gradient(135deg, #a4133c 0%, #ff4d6d 100%);
      color:#fff; 
      border:none; 
      padding:12px 24px; 
      border-radius:8px; 
      cursor:pointer; 
      font-weight:600;
      transition: all 0.3s ease;
      box-shadow: 0 2px 8px rgba(164, 19, 60, 0.2);
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .btn:hover { 
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(164, 19, 60, 0.3);
    }
    .btn:active {
      transform: translateY(0);
    }
    .btn-danger { 
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      box-shadow: 0 2px 8px rgba(220, 38, 38, 0.2);
    }
    .btn-danger:hover { 
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }
    .btn-outline {
      background: transparent;
      border: 2px solid #a4133c;
      color: #a4133c;
      box-shadow: none;
    }
    .btn-outline:hover {
      background: linear-gradient(135deg, #a4133c 0%, #ff4d6d 100%);
      color: #fff;
    }

    /* QR cards */
    .qr-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:24px; margin-top:8px; }
    .service-card { 
      background:#fff; 
      border-radius:12px; 
      padding:24px; 
      box-shadow:0 2px 8px rgba(0,0,0,0.06); 
      border:2px solid #e2e8f0; 
      text-align:center;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    .service-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, #a4133c 0%, #ff4d6d 100%);
    }
    .service-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      border-color: #ff4d6d;
    }
    .service-card p {
      font-size: 16px;
      font-weight: 700;
      color: #1a202c;
      margin-bottom: 16px;
    }
    .service-card img { 
      max-width:100%; 
      height:240px; 
      object-fit:contain; 
      border-radius:8px; 
      display:block; 
      margin:12px auto; 
      border:1px solid #e2e8f0; 
    }

    /* modal */
    .modal { 
      display:none; 
      position:fixed; 
      left:0; 
      top:0; 
      width:100%; 
      height:100%; 
      background:rgba(0,0,0,0.5); 
      justify-content:center; 
      align-items:center; 
      z-index:1200;
      backdrop-filter: blur(4px);
    }
    .modal.show { display:flex; }
    .modal-content { 
      background:#fff; 
      border-radius:16px; 
      padding:32px; 
      width:600px; 
      max-width:96%; 
      box-shadow:0 20px 60px rgba(0,0,0,0.3);
      animation: modalSlideIn 0.3s ease;
    }
    @keyframes modalSlideIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .modal-content h3 { 
      margin-bottom:24px; 
      color:#a4133c; 
      font-size: 24px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .modal-row { 
      display:flex; 
      gap:16px; 
      align-items:center; 
      justify-content:space-between; 
      margin-bottom:16px;
      padding: 12px;
      background: #f7fafc;
      border-radius: 8px;
    }
    .modal-row .mtitle { 
      flex: 1;
      font-weight:600; 
      color:#2d3748; 
    }
    .modal-row input[type="text"] { 
      width:140px; 
      padding:10px 14px; 
      border-radius:8px; 
      border:2px solid #e2e8f0; 
      text-align:right;
      font-weight: 600;
      transition: border-color 0.3s ease;
    }
    .modal-row input[type="text"]:focus {
      outline: none;
      border-color: #ff4d6d;
    }

    .manage-add { 
      display:flex; 
      gap:12px; 
      margin-bottom:20px;
      padding: 16px;
      background: #f7fafc;
      border-radius: 8px;
    }
    .manage-add input[type="text"] { 
      padding:10px 14px; 
      border-radius:8px; 
      border:2px solid #e2e8f0; 
      flex: 1;
      transition: border-color 0.3s ease;
    }
    .manage-add input[type="text"]:focus {
      outline: none;
      border-color: #ff4d6d;
    }

    .confirm-content { text-align:center; }
    .confirm-content p {
      font-size: 16px;
      color: #4a5568;
      margin: 20px 0;
    }
    .announcement-preview { 
      max-width:100%; 
      max-height:300px; 
      object-fit:contain; 
      display:block; 
      margin:16px auto; 
      border-radius:8px; 
      border:2px solid #e2e8f0; 
    }

    /* toast */
    .toast { 
      position:fixed; 
      right:20px; 
      bottom:20px; 
      background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
      color:#fff; 
      padding:16px 24px; 
      border-radius:12px; 
      display:none; 
      z-index:1300; 
      box-shadow:0 8px 30px rgba(0,0,0,0.3);
      font-weight: 600;
    }
    .toast.show { display:block; animation: fadeInOut 3s forwards; }
    @keyframes fadeInOut { 
      0%{opacity:0;transform:translateY(20px)}
      10%{opacity:1;transform:translateY(0)}
      90%{opacity:1}
      100%{opacity:0;transform:translateY(20px)} 
    }

    /* manage row */
    .manage-row { 
      display:flex; 
      justify-content:space-between; 
      align-items:center; 
      padding:14px; 
      border-bottom:1px solid #e2e8f0; 
      gap:16px;
      transition: background 0.2s ease;
    }
    .manage-row:hover {
      background: #f7fafc;
    }
    .manage-row:last-child {
      border-bottom: none;
    }
    
    .qr-placeholder { 
      width:100%; 
      height:240px; 
      background: linear-gradient(135deg, #f7fafc 0%, #e2e8f0 100%);
      border-radius:8px; 
      display:flex; 
      align-items:center; 
      justify-content:center; 
      color:#a0aec0; 
      margin:12px auto; 
      border:2px dashed #cbd5e0; 
    }

    .modal-actions {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      margin-top: 24px;
      padding-top: 20px;
      border-top: 1px solid #e2e8f0;
    }

    #manageList {
      max-height: 400px;
      overflow-y: auto;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      background: #fff;
    }

    /* Scrollbar styling */
    #manageList::-webkit-scrollbar {
      width: 8px;
    }
    #manageList::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 8px;
    }
    #manageList::-webkit-scrollbar-thumb {
      background: #cbd5e0;
      border-radius: 8px;
    }
    #manageList::-webkit-scrollbar-thumb:hover {
      background: #a0aec0;
    }

    input[type="file"] {
      padding: 10px;
      border: 2px dashed #e2e8f0;
      border-radius: 8px;
      width: 100%;
      cursor: pointer;
      transition: border-color 0.3s ease;
    }
    input[type="file"]:hover {
      border-color: #ff4d6d;
    }

    label {
      display: block;
      margin-top: 20px;
      margin-bottom: 8px;
      font-weight: 600;
      color: #2d3748;
    }
  </style>
</head>
<body>

  <?php include 'sidebar.php'; ?>

  <!-- Main content -->
  <div class="main">
    <div class="topbar">
      <div class="topbar-left">
        <div class="logo">
          <i class="fas fa-clipboard-list"></i>
          Reservation Details
        </div>
      </div>
      <button class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </button>
    </div>

    <div class="content">
      <div class="page-header">
        <p>Manage pricing and payment QR codes for your branches</p>
      </div>

      <!-- Pricing Card -->
      <div class="reservation-card">
        <div class="card-header">
          <h3>
            <i class="fas fa-tags"></i>
            Pricing Details
          </h3>
          <div class="controls">
            <button class="btn btn-outline" id="editPricingBtn">
              <i class="fas fa-edit"></i>
              Edit Pricing
            </button>
            <button class="btn" id="managePricingBtn">
              <i class="fas fa-cog"></i>
              Manage
            </button>
          </div>
        </div>

        <div class="price-container" id="priceContainer">
          <?php foreach ($vehicle_types as $vt): ?>
            <div class="price-item" data-id="<?php echo (int)$vt['VehicleTypeID']; ?>">
              <h4><?php echo htmlspecialchars($vt['Name']); ?></h4>
              <div class="price-value" data-price-id="<?php echo (int)$vt['VehicleTypeID']; ?>">₱<?php echo number_format((float)$vt['Price'], 2); ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- QR Codes for Branches -->
      <div class="reservation-card">
        <div class="card-header">
          <h3>
            <i class="fas fa-qrcode"></i>
            QR Codes for Payments
          </h3>
        </div>
        <div class="qr-grid">
          <?php foreach ($branches as $branch): ?>
            <div class="service-card">
              <p><strong><?php echo htmlspecialchars($branch['BranchName']); ?></strong></p>
              <?php 
              $qrPath = !empty($branch['GcashQR']) ? htmlspecialchars($branch['GcashQR']) : '';
              $qrExists = !empty($qrPath) && file_exists('/var/www/html/' . $qrPath);
              ?>
              
              <?php if ($qrExists): ?>
                <img id="branchQR_<?php echo $branch['AboutID']; ?>" 
                     src="<?php echo $qrPath; ?>?v=<?php echo time(); ?>" 
                     alt="<?php echo htmlspecialchars($branch['BranchName']); ?> QR">
              <?php else: ?>
                <div class="qr-placeholder" id="branchQR_<?php echo $branch['AboutID']; ?>">
                  <i class="fas fa-qrcode" style="font-size:64px;"></i>
                </div>
              <?php endif; ?>
              
              <div class="button-row">
                <button class="btn" onclick="openQRModal(<?php echo $branch['AboutID']; ?>, '<?php echo htmlspecialchars($branch['BranchName'], ENT_QUOTES); ?>')">
                  <i class="fas fa-<?php echo $qrExists ? 'edit' : 'upload'; ?>"></i>
                  <?php echo $qrExists ? 'Edit' : 'Upload'; ?>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>

  <!-- Edit Pricing Modal -->
  <div id="pricingModal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <h3>
        <i class="fas fa-edit"></i>
        Edit Pricing Details
      </h3>
      <div id="pricingRows"></div>
      <div class="modal-actions">
        <button class="btn" id="savePricingBtn">
          <i class="fas fa-save"></i>
          Save Changes
        </button>
        <button class="btn btn-danger" id="closePricingBtn">
          <i class="fas fa-times"></i>
          Cancel
        </button>
      </div>
    </div>
  </div>

  <!-- Manage Modal -->
  <div id="manageModal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <h3>
        <i class="fas fa-cog"></i>
        Manage Vehicle Types
      </h3>

      <div class="manage-add">
        <input type="text" id="newName" placeholder="Vehicle name (e.g. 'Tricycle')" />
        <input type="text" id="newPrice" placeholder="Price" />
        <button class="btn" id="addNewBtn">
          <i class="fas fa-plus"></i>
          Add
        </button>
      </div>

      <div id="manageList"></div>

      <div class="modal-actions">
        <button class="btn btn-danger" id="closeManageBtn">
          <i class="fas fa-times"></i>
          Close
        </button>
      </div>
    </div>
  </div>

  <!-- Confirm Delete Modal -->
  <div id="confirmModal" class="modal" aria-hidden="true">
    <div class="modal-content confirm-content">
      <h3>
        <i class="fas fa-exclamation-triangle"></i>
        Confirm Remove
      </h3>
      <p id="confirmText">Are you sure you want to remove this item?</p>
      <div style="display:flex; gap:12px; justify-content:center; margin-top:20px;">
        <button class="btn btn-danger" id="confirmYes">
          <i class="fas fa-trash"></i>
          Yes, Remove
        </button>
        <button class="btn btn-outline" id="confirmNo">
          <i class="fas fa-times"></i>
          Cancel
        </button>
      </div>
    </div>
  </div>

  <!-- QR Modal -->
  <div id="qrModal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <h3 id="qrModalTitle">
        <i class="fas fa-qrcode"></i>
        Edit QR Code
      </h3>
      <p style="font-weight:600; color:#4a5568; margin-bottom:12px;">Current Image:</p>
      <img id="qrPreview" src="" class="announcement-preview" style="display:none;" />
      <div id="qrPreviewPlaceholder" class="qr-placeholder" style="margin:10px auto; height:200px;">
        <i class="fas fa-qrcode" style="font-size:64px;"></i>
      </div>
      <label>Upload New QR Code:</label>
      <input type="file" id="qrUpload" accept="image/*" />
      <div class="modal-actions">
        <button class="btn" id="saveQRBtn">
          <i class="fas fa-save"></i>
          Save
        </button>
        <button class="btn btn-danger" id="closeQRBtn">
          <i class="fas fa-times"></i>
          Cancel
        </button>
      </div>
    </div>
  </div>

  <div id="toast" class="toast"></div>

  <script>
    // Utility: toast
    function showToast(msg, timeout = 2500) {
      const t = document.getElementById('toast');
      t.innerText = msg;
      t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), timeout);
    }

    // toggle sidebar menus (keeps your original behavior)
    function toggleMenu(id) {
      const menu = document.getElementById(id);
      const isVisible = menu.style.display === 'block';
      menu.style.display = isVisible ? 'none' : 'block';
    }

    /* ---------- Edit Pricing Modal logic ---------- */
    const pricingModal = document.getElementById('pricingModal');
    const pricingRows = document.getElementById('pricingRows');
    document.getElementById('editPricingBtn').addEventListener('click', openPricingModal);
    document.getElementById('closePricingBtn').addEventListener('click', closePricingModal);

    function openPricingModal() {
      pricingRows.innerHTML = '';
      const items = document.querySelectorAll('.price-item');
      items.forEach(item => {
        const id = item.getAttribute('data-id');
        const name = item.querySelector('h4').innerText;
        const priceText = item.querySelector('.price-value').innerText.replace(/[^0-9.\-]/g, '');
        const row = document.createElement('div');
        row.className = 'modal-row';
        row.innerHTML = `<div class="mtitle">${escapeHtml(name)}</div><input type="text" data-vid="${id}" value="${priceText}">`;
        pricingRows.appendChild(row);
      });
      pricingModal.classList.add('show');
      pricingModal.setAttribute('aria-hidden', 'false');
    }

    function closePricingModal() {
      pricingModal.classList.remove('show');
      pricingModal.setAttribute('aria-hidden', 'true');
    }

    // Save pricing
    document.getElementById('savePricingBtn').addEventListener('click', function() {
      const inputs = pricingRows.querySelectorAll('input[data-vid]');
      const updates = [];
      for (let i = 0; i < inputs.length; i++) {
        const inp = inputs[i];
        const id = parseInt(inp.getAttribute('data-vid'), 10);
        const raw = inp.value.trim();
        if (raw === '' || isNaN(raw)) { alert('Please enter valid numeric prices for all items.'); return; }
        updates.push({ id: id, price: parseFloat(raw).toFixed(2) });
      }

      // send JSON to update endpoint
      fetch('update_vehicle_types.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ updates: updates })
      })
      .then(resp => {
        if (!resp.ok) throw new Error('Network response not ok');
        return resp.json();
      })
      .then(data => {
        if (data && data.success) {
          // update DOM
          data.updated.forEach(row => {
            const el = document.querySelector('.price-value[data-price-id="' + row.VehicleTypeID + '"]');
            if (el) el.innerText = '₱' + parseFloat(row.Price).toFixed(2);
          });
          closePricingModal();
          showToast('Pricing updated successfully');
        } else {
          alert('Update failed: ' + (data.error || 'Unknown error'));
        }
      })
      .catch(err => {
        console.error('Save pricing error:', err);
        alert('Error saving changes (see console).');
      });
    });

    /* ---------- Manage Modal logic (Add/Delete) ---------- */
    const manageModal = document.getElementById('manageModal');
    const manageList = document.getElementById('manageList');
    document.getElementById('managePricingBtn').addEventListener('click', openManageModal);
    document.getElementById('closeManageBtn').addEventListener('click', closeManageModal);

    function openManageModal() {
      refreshManageList();
      manageModal.classList.add('show');
      manageModal.setAttribute('aria-hidden','false');
    }
    function closeManageModal() {
      manageModal.classList.remove('show');
      manageModal.setAttribute('aria-hidden','true');
    }

    function refreshManageList() {
      manageList.innerHTML = '';
      const items = document.querySelectorAll('.price-item');
      items.forEach(item => {
        const id = item.getAttribute('data-id');
        const name = item.querySelector('h4').innerText;
        const priceText = item.querySelector('.price-value').innerText.replace(/[^0-9.\-]/g, '');
        const row = document.createElement('div');
        row.className = 'manage-row';
        row.innerHTML = `
          <div style="display:flex;gap:12px;align-items:center;">
            <div style="font-weight:600;">${escapeHtml(name)}</div>
            <div style="color:#718096;" class="manage-price" data-id="${id}">₱${parseFloat(priceText).toFixed(2)}</div>
          </div>
          <div>
            <button class="btn btn-danger" data-remove-id="${id}" data-remove-name="${escapeAttr(name)}">
              <i class="fas fa-trash"></i>
              Remove
            </button>
          </div>
        `;
        manageList.appendChild(row);
      });

      // attach remove handlers (delegation style)
      manageList.querySelectorAll('button[data-remove-id]').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = parseInt(btn.getAttribute('data-remove-id'), 10);
          const name = btn.getAttribute('data-remove-name');
          askRemove(id, name);
        });
      });
    }

    // Add new
    document.getElementById('addNewBtn').addEventListener('click', function() {
      const name = document.getElementById('newName').value.trim();
      const priceRaw = document.getElementById('newPrice').value.trim();
      if (!name) { alert('Enter vehicle name'); return; }
      if (priceRaw === '' || isNaN(priceRaw)) { alert('Enter valid numeric price'); return; }
      const price = parseFloat(priceRaw).toFixed(2);

      if (!confirm(`Add "${name}" with price ₱${price}?`)) return;

      fetch('manage_vehicle_types.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action:'add', name: name, price: price })
      })
      .then(resp => { if(!resp.ok) throw new Error('Network'); return resp.json(); })
      .then(data => {
        if (data && data.success && data.inserted) {
          const vt = data.inserted;
          // append card
          const pc = document.getElementById('priceContainer');
          const el = document.createElement('div');
          el.className = 'price-item';
          el.setAttribute('data-id', vt.VehicleTypeID);
          el.innerHTML = `<h4>${escapeHtml(vt.Name)}</h4><div class="price-value" data-price-id="${vt.VehicleTypeID}">₱${parseFloat(vt.Price).toFixed(2)}</div>`;
          pc.appendChild(el);

          // clear inputs and refresh
          document.getElementById('newName').value = '';
          document.getElementById('newPrice').value = '';
          refreshManageList();
          showToast('Vehicle type added');
        } else {
          alert('Add failed: ' + (data.error || 'Unknown'));
        }
      })
      .catch(err => { console.error('Add error:', err); alert('Error adding vehicle type'); });
    });

    /* ---------- Remove flow with confirm modal ---------- */
    let pendingDeleteId = null;
    const confirmModal = document.getElementById('confirmModal');
    document.getElementById('confirmNo').addEventListener('click', () => { pendingDeleteId = null; confirmModal.classList.remove('show'); });
    document.getElementById('confirmYes').addEventListener('click', () => {
      if (!pendingDeleteId) return;
      // call delete
      fetch('manage_vehicle_types.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id: pendingDeleteId })
      })
      .then(resp => { if(!resp.ok) throw new Error('Network'); return resp.json(); })
      .then(data => {
        if (data && data.success && data.deleted_id) {
          // remove item from DOM
          const el = document.querySelector('.price-item[data-id="' + data.deleted_id + '"]');
          if (el) el.remove();
          refreshManageList();
          showToast('Vehicle type removed');
        } else {
          alert('Remove failed: ' + (data.error || 'Unknown'));
        }
      })
      .catch(err => { console.error('Delete error:', err); alert('Error removing vehicle type'); })
      .finally(() => {
        pendingDeleteId = null;
        confirmModal.classList.remove('show');
      });
    });

    function askRemove(id, name) {
      pendingDeleteId = id;
      document.getElementById('confirmText').innerText = `Remove "${name}"? This cannot be undone.`;
      confirmModal.classList.add('show');
    }

    /* ---------- QR modal (with actual upload to server) ---------- */
    const qrModal = document.getElementById('qrModal');
    const qrPreview = document.getElementById('qrPreview');
    const qrPreviewPlaceholder = document.getElementById('qrPreviewPlaceholder');
    let currentQRBranchId = null;

    function openQRModal(branchId, branchName) {
      currentQRBranchId = branchId;
      document.getElementById('qrModalTitle').innerHTML = '<i class="fas fa-qrcode"></i> Edit ' + branchName + ' QR Code';
      
      // Get current QR image
      const img = document.getElementById('branchQR_' + branchId);
      if (img && img.tagName === 'IMG') {
        qrPreview.src = img.src;
        qrPreview.style.display = 'block';
        qrPreviewPlaceholder.style.display = 'none';
      } else {
        qrPreview.style.display = 'none';
        qrPreviewPlaceholder.style.display = 'flex';
      }
      
      // Clear file input
      document.getElementById('qrUpload').value = '';
      
      qrModal.classList.add('show');
      qrModal.setAttribute('aria-hidden','false');
    }

    document.getElementById('closeQRBtn').addEventListener('click', () => { 
      qrModal.classList.remove('show'); 
      currentQRBranchId = null;
    });

    document.getElementById('saveQRBtn').addEventListener('click', function(){
      const input = document.getElementById('qrUpload');
      
      if (!input.files || !input.files[0]) { 
        showToast('Please select a file first');
        return; 
      }
      
      if (!currentQRBranchId) {
        showToast('Invalid branch selection');
        return;
      }
      
      // Create FormData for file upload
      const formData = new FormData();
      formData.append('action', 'upload_qr');
      formData.append('branch_id', currentQRBranchId);
      formData.append('qr_image', input.files[0]);
      
      // Disable button during upload
      const saveBtn = document.getElementById('saveQRBtn');
      const originalText = saveBtn.innerHTML;
      saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
      saveBtn.disabled = true;
      
      // Upload to server
      fetch('reservation-edit.php', {
        method: 'POST',
        body: formData
      })
      .then(resp => resp.json())
      .then(data => {
        if (data.success) {
          // Update the QR image on the page
          const targetElement = document.getElementById('branchQR_' + currentQRBranchId);
          
          if (targetElement.tagName === 'IMG') {
            // Already an image, just update src
            targetElement.src = data.path + '?v=' + Date.now();
          } else {
            // Was a placeholder, replace with image
            const newImg = document.createElement('img');
            newImg.id = 'branchQR_' + currentQRBranchId;
            newImg.src = data.path + '?v=' + Date.now();
            newImg.alt = 'Branch QR';
            targetElement.parentElement.replaceChild(newImg, targetElement);
          }
          
          qrModal.classList.remove('show');
          showToast('QR code updated successfully!');
          currentQRBranchId = null;
        } else {
          alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
      })
      .catch(err => {
        console.error('QR upload error:', err);
        alert('Error uploading QR code. Please try again.');
      })
      .finally(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
      });
    });

    // small helpers:
    function escapeHtml(str) {
      return String(str).replace(/[&<>"'\/]/g, function(s){ const e = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;'}; return e[s]; });
    }
    function escapeAttr(str) {
      return String(str).replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

  </script>
</body>
</html>