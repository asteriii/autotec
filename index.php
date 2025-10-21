<?php
// Database connection
require_once 'db.php'; 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoTEC - Automotive Testing Center</title>
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
   <?php include 'header.php'; ?>

    <div class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1><span class="highlight">Automotive</span> Testing <span class="highlight">Center</span></h1>
            <p>AutoTEC provides fast & reliable testing to ensure your vehicle meets the LTO standards in the
                Philippines.</p>

            <div class="hero-info">
                <div class="info-item">
                    <span class="info-label">Operating Hours:</span>
                    <span>Monday to Saturday, 8:00 AM to 5:00 PM</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Location:</span>
                    <span>Barangay Central, City Proper, Philippines</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Contact:</span>
                    <span>(02) 1234-5678 | autotec_mandaluyong@yahoo.com</span>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="services">
            <h2>Services</h2>
            <div class="service-content">
                <div class="service-text">
                    <h3>Emission Test:</h3>
                    <p>Comprehensive emission testing services to ensure your vehicle meets environmental standards. Our
                        state-of-the-art equipment provides accurate readings and fast results for both gasoline and
                        diesel vehicles.</p>
                </div>
                <div class="carousel-container">
                    <div class="carousel">
                        <div class="carousel-slide active">
                            <img src="https://images.unsplash.com/photo-1632767542181-a7b3e0b5d7b7?w=400&h=250&fit=crop"
                                alt="Emission testing equipment">
                        </div>
                        <div class="carousel-slide">
                            <img src="https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?w=400&h=250&fit=crop"
                                alt="Vehicle testing bay">
                        </div>
                        <div class="carousel-slide">
                            <img src="https://images.unsplash.com/photo-1609255993087-fb5fb5ef793e?w=400&h=250&fit=crop"
                                alt="Automotive diagnostic equipment">
                        </div>
                        <div class="carousel-slide">
                            <img src="https://images.unsplash.com/photo-1581244277943-fe4a9c777189?w=400&h=250&fit=crop"
                                alt="Testing facility">
                        </div>
                        <div class="carousel-slide">
                            <img src="https://images.unsplash.com/photo-1637760568905-cfe1c8f2c9d4?w=400&h=250&fit=crop"
                                alt="Vehicle inspection">
                        </div>
                        <div class="carousel-slide">
                            <img src="https://images.unsplash.com/photo-1615906655593-ad0386982651?w=400&h=250&fit=crop"
                                alt="Auto service center">
                        </div>
                    </div>
                    <div class="carousel-indicators">
                        <div class="indicator active" onclick="currentSlide(1)"></div>
                        <div class="indicator" onclick="currentSlide(2)"></div>
                        <div class="indicator" onclick="currentSlide(3)"></div>
                        <div class="indicator" onclick="currentSlide(4)"></div>
                        <div class="indicator" onclick="currentSlide(5)"></div>
                        <div class="indicator" onclick="currentSlide(6)"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Announcement Section -->
        <section class="announcement">
            <h2>Announcement</h2>
            <div class="announcement-content">
                <div class="announcement-logo">
                    <div
                        style="width: 120px; height: 120px; background: linear-gradient(45deg, #dc3545, #ff6b6b); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; text-align: center; font-weight: bold;">
                        Vehicle<br>Inspection<br>+<br>Emission<br>Testing<br>
                        <div
                            style="background: white; color: #dc3545; padding: 5px; margin-top: 5px; border-radius: 15px; font-size: 10px;">
                            AutoTEC</div>
                    </div>
                </div>
                <div class="announcement-text">
                    <div class="announcement-header">ANNOUNCEMENT</div>

                    <div class="operation-times">
                        OPERATION TIME:<br>
                        DECEMBER 19, 2024 (8:00AM – 12NN)<br>
                        DECEMBER 20, 2024 (8:00AM – 12NN)
                    </div>

                    <div class="closure-notice">
                        IN CELEBRATION OF CHRISTMAS HOLIDAYS, WE WILL BE<br>
                        CLOSED FROM DECEMBER 23, 2024 TO JANUARY 1, 2025
                    </div>

                    <div class="resume-notice">
                        NORMAL OPERATIONS WILL RESUME ON JANUARY 2, 2025
                    </div>

                    <div class="greeting">
                        MERRY CHRISTMAS AND A PROSPEROUS NEW YEAR!
                    </div>
                </div>
            </div>
        </section>
    </div>

    
    <?php include 'footer.php'; ?>

    <script>
        let slideIndex = 1;

