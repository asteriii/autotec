<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modals Example</title>
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
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
                <div class="dropdown">
                    <button class="btn btn-profile">
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
                <p><strong>Note:</strong> A link will be sent to your email/phone number to reset your password.</p>
                <div class="form-group">
                    <label for="resetEmail">Email / Mobile</label>
                    <input type="text" id="resetEmail" name="email" required>
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

<!-- ============ NEW: Login Required Modal ============ -->
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

    // ============ NEW: Login Required Modal Functions ============
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

    // ============ NEW: Check if user is logged in before accessing Reserve Now ============
    function checkLoginForReservation(event) {
        <?php if (!isset($_SESSION['user_id'])): ?>
            event.preventDefault();
            showLoginRequiredModal();
            return false;
        <?php endif; ?>
        return true;
    }

    // Terms checkbox functionality
    if (termsCheckbox && registerSubmitBtn) {
        termsCheckbox.addEventListener('change', function() {
            registerSubmitBtn.disabled = !this.checked;
        });
    }

    // Terms link functionality
    if (termsLink) {
        termsLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (termsModal) {
                termsModal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        });
    }

    // Terms modal close functionality
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
        // ============ NEW: Close login required modal when clicking outside ============
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

    // ============ NEW: Add event listener to Reserve Now link ============
    document.addEventListener('DOMContentLoaded', function() {
        // Check the Reserve Now link in navigation
        const reserveLink = document.getElementById('reserveNowLink');
        if (reserveLink) {
            reserveLink.addEventListener('click', checkLoginForReservation);
        }
        
        // Also check any buttons with these classes (if you have Book Now buttons elsewhere)
        const reserveButtons = document.querySelectorAll('.btn-book, .reserve-btn');
        reserveButtons.forEach(button => {
            button.addEventListener('click', checkLoginForReservation);
        });
    });
</script>
</body>
</html>