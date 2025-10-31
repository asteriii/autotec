<?php 
require_once 'db.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>About Us - AutoTEC</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f5f5f5;
      color: #333;
      line-height: 1.6;
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



    /* Content Grid */
    .content-grid {
      display: grid;
      gap: 30px;
      margin-top: 40px;
    }

    /* Branch Cards */
    .branch-card {
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 5px 20px rgba(0,0,0,0.08);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .branch-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }

    .branch-content {
      display: grid;
      grid-template-columns: 1fr 1fr;
      min-height: 400px;
    }

    .branch-content.reverse {
      direction: rtl;
    }

    .branch-content.reverse > * {
      direction: ltr;
    }

    .branch-image {
      position: relative;
      overflow: hidden;
    }

    .branch-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .branch-card:hover .branch-image img {
      transform: scale(1.05);
    }

    .branch-info {
      padding: 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
    }

    .branch-info h2 {
      color: #bd1e51;
      font-size: 28px;
      font-weight: 600;
      margin-bottom: 20px;
    }

    .branch-info p {
      color: #666;
      font-size: 16px;
      line-height: 1.7;
      margin-bottom: 25px;
    }

    .view-location-btn {
      background: linear-gradient(135deg, #bd1e51, #d63969);
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 25px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-block;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .view-location-btn:hover {
      background: linear-gradient(135deg, #a01a45, #bd1e51);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(189, 30, 81, 0.3);
    }

    .view-location-btn::after {
      content: '▼';
      margin-left: 8px;
      transition: transform 0.3s ease;
      display: inline-block;
    }

    .view-location-btn.active::after {
      transform: rotate(180deg);
    }

    /* Map Section */
    .map-section {
      background: white;
      border-radius: 0 0 15px 15px;
      padding: 0;
      margin-top: 0;
      box-shadow: none;
      max-height: 0;
      overflow: hidden;
      transition: all 0.4s ease;
      opacity: 0;
    }

    .map-section.show {
      max-height: 500px;
      padding: 30px;
      opacity: 1;
      border-top: 1px solid #eee;
    }

    .map-section h3 {
      color: #bd1e51;
      font-size: 20px;
      margin-bottom: 15px;
      text-align: center;
    }

    .map-container {
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }

    .map-container iframe {
      width: 100%;
      height: 350px;
      border: 0;
    }

    /* No Data Message */
    .no-data {
      background: white;
      border-radius: 15px;
      padding: 60px 40px;
      text-align: center;
      box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }

    .no-data h2 {
      color: #bd1e51;
      font-size: 24px;
      margin-bottom: 15px;
    }

    .no-data p {
      color: #666;
      font-size: 16px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {

      .branch-content {
        grid-template-columns: 1fr;
      }

      .branch-content.reverse {
        direction: ltr;
      }

      .branch-info {
        padding: 30px 25px;
      }

      .branch-info h2 {
        font-size: 24px;
      }

      .map-container iframe {
        height: 250px;
      }

      .map-section.show {
        max-height: 400px;
      }
    }

    @media (max-width: 480px) {
      .header-section {
        padding: 30px 20px;
      }

      .header-section h1 {
        font-size: 28px;
      }

      .branch-info {
        padding: 25px 20px;
      }

      .map-section {
        padding: 20px;
      }

      .map-section.show {
        max-height: 350px;
      }
    }
  </style>
</head>
<body>
 <?php include 'header.php'; ?>

    <div class="container">
        <!-- Hero Section -->
        <section class="hero">
            <h1><span class="highlight">ABOUT</span> US</h1>
            <p>So we may hear your concerns and for us to address them. We encourage you to address your concern to us
                so that we may improve our services!</p>
        </section>

  <!-- Content -->
  <div class="content-grid">
    <?php if (!empty($aboutEntries)): ?>
      <?php foreach ($aboutEntries as $index => $entry): ?>
        <div class="branch-card">
          <div class="branch-content <?php echo ($index % 2 == 1) ? 'reverse' : ''; ?>">
            <div class="branch-image">
              <?php if (!empty($entry['Picture'])): ?>
                <?php 
                  // Convert file system path to web path
                  $imagePath = str_replace('\\', '/', $entry['Picture']);
                  $imagePath = str_replace('C:/xampp/htdocs/autotec/', '', $imagePath);
                ?>
                <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                     alt="<?php echo htmlspecialchars($entry['BranchName']); ?>">
              <?php else: ?>
                <img src="pictures/branches/default-branch.jpg" alt="Default Branch Image">
              <?php endif; ?>
            </div>
            
            <div class="branch-info">
              <h2><?php echo htmlspecialchars($entry['BranchName']); ?></h2>
              <?php if (!empty($entry['Description'])): ?>
                <p><?php echo nl2br(htmlspecialchars($entry['Description'])); ?></p>
              <?php endif; ?>
              <?php if (!empty($entry['MapLink'])): ?>
                <button onclick="toggleMap('map-<?php echo $entry['AboutID']; ?>', this)" 
                        class="view-location-btn">
                  View Location
                </button>
              <?php endif; ?>
            </div>
          </div>
          
          <?php if (!empty($entry['MapLink'])): ?>
            <div class="map-section" id="map-<?php echo $entry['AboutID']; ?>">
              <h3><?php echo htmlspecialchars($entry['BranchName']); ?> Location</h3>
              <div class="map-container">
                <iframe
                  src="<?php echo htmlspecialchars($entry['MapLink']); ?>"
                  allowfullscreen="" 
                  loading="lazy" 
                  referrerpolicy="no-referrer-when-downgrade"
                  title="<?php echo htmlspecialchars($entry['BranchName']); ?> Location">
                </iframe>
              </div>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="no-data">
        <h2>Coming Soon</h2>
        <p>We're working on updating our branch information. Please check back soon or contact us directly for more details about our locations.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function toggleMap(mapId, button) {
  const mapSection = document.getElementById(mapId);
  const isVisible = mapSection.classList.contains('show');
  
  if (isVisible) {
    // Hide the map
    mapSection.classList.remove('show');
    button.textContent = 'View Location';
    button.classList.remove('active');
  } else {
    // Show the map
    mapSection.classList.add('show');
    button.textContent = 'Hide Location';
    button.classList.add('active');
  }
}
</script>

<?php include 'footer.php'; ?>

</body>
</html>