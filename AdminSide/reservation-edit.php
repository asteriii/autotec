<?php
// reservation-edit.php
include 'db.php'; // uses mysqli $conn

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
        
        echo json_encode([
            'success' => true,
            'message' => 'QR code updated successfully',
            'path' => $dbPath
        ]);
        
    } catch (Exception $e) {
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
  <style>
    /* Clean, minimal styles (kept consistent with previous design) */
    * { box-sizing: border-box; font-family: 'Inter', sans-serif; margin:0; padding:0; }
    body { display:flex; min-height:100vh; background:#f4f4f4; color:#2d3748; }


    /* Main and topbar */
    .main { flex:1; display:flex; flex-direction:column; background:#f9f9f9; }
    .topbar { display:flex; justify-content:space-between; align-items:center; background:#fff; padding:10px 20px; border-bottom:1px solid #ccc; }
    .logo { font-size:20px; }
    .logout-btn { background:#e57373; color:#fff; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; }

    /* content */
    .content { padding:30px; flex:1; }
    .content h2 { margin-bottom:20px; font-size:22px; }

    /* cards */
    .reservation-card { background:#fff; border-radius:10px; padding:25px; box-shadow:0 2px 8px rgba(0,0,0,0.07); border:1px solid #e6e6e6; margin-bottom:24px; }
    .reservation-card h3 { color:#a93226; margin-bottom:16px; font-size:18px; }

    /* price grid equal cards */
    .price-container { display:grid; grid-template-columns: repeat(3,1fr); gap:20px; }
    @media (max-width:980px){ .price-container{grid-template-columns:repeat(2,1fr);} }
    @media (max-width:640px){ .price-container{grid-template-columns:1fr;} }

    .price-item { background:#fafafa; border-radius:8px; border:1px solid #eef0f2; padding:18px; display:flex; flex-direction:column; justify-content:center; align-items:center; height:140px; text-align:center; transition:transform .18s, box-shadow .18s; }
    .price-item:hover { transform:translateY(-5px); box-shadow:0 8px 22px rgba(0,0,0,0.06); }
    .price-item h4 { margin-bottom:8px; font-size:16px; color:#2d3748; }
    .price-value { font-size:18px; font-weight:700; color:#2d3748; }

    /* controls */
    .controls { display:flex; gap:10px; justify-content:flex-end; margin-top:14px; }
    .button-row { margin-top:18px; text-align:right; }
    .btn { background:#a93226; color:#fff; border:none; padding:9px 16px; border-radius:6px; cursor:pointer; font-weight:600; }
    .btn:hover { background:#922b20; }
    .btn-danger { background:#e57373; }
    .btn-danger:hover { background:#d32f2f; }

    /* QR cards */
    .qr-grid { display:flex; gap:20px; flex-wrap:wrap; margin-top:8px; }
    .service-card { background:#fff; border-radius:8px; padding:14px; box-shadow:0 1px 6px rgba(0,0,0,0.04); width:320px; border:1px solid #eef2f6; text-align:center; }
    .service-card img { max-width:220px; height:220px; object-fit:contain; border-radius:8px; display:block; margin:8px auto; border:1px solid #eee; }

    /* modal */
    .modal { display:none; position:fixed; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.45); justify-content:center; align-items:center; z-index:1200; }
    .modal.show { display:flex; }
    .modal-content { background:#fff; border-radius:10px; padding:18px; width:560px; max-width:96%; box-shadow:0 10px 30px rgba(0,0,0,0.2); }
    .modal-content h3 { margin-bottom:12px; color:#a93226; }
    .modal-row { display:flex; gap:12px; align-items:center; justify-content:space-between; margin-bottom:10px; }
    .modal-row .mtitle { width:60%; font-weight:600; color:#2d3748; }
    .modal-row input[type="text"] { width:40%; padding:8px 10px; border-radius:6px; border:1px solid #d1d5db; text-align:right; }

    .manage-add { display:flex; gap:10px; margin-bottom:10px; }
    .manage-add input[type="text"] { padding:8px 10px; border-radius:6px; border:1px solid #d1d5db; width:50%; }

    .confirm-content { text-align:center; }
    .announcement-preview { max-width:100%; max-height:300px; object-fit:contain; display:block; margin:10px auto; border-radius:6px; border:1px solid #ddd; }

    /* toast */
    .toast { position:fixed; right:20px; bottom:20px; background:#111827; color:#fff; padding:12px 16px; border-radius:8px; display:none; z-index:1300; box-shadow:0 8px 30px rgba(0,0,0,0.3); }
    .toast.show { display:block; animation: fadeInOut 3s forwards; }
    @keyframes fadeInOut { 0%{opacity:0;transform:translateY(8px)}10%{opacity:1;transform:translateY(0)}90%{opacity:1}100%{opacity:0;transform:translateY(8px)} }

    /* small utility */
    .manage-row { display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid #f3f3f3; gap:12px; }
    
    .qr-placeholder { width:220px; height:220px; background:#f0f0f0; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#999; margin:8px auto; border:2px dashed #ddd; }
  </style>
</head>
<body>

  <?php include 'sidebar.php'; ?>

  <!-- Main content -->
  <div class="main">
    <div class="topbar">
      <div class="logo">☰</div>
        <button class="logout-btn" onclick="window.location.href='logout.php'">
            <i class="fas fa-sign-out-alt"></i> Logout
          </button>
    </div>

    <div class="content">
      <h2>Reservation Details</h2>

      <!-- Pricing Card -->
      <div class="reservation-card">
        <h3>Pricing Details</h3>

        <div class="price-container" id="priceContainer">
          <?php foreach ($vehicle_types as $vt): ?>
            <div class="price-item" data-id="<?php echo (int)$vt['VehicleTypeID']; ?>">
              <h4><?php echo htmlspecialchars($vt['Name']); ?></h4>
              <div class="price-value" data-price-id="<?php echo (int)$vt['VehicleTypeID']; ?>">PHP <?php echo number_format((float)$vt['Price'], 2); ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="controls">
          <button class="btn" id="editPricingBtn">Edit Pricing</button>
          <button class="btn" id="managePricingBtn">Manage</button>
        </div>
      </div>

      <!-- QR Codes for Branches -->
      <div class="reservation-card">
        <h3>QR Codes for Payments</h3>
        <div class="qr-grid">
          <?php foreach ($branches as $branch): ?>
            <div class="service-card">
              <p><strong><?php echo htmlspecialchars($branch['BranchName']); ?>:</strong></p>
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
                  <i class="fas fa-qrcode" style="font-size:48px;"></i>
                </div>
              <?php endif; ?>
              
              <div class="button-row">
                <button class="btn" onclick="openQRModal(<?php echo $branch['AboutID']; ?>, '<?php echo htmlspecialchars($branch['BranchName'], ENT_QUOTES); ?>')">
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
      <h3>Edit Pricing Details</h3>
      <div id="pricingRows"></div>
      <div style="text-align:right; margin-top:14px;">
        <button class="btn" id="savePricingBtn">Save</button>
        <button class="btn btn-danger" id="closePricingBtn">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Manage Modal -->
  <div id="manageModal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <h3>Manage Vehicle Types</h3>

      <div class="manage-add">
        <input type="text" id="newName" placeholder="New vehicle name (e.g. 'Tricycle')" />
        <input type="text" id="newPrice" placeholder="Price (numeric only)" />
        <button class="btn" id="addNewBtn">Add</button>
      </div>

      <div id="manageList" style="max-height:300px; overflow:auto;"></div>

      <div style="text-align:right; margin-top:12px;">
        <button class="btn btn-danger" id="closeManageBtn">Close</button>
      </div>
    </div>
  </div>

  <!-- Confirm Delete Modal -->
  <div id="confirmModal" class="modal" aria-hidden="true">
    <div class="modal-content confirm-content">
      <h3>Confirm Remove</h3>
      <p id="confirmText">Are you sure you want to remove this item?</p>
      <div style="display:flex; gap:10px; justify-content:center; margin-top:12px;">
        <button class="btn btn-danger" id="confirmYes">Yes, remove</button>
        <button class="btn" id="confirmNo">Cancel</button>
      </div>
    </div>
  </div>

  <!-- QR Modal -->
  <div id="qrModal" class="modal" aria-hidden="true">
    <div class="modal-content">
      <h3 id="qrModalTitle">Edit QR Code</h3>
      <p>Current Image:</p>
      <img id="qrPreview" src="" class="announcement-preview" style="display:none;" />
      <div id="qrPreviewPlaceholder" class="qr-placeholder" style="margin:10px auto;">
        <i class="fas fa-qrcode" style="font-size:48px;"></i>
      </div>
      <label style="display:block; margin-top:15px; font-weight:600;">Upload New QR Code:</label>
      <input type="file" id="qrUpload" accept="image/*" style="margin-top:8px;" />
      <div style="text-align:right; margin-top:12px;">
        <button class="btn" id="saveQRBtn">Save</button>
        <button class="btn btn-danger" id="closeQRBtn">Cancel</button>
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
            if (el) el.innerText = 'PHP ' + parseFloat(row.Price).toFixed(2);
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
            <div style="color:#555;" class="manage-price" data-id="${id}">${parseFloat(priceText).toFixed(2)}</div>
          </div>
          <div>
            <button class="btn btn-danger" data-remove-id="${id}" data-remove-name="${escapeAttr(name)}">Remove</button>
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

      if (!confirm(`Add "${name}" with price PHP ${price}?`)) return;

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
          el.innerHTML = `<h4>${escapeHtml(vt.Name)}</h4><div class="price-value" data-price-id="${vt.VehicleTypeID}">PHP ${parseFloat(vt.Price).toFixed(2)}</div>`;
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
      document.getElementById('qrModalTitle').innerText = 'Edit ' + branchName + ' QR Code';
      
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
      const originalText = saveBtn.textContent;
      saveBtn.textContent = 'Uploading...';
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
        saveBtn.textContent = originalText;
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