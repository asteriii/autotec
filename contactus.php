<?php 
// Check if session is not already started before calling session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - AutoTEC</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            line-height: 1.6;
        }

        /* Header */
        header {
            background-color: white;
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        nav {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 30px;
        }

        .nav-links a {
            text-decoration: none;
            color: #666;
            font-weight: 500;
        }

        .nav-links a:hover {
            color: #ffb3c1;
        }

        .auth-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-outline {
            background: none;
            color: #a4133c;
            border: 1px solid #a4133c;
        }

        .btn-primary {
            background-color: #a4133c;
            color: white;
        }

        .btn:hover {
            opacity: 0.8;
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #e8f4f8 0%, #d1e7dd 100%);
            padding: 40px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .hero h1 {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 20px;
        }

        .hero h1 .highlight {
            color: #a4133c;
        }

        .hero p {
            font-size: 1.1em;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Contact Section */
        .contact-section {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .contact-info {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            height: fit-content;
        }

        .contact-info h2 {
            color: #333;
            font-size: 1.8em;
            margin-bottom: 20px;
        }

        .info-item {
            margin-bottom: 25px;
        }

        .info-item h3 {
            color: #a4133c;
            font-size: 1.2em;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-item p {
            color: #666;
            margin-left: 30px;
            line-height: 1.5;
        }

        /* Contact Form */
        .contact-form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 8px;
        }

        .form-title .highlight {
            color: #a4133c;
        }

        .form-subtitle {
            color: #666;
            margin-bottom: 25px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #a4133c;
            box-shadow: 0 0 5px rgba(255, 0, 111, 0.3);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .submit-btn {
            background-color: #a4133c;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #a4133c;
        }

        .submit-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        /* Success Message */
        .success-message {
            background: #f8d7da;
            color: #a4133c;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
            border: 1px solid #ff758f;
        }

        /* Error Message */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
            border: 1px solid #f5c6cb;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            position: relative;
            animation: slideIn 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
            transition: color 0.3s;
        }

        .close:hover {
            color: #a4133c;
        }

        .modal h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .modal-form input {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .modal-form input:focus {
            outline: none;
            border-color: #a4133c;
            box-shadow: 0 0 5px rgba(255, 0, 111, 0.3);
        }

        .modal-btn {
            background-color: #a4133c;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .modal-btn:hover {
            background-color: #ffb3c1;
        }

        .modal-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        .modal-link {
            text-align: center;
            margin-top: 15px;
            color: #666;
        }

        .modal-link a {
            color: #a4133c;
            text-decoration: none;
            font-weight: bold;
        }

        .modal-link a:hover {
            text-decoration: underline;
        }

        /* Footer */
        footer {
            background-color: #666;
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer-section {
            margin-bottom: 20px;
        }

        .footer-section h3 {
            margin-bottom: 10px;
        }

        .footer-section p {
            margin-bottom: 5px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .hero h1 {
                font-size: 2em;
            }

            .contact-section {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .contact-info,
            .contact-form-container {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {

            .contact-info,
            .contact-form-container {
                padding: 15px;
            }

            .hero {
                padding: 20px;
            }

            .hero h1 {
                font-size: 1.8em;
            }
        }
    </style>
</head>

<body>
<?php include 'header.php'; ?>


    <div class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1><span class="highlight">Contact</span> Us</h1>
            <p>So we may hear your concerns and for us to address them. We encourage you to address your concern to us
                so that we may improve our services!</p>
        </section>

        <!-- Contact Section -->
        <section class="contact-section">
            <div class="contact-info">
                <h2>Our Address</h2>

                <div class="info-item">
                    <h3>Address</h3>
                    <p>AutoTEC Shaw<br>
                        Mandaluyong, Philippines</p>
                </div>

                <div class="info-item">
                    <h3>Call us</h3>
                    <p>286527257<br></p>
                </div>

            </div>

            <div class="contact-form-container">
                <div class="success-message" id="successMessage">
                     Your message has been delivered successfully!
                </div>

                <div class="error-message" id="errorMessage">
                     There was an error sending your message. Please try again.
                </div>

                <h2 class="form-title">Send us a <span class="highlight">message !</span></h2>
                <p class="form-subtitle">We'd love to hear from you. Send us a message and we'll respond as soon as
                    possible.</p>

                <form id="contactForm">
                    <div class="form-group">
                        <label for="name">Tell us your name:</label>
                        <div class="form-row">
                            <input type="text" id="firstName" name="firstName" placeholder="First Name" required>
                            <input type="text" id="lastName" name="lastName" placeholder="Last Name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Enter your Email:</label>
                            <input type="email" id="email" name="email" placeholder="Email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Enter your phone number:</label>
                            <input type="tel" id="phone" name="phone" placeholder="Phone Number">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="message">Your message to us:</label>
                        <textarea id="message" name="message" placeholder="Write us a message" required></textarea>
                    </div>

                    <button type="submit" class="submit-btn">Submit</button>
                </form>
            </div>
        </section>
    </div>

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
                        <label for="loginEmail">Email / Username</label>
                        <input type="text" id="loginEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="loginPassword">Password</label>
                        <input type="password" id="loginPassword" name="password" required>
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
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="fullName" required>
                    </div>
                    <div class="form-group">
                        <label for="regUsername">Username</label>
                        <input type="text" id="regUsername" name="regUsername" required>
                    </div>
                    <div class="form-group">
                        <label for="regEmail">Email</label>
                        <input type="email" id="regEmail" name="regEmail" required>
                    </div>
                    <div class="form-group">
                        <label for="phoneNumber">Phone Number</label>
                        <input type="tel" id="phoneNumber" name="phoneNumber" required>
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" required>
                    </div>
                    <div class="form-group">
                        <label for="regPassword">Password</label>
                        <input type="password" id="regPassword" name="regPassword" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="registration-btn">Register</button>
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

    <?php include 'footer.php'; ?>

    <script>
        // Get elements
        const loginBtn = document.getElementById('loginBtn');
        const registerBtn = document.getElementById('registerBtn');
        const loginModal = document.getElementById('loginModal');
        const registrationModal = document.getElementById('registrationModal');
        const forgotPasswordModal = document.getElementById('forgotPasswordModal');
        const forgotPasswordLink = document.querySelector('.forgot-password');
        const cancelResetBtn = document.getElementById('cancelResetBtn');
        const loginForm = document.getElementById('loginForm');
        const registrationForm = document.getElementById('registrationForm');
        const forgotPasswordForm = document.getElementById('forgotPasswordForm');

        // Modal functionality
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

        // Event listeners for buttons (only if they exist)
        if (loginBtn) {
            loginBtn.addEventListener('click', () => openModal('loginModal'));
        }
        if (registerBtn) {
            registerBtn.addEventListener('click', () => openModal('registrationModal'));
        }

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

        // Close modal when clicking outside
        window.addEventListener('click', function (event) {
            if (event.target === loginModal) closeModal('loginModal');
            if (event.target === registrationModal) closeModal('registrationModal');
            if (event.target === forgotPasswordModal) closeModal('forgotPasswordModal');
        });

        // Form validation
        if (loginForm) {
            loginForm.addEventListener('submit', function (event) {
                const email = document.getElementById('loginEmail').value;
                const password = document.getElementById('loginPassword').value;
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
                if (password !== confirmPassword) {
                    event.preventDefault();
                    alert('Passwords do not match!');
                    return;
                }
            });
        }

        if (forgotPasswordForm) {
            forgotPasswordForm.addEventListener('submit', function () {
                closeModal('forgotPasswordModal');
            });
        }

        // Contact Form handling with database integration
        document.getElementById('contactForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(this);
            const data = {
                firstName: formData.get('firstName'),
                lastName: formData.get('lastName'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                message: formData.get('message')
            };

            // Hide any previous messages
            document.getElementById('successMessage').style.display = 'none';
            document.getElementById('errorMessage').style.display = 'none';

            // Update button state
            const submitBtn = document.querySelector('.submit-btn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Sending...';
            submitBtn.disabled = true;

            try {
                // Send data to PHP backend
                const response = await fetch('submit_contact.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    // Show success message
                    document.getElementById('successMessage').textContent = result.message;
                    document.getElementById('successMessage').style.display = 'block';
                    
                    // Reset form
                    this.reset();
                    
                    // Scroll to success message
                    document.getElementById('successMessage').scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    // Hide success message after 5 seconds
                    setTimeout(() => {
                        document.getElementById('successMessage').style.display = 'none';
                    }, 5000);

                    console.log('Form submitted successfully:', result);
                } else {
                    // Show error message
                    document.getElementById('errorMessage').textContent = result.message;
                    document.getElementById('errorMessage').style.display = 'block';
                    
                    // Scroll to error message
                    document.getElementById('errorMessage').scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    // Hide error message after 5 seconds
                    setTimeout(() => {
                        document.getElementById('errorMessage').style.display = 'none';
                    }, 5000);
                }

            } catch (error) {
                console.error('Error:', error);
                
                // Show generic error message
                document.getElementById('errorMessage').textContent = 'Network error. Please check your connection and try again.';
                document.getElementById('errorMessage').style.display = 'block';
                
                // Scroll to error message
                document.getElementById('errorMessage').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                // Hide error message after 5 seconds
                setTimeout(() => {
                    document.getElementById('errorMessage').style.display = 'none';
                }, 5000);
            } finally {
                // Reset button state
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Add subtle hover effects to form inputs
        document.querySelectorAll('input, textarea').forEach(input => {
            input.addEventListener('focus', function () {
                this.style.transform = 'scale(1.01)';
                this.style.transition = 'transform 0.2s ease';
            });

            input.addEventListener('blur', function () {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>

</html>
