<?php
require_once '../db.php';

// Define upload directory - USE ABSOLUTE PATHS FOR RAILWAY
define('UPLOAD_DIR', '/var/www/html/uploads/branches/');
define('UPLOAD_DIR_RELATIVE', 'uploads/branches/');

// Debug logging
error_log("=== ABOUT-EDIT.PHP ===");
error_log("Upload directory: " . UPLOAD_DIR);
error_log("Upload dir exists: " . (file_exists(UPLOAD_DIR) ? 'YES' : 'NO'));
error_log("======================");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update') {
        $aboutID = $_POST['AboutID'];
        $branchName = $_POST['BranchName'];
        $mapLink = $_POST['MapLink'];
        $description = $_POST['Description'];
        
        // Create upload directory if it doesn't exist
        if (!file_exists(UPLOAD_DIR)) {
            if (!mkdir(UPLOAD_DIR, 0755, true)) {
                error_log("Failed to create directory: " . UPLOAD_DIR);
                $error_message = "Failed to create upload directory.";
            }
        }
        
        // Handle file upload
        $pictureFileName = null;
        if (isset($_FILES['Picture']) && $_FILES['Picture']['error'] == UPLOAD_ERR_OK) {
            
            $fileTmpPath = $_FILES['Picture']['tmp_name'];
            $fileName = $_FILES['Picture']['name'];
            $fileSize = $_FILES['Picture']['size'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));
            
            // Allowed extensions
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                if ($fileSize <= 5 * 1024 * 1024) { // 5MB max
                    // Validate it's actually an image
                    $imageInfo = @getimagesize($fileTmpPath);
                    if ($imageInfo !== false) {
                        // Generate unique filename
                        $newFileName = 'branch_' . $aboutID . '_' . time() . '.' . $fileExtension;
                        $destPath = UPLOAD_DIR . $newFileName;  // Absolute path
                        
                        // Get old picture to delete
                        $oldPictureSql = "SELECT Picture FROM about_us WHERE AboutID = ?";
                        $oldStmt = $pdo->prepare($oldPictureSql);
                        $oldStmt->execute([$aboutID]);
                        $oldPicture = $oldStmt->fetchColumn();
                        
                        // Move uploaded file
                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            @chmod($destPath, 0644);
                            $pictureFileName = UPLOAD_DIR_RELATIVE . $newFileName;  // Relative path for database
                            
                            // Delete old picture if exists (construct absolute path)
                            if ($oldPicture) {
                                $oldPictureAbsPath = '/var/www/html/' . $oldPicture;
                                if (file_exists($oldPictureAbsPath)) {
                                    @unlink($oldPictureAbsPath);
                                    error_log("Deleted old branch image: " . $oldPictureAbsPath);
                                }
                            }
                            
                            error_log("Branch image uploaded: " . $pictureFileName);
                        } else {
                            error_log("Failed to move uploaded file");
                            $error_message = "Failed to upload image.";
                        }
                    } else {
                        $error_message = "Invalid image file.";
                    }
                } else {
                    $error_message = "Image size must be less than 5MB.";
                }
            } else {
                $error_message = "Invalid file type. Only JPG, JPEG, PNG, and GIF allowed.";
            }
        }
        
        // Update database
        if (!isset($error_message)) {
            try {
                if ($pictureFileName) {
                    // Update with new picture
                    $sql = "UPDATE about_us SET BranchName = ?, Picture = ?, MapLink = ?, Description = ? WHERE AboutID = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$branchName, $pictureFileName, $mapLink, $description, $aboutID]);
                } else {
                    // Update without changing picture
                    $sql = "UPDATE about_us SET BranchName = ?, MapLink = ?, Description = ? WHERE AboutID = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$branchName, $mapLink, $description, $aboutID]);
                }
                
                $success_message = "Branch information updated successfully!";
                error_log("Branch updated successfully - AboutID: " . $aboutID);
            } catch(PDOException $e) {
                $error_message = "Error updating branch: " . $e->getMessage();
                error_log("Database error: " . $e->getMessage());
            }
        }
    }
}

