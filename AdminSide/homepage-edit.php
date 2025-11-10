<?php
include 'db.php';

$sql = "SELECT title, header, operate, location, contact, service1_img, service2_img, service3_img, announcement_img FROM homepage WHERE id = 1";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

$title = $row['title'] ?? '';
$header = $row['header'] ?? '';
$operate = $row['operate'] ?? '';
$location = $row['location'] ?? '';
$contact = $row['contact'] ?? '';

// Fixed file paths to use AdminSide/uploads
$service1_img = !empty($row['service1_img']) ? 'uploads/homepage/' . $row['service1_img'] : 'placeholder1.jpg';
$service2_img = !empty($row['service2_img']) ? 'uploads/homepage/' . $row['service2_img'] : 'placeholder2.jpg';
$service3_img = !empty($row['service3_img']) ? 'uploads/homepage/' . $row['service3_img'] : 'placeholder3.jpg';
$announcement_img = !empty($row['announcement_img']) ? 'uploads/homepage/' . $row['announcement_img'] : 'announcement.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Homepage</title>

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
      background: #f5f7fa;
    }

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

    .main {
        flex: 1;
        background-color: #f5f7fa;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
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

    .content {
        padding: 30px;
        width: 100%;
    }

    h2 { 
        margin-bottom: 25px; 
        color: #2d3748; 
        font-size: 28px;
        font-weight: 700;
    }

    /* Card-based design */
    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        margin-bottom: 25px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    }

    .card-header {
        background: linear-gradient(135deg, #a4133c, #ff4d6d);
        color: white;
        padding: 20px 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 18px;
        font-weight: 600;
    }

    .card-header i {
        font-size: 22px;
    }

    .card-body {
        padding: 25px;
    }

    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f1f3f5;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 600;
        color: #64748b;
        min-width: 160px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-label i {
        color: #a4133c;
        font-size: 14px;
    }

    .info-value {
        color: #334155;
        flex: 1;
    }

    .btn-edit {
        background: linear-gradient(135deg, #a4133c, #ff4d6d);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 15px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(164, 19, 60, 0.3);
    }

    .btn-edit:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(164, 19, 60, 0.4);
    }

    /* Service cards grid */
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }

    .service-item {
        background: #f8fafc;
        border: 2px dashed #e2e8f0;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .service-item:hover {
        border-color: #ff4d6d;
        background: #fff;
    }

    .service-item img {
        width: 100%;
        max-width: 300px;
        height: 200px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .service-title {
        font-weight: 600;
        color: #334155;
        margin-bottom: 12px;
        font-size: 16px;
    }

    .announcement-preview-box {
        text-align: center;
        padding: 20px;
        background: #f8fafc;
        border-radius: 10px;
        border: 2px dashed #e2e8f0;
    }

    .announcement-preview-box img {
        max-width: 100%;
        max-height: 400px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0; 
      top: 0;
      width: 100%; 
      height: 100%;
      background-color: rgba(0,0,0,0.6);
      justify-content: center;
      align-items: center;
      backdrop-filter: blur(4px);
    }

    .modal-content {
      background-color: #fff;
      padding: 0;
      border-radius: 16px;
      width: 600px;
      max-width: 95%;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      overflow: hidden;
      animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        background: linear-gradient(135deg, #a4133c, #ff4d6d);
        color: white;
        padding: 20px 30px;
        font-size: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .modal-body {
        padding: 30px;
    }

    .modal-content label {
      margin: 15px 0 8px;
      font-weight: 600;
      display: block;
      color: #334155;
      font-size: 14px;
    }

    .modal-content input[type="text"],
    .modal-content input[type="file"] {
      padding: 12px 16px;
      font-size: 15px;
      border-radius: 8px;
      border: 1px solid #cbd5e1;
      width: 100%;
      margin-bottom: 12px;
      transition: all 0.3s ease;
    }

    .modal-content input[type="text"]:focus,
    .modal-content input[type="file"]:focus {
        outline: none;
        border-color: #ff4d6d;
        box-shadow: 0 0 0 3px rgba(255, 77, 109, 0.1);
    }

    .preview-container {
        margin-top: 15px;
        text-align: center;
        padding: 20px;
        background: #f8fafc;
        border-radius: 8px;
        border: 2px dashed #e2e8f0;
    }

    .preview-container p {
        margin-bottom: 10px;
        color: #64748b;
        font-size: 13px;
        font-weight: 500;
    }

    .preview-container img {
      max-width: 100%;
      max-height: 300px;
      object-fit: contain;
      border-radius: 8px;
    }

    .modal-footer {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 25px;
    }

    .btn-save {
        background: linear-gradient(135deg, #a4133c, #ff4d6d);
        color: white;
        border: none;
        padding: 12px 28px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(164, 19, 60, 0.4);
    }

    .btn-cancel {
        background: #e2e8f0;
        color: #475569;
        border: none;
        padding: 12px 28px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background: #cbd5e1;
    }

    /* Toast notification */
    .toast {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: toastSlideIn 0.3s ease;
    }

    @keyframes toastSlideIn {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @media (max-width: 768px) {
      .modal-content {
        width: 90%;
      }

      .services-grid {
        grid-template-columns: 1fr;
      }

      .info-row {
        flex-direction: column;
        gap: 8px;
      }

      .info-label {
        min-width: auto;
      }
    }
  </style>
</head>
<body>
    <div class="sidebar">
        <div class="section">
            <div class="section-title active">
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
            <ul class="submenu show" id="page-settings">
                <li><a href="homepage-edit.php" style="font-weight:700;"><i class="fas fa-home"></i> Home Page</a></li>
                <li><a href="reservation-edit.php"><i class="fas fa-envelope"></i> Reservation Details</a></li>
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

        <div class="section">
            <div class="section-title" onclick="toggleMenu('master-controls')">
                <span><i class="fas fa-user-shield"></i> Master Controls</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <ul class="submenu" id="master-controls">
                <li><a href="admin_acc_manage.php"><i class="fas fa-users-cog"></i> Admin Accounts Manager</a></li>
            </ul>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="logo">
                <i class="fas fa-car"></i> AutoTec Admin
            </div>
            <button class="logout-btn" onclick="window.location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>

        <div class="content">
            <h2><i class="fas fa-home"></i> Homepage Settings</h2>

            <!-- Description Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-align-left"></i>
                    <span>Description</span>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-heading"></i>
                            Title:
                        </div>
                        <div class="info-value" id="desc-title"><?= htmlspecialchars($title) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-file-alt"></i>
                            Description:
                        </div>
                        <div class="info-value" id="desc-header"><?= htmlspecialchars($header) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-clock"></i>
                            Operating Hours:
                        </div>
                        <div class="info-value" id="desc-operate"><?= htmlspecialchars($operate) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-map-marker-alt"></i>
                            Location:
                        </div>
                        <div class="info-value" id="desc-location"><?= htmlspecialchars($location) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-phone"></i>
                            Contact:
                        </div>
                        <div class="info-value" id="desc-contact"><?= htmlspecialchars($contact) ?></div>
                    </div>
                    <button class="btn-edit" onclick="openDescModal()">
                        <i class="fas fa-edit"></i>
                        Edit Description
                    </button>
                </div>
            </div>

            <!-- Services Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-wrench"></i>
                    <span>Services</span>
                </div>
                <div class="card-body">
                    <div class="services-grid">
                        <div class="service-item">
                            <div class="service-title">
                                <i class="fas fa-car"></i> Service 1
                            </div>
                            <img id="preview-service1" src="<?php echo $service1_img . '?v=' . time(); ?>" alt="Service 1" />
                            <button class="btn-edit" onclick="openServiceModal(1)">
                                <i class="fas fa-edit"></i> Edit Image
                            </button>
                        </div>
                        <div class="service-item">
                            <div class="service-title">
                                <i class="fas fa-car"></i> Service 2
                            </div>
                            <img id="preview-service2" src="<?php echo $service2_img . '?v=' . time(); ?>" alt="Service 2" />
                            <button class="btn-edit" onclick="openServiceModal(2)">
                                <i class="fas fa-edit"></i> Edit Image
                            </button>
                        </div>
                        <div class="service-item">
                            <div class="service-title">
                                <i class="fas fa-car"></i> Service 3
                            </div>
                            <img id="preview-service3" src="<?php echo $service3_img . '?v=' . time(); ?>" alt="Service 3" />
                            <button class="btn-edit" onclick="openServiceModal(3)">
                                <i class="fas fa-edit"></i> Edit Image
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Announcement Card -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcement / Promotions</span>
                </div>
                <div class="card-body">
                    <div class="announcement-preview-box">
                        <img id="preview-announcement" src="<?= $announcement_img  . '?v=' . time(); ?>" alt="Announcement" />
                    </div>
                    <button class="btn-edit" onclick="openAnnouncementModal()">
                        <i class="fas fa-edit"></i>
                        Edit Announcement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Description Modal -->
    <div id="descModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-edit"></i>
            Edit Description
        </div>
        <div class="modal-body">
            <label><i class="fas fa-heading"></i> Title:</label>
            <input type="text" id="edit-title" value="<?= htmlspecialchars($title) ?>" />
            
            <label><i class="fas fa-file-alt"></i> Description:</label>
            <input type="text" id="edit-header" value="<?= htmlspecialchars($header) ?>" />
            
            <label><i class="fas fa-clock"></i> Operating Hours:</label>
            <input type="text" id="edit-operate" value="<?= htmlspecialchars($operate) ?>" />
            
            <label><i class="fas fa-map-marker-alt"></i> Location:</label>
            <input type="text" id="edit-location" value="<?= htmlspecialchars($location) ?>" />
            
            <label><i class="fas fa-phone"></i> Contact:</label>
            <input type="text" id="edit-contact" value="<?= htmlspecialchars($contact) ?>" />
            
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeDescModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn-save" onclick="saveDescChanges()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
      </div>
    </div>

    <!-- Service Modal -->
    <div id="serviceModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-image"></i>
            <span id="serviceModalTitle">Edit Service</span>
        </div>
        <div class="modal-body">
            <label><i class="fas fa-upload"></i> Select New Image:</label>
            <input type="file" accept="image/*" id="serviceImageInput" onchange="previewImage()" />
            
            <div class="preview-container">
                <p>Preview:</p>
                <img id="serviceImagePreview" src="placeholder1.jpg" />
            </div>
            
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeServiceModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn-save" onclick="saveServiceImage()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
      </div>
    </div>

    <!-- Announcement Modal -->
    <div id="announcementModal" class="modal">
      <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-bullhorn"></i>
            Edit Announcement
        </div>
        <div class="modal-body">
            <label><i class="fas fa-upload"></i> Select New Image:</label>
            <input type="file" accept="image/*" id="announcementImageInput" onchange="previewAnnouncementImage()" />
            
            <div class="preview-container">
                <p>Preview:</p>
                <img id="announcementImagePreview" src="<?= $announcement_img ?>" />
            </div>
            
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeAnnouncementModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn-save" onclick="saveAnnouncementImage()">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
      </div>
    </div>

<script>
function toggleMenu(id) {
  const menu = document.getElementById(id);
  const isVisible = menu.classList.contains('show');
  
  document.querySelectorAll('.submenu').forEach(submenu => {
      submenu.classList.remove('show');
  });
  
  if (!isVisible) {
      menu.classList.add('show');
  }
}

function openDescModal() {
  document.getElementById("descModal").style.display = "flex";
}
function closeDescModal() {
  document.getElementById("descModal").style.display = "none";
}

function saveDescChanges() {
  const title = document.getElementById("edit-title").value;
  const header = document.getElementById("edit-header").value;
  const operate = document.getElementById("edit-operate").value;
  const location = document.getElementById("edit-location").value;
  const contact = document.getElementById("edit-contact").value;

  fetch('update_homepage.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ title, header, operate, location, contact })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      document.getElementById("desc-title").innerText = title;
      document.getElementById("desc-header").innerText = header;
      document.getElementById("desc-operate").innerText = operate;
      document.getElementById("desc-location").innerText = location;
      document.getElementById("desc-contact").innerText = contact;
      closeDescModal();
      showToast("Description updated successfully!");
    } else {
      alert("Update failed: " + data.error);
    }
  })
  .catch(err => alert("Error updating description: " + err));
}

let currentService = null;
function openServiceModal(serviceNumber) {
  currentService = serviceNumber;
  document.getElementById('serviceModalTitle').innerText = 'Edit Service ' + serviceNumber;
  const currentImg = document.getElementById('preview-service' + serviceNumber).src;
  document.getElementById('serviceImagePreview').src = currentImg;
  document.getElementById('serviceModal').style.display = 'flex';
}
function closeServiceModal() {
  document.getElementById('serviceModal').style.display = 'none';
  document.getElementById('serviceImageInput').value = '';
}
function previewImage() {
  const input = document.getElementById('serviceImageInput');
  const preview = document.getElementById('serviceImagePreview');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function saveServiceImage() {
  const input = document.getElementById('serviceImageInput');
  if (!input.files[0]) {
    alert("Please select an image first.");
    return;
  }

  const formData = new FormData();
  formData.append('image', input.files[0]);
  formData.append('type', 'service');
  formData.append('service', currentService);

  fetch('update_image.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      document.getElementById('preview-service' + currentService).src = data.filePath + '?v=' + new Date().getTime();
      closeServiceModal();
      showToast("Service image updated successfully!");
    } else {
      alert("Error: " + data.error);
    }
  })
  .catch(err => alert("Upload failed: " + err));
}

function openAnnouncementModal() {
  const currentImg = document.getElementById('preview-announcement').src;
  document.getElementById('announcementImagePreview').src = currentImg;
  document.getElementById('announcementModal').style.display = 'flex';
}
function closeAnnouncementModal() {
  document.getElementById('announcementModal').style.display = 'none';
  document.getElementById('announcementImageInput').value = '';
}
function previewAnnouncementImage() {
  const input = document.getElementById('announcementImageInput');
  const preview = document.getElementById('announcementImagePreview');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      preview.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function saveAnnouncementImage() {
  const input = document.getElementById('announcementImageInput');
  if (!input.files[0]) {
    alert("Please select an image first.");
    return;
  }

  const formData = new FormData();
  formData.append('image', input.files[0]);
  formData.append('type', 'announcement');

  fetch('update_image.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      document.getElementById('preview-announcement').src = data.filePath + '?v=' + new Date().getTime();
      closeAnnouncementModal();
      showToast("Announcement image updated successfully!");
    } else {
      alert("Upload failed: " + data.error);
    }
  })
  .catch(err => {
    console.error(err);
    alert("Something went wrong while uploading the image.");
  });
}

function showToast(message) {
  const el = document.createElement('div');
  el.className = 'toast';
  el.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
  document.body.appendChild(el);
  setTimeout(()=> el.remove(), 2500);
}
</script>
</body>
</html>