document.addEventListener('DOMContentLoaded', function () {
    // Function to show the login modal
    function showLoginModal() {
        const loginModal = document.getElementById('loginModal');
        if (loginModal) {
            loginModal.style.display = 'flex';
        } else {
            console.error('Login modal element not found');
        }
    }

    // Function to close any modal
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Bind "Book Now" buttons to open the login modal
    const bookButtons = document.querySelectorAll('.btn-book');
    bookButtons.forEach(button => {
        button.addEventListener('click', function () {
            showLoginModal();
        });
    });

    // Close modal when clicking on the close button (x)
    const closeButtons = document.querySelectorAll('.close-modal');
    closeButtons.forEach(button => {
        button.addEventListener('click', function () {
            const modal = button.closest('.login-modal, .forgot-password-modal, .registration-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Close modal when clicking outside the modal content
    window.addEventListener('click', function (event) {
        const modals = document.querySelectorAll('.login-modal, .forgot-password-modal, .registration-modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Forgot password link inside login modal
    const forgotLink = document.querySelector('.forgot-password');
    if (forgotLink) {
        forgotLink.addEventListener('click', function (e) {
            e.preventDefault();
            closeModal('loginModal');
            const forgotModal = document.getElementById('forgotPasswordModal');
            if (forgotModal) {
                forgotModal.style.display = 'flex';
            }
        });
    }

    // "Create Account" link inside login modal (if present)
    const createAccountLink = document.querySelector('.create-account');
    if (createAccountLink) {
        createAccountLink.addEventListener('click', function (e) {
            e.preventDefault();
            closeModal('loginModal');
            const registerModal = document.getElementById('registrationModal');
            if (registerModal) {
                registerModal.style.display = 'flex';
            }
        });
    }

    // Optional: Cancel button inside Forgot Password modal
    const cancelBtn = document.querySelector('.cancel-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function () {
            closeModal('forgotPasswordModal');
        });
    }
});
        // Close modal with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                const loginModal = document.getElementById('loginModal');
                const registerModal = document.getElementById('registerModal');
                const forgotModal = document.getElementById('forgotPasswordModal');

                if (loginModal.classList.contains('show')) {
                    closeModal('loginModal');
                }
                if (registerModal.classList.contains('show')) {
                    closeModal('registerModal');
                }
                if (forgotModal.classList.contains('show')) {
                    closeModal('forgotPasswordModal');
                }
            }
        });

        // Carousel Functions
        function currentSlide(n) {
            showSlide(slideIndex = n);
        }

        function showSlide(n) {
            let slides = document.getElementsByClassName('carousel-slide');
            let indicators = document.getElementsByClassName('indicator');

            if (n > slides.length) { slideIndex = 1; }
            if (n < 1) { slideIndex = slides.length; }

            for (let i = 0; i < slides.length; i++) {
                slides[i].classList.remove('active');
            }

            for (let i = 0; i < indicators.length; i++) {
                indicators[i].classList.remove('active');
            }

            slides[slideIndex - 1].classList.add('active');
            indicators[slideIndex - 1].classList.add('active');
        }

        // Auto-advance carousel
        function autoSlide() {
            slideIndex++;
            if (slideIndex > document.getElementsByClassName('carousel-slide').length) {
                slideIndex = 1;
            }
            showSlide(slideIndex);
        }

        // Start auto-advance
        setInterval(autoSlide, 4000);

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
    </script>
</body>

</html>