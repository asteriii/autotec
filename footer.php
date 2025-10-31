<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Footer with Terms Modal</title>
    <link rel="stylesheet" href="css/footer.css">
</head>
<body>
    <footer>
        <div class="footer-container">
            <div class="footer-top">
                <div class="footer-logo">
                    <h2>AUTOTEC</h2>
                    <p>AUTOMOTIVE TESTING CENTER</p>
                </div>
                
                <div class="footer-links">
                    
                    <div class="footer-section">
                        <h3>Links</h3>
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="vehicleinfo.php">Registration</a></li>
                            <li><a href="aboutus.php">About Us</a></li>
                            <li><a href="contactus.php">Contact</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h3>Support</h3>
                        <ul>
                            <li><a href="#" onclick="openTermsModal(); return false;">Terms and Conditions</a></li>
                            <li><a href="#">Contact: 286527257</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="copyright">
                    ©Copyright. All rights reserved.
                </div>
                
                <div class="social-links">
                    <a href="https://www.facebook.com/AutotecShawPH" class="social-link facebook" title="Facebook">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="mailto:autotecshaw@gmail.com" class="social-link gmail" title="Gmail">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L5.455 4.64 12 9.548l6.545-4.91 1.528-1.145C21.69 2.28 24 3.434 24 5.457z"/>
                        </svg>
                    </a>
                    <a href="mailto:autotec_mandaluyong@yahoo.com" class="social-link yahoo" title="Yahoo">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.383 0 0 5.383 0 12s5.383 12 12 12 12-5.383 12-12S18.617 0 12 0zm5.5 17.125c-.688.688-1.813.688-2.5 0l-3-3-3 3c-.688.688-1.813.688-2.5 0s-.688-1.813 0-2.5l3-3-3-3c-.688-.688-.688-1.813 0-2.5s1.813-.688 2.5 0l3 3 3-3c.688-.688 1.813-.688 2.5 0s.688 1.813 0 2.5l-3 3 3 3c.688.688.688 1.813 0 2.5z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Include Terms Modal -->
    <?php include 'terms-modal.php'; ?>

</body>
</html>