// Fetch all branches
$sql = "SELECT * FROM about_us ORDER BY AboutID";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>About Page Edit - AutoTec Admin</title>
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

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #c0392b 0%, #a93226 100%);
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

        .active {
            background-color: rgba(255,255,255,0.15);
            font-weight: 500;
        }

        .main {
            flex: 1;
            background-color: #f8fafc;
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
            background: linear-gradient(135deg, #e74c3c, #c0392b);
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
        }

        .content h2 {
            margin-bottom: 30px;
            color: #2d3748;
            font-weight: 700;
            font-size: 28px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert.success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .branch-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .branch-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #c0392b, #a93226);
        }

        .branch-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .branch-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .branch-title {
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .branch-id {
            background: linear-gradient(135deg, #c0392b, #a93226);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .branch-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 25px;
        }

        .info-section h4 {
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .branch-image {
            width: 100%;
            max-width: 400px;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .branch-image:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .no-image {
            width: 100%;
            max-width: 400px;
            height: 200px;
            background: #f7fafc;
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #718096;
        }

        .map-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 2px solid #e2e8f0;
        }

        .map-container iframe {
            width: 100%;
            height: 200px;
            border: none;
        }

        .description-text {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #c0392b;
            color: #4a5568;
            line-height: 1.6;
            margin-top: 10px;
        }

        .edit-btn {
            background: linear-gradient(135deg, #3182ce, #2c5282);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(49, 130, 206, 0.3);
        }

        .edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(49, 130, 206, 0.4);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(5px);
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            width: 90%;
            max-width: 600px;
            border-radius: 16px;
            position: relative;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-50px) scale(0.9); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .modal-title {
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: #718096;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 5px;
            border-radius: 50%;
        }

        .close-btn:hover {
            background: #f7fafc;
            color: #c0392b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2d3748;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #c0392b;
            box-shadow: 0 0 0 3px rgba(192, 57, 43, 0.1);
        }

        .form-group textarea {
            height: 120px;
            resize: vertical;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #c0392b, #a93226);
            color: white;
            box-shadow: 0 2px 8px rgba(192, 57, 43, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(192, 57, 43, 0.4);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1000;
                height: 100vh;
            }
            .branch-content {
                grid-template-columns: 1fr;
            }
            .modal-content {
                width: 95%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
   <?php include 'sidebar.php'; ?>

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
            <h2><i class="fas fa-info-circle"></i> About Page Management</h2>

            <?php if (isset($success_message)): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php foreach ($branches as $branch): ?>
                <div class="branch-card">
                    <div class="branch-header">
                        <div class="branch-title">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($branch['BranchName']); ?>
                        </div>
                        <div class="branch-id">ID: <?php echo $branch['AboutID']; ?></div>
                    </div>
                    
                    <div class="branch-content">
                        <div class="info-section">
                            <h4><i class="fas fa-image"></i> Branch Image</h4>
                            <?php 
                            // Build the web-accessible path for display
                            $displayPath = '';
                            if (!empty($branch['Picture'])) {
                                // Picture should be like: uploads/branches/branch_1_123456.jpg
                                $displayPath = '../' . $branch['Picture'];
                            }
                            
                            // Check if file exists using absolute path
                            $absolutePath = '/var/www/html/' . $branch['Picture'];
                            $fileExists = !empty($branch['Picture']) && file_exists($absolutePath);
                            ?>
                            
                            <?php if ($fileExists): ?>
                                <img src="<?php echo htmlspecialchars($displayPath); ?>?v=<?php echo time(); ?>" 
                                     alt="Branch Image" 
                                     class="branch-image"
                                     onerror="this.parentElement.innerHTML='<div class=\'no-image\'><i class=\'fas fa-image\' style=\'font-size: 48px; opacity: 0.5;\'></i><p style=\'margin-top: 10px; font-size: 12px;\'>Image not found</p></div>'">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image" style="font-size: 48px; opacity: 0.5;"></i>
                                    <p style="margin-top: 10px; font-size: 12px;">No image uploaded</p>
                                </div>
                            <?php endif; ?>
                            
                            <h4 style="margin-top: 20px;"><i class="fas fa-align-left"></i> Description</h4>
                            <div class="description-text">
                                <?php echo nl2br(htmlspecialchars($branch['Description'])); ?>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <h4><i class="fas fa-map"></i> Branch Location</h4>
                            <div class="map-container">
                                <iframe 
                                    src="<?php echo htmlspecialchars($branch['MapLink']); ?>" 
                                    allowfullscreen="" 
                                    loading="lazy" 
                                    referrerpolicy="no-referrer-when-downgrade">
                                </iframe>
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: right;">
                        <button class="edit-btn" onclick="openEditModal(
                            <?php echo $branch['AboutID']; ?>,
                            '<?php echo htmlspecialchars($branch['BranchName'], ENT_QUOTES); ?>',
                            '<?php echo htmlspecialchars($branch['MapLink'], ENT_QUOTES); ?>',
                            '<?php echo htmlspecialchars($branch['Description'], ENT_QUOTES); ?>'
                        )">
                            <i class="fas fa-edit"></i> Edit Branch
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-edit"></i> Edit Branch Information</h3>
                <button type="button" class="close-btn" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="AboutID" id="editAboutID">

                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Branch Name</label>
                    <input type="text" name="BranchName" id="editBranchName" placeholder="Enter branch name" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-image"></i> Branch Image</label>
                    <input type="file" name="Picture" accept="image/*">
                    <small style="color: #718096; font-size: 12px;">Leave blank to keep current image (Max 5MB)</small>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-map"></i> Google Maps Embed Link</label>
                    <textarea name="MapLink" id="editMapLink" placeholder="Paste Google Maps embed link here..." required></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-align-left"></i> Branch Description</label>
                    <textarea name="Description" id="editDescription" placeholder="Enter branch description..." required></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
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

        function openEditModal(id, name, mapLink, description) {
            document.getElementById('editAboutID').value = id;
            document.getElementById('editBranchName').value = name;
            document.getElementById('editMapLink').value = mapLink;
            document.getElementById('editDescription').value = description;
            document.getElementById('editModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('show');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });

        document.getElementById('editForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });
    </script>
</body>
</html>