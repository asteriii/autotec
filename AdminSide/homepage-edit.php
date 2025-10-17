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

$service1_img = !empty($row['service1_img']) ? 'uploads/' . $row['service1_img'] : 'placeholder1.jpg';
$service2_img = !empty($row['service2_img']) ? 'uploads/' . $row['service2_img'] : 'placeholder2.jpg';
$service3_img = !empty($row['service3_img']) ? 'uploads/' . $row['service3_img'] : 'placeholder3.jpg';
$announcement_img = !empty($row['announcement_img']) ? 'uploads/' . $row['announcement_img'] : 'announcement.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Homepage</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet"/>
  <style>
    * {
      margin: 0; padding: 0; box-sizing: border-box;
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
    .sidebar .section { padding: 0 20px; }
    .sidebar .section-title {
      padding: 10px 0; cursor: pointer; font-weight: bold;
    }
    .sidebar .submenu {
      list-style: none;
      padding-left: 15px;
      display: none;
    }
    .sidebar .submenu li {
      padding: 8px 0; font-size: 14px; cursor: pointer;
    }
    .sidebar .submenu li:hover {
      text-decoration: underline;
    }
    .sidebar .active { background-color: #922b20; }
    .main { flex: 1; background-color: #f9f9f9; }
    .topbar {
      display: flex; justify-content: space-between;
      align-items: center; background: #fff;
      padding: 10px 20px; border-bottom: 1px solid #ccc;
    }
    .logout-btn {
      background-color: #e57373; color: white;
      border: none; padding: 8px 16px;
      border-radius: 4px; cursor: pointer;
    }
    .content { padding: 30px; }
    h2 { margin-bottom: 20px; }
    .section-box {
      background-color: white;
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 30px;
    }
    .section-box h3 { margin-bottom: 15px; }
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
    .service-card, .announcement-box {
      border: 1px solid #ccc;
      padding: 15px;
      border-radius: 6px;
      margin-bottom: 15px;
      background-color: #fefefe;
    }
    img.announcement-preview {
      max-width: 100%;
      margin-top: 10px;
      border: 1px solid #aaa;
    }
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
  width: 700px; /* ⬅️ Wider modal */
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

/* Limit image preview size inside modals */
.modal-content img.announcement-preview {
  max-width: 100%;
  max-height: 300px; /* Adjust as needed */
  object-fit: contain;
}

    .service-card img {
      width: 100%;
      max-width: 400px;
      border: 1px solid #aaa;
      margin-top: 10px;
    }
    
  </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
  <div class="section">
    <div class="section-title">
      <a href="admindash.php" style="color: white; text-decoration: none;">Dashboard</a>
    </div>
  </div>
  
  <div class="section">
    <div class="section-title" onclick="toggleMenu('admin-controls')">Admin Controls ▾</div>
    <ul class="submenu" id="admin-controls" style="display: block;">
      <li><a href="reservations.php" style="color: white; text-decoration: none;">Reservations</a></li>
      <li><a href="ongoing-list.php" style="color: white; text-decoration: none;">Ongoing List</a></li>
      <li><a href="completed-list.php" style="color: white; text-decoration: none;">Completed List</a></li>
    </ul>
  </div>
  
  <div class="section">
    <div class="section-title" onclick="toggleMenu('page-settings')">Page Settings ▾</div>
    <ul class="submenu" id="page-settings" style="display: block;">
      <li class="active"><a href="homepage-edit.php" style="color: white; text-decoration: none;">Home Page</a></li>
      <li><a href="reservation-edit.php" style="color: white; text-decoration: none;">Reservation Page</a></li>
      <li><a href="contact-edit.php" style="color: white; text-decoration: none;">Contact Page</a></li>
      <li><a href="about-edit.php" style="color: white; text-decoration: none;">About Page</a></li>
    </ul>
  </div>

  <div class="section">
    <div class="section-title" onclick="toggleMenu('activity-logs')">Activity Logs ▾</div>
    <ul class="submenu" id="activity-logs">
      <li>Page Edits</li>
      <li>Confirmed Logs</li>
      <li>Ongoing Logs</li>
    </ul>
  </div>
</div>


<!-- Main Content -->
<div class="main">
  <div class="topbar">
    <div class="logo">☰</div>
    <button class="logout-btn">Logout</button>
  </div>

  <div class="content">
    <h2>Edit Homepage</h2>

    <!-- Description -->
    <div class="section-box">
      <h3>Description</h3><br>
      <p><strong>Title:</strong> <span id="desc-title"><?= htmlspecialchars($title) ?></span></p><br>
      <p><strong>Description:</strong> <span id="desc-header"><?= htmlspecialchars($header) ?></span></p><br>
      <p><strong>Operating Hours:</strong> <span id="desc-operate"><?= htmlspecialchars($operate) ?></span></p><br>
      <p><strong>Location:</strong> <span id="desc-location"><?= htmlspecialchars($location) ?></span></p><br>
      <p><strong>Contact:</strong> <span id="desc-contact"><?= htmlspecialchars($contact) ?></span></p><br>
      <div class="button-row">
        <button class="btn" onclick="openDescModal()">Edit</button>
      </div>
    </div>

    <!-- Services -->
    <div class="section-box">
      <h3>Services</h3> 
      <div class="service-card">
        <p><strong>Service 1:</strong></p>
        <img id="preview-service1" src="<?php echo $service1_img . '?v=' . time(); ?>" alt="Service 1 Image" class="announcement-preview" />
        <div class="button-row"><button class="btn" onclick="openServiceModal(1)">Edit</button></div>
      </div>
      <div class="service-card">
        <p><strong>Service 2:</strong></p>
        <img id="preview-service2" src="<?php echo $service2_img . '?v=' . time(); ?>" alt="Service 2 Image" class="announcement-preview" />
        <div class="button-row"><button class="btn" onclick="openServiceModal(2)">Edit</button></div>
      </div>
      <div class="service-card">
        <p><strong>Service 3:</strong></p>
        <img id="preview-service3" src="<?php echo $service3_img . '?v=' . time(); ?>" alt="Service 3 Image" class="announcement-preview" />
        <div class="button-row"><button class="btn" onclick="openServiceModal(3)">Edit</button></div>
      </div>
    </div>

    <!-- Announcement -->
    <div class="section-box">
      <h3>Announcement / Promotions</h3>
      <div class="announcement-box">
        <img id="preview-announcement" src="<?= $announcement_img  . '?v=' . time(); ?>" alt="Announcement Preview" class="announcement-preview" />
      </div>
      <div class="button-row">
        <button class="btn" onclick="openAnnouncementModal()">Edit</button>
      </div>
    </div>
  </div>
</div>

<!-- Description Modal -->
<div id="descModal" class="modal">
  <div class="modal-content">
    <h3>Edit Description</h3>
    <label>Title:</label><input type="text" id="edit-title" value="<?= htmlspecialchars($title) ?>" />
    <label>Description:</label><input type="text" id="edit-header" value="<?= htmlspecialchars($header) ?>" />
    <label>Operating Hours:</label><input type="text" id="edit-operate" value="<?= htmlspecialchars($operate) ?>" />
    <label>Location:</label><input type="text" id="edit-location" value="<?= htmlspecialchars($location) ?>" />
    <label>Contact:</label><input type="text" id="edit-contact" value="<?= htmlspecialchars($contact) ?>" />
    <div class="button-row">
      <button class="btn" onclick="saveDescChanges()">Save Changes</button>
      <button class="btn btn-danger" onclick="closeDescModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- Service Modal -->
<div id="serviceModal" class="modal">
  <div class="modal-content">
    <h3 id="serviceModalTitle">Edit Service</h3>
    <label>Select New Image:</label>
    <input type="file" accept="image/*" id="serviceImageInput" onchange="previewImage()" />
    <p style="margin-top: 10px;">Preview:</p>
    <img id="serviceImagePreview" src="placeholder1.jpg" class="announcement-preview" />
    <div class="button-row">
      <button class="btn" onclick="saveServiceImage()">Save Changes</button>
      <button class="btn btn-danger" onclick="closeServiceModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- Announcement Modal -->
<div id="announcementModal" class="modal">
  <div class="modal-content">
    <h3>Edit Announcement</h3>
    <label>Select New Image:</label>
    <input type="file" accept="image/*" id="announcementImageInput" onchange="previewAnnouncementImage()" />
    <p style="margin-top: 10px;">Preview:</p>
    <img id="announcementImagePreview" src="<?= $announcement_img ?>" class="announcement-preview" />
    <div class="button-row">
      <button class="btn" onclick="saveAnnouncementImage()">Save Changes</button>
      <button class="btn btn-danger" onclick="closeAnnouncementModal()">Cancel</button>
    </div>
  </div>
</div>

<script>
function toggleMenu(id) {
  const menu = document.getElementById(id);
  const isVisible = menu.style.display === 'block';
  menu.style.display = isVisible ? 'none' : 'block';
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
      // Update the visible text on the page
      document.getElementById("desc-title").innerText = title;
      document.getElementById("desc-header").innerText = header;
      document.getElementById("desc-operate").innerText = operate;
      document.getElementById("desc-location").innerText = location;
      document.getElementById("desc-contact").innerText = contact;
      closeDescModal();
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
  formData.append('service', currentService);

  fetch('update_service_image.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      // Update the corresponding preview image
      document.getElementById('preview-service' + currentService).src = data.filePath;
      closeServiceModal();
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

  fetch('update_announcement_image.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      document.getElementById('preview-announcement').src = data.filePath + '?v=' + new Date().getTime();
      closeAnnouncementModal();
      showToast("Announcement image updated!");
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
  alert(message); // Replace with fancier toast if desired
}



</script>

</body>
</html>
