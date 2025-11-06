<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modals Example</title>
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
<?php
// Ensure database connection is available
if (!isset($conn)) {
    require_once 'db.php';
}
?>
<header>
    <nav>
        <div class="logo">
            <img src="pictures/logo/autoteclogo.png" alt="Autotec Logo" style="height: 50px;">
        </div>
        <ul class="nav-links">
            <li><a href="<?= isset($_SESSION['user_id']) ? 'homepage.php' : 'index.php' ?>">Home</a></li>
            <li><a href="vehicleinfo.php" id="reserveNowLink">Reserve Now!</a></li>
            <li><a href="aboutus.php">About Us</a></li>
            <li><a href="contactus.php">Contact Us</a></li>
        </ul>
        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                // Fetch user profile picture
                $userProfilePic = 'pictures/default-avatar.png';
                
                // First, try to get from session (faster)
                if (isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture'])) {
                    $profilePath = 'uploads/profile/' . $_SESSION['profile_picture'];
                    if (file_exists($profilePath)) {
                        $userProfilePic = $profilePath;
                    }
                } 
                // If not in session, try database
                elseif (isset($conn) && $conn) {
                    $userId = $_SESSION['user_id'];
                    $profileQuery = "SELECT profile_picture FROM users WHERE UserID = ?";
                    $stmt = mysqli_prepare($conn, $profileQuery);
                    
                    if ($stmt) {
                        mysqli_stmt_bind_param($stmt, "i", $userId);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if ($row = mysqli_fetch_assoc($result)) {
                            if (!empty($row['profile_picture'])) {
                                $profilePath = 'uploads/profile/' . $row['profile_picture'];
                                if (file_exists($profilePath)) {
                                    $userProfilePic = $profilePath;
                                    // Update session for next time
                                    $_SESSION['profile_picture'] = $row['profile_picture'];
                                }
                            }
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
                ?>
                <div class="dropdown">
                    <button class="btn btn-profile">
                        <img src="<?php echo htmlspecialchars($userProfilePic); ?>" 
                             alt="Profile" 
                             class="header-profile-pic"
                             onerror="this.src='pictures/default-avatar.png'">
                        <?= htmlspecialchars($_SESSION['fname'] ?? $_SESSION['username'] ?? 'My Account') ?> ▼
                    </button>
                    <div class="dropdown-content">
                        <a href="profile.php">Profile</a>
                        <a href="user_reservation.php">Record</a>
                        <a href="process/logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <button id="loginBtn" class="btn btn-outline">Sign In</button>
                <button id="registerBtn" class="btn btn-primary">Register</button>
            <?php endif; ?>
        </div>
    </nav>
</header>

<!-- Login Modal -->
<div class="login-modal" id="loginModal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="user-icon">👤</span>
            <span>User Login</span>
            <button class="close" onclick="closeModal('loginModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="loginForm" action="process/check.php" method="POST">
                <div class="form-group">
                    <label for="email">Email / Username</label>
                    <input type="text" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-actions">
                    <button type="submit" class="login-btn">Login</button>
                    <a href="#" class="forgot-password">Forgot Password?</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Registration Modal -->
<div class="registration-modal" id="registrationModal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="user-icon">👤</span>
            <span>User Registration</span>
            <button class="close" onclick="closeModal('registrationModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="registrationForm" method="POST" action="process/registration.php" enctype="multipart/form-data">
                
                <!-- Profile Picture Upload Section -->
                <div class="profile-upload-section">
                    <div class="profile-preview-container">
                        <img id="profilePreview" src="pictures/default-avatar.png" alt="Profile Preview" class="profile-preview-img">
                        <div class="upload-overlay">
                            <label for="profilePicture" class="upload-label">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                                <span>Upload Photo</span>
                            </label>
                            <input type="file" id="profilePicture" name="profilePicture" accept="image/*" style="display: none;">
                        </div>
                    </div>
                    <p class="upload-hint">Click to upload profile picture (Max 5MB)</p>
                </div>

                <!-- Name and Username Row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="fullName" required>
                    </div>
                    <div class="form-group">
                        <label for="regUsername">Username</label>
                        <input type="text" id="regUsername" name="regUsername" required>
                    </div>
                </div>

                <!-- Email and Phone Row -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="regEmail">Email</label>
                        <input type="email" id="regEmail" name="regEmail" required>
                    </div>
                    <div class="form-group">
                        <label for="phoneNumber">Phone Number</label>
                        <input type="tel" id="phoneNumber" name="phoneNumber" required>
                    </div>
                </div>

                <!-- Address Row (Full Width) -->
                <div class="form-row full-width">
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" required>
                    </div>
                </div>

                <!-- Password Row -->
                <div class="password-row">
                    <div class="form-group">
                        <label for="regPassword">Password</label>
                        <input type="password" id="regPassword" name="regPassword" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required>
                    </div>
                </div>

                <!-- Terms and Conditions Checkbox -->
                <div class="form-row full-width">
                    <div class="form-group terms-group">
                        <label class="checkbox-container">
                            <input type="checkbox" id="termsCheckbox" name="termsAccepted" required>
                            <span class="custom-checkmark"></span>
                            <span class="terms-text">
                                I agree to the <a href="#" id="termsLink" class="terms-link">Terms and Conditions</a>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" id="registerSubmitBtn" class="registration-btn" disabled>Register</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="forgot-password-modal" id="forgotPasswordModal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="user-icon">🔐</span>
            <span>Forgot Password</span>
            <button class="close" onclick="closeModal('forgotPasswordModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="forgotPasswordForm" action="process/resetlink.php" method="POST">
                <p><strong>Note:</strong> A link will be sent to your email to reset your password.</p>
                <div class="form-group">
                    <label for="resetEmail">EmaiL</label>
                    <input type="email" id="resetEmail" name="email" required>
                </div>
                <input type="hidden" name="send" value="true">
                <div class="form-actions">
                    <button type="button" id="cancelResetBtn">Cancel</button>
                    <button type="submit">Send Link</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Terms and Conditions Modal -->
<div id="termsModal" class="terms-modal">
    <div class="terms-modal-content">
        <div class="terms-modal-header">
            <h2>Terms and Conditions</h2>
            <span class="terms-modal-close">&times;</span>
        </div>
        <div class="terms-modal-body">
            <div class="terms-content">
                <h3>1. Service Agreement</h3>
                <p>Welcome to Autotec Shaw Branch. By utilizing our automotive services, you agree to comply with and be bound by the following terms and conditions. Please review these terms carefully before using our services.</p>
                <p>These terms constitute a legally binding agreement between you (the customer) and Autotec Shaw Branch regarding the use of our automotive repair and maintenance services.</p>

                <h3>2. Services Provided</h3>
                <p>Autotec Shaw Branch provides the following automotive services:</p>
                <ul>
                    <li>Vehicle diagnostic and repair services</li>
                    <li>Routine maintenance and inspection</li>
                    <li>Parts replacement and installation</li>
                    <li>Emergency roadside assistance (where applicable)</li>
                    <li>Vehicle registration assistance</li>
                </ul>

                <h3>3. Customer Responsibilities</h3>
                <p>As a customer, you agree to:</p>
                <ul>
                    <li>Provide accurate information about your vehicle and its condition</li>
                    <li>Pay all fees and charges in accordance with our payment terms</li>
                    <li>Pick up your vehicle within the agreed timeframe</li>
                    <li>Comply with all safety regulations while on our premises</li>
                    <li>Maintain proper insurance coverage for your vehicle</li>
                </ul>

                <h3>4. Payment Terms</h3>
                <p>Payment for services rendered is due upon completion of work unless other arrangements have been made in writing. We accept cash, major credit cards, and approved financing options.</p>

                <h3>5. Privacy Policy</h3>
                <p>We respect your privacy and are committed to protecting your personal information. Customer information is kept confidential and used only for service-related purposes.</p>

                <h3>6. Contact Information</h3>
                <p>For questions regarding these terms or our services, please contact us:</p>
                <ul>
                    <li><strong>Phone:</strong> 286527257</li>
                    <li><strong>Email:</strong> autotec_mandaluyong@yahoo.com</li>
                    <li><strong>Facebook:</strong> AutotecShawPH</li>
                    <li><strong>Location:</strong> Shaw Branch, Mandaluyong</li>
                </ul>

                <div class="footer-highlight">
                    <p><strong>By using our services, you acknowledge that you have read, understood, and agree to be bound by these terms and conditions.</strong></p>
                </div>
            </div>
        </div>
        <div class="terms-modal-footer">
            <button class="terms-btn-close">Close</button>
        </div>
    </div>
</div>

<!-- Login Required Modal -->
<div id="loginRequiredModal" class="login-required-modal">
    <div class="login-required-content">
        <div class="login-required-icon">
            <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="10" stroke="#a4133c" stroke-width="2"/>
                <path d="M12 8V12" stroke="#a4133c" stroke-width="2" stroke-linecap="round"/>
                <circle cx="12" cy="16" r="1" fill="#a4133c"/>
            </svg>
        </div>
        <h2>Authentication Required</h2>
        <p>Please sign in or create an account to make a reservation.</p>
        <div class="login-required-actions">
            <button class="btn-cancel" onclick="closeLoginRequiredModal()">Cancel</button>
            <button class="btn-signin" onclick="redirectToLogin()">Sign In</button>
            <button class="btn-register-new" onclick="redirectToRegister()">Register</button>
        </div>
    </div>
</div>

<style>
/* Header Profile Picture */
.header-profile-pic {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 10px;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    vertical-align: middle;
}

.btn-profile {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Profile Picture Upload Section */
.profile-upload-section {
    text-align: center;
    margin-bottom: 25px;
    padding: 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 15px;
}

.profile-preview-container {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 0 auto 15px;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 8px 20px rgba(164, 19, 60, 0.2);
    transition: all 0.3s ease;
}

.profile-preview-container:hover {
    transform: scale(1.05);
    box-shadow: 0 12px 30px rgba(164, 19, 60, 0.3);
}

.profile-preview-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    background: white;
}

.upload-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(164, 19, 60, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    cursor: pointer;
}

.profile-preview-container:hover .upload-overlay {
    opacity: 1;
}

.upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: white;
    cursor: pointer;
    font-weight: 500;
    font-size: 14px;
}

.upload-hint {
    color: #666;
    font-size: 13px;
    margin: 0;
}

/* Terms and Conditions Styling */
.terms-group {
    margin: 15px 0;
}

.checkbox-container {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-size: 14px;
    color: #333;
    margin-top: 10px;
    user-select: none;
    position: relative;
}

.checkbox-container input[type="checkbox"] {
    opacity: 0;
    width: 0;
    height: 0;
    position: absolute;
}

.custom-checkmark {
    width: 18px;
    height: 18px;
    border: 2px solid #a4133c;
    border-radius: 4px;
    background-color: white;
    display: inline-block;
    position: relative;
    transition: background 0.2s ease;
}

.checkbox-container input[type="checkbox"]:checked + .custom-checkmark::after {
    content: "";
    position: absolute;
    left: 4px;
    top: 0px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
    background: transparent;
}

.checkbox-container input[type="checkbox"]:checked + .custom-checkmark {
    background-color: #a4133c;
    border-color: #a4133c;
}

.terms-text {
    font-size: 14px;
    color: #444;
}

.terms-text .terms-link {
    color: #a4133c;
    text-decoration: underline;
    font-weight: 500;
}

.terms-text .terms-link:hover {
    color: #ff5e7e;
}

.registration-btn:disabled {
    background-color: #ccc;
    cursor: not-allowed;
    opacity: 0.6;
}

.registration-btn:disabled:hover {
    background-color: #ccc;
}

/* Terms Modal Styles */
.terms-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
    animation: termsModalFadeIn 0.3s ease-out;
}

.terms-modal-content {
    background-color: #fff;
    margin: 2% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    animation: termsModalSlideIn 0.3s ease-out;
    overflow: hidden;
}

.terms-modal-header {
    background: linear-gradient(135deg, #a4133c 0%, #ff758f 100%);
    color: white;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.terms-modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.terms-modal-close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    line-height: 1;
}

.terms-modal-close:hover {
    color: #f0f0f0;
    transform: scale(1.1);
}

.terms-modal-body {
    padding: 30px;
    max-height: 60vh;
    overflow-y: auto;
}

.terms-content h3 {
    color: #333;
    margin: 25px 0 10px 0;
    font-size: 1.1rem;
    font-weight: 600;
    border-bottom: 2px solid #a4133c;
    padding-bottom: 5px;
}

.terms-content h3:first-child {
    margin-top: 0;
}

.terms-content p {
    color: #666;
    line-height: 1.6;
    margin-bottom: 15px;
    text-align: justify;
}

.terms-content ul {
    color: #666;
    line-height: 1.6;
    margin: 15px 0;
    padding-left: 20px;
}

.terms-content li {
    margin-bottom: 5px;
}

.footer-highlight {
    background-color: #f8f9ff;
    padding: 15px;
    border-radius: 6px;
    border-left: 4px solid #a4133c;
    margin: 20px 0;
}

.footer-highlight p {
    margin: 0;
    color: #333 !important;
}

.terms-modal-footer {
    background-color: #f8f9fa;
    padding: 20px 30px;
    text-align: right;
    border-top: 1px solid #eee;
}

.terms-btn-close {
    background: linear-gradient(135deg, #a4133c 0%, #ff758f 100%);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.terms-btn-close:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(234, 102, 131, 0.4);
}

@keyframes termsModalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes termsModalSlideIn {
    from { 
        opacity: 0;
        transform: translateY(-50px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}

.terms-modal-body::-webkit-scrollbar {
    width: 8px;
}

.terms-modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.terms-modal-body::-webkit-scrollbar-thumb {
    background: #ff758f;
    border-radius: 4px;
}

.terms-modal-body::-webkit-scrollbar-thumb:hover {
    background: #ff758f;
}

/* Login Required Modal Styles */
.login-required-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease-out;
    align-items: center;
    justify-content: center;
}

.login-required-content {
    background: white;
    border-radius: 16px;
    padding: 40px 30px;
    max-width: 450px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(164, 19, 60, 0.2);
    animation: slideUp 0.3s ease-out;
    position: relative;
}

.login-required-icon {
    margin: 0 auto 20px;
    width: 60px;
    height: 60px;
    animation: pulse 2s infinite;
}

.login-required-content h2 {
    color: #a4133c;
    font-size: 1.8rem;
    margin: 0 0 15px 0;
    font-weight: 600;
}

.login-required-content p {
    color: #666;
    font-size: 1rem;
    line-height: 1.6;
    margin: 0 0 30px 0;
}

.login-required-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.login-required-actions button {
    padding: 12px 28px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 110px;
}

.btn-cancel {
    background-color: #f0f0f0;
    color: #666;
}

.btn-cancel:hover {
    background-color: #e0e0e0;
    transform: translateY(-2px);
}

.btn-signin {
    background: linear-gradient(135deg, #a4133c 0%, #ff758f 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(164, 19, 60, 0.3);
}

.btn-signin:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(164, 19, 60, 0.4);
}

.btn-register-new {
    background: linear-gradient(135deg, #ff758f 0%, #ffb3c6 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(255, 117, 143, 0.3);
}

.btn-register-new:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 117, 143, 0.4);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Responsive */
@media (max-width: 768px) {
    .terms-modal-content {
        margin: 5% auto;
        width: 95%;
    }
    
    .terms-modal-header, .terms-modal-body, .terms-modal-footer {
        padding: 20px;
    }
    
    .terms-modal-body {
        max-height: 50vh;
    }
    
    .login-required-content {
        padding: 30px 20px;
    }
    
    .login-required-content h2 {
        font-size: 1.5rem;
    }
    
    .login-required-actions {
        flex-direction: column;
        gap: 10px;
    }
    
    .login-required-actions button {
        width: 100%;
    }
    
    .profile-preview-container {
        width: 120px;
        height: 120px;
    }
}
</style>

<script>
    const loginBtn = document.getElementById('loginBtn');
    const registerBtn = document.getElementById('registerBtn');
    const loginModal = document.getElementById('loginModal');
    const registrationModal = document.getElementById('registrationModal');
    const forgotPasswordModal = document.getElementById('forgotPasswordModal');
    const termsModal = document.getElementById('termsModal');
    const forgotPasswordLink = document.querySelector('.forgot-password');
    const cancelResetBtn = document.getElementById('cancelResetBtn');
    const loginForm = document.getElementById('loginForm');
    const registrationForm = document.getElementById('registrationForm');
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    const termsCheckbox = document.getElementById('termsCheckbox');
    const registerSubmitBtn = document.getElementById('registerSubmitBtn');
    const termsLink = document.getElementById('termsLink');
    const termsModalClose = document.querySelector('.terms-modal-close');
    const termsCloseBtn = document.querySelector('.terms-btn-close');

    // Profile Picture Preview
    const profilePictureInput = document.getElementById('profilePicture');
    const profilePreview = document.getElementById('profilePreview');

    if (profilePictureInput) {
        profilePictureInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    e.target.value = '';
                    return;
                }

                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert('Please select an image file');
                    e.target.value = '';
                    return;
                }

                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    function showLoginRequiredModal() {
        const modal = document.getElementById('loginRequiredModal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeLoginRequiredModal() {
        const modal = document.getElementById('loginRequiredModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    function redirectToLogin() {
        closeLoginRequiredModal();
        openModal('loginModal');
    }

    function redirectToRegister() {
        closeLoginRequiredModal();
        openModal('registrationModal');
    }

    function checkLoginForReservation(event) {
        <?php if (!isset($_SESSION['user_id'])): ?>
            event.preventDefault();
            showLoginRequiredModal();
            return false;
        <?php endif; ?>
        return true;
    }

    if (termsCheckbox && registerSubmitBtn) {
        termsCheckbox.addEventListener('change', function() {
            registerSubmitBtn.disabled = !this.checked;
        });
    }

    if (termsLink) {
        termsLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (termsModal) {
                termsModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    }

    function closeTermsModal() {
        if (termsModal) {
            termsModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    if (termsModalClose) {
        termsModalClose.addEventListener('click', closeTermsModal);
    }

    if (termsCloseBtn) {
        termsCloseBtn.addEventListener('click', closeTermsModal);
    }

    if (loginBtn) loginBtn.addEventListener('click', () => openModal('loginModal'));
    if (registerBtn) registerBtn.addEventListener('click', () => openModal('registrationModal'));

    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', function (e) {
            e.preventDefault();
            closeModal('loginModal');
            openModal('forgotPasswordModal');
        });
    }

    if (cancelResetBtn) {
        cancelResetBtn.addEventListener('click', function () {
            closeModal('forgotPasswordModal');
            openModal('loginModal');
        });
    }

    window.addEventListener('click', function (event) {
        if (event.target === loginModal) closeModal('loginModal');
        if (event.target === registrationModal) closeModal('registrationModal');
        if (event.target === forgotPasswordModal) closeModal('forgotPasswordModal');
        if (event.target === termsModal) {
            termsModal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        const loginRequiredModal = document.getElementById('loginRequiredModal');
        if (event.target === loginRequiredModal) {
            closeLoginRequiredModal();
        }
    });

    if (loginForm) {
        loginForm.addEventListener('submit', function (event) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            if (!email || !password) {
                event.preventDefault();
                alert('Please enter email and password.');
            }
        });
    }

    if (registrationForm) {
        registrationForm.addEventListener('submit', function (event) {
            const password = document.getElementById('regPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const termsAccepted = document.getElementById('termsCheckbox').checked;
            
            if (password !== confirmPassword) {
                event.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if (!termsAccepted) {
                event.preventDefault();
                alert('Please accept the Terms and Conditions to proceed.');
                return;
            }
        });
    }

    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', function () {
            closeModal('forgotPasswordModal');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const reserveLink = document.getElementById('reserveNowLink');
        if (reserveLink) {
            reserveLink.addEventListener('click', checkLoginForReservation);
        }
        
        const reserveButtons = document.querySelectorAll('.btn-book, .reserve-btn');
        reserveButtons.forEach(button => {
            button.addEventListener('click', checkLoginForReservation);
        });
    });
</script>
</body>
</html>