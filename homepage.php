<?php 
// Check if session is not already started before calling session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Fetch homepage data
$sql = "SELECT * FROM homepage LIMIT 1";
$result = $conn->query($sql);
$homepage = $result->fetch_assoc() ?? [];

// Assign dynamic content (with fallbacks)
$title = $homepage['title'] ?? 'AutoTEC - Automotive Testing Center';
$header = $homepage['header'] ?? 'Automotive Testing Center';
$operate = $homepage['operate'] ?? 'Monday to Saturday, 8:00 AM to 5:00 PM';
$location = $homepage['location'] ?? 'Barangay Central, City Proper, Philippines';
$contact = $homepage['contact'] ?? '(02) 1234-5678 | autotec_mandaluyong@yahoo.com';

// Handle image paths (same logic as adminside)
$service1_img = !empty($homepage['service1_img']) ? 'AdminSide/uploads/homepage/' . $homepage['service1_img'] : 'AdminSide/uploads/homepage/placeholder1.jpg';
$service2_img = !empty($homepage['service2_img']) ? 'AdminSide/uploads/homepage/' . $homepage['service2_img'] : 'AdminSide/uploads/homepage/placeholder2.jpg';
$service3_img = !empty($homepage['service3_img']) ? 'AdminSide/uploads/homepage/' . $homepage['service3_img'] : 'AdminSide/uploads/homepage/placeholder3.jpg';
$announcement_img = !empty($homepage['announcement_img']) ? 'AdminSide/uploads/homepage/' . $homepage['announcement_img'] : 'AdminSide/uploads/homepage/announcement.png';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
   <?php include 'header.php'; ?>

    <div class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1><span class="highlight">Automotive</span> Testing <span class="highlight">Center</span></h1>
            <p><?= htmlspecialchars($header) ?></p>

            <div class="hero-info">
                <div class="info-item">
                    <span class="info-label">Operating Hours:</span>
                    <span><?= htmlspecialchars($operate) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Location:</span>
                    <span><?= htmlspecialchars($location) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Contact:</span>
                    <span><?= htmlspecialchars($contact) ?></span>
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

                <!-- Dynamic carousel -->
                <div class="carousel-container">
                    <div class="carousel">
                        <?php
                        $serviceImgs = [$service1_img, $service2_img, $service3_img];
                        $active = "active";

                        foreach ($serviceImgs as $img) {
                            echo "<div class='carousel-slide $active'>
                                    <img src='" . htmlspecialchars($img) . "' alt='Service Image'>
                                  </div>";
                            $active = "";
                        }
                        ?>
                    </div>

                    <div class="carousel-indicators">
                        <?php
                        $count = count(array_filter($serviceImgs));
                        for ($i = 1; $i <= $count; $i++) {
                            $activeClass = ($i == 1) ? "active" : "";
                            echo "<div class='indicator $activeClass' onclick='currentSlide($i)'></div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- Announcement Section -->
        <section class="announcement">
            <h2>Announcement</h2>
            <div class="announcement-content" style="text-align:center;">
                <img src="<?= htmlspecialchars($announcement_img) ?>" 
                     alt="Announcement" 
                     style="width:100%;max-width:700px;border-radius:10px;">
            </div>
        </section>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        let slideIndex = 1;

        function currentSlide(n) {
            showSlide(slideIndex = n);
        }

        function showSlide(n) {
            let slides = document.getElementsByClassName('carousel-slide');
            let indicators = document.getElementsByClassName('indicator');

            if (n > slides.length) { slideIndex = 1; }
            if (n < 1) { slideIndex = slides.length; }

            for (let i = 0; i < slides.length; i++) slides[i].classList.remove('active');
            for (let i = 0; i < indicators.length; i++) indicators[i].classList.remove('active');

            slides[slideIndex - 1].classList.add('active');
            indicators[slideIndex - 1].classList.add('active');
        }

        // Auto-advance carousel every 4 seconds
        setInterval(() => {
            slideIndex++;
            showSlide(slideIndex);
        }, 4000);
    </script>
</body>
</html>
