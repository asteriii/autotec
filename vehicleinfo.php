<?php
session_start();
// Database connection
require_once 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fetch branches from about_us table (now including QR codes)
$branches_query = "SELECT AboutID, BranchName, Description, MapLink, GCashQR FROM about_us ORDER BY BranchName";
$branches_result = $conn->query($branches_query);

// Store branch data in array for later use
$branches_data = [];
if ($branches_result->num_rows > 0) {
    $branches_result->data_seek(0); // Reset pointer
    while($row = $branches_result->fetch_assoc()) {
        $branches_data[] = $row;
    }
    $branches_result->data_seek(0); // Reset pointer again for the HTML loop
}

// Fetch vehicle types with their prices
$vehicle_types_query = "SELECT VehicleTypeID, Name, Price FROM vehicle_types ORDER BY Name";
$vehicle_types_result = $conn->query($vehicle_types_query);

// Fetch vehicle categories
$vehicle_categories_query = "SELECT CategoryID, Name FROM vehicle_categories ORDER BY Name";
$vehicle_categories_result = $conn->query($vehicle_categories_query);
?>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <title>Registration - AutoTEC</title>
    <link rel="stylesheet" href="css/registration.css">
    <style>
        /* Additional styles for branch selection */
        .branch-selection {
            display: grid;
            gap: 20px;
            margin-top: 20px;
        }
        
        .branch-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .branch-card:hover {
            border-color: #a4133c;
            box-shadow: 0 4px 12px rgba(164, 19, 60, 0.1);
        }
        
        .branch-card.selected {
            border-color: #a4133c;
            background: #fff5f7;
            box-shadow: 0 4px 12px rgba(164, 19, 60, 0.15);
        }
        
        .branch-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .branch-name {
            font-size: 18px;
            font-weight: bold;
            color: #a4133c;
        }
        
        .branch-radio {
            width: 20px;
            height: 20px;
            accent-color: #a4133c;
        }
        
        .branch-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .branch-map {
            width: 100%;
            height: 200px;
            border: none;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .map-toggle {
            background: #a4133c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 10px;
        }
        
        .map-toggle:hover {
            background: #8a1132;
        }

        /* Payment Options Styles */
        .payment-options {
            margin: 30px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
        }

        .payment-options h3 {
            color: #a4133c;
            margin-bottom: 20px;
        }

        .payment-method-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .payment-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            text-align: center;
        }

        .payment-card:hover {
            border-color: #a4133c;
            box-shadow: 0 4px 12px rgba(164, 19, 60, 0.1);
        }

        .payment-card.selected {
            border-color: #a4133c;
            background: #fff5f7;
            box-shadow: 0 4px 12px rgba(164, 19, 60, 0.15);
        }

        .payment-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .payment-title {
            font-size: 18px;
            font-weight: bold;
            color: #a4133c;
            margin-bottom: 5px;
        }

        .payment-description {
            font-size: 14px;
            color: #666;
        }

        .gcash-section {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            border: 2px solid #a4133c;
        }

        .gcash-section.active {
            display: block;
        }

        .qr-code-container {
            text-align: center;
            margin: 20px 0;
        }

        .qr-code-image {
            max-width: 300px;
            height: auto;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 10px;
            background: white;
        }

        .upload-section {
            margin-top: 20px;
        }

        .file-upload-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload-input {
            display: none;
        }

        .file-upload-label {
            display: block;
            padding: 15px;
            background: #a4133c;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            transition: background 0.3s;
        }

        .file-upload-label:hover {
            background: #8a1132;
        }

        .file-preview {
            margin-top: 15px;
            text-align: center;
        }

        .file-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
        }

        .file-name {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }

        .onsite-message {
            display: none;
            padding: 20px;
            background: #fff5f7;
            border-radius: 10px;
            border-left: 4px solid #a4133c;
            margin-top: 20px;
        }

        .onsite-message.active {
            display: block;
        }

        .onsite-message h4 {
            color: #a4133c;
            margin-bottom: 10px;
        }

        .onsite-message ul {
            margin-left: 20px;
            color: #666;
        }

        .onsite-message li {
            margin-bottom: 8px;
        }
        
        @media (min-width: 768px) {
            .branch-selection {
                grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            }
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="registration-header">
            <h1><span class="highlight">Regis</span>tration</h1>
        </div>

        <div class="progress-container">
            <div class="progress-steps">
                <div class="step">
                    <div class="step-number active" id="step1-number">1</div>
                    <div class="step-title active" id="step1-title">Branch Selection</div>
                </div>
                <div class="step-line" id="line1"></div>
                <div class="step">
                    <div class="step-number" id="step2-number">2</div>
                    <div class="step-title" id="step2-title">Vehicle Info</div>
                </div>
                <div class="step-line" id="line2"></div>
                <div class="step">
                    <div class="step-number" id="step3-number">3</div>
                    <div class="step-title" id="step3-title">Owner Details</div>
                </div>
                <div class="step-line" id="line3"></div>
                <div class="step">
                    <div class="step-number" id="step4-number">4</div>
                    <div class="step-title" id="step4-title">Schedule</div>
                </div>
                <div class="step-line" id="line4"></div>
                <div class="step">
                    <div class="step-number" id="step5-number">5</div>
                    <div class="step-title" id="step5-title">Confirmation</div>
                </div>
            </div>
        </div>

        <div class="form-container">
            <!-- Step 1: Branch Selection -->
            <div class="form-step active" id="step1">
                <h2>Select Your Preferred Branch</h2>
                <p>Choose the AutoTEC branch where you want to schedule your emission test</p>

                <div class="branch-selection">
                    <?php
                    if ($branches_result->num_rows > 0) {
                        while($branch = $branches_result->fetch_assoc()) {
                            $gcashQR = $branch['GCashQR'] ?? '';
                            echo '<div class="branch-card" onclick="selectBranch(this, ' . $branch['AboutID'] . ', \'' . htmlspecialchars($branch['BranchName']) . '\', \'' . htmlspecialchars($gcashQR) . '\')">';
                            echo '<div class="branch-card-header">';
                            echo '<div class="branch-name">' . htmlspecialchars($branch['BranchName']) . '</div>';
                            echo '<input type="radio" name="selectedBranch" value="' . $branch['AboutID'] . '" class="branch-radio">';
                            echo '</div>';
                            echo '<div class="branch-description">' . htmlspecialchars($branch['Description']) . '</div>';
                            
                            if (!empty($branch['MapLink'])) {
                                echo '<button type="button" class="map-toggle" onclick="toggleMap(event, this, \'' . htmlspecialchars($branch['MapLink']) . '\')">View Location</button>';
                                echo '<div class="map-container" style="display: none;">';
                                echo '<iframe class="branch-map" src="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                    } else {
                        echo '<p>No branches available at the moment.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Step 2: Vehicle Information -->
            <div class="form-step" id="step2">
                <h2>Vehicle Information</h2>
                <p>Please provide your vehicle details as they appear in your OR/CR</p>

                <div class="warning-box">
                    <span class="warning-icon">⚠️</span>
                    <span class="warning-text">Testing Requirements</span><br>
                    <span style="color: #666; font-size: 14px;">Vehicle must be physically presented for testing.</span>
                </div>

                <h3 style="color: #a4133c; margin-bottom: 20px;">Vehicle Details</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="plateNumber">Plate Number</label>
                        <input type="text" id="plateNumber" name="plateNumber" placeholder="ABC-1234" required>
                    </div>
                    <div class="form-group">
                        <label for="vehicleType">Vehicle Type</label>
                        <select id="vehicleType" name="vehicleType" required onchange="updateVehicleTypePrice()">
                            <option value="">Select Vehicle Type</option>
                            <?php
                            if ($vehicle_types_result->num_rows > 0) {
                                while($row = $vehicle_types_result->fetch_assoc()) {
                                    $price = number_format($row['Price'], 0);
                                    echo "<option value='{$row['Name']}' data-price='{$row['Price']}'>{$row['Name']} - ₱{$price}</option>";
                                }
                            }
                            ?>
                        </select>
                        <small id="priceDisplay" style="color: #a4133c; font-weight: bold; margin-top: 5px; display: block;"></small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" id="brand" name="brand" placeholder="Toyota" required>
                    </div>
                    <div class="form-group">
                        <label for="vehicleCategory">Vehicle Category</label>
                        <select id="vehicleCategory" name="vehicleCategory" required>
                            <option value="">Select Category</option>
                            <?php
                            if ($vehicle_categories_result->num_rows > 0) {
                                while($row = $vehicle_categories_result->fetch_assoc()) {
                                    echo "<option value='{$row['Name']}'>{$row['Name']}</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Step 3: Owner Details -->
            <div class="form-step" id="step3">
                <h2>Owner Details</h2>
                <p>Please provide the vehicle owner's information</p>

                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="firstName" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="lastName" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="middleName">Middle Name</label>
                        <input type="text" id="middleName" name="middleName">
                    </div>
                    <div class="form-group">
                        <label for="contactNumber">Contact Number</label>
                        <input type="tel" id="contactNumber" name="contactNumber" placeholder="09XX-XXX-XXXX" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="your.email@example.com" required>
                </div>

                <div class="form-group">
                    <label for="address">Complete Address</label>
                    <input type="text" id="address" name="address" placeholder="Street, Barangay, City, Province" required>
                </div>
            </div>

            <!-- Step 4: Schedule -->
            <div class="form-step" id="step4">
                <h2>Schedule</h2>
                <p>Select your preferred date and time for the emission test</p>

                <div class="schedule-grid">
                    <div class="calendar-section">
                        <h3>Select Date</h3>
                        <div class="calendar-nav">
                            <button class="nav-btn" onclick="changeMonth(-1)">‹</button>
                            <span id="currentMonth" class="month-year"></span>
                            <button class="nav-btn" onclick="changeMonth(1)">›</button>
                        </div>
                        <div class="calendar" id="calendar">
                            <div class="calendar-header">Sun</div>
                            <div class="calendar-header">Mon</div>
                            <div class="calendar-header">Tue</div>
                            <div class="calendar-header">Wed</div>
                            <div class="calendar-header">Thu</div>
                            <div class="calendar-header">Fri</div>
                            <div class="calendar-header">Sat</div>
                        </div>
                    </div>

                    <div class="time-section">
                        <h3>Select Time</h3>
                        <div class="time-category">
                            <h4>Morning</h4>
                            <div class="time-slots morning-slots">
                                <div class="time-slot" onclick="selectTimeSlot(this, '09:00:00')">9:00 AM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '09:20:00')">9:20 AM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '09:40:00')">9:40 AM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '10:00:00')">10:00 AM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '10:20:00')">10:20 AM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '10:40:00')">10:40 AM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '11:00:00')">11:00 AM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '11:20:00')">11:20 AM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '11:40:00')">11:40 AM</div>
                            </div>
                        </div>
                        
                        <div class="lunch-break">
                            <span>Lunch Break: 12:00 PM – 1:00 PM</span>
                        </div>
                        
                        <div class="time-category">
                            <h4>Afternoon</h4>
                            <div class="time-slots afternoon-slots">
                                <div class="time-slot" onclick="selectTimeSlot(this, '13:00:00')">1:00 PM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '13:20:00')">1:20 PM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '13:40:00')">1:40 PM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '14:00:00')">2:00 PM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '14:20:00')">2:20 PM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '14:40:00')">2:40 PM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '15:00:00')">3:00 PM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '15:20:00')">3:20 PM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '15:40:00')">3:40 PM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '16:00:00')">4:00 PM</div>
                                <div class="time-slot" onclick="selectTimeSlot(this, '16:20:00')">4:20 PM</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 5: Confirmation & Payment -->
            <div class="form-step" id="step5">
                <h2>Confirmation & Payment</h2>
                <p>Please review your registration details and choose your payment method</p>

                <div class="confirmation-summary">
                    <div class="summary-item">
                        <span class="summary-label">Branch:</span>
                        <span class="summary-value" id="summary-branch">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Vehicle:</span>
                        <span class="summary-value" id="summary-vehicle">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Plate Number:</span>
                        <span class="summary-value" id="summary-plate">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Owner:</span>
                        <span class="summary-value" id="summary-owner">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Contact:</span>
                        <span class="summary-value" id="summary-contact">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Schedule:</span>
                        <span class="summary-value" id="summary-schedule">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Amount:</span>
                        <span class="summary-value" id="summary-amount" style="font-weight: bold; color: #a4133c;">-</span>
                    </div>
                </div>

                <!-- Payment Options -->
                <div class="payment-options">
                    <h3>Choose Payment Method</h3>
                    <div class="payment-method-cards">
                        <div class="payment-card" onclick="selectPaymentMethod('gcash')">
                            <div class="payment-icon">💳</div>
                            <div class="payment-title">GCash Payment</div>
                            <div class="payment-description">Pay now via GCash and upload receipt</div>
                        </div>
                        <div class="payment-card" onclick="selectPaymentMethod('onsite')">
                            <div class="payment-icon">🏢</div>
                            <div class="payment-title">Pay On-Site</div>
                            <div class="payment-description">Pay at the testing center</div>
                        </div>
                    </div>

                    <!-- GCash Payment Section -->
                    <div class="gcash-section" id="gcashSection">
                        <h4 style="color: #a4133c; margin-bottom: 15px;">Scan QR Code to Pay</h4>
                        <div class="qr-code-container">
                            <img src="" alt="GCash QR Code" class="qr-code-image" id="qrCodeImage">
                            <p style="margin-top: 10px; color: #666;">Scan this QR code with your GCash app</p>
                        </div>
                        
                        <div class="upload-section">
                            <h4 style="color: #a4133c; margin-bottom: 10px;">Upload Payment Receipt</h4>
                            <p style="color: #666; margin-bottom: 15px;">Please upload a screenshot of your payment confirmation</p>
                            
                            <!-- Add file size and format info -->
                            <div style="background: #fff3e0; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                                <small style="color: #666;">
                                    ✓ Accepted formats: JPG, PNG, GIF, WebP<br>
                                    ✓ Maximum file size: 5MB<br>
                                    ✓ Make sure your payment details are clearly visible
                                </small>
                            </div>
                            
                            <div class="file-upload-wrapper">
                                <input type="file" 
                                    id="paymentReceipt" 
                                    name="paymentReceipt"
                                    class="file-upload-input" 
                                    accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" 
                                    onchange="previewReceipt(this)"
                                    required>
                                <label for="paymentReceipt" class="file-upload-label">
                                    📤 Choose File to Upload
                                </label>
                            </div>
                            <div class="file-preview" id="receiptPreview"></div>
                        </div>
                    </div>

                    <!-- On-Site Payment Message -->
                    <div class="onsite-message" id="onsiteMessage">
                        <h4>📋 On-Site Payment Instructions</h4>
                        <p>You have chosen to pay at the testing center. Please note:</p>
                        <ul>
                            <li>Payment must be made before your scheduled appointment time</li>
                            <li>Bring valid ID and vehicle documents</li>
                            <li>Your appointment receipt will be available in your user profile</li>
                            <li>You can download and print your receipt from your profile dashboard</li>
                        </ul>
                        <div style="margin-top: 15px; padding: 10px; background: #fff; border-radius: 5px; border: 1px solid #a4133c;">
                            <strong style="color: #a4133c;">💡 Tip:</strong> Download your receipt from your profile before visiting to speed up the check-in process.
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-navigation">
                <button class="nav-button prev" onclick="previousStep()" id="prevBtn" style="visibility: hidden;">Previous</button>
                <button class="nav-button next" onclick="nextStep()" id="nextBtn">Continue to Vehicle Info →</button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <h2>Registration Successful!</h2>
        <p>Thank you for registering! Your appointment has been confirmed.</p>
        <p><strong>Reference Number:</strong> <span id="referenceNumber"></span></p>
        <div id="paymentSuccessMessage"></div>
        <button class="btn" onclick="closeModal()">Close</button>
    </div>
</div>

    <!-- Error Modal -->
    <div id="errorModal" class="error-modal">
        <div class="error-modal-content">
            <h2>Registration Failed</h2>
            <p id="errorMessage">An error occurred while processing your registration.</p>
            <button class="btn" onclick="closeErrorModal()">Close</button>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        let currentStep = 1;
        const totalSteps = 5;
        let selectedDate = null;
        let selectedTime = null;
        let selectedBranchId = null;
        let selectedBranchName = null;
        let selectedBranchQR = null;
        let selectedPaymentMethod = null;
        let paymentReceiptFile = null;
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();

        // Initialize calendar when page loads
        document.addEventListener('DOMContentLoaded', function () {
            initializeCalendar();
        });

        // Branch selection function
        function selectBranch(element, branchId, branchName, gcashQR) {
            // Remove previous selection
            const previousSelected = document.querySelector('.branch-card.selected');
            if (previousSelected) {
                previousSelected.classList.remove('selected');
                const previousRadio = previousSelected.querySelector('.branch-radio');
                if (previousRadio) previousRadio.checked = false;
            }
            
            // Add selection to clicked branch
            element.classList.add('selected');
            const radio = element.querySelector('.branch-radio');
            if (radio) radio.checked = true;
            
            selectedBranchId = branchId;
            selectedBranchName = branchName;
            selectedBranchQR = gcashQR;
        }

        // Toggle map display
        function toggleMap(event, button, mapLink) {
            event.stopPropagation();
            
            const mapContainer = button.nextElementSibling;
            const iframe = mapContainer.querySelector('iframe');
            
            if (mapContainer.style.display === 'none') {
                mapContainer.style.display = 'block';
                iframe.src = mapLink;
                button.textContent = 'Hide Location';
            } else {
                mapContainer.style.display = 'none';
                iframe.src = '';
                button.textContent = 'View Location';
            }
        }

        // Payment method selection
        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;
            
            // Remove previous selection
            document.querySelectorAll('.payment-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selection to clicked card
            event.currentTarget.classList.add('selected');
            
            // Show/hide relevant sections
            const gcashSection = document.getElementById('gcashSection');
            const onsiteMessage = document.getElementById('onsiteMessage');
            
            if (method === 'gcash') {
                gcashSection.classList.add('active');
                onsiteMessage.classList.remove('active');
                
                // Load QR code
                if (selectedBranchQR) {
                    document.getElementById('qrCodeImage').src = selectedBranchQR;
                }
            } else {
                gcashSection.classList.remove('active');
                onsiteMessage.classList.add('active');
            }
        }

        // Preview payment receipt
        // Replace your existing previewReceipt function
        function previewReceipt(input) {
            const preview = document.getElementById('receiptPreview');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file size (5MB = 5 * 1024 * 1024 bytes)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('File size exceeds 5MB. Please choose a smaller file.');
                    input.value = ''; // Clear the input
                    preview.innerHTML = '';
                    paymentReceiptFile = null;
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type.toLowerCase())) {
                    alert('Invalid file type. Please upload an image file (JPG, PNG, GIF, or WebP).');
                    input.value = ''; // Clear the input
                    preview.innerHTML = '';
                    paymentReceiptFile = null;
                    return;
                }
                
                paymentReceiptFile = file;
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const fileSizeKB = (file.size / 1024).toFixed(2);
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Payment Receipt" style="max-width: 100%; max-height: 300px; border-radius: 10px; border: 2px solid #e0e0e0;">
                        <div class="file-name" style="margin-top: 10px; color: #4caf50;">
                            ✓ ${file.name} (${fileSizeKB} KB)
                        </div>
                    `;
                };
                
                reader.onerror = function() {
                    alert('Error reading file. Please try again.');
                    input.value = '';
                    preview.innerHTML = '';
                    paymentReceiptFile = null;
                };
                
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
                paymentReceiptFile = null;
            }
        }

        // Update price display when vehicle type changes
        function updateVehicleTypePrice() {
            const vehicleTypeSelect = document.getElementById('vehicleType');
            const priceDisplay = document.getElementById('priceDisplay');
            const selectedOption = vehicleTypeSelect.options[vehicleTypeSelect.selectedIndex];
            
            if (selectedOption && selectedOption.dataset.price) {
                const price = parseInt(selectedOption.dataset.price);
                priceDisplay.textContent = `Testing Fee: ₱${price.toLocaleString()}.00`;
                priceDisplay.style.display = 'block';
            } else {
                priceDisplay.style.display = 'none';
            }
        }

        // Initialize calendar
        function initializeCalendar() {
            updateCalendar();
        }

        function updateCalendar() {
            const calendar = document.getElementById('calendar');
            const today = new Date();
            
            // Clear existing calendar days (keep headers)
            const calendarDays = calendar.querySelectorAll('.calendar-day');
            calendarDays.forEach(day => day.remove());
            
            // Update month/year display
            const monthNames = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"];
            document.getElementById('currentMonth').textContent = `${monthNames[currentMonth]} ${currentYear}`;
            
            // Generate calendar days
            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            
            // Add empty cells for days before month start
            for (let i = 0; i < firstDay; i++) {
                const emptyDay = document.createElement('div');
                emptyDay.className = 'calendar-day unavailable';
                calendar.appendChild(emptyDay);
            }
            
            // Add days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = document.createElement('div');
                dayElement.className = 'calendar-day';
                dayElement.textContent = day;
                
                const dayDate = new Date(currentYear, currentMonth, day);
                
                // Disable past dates and weekends
                if (dayDate < today.setHours(0, 0, 0, 0) || dayDate.getDay() === 0 || dayDate.getDay() === 6) {
                    dayElement.classList.add('unavailable');
                } else {
                    dayElement.classList.add('available');
                    dayElement.onclick = function() {
                        selectDate(dayDate, dayElement);
                    };
                }
                
                calendar.appendChild(dayElement);
            }
        }

        function changeMonth(direction) {
            currentMonth += direction;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            } else if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            updateCalendar();
        }

        function selectDate(date, element) {
            // Remove previous selection
            const previousSelected = document.querySelector('.calendar-day.selected');
            if (previousSelected) {
                previousSelected.classList.remove('selected');
            }
            
            // Add selection to clicked date
            element.classList.add('selected');
            selectedDate = date;
            
            // Enable time slots
            const timeSlots = document.querySelectorAll('.time-slot');
            timeSlots.forEach(slot => {
                slot.classList.remove('unavailable');
            });
        }

        function selectTimeSlot(element, time) {
            // Remove previous selection
            const previousSelected = document.querySelector('.time-slot.selected');
            if (previousSelected) {
                previousSelected.classList.remove('selected');
            }
            
            // Add selection to clicked time slot
            element.classList.add('selected');
            selectedTime = time;
        }

        function nextStep() {
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateStepDisplay();
                    updateFormDisplay();
                    updateNavigationButtons();
                    
                    // Update summary if on confirmation step
                    if (currentStep === 5) {
                        updateSummary();
                    }
                }
            }
        }

        function previousStep() {
            if (currentStep > 1) {
                currentStep--;
                updateStepDisplay();
                updateFormDisplay();
                updateNavigationButtons();
            }
        }

        function validateCurrentStep() {
            switch (currentStep) {
                case 1:
                    return validateBranchSelection();
                case 2:
                    return validateVehicleInfo();
                case 3:
                    return validateOwnerDetails();
                case 4:
                    return validateSchedule();
                case 5:
                    return validatePaymentMethod();
                default:
                    return true;
            }
        }

        function validateBranchSelection() {
            if (!selectedBranchId) {
                alert('Please select a branch for your appointment.');
                return false;
            }
            return true;
        }

        function validateVehicleInfo() {
            const plateNumber = document.getElementById('plateNumber').value.trim();
            const vehicleType = document.getElementById('vehicleType').value;
            const brand = document.getElementById('brand').value.trim();
            const vehicleCategory = document.getElementById('vehicleCategory').value;
            
            if (!plateNumber || !vehicleType || !brand || !vehicleCategory) {
                alert('Please fill in all required vehicle information fields.');
                return false;
            }
            
            // Basic plate number validation (Philippine format)
            const platePattern = /^[A-Z]{1,3}[-\s]?\d{3,4}$/i;
            if (!platePattern.test(plateNumber)) {
                alert('Please enter a valid plate number format (e.g., ABC-1234).');
                return false;
            }
            
            return true;
        }

        function validateOwnerDetails() {
            const step3Form = document.getElementById('step3');
            const firstName = step3Form.querySelector('#firstName');
            const lastName = step3Form.querySelector('#lastName');
            const contactNumber = step3Form.querySelector('#contactNumber');
            const email = step3Form.querySelector('#email');
            const address = step3Form.querySelector('#address');
            
            if (!firstName || !lastName || !contactNumber || !email || !address) {
                alert('One or more form elements could not be found.');
                return false;
            }
            
            const firstNameVal = firstName.value.trim();
            const lastNameVal = lastName.value.trim();
            const contactNumberVal = contactNumber.value.trim();
            const emailVal = email.value.trim();
            const addressVal = address.value.trim();
            
            if (!firstNameVal || !lastNameVal || !contactNumberVal || !emailVal || !addressVal) {
                alert(`Missing fields: ${[
                    !firstNameVal ? 'First Name' : '',
                    !lastNameVal ? 'Last Name' : '',
                    !contactNumberVal ? 'Contact Number' : '',
                    !emailVal ? 'Email' : '',
                    !addressVal ? 'Address' : ''
                ].filter(Boolean).join(', ')}`);
                return false;
            }
            
            // Email validation
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailVal)) {
                alert('Please enter a valid email address.');
                return false;
            }
            
            // Philippine phone number validation
            const phonePattern = /^(09|\+639)\d{9}$/;
            if (!phonePattern.test(contactNumberVal.replace(/[-\s]/g, ''))) {
                alert('Please enter a valid Philippine phone number.');
                return false;
            }
            
            return true;
        }

        function validateSchedule() {
            if (!selectedDate || !selectedTime) {
                alert('Please select both a date and time for your appointment.');
                return false;
            }
            
            const today = new Date();
            if (selectedDate < today.setHours(0, 0, 0, 0)) {
                alert('Please select a future date.');
                return false;
            }
            
            return true;
        }

        // Replace your existing validatePaymentMethod function
        function validatePaymentMethod() {
            if (!selectedPaymentMethod) {
                alert('Please select a payment method.');
                return false;
            }
            
            if (selectedPaymentMethod === 'gcash') {
                const fileInput = document.getElementById('paymentReceipt');
                
                if (!paymentReceiptFile || !fileInput.files[0]) {
                    alert('Please upload your GCash payment receipt.');
                    return false;
                }
                
                // Double-check file size
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (fileInput.files[0].size > maxSize) {
                    alert('Payment receipt file is too large. Maximum size is 5MB.');
                    return false;
                }
                
                // Verify file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(fileInput.files[0].type.toLowerCase())) {
                    alert('Invalid payment receipt format. Please upload an image file.');
                    return false;
                }
            }
            
            return true;
        }

        function updateStepDisplay() {
            for (let i = 1; i <= totalSteps; i++) {
                const stepNumber = document.getElementById(`step${i}-number`);
                const stepTitle = document.getElementById(`step${i}-title`);
                const stepLine = document.getElementById(`line${i}`);
                
                if (i < currentStep) {
                    stepNumber.className = 'step-number completed';
                    stepTitle.className = 'step-title';
                    if (stepLine) stepLine.className = 'step-line completed';
                } else if (i === currentStep) {
                    stepNumber.className = 'step-number active';
                    stepTitle.className = 'step-title active';
                    if (stepLine) stepLine.className = 'step-line';
                } else {
                    stepNumber.className = 'step-number';
                    stepTitle.className = 'step-title';
                    if (stepLine) stepLine.className = 'step-line';
                }
            }
        }

        function updateFormDisplay() {
            // Hide all steps
            for (let i = 1; i <= totalSteps; i++) {
                const step = document.getElementById(`step${i}`);
                if (step) {
                    step.classList.remove('active');
                }
            }
            
            // Show current step
            const currentStepElement = document.getElementById(`step${currentStep}`);
            if (currentStepElement) {
                currentStepElement.classList.add('active');
            }
        }

        function updateNavigationButtons() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            
            // Previous button visibility
            if (currentStep === 1) {
                prevBtn.style.visibility = 'hidden';
            } else {
                prevBtn.style.visibility = 'visible';
            }
            
            // Next button text and functionality
            if (currentStep === totalSteps) {
                nextBtn.textContent = 'Submit Registration';
                nextBtn.onclick = submitRegistration;
            } else {
                const stepTexts = {
                    1: 'Continue to Vehicle Info →',
                    2: 'Continue to Owner Details →',
                    3: 'Continue to Schedule →',
                    4: 'Review & Confirm →'
                };
                nextBtn.textContent = stepTexts[currentStep] || 'Continue →';
                nextBtn.onclick = nextStep;
            }
        }

        function updateSummary() {
            // Branch
            document.getElementById('summary-branch').textContent = selectedBranchName || '-';
            
            // Vehicle info
            const brand = document.getElementById('brand').value;
            const vehicleType = document.getElementById('vehicleType').value;
            const vehicleCategory = document.getElementById('vehicleCategory').value;
            document.getElementById('summary-vehicle').textContent = `${brand} (${vehicleType} - ${vehicleCategory})`;
            
            // Plate number
            document.getElementById('summary-plate').textContent = document.getElementById('plateNumber').value;
            
            // Owner
            const firstName = document.querySelector('#step3 #firstName').value;
            const lastName = document.querySelector('#step3 #lastName').value;
            const middleName = document.querySelector('#step3 #middleName').value;
            const fullName = middleName ? `${firstName} ${middleName} ${lastName}` : `${firstName} ${lastName}`;
            document.getElementById('summary-owner').textContent = fullName;
            
            // Contact
            const email = document.querySelector('#step3 #email').value;
            const contactNumber = document.querySelector('#step3 #contactNumber').value;
            document.getElementById('summary-contact').textContent = `${contactNumber} | ${email}`;
            
            // Schedule
            if (selectedDate && selectedTime) {
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const formattedDate = selectedDate.toLocaleDateString('en-US', options);
                const timeString = formatTime(selectedTime);
                document.getElementById('summary-schedule').textContent = `${formattedDate} at ${timeString}`;
            }
            
            // Amount
            const vehicleTypeSelect = document.getElementById('vehicleType');
            const selectedOption = vehicleTypeSelect.options[vehicleTypeSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.price) {
                const price = parseInt(selectedOption.dataset.price);
                document.getElementById('summary-amount').textContent = `₱${price.toLocaleString()}.00`;
            }
        }

        function formatTime(time24) {
            const [hours, minutes] = time24.split(':');
            const hour12 = hours % 12 || 12;
            const ampm = hours < 12 ? 'AM' : 'PM';
            return `${hour12}:${minutes} ${ampm}`;
        }

        function submitRegistration() {
            if (!validateCurrentStep()) {
                return;
            }
            
            // Prepare form data
            const formData = new FormData();
            
            // Branch info
            formData.append('branchId', selectedBranchId);
            formData.append('branchName', selectedBranchName);
            
            // Vehicle info
            formData.append('plateNumber', document.getElementById('plateNumber').value);
            formData.append('vehicleType', document.getElementById('vehicleType').value);
            formData.append('brand', document.getElementById('brand').value);
            formData.append('vehicleCategory', document.getElementById('vehicleCategory').value);
            
            // Get price from selected vehicle type
            const vehicleTypeSelect = document.getElementById('vehicleType');
            const selectedOption = vehicleTypeSelect.options[vehicleTypeSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.price) {
                formData.append('price', selectedOption.dataset.price);
            }
            
            // Owner details
            formData.append('firstName', document.querySelector('#step3 #firstName').value);
            formData.append('lastName', document.querySelector('#step3 #lastName').value);
            formData.append('middleName', document.querySelector('#step3 #middleName').value);
            formData.append('contactNumber', document.querySelector('#step3 #contactNumber').value);
            formData.append('email', document.querySelector('#step3 #email').value);
            formData.append('address', document.querySelector('#step3 #address').value);
            
            // Schedule info
            const year = selectedDate.getFullYear();
            const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
            const day = String(selectedDate.getDate()).padStart(2, '0');
            const scheduleDate = `${year}-${month}-${day}`;

            formData.append('scheduleDate', scheduleDate);
            formData.append('scheduleTime', selectedTime);
            
            // Payment info
            formData.append('paymentMethod', selectedPaymentMethod);
            
            // Add payment receipt if GCash payment
            if (selectedPaymentMethod === 'gcash' && paymentReceiptFile) {
                formData.append('paymentReceipt', paymentReceiptFile);
            }
            
            // Disable submit button to prevent double submission
            const nextBtn = document.getElementById('nextBtn');
            nextBtn.disabled = true;
            nextBtn.textContent = 'Submitting...';
            
            // Submit to server
            fetch('submit_reservation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Try to get the response as text first to see what we're getting
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                
                // Try to parse as JSON
                try {
                    const data = JSON.parse(text);
                    return data;
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    throw new Error('Invalid JSON response from server');
                }
            })
            .then(data => {
                console.log('Parsed data:', data);
                if (data.success) {
                    // Show success modal with payment-specific message
                    document.getElementById('referenceNumber').textContent = data.referenceNumber;
                    
                    const paymentMessage = document.getElementById('paymentSuccessMessage');
                    if (selectedPaymentMethod === 'gcash') {
                        paymentMessage.innerHTML = `
                            <div style="margin-top: 15px; padding: 15px; background: #fff3e0; border-radius: 5px; border-left: 4px solid #ff9800;">
                                <strong>⏳ Payment Pending Verification</strong><br>
                                <span style="color: #666;">Your payment receipt has been uploaded successfully. Please wait while we verify your payment. You will be notified once verified.</span>
                            </div>
                        `;
                    } else {
                        paymentMessage.innerHTML = `
                            <div style="margin-top: 15px; padding: 15px; background: #fff3e0; border-radius: 5px; border-left: 4px solid #ff9800;">
                                <strong>📋 On-Site Payment</strong><br>
                                <span style="color: #666;">Please pay at the testing center. Your appointment receipt is available in your profile for download.</span>
                            </div>
                        `;
                    }
                    
                    document.getElementById('successModal').style.display = 'block';
                    
                    // Reset form after successful submission
                    setTimeout(() => {
                        resetForm();
                    }, 2000);
                } else {
                    // Show error modal
                    document.getElementById('errorMessage').textContent = data.message || 'Registration failed. Please try again.';
                    document.getElementById('errorModal').style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('errorMessage').textContent = 'Network error. Please check your connection and try again.';
                document.getElementById('errorModal').style.display = 'block';
            })
            .finally(() => {
                // Re-enable submit button
                nextBtn.disabled = false;
                nextBtn.textContent = 'Submit Registration';
            });
        }

        function resetForm() {
            // Reset all form fields
            document.querySelectorAll('input, select, textarea').forEach(element => {
                if (element.type === 'radio' || element.type === 'checkbox') {
                    element.checked = false;
                } else {
                    element.value = '';
                }
            });
            
            // Reset selections
            selectedBranchId = null;
            selectedBranchName = null;
            selectedBranchQR = null;
            selectedDate = null;
            selectedTime = null;
            selectedPaymentMethod = null;
            paymentReceiptFile = null;
            
            // Remove all selected classes
            document.querySelectorAll('.selected').forEach(element => {
                element.classList.remove('selected');
            });
            
            // Hide payment sections
            document.getElementById('gcashSection').classList.remove('active');
            document.getElementById('onsiteMessage').classList.remove('active');
            document.getElementById('receiptPreview').innerHTML = '';
            
            // Reset to first step
            currentStep = 1;
            updateStepDisplay();
            updateFormDisplay();
            updateNavigationButtons();
            
            // Reset calendar to current month
            currentMonth = new Date().getMonth();
            currentYear = new Date().getFullYear();
            updateCalendar();
        }

        function closeModal() {
            document.getElementById('successModal').style.display = 'none';
        }

        function closeErrorModal() {
            document.getElementById('errorModal').style.display = 'none';
        }

        // Close modals when clicking outside of them
        window.addEventListener('click', function(event) {
            const successModal = document.getElementById('successModal');
            const errorModal = document.getElementById('errorModal');
            
            if (event.target === successModal) {
                closeModal();
            }
            if (event.target === errorModal) {
                closeErrorModal();
            }
        });

        // Phone number formatting
        document.addEventListener('DOMContentLoaded', function() {
            const contactInput = document.querySelector('#step3 #contactNumber');
            if (contactInput) {
                contactInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    
                    if (value.startsWith('63')) {
                        value = '+' + value;
                    } else if (value.startsWith('9') && value.length === 10) {
                        value = '0' + value;
                    }
                    
                    if (value.startsWith('09') && value.length > 4) {
                        value = value.substring(0, 4) + '-' + 
                               value.substring(4, 7) + '-' + 
                               value.substring(7, 11);
                    }
                    
                    e.target.value = value;
                });
            }
        });

        // Plate number formatting
        document.getElementById('plateNumber').addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase();
            value = value.replace(/-/g, '');
            
            if (value.length > 3) {
                value = value.substring(0, 3) + '-' + value.substring(3);
            }
            
            e.target.value = value;
        });

        // Add this to your existing script section in registration.php

let slotAvailability = {}; // Store slot availability counts

// Modified selectDate function to fetch availability
function selectDate(date, element) {
    // Remove previous selection
    const previousSelected = document.querySelector('.calendar-day.selected');
    if (previousSelected) {
        previousSelected.classList.remove('selected');
    }
    
    // Add selection to clicked date
    element.classList.add('selected');
    selectedDate = date;
    
    // Reset selected time
    selectedTime = null;
    const previousTimeSelected = document.querySelector('.time-slot.selected');
    if (previousTimeSelected) {
        previousTimeSelected.classList.remove('selected');
    }
    
    // Fetch availability for this date
    fetchSlotAvailability(date);
}

// New function to fetch slot availability
// Replace the fetchSlotAvailability function with this:
function fetchSlotAvailability(date) {
    // Use local date format instead of UTC
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const formattedDate = `${year}-${month}-${day}`;
    
    // Show loading state
    const timeSlots = document.querySelectorAll('.time-slot');
    timeSlots.forEach(slot => {
        slot.classList.add('checking');
        // Store original time text as data attribute if not already stored
        if (!slot.dataset.originalTime) {
            slot.dataset.originalTime = slot.textContent.trim();
        }
        slot.innerHTML = slot.dataset.originalTime + '<br><small>Checking...</small>';
    });
    
    // Check if branch is selected
    if (!selectedBranchName) {
        alert('Please select a branch first before choosing a date.');
        timeSlots.forEach(slot => {
            slot.classList.remove('checking');
            slot.innerHTML = slot.dataset.originalTime;
        });
        return;
    }
    
    // Fetch availability from server - using branchName instead of branchId
    const url = `check_availability.php?date=${formattedDate}&branchName=${encodeURIComponent(selectedBranchName)}`;
    console.log('Fetching from URL:', url);
    
    fetch(url)
        .then(response => {
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            try {
                const data = JSON.parse(text);
                console.log('Parsed data:', data);
                
                if (data.error) {
                    console.error('Error from server:', data.error);
                    alert('Error: ' + data.error);
                    return;
                }
                
                slotAvailability = data.slot_counts || {};
                const maxSlots = data.max_slots || 3;
                
                // Update time slots with availability
                timeSlots.forEach(slot => {
                    slot.classList.remove('checking');
                    const onclickAttr = slot.getAttribute('onclick');
                    if (!onclickAttr) {
                        console.error('No onclick attribute found for slot:', slot);
                        return;
                    }
                    
                    const timeMatch = onclickAttr.match(/'(\d{2}:\d{2}:\d{2})'/);
                    if (!timeMatch) {
                        console.error('Could not extract time from onclick:', onclickAttr);
                        return;
                    }
                    
                    const timeValue = timeMatch[1];
                    const bookedCount = slotAvailability[timeValue] || 0;
                    const availableSlots = maxSlots - bookedCount;
                    
                    // Use stored original time text
                    const timeText = slot.dataset.originalTime;
                    
                    if (availableSlots > 0) {
                        slot.classList.remove('unavailable', 'full');
                        slot.classList.add('available');
                        
                        // Add availability indicator
                        const availabilityColor = availableSlots === 3 ? '#4caf50' : 
                                                 availableSlots === 2 ? '#ff9800' : '#f44336';
                        
                        slot.innerHTML = `${timeText}<br><small style="color: ${availabilityColor}; font-weight: bold;">${availableSlots} slot${availableSlots > 1 ? 's' : ''} left</small>`;
                    } else {
                        slot.classList.remove('available');
                        slot.classList.add('unavailable', 'full');
                        slot.onclick = null;
                        slot.style.cursor = 'not-allowed';
                        slot.innerHTML = `${timeText}<br><small style="color: #999;">FULL</small>`;
                    }
                });
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Text that failed to parse:', text);
                alert('Failed to parse server response. Check console for details.');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Failed to load time slot availability: ' + error.message);
            
            // Reset time slots on error
            timeSlots.forEach(slot => {
                slot.classList.remove('checking', 'unavailable');
                slot.innerHTML = slot.dataset.originalTime;
            });
        });
}

// Modified selectTimeSlot function
function selectTimeSlot(element, time) {
    // Check if slot is full
    if (element.classList.contains('full') || element.classList.contains('unavailable')) {
        return; // Don't allow selection of full slots
    }
    
    // Remove previous selection
    const previousSelected = document.querySelector('.time-slot.selected');
    if (previousSelected) {
        previousSelected.classList.remove('selected');
    }
    
    // Add selection to clicked time slot
    element.classList.add('selected');
    selectedTime = time;
}

// Update the branch selection to reset time slots
function selectBranch(element, branchId, branchName, gcashQR) {
    // Remove previous selection
    const previousSelected = document.querySelector('.branch-card.selected');
    if (previousSelected) {
        previousSelected.classList.remove('selected');
        const previousRadio = previousSelected.querySelector('.branch-radio');
        if (previousRadio) previousRadio.checked = false;
    }
    
    // Add selection to clicked branch
    element.classList.add('selected');
    const radio = element.querySelector('.branch-radio');
    if (radio) radio.checked = true;
    
    selectedBranchId = branchId;
    selectedBranchName = branchName;
    selectedBranchQR = gcashQR;
    
    // Reset date and time selection when branch changes
    selectedDate = null;
    selectedTime = null;
    
    // Clear any selected date
    const selectedDateElement = document.querySelector('.calendar-day.selected');
    if (selectedDateElement) {
        selectedDateElement.classList.remove('selected');
    }
    
    // Clear any selected time
    const selectedTimeElement = document.querySelector('.time-slot.selected');
    if (selectedTimeElement) {
        selectedTimeElement.classList.remove('selected');
    }
    
    // Reset time slots to default state
    const timeSlots = document.querySelectorAll('.time-slot');
    timeSlots.forEach(slot => {
        slot.classList.remove('available', 'unavailable', 'full', 'selected');
        const timeText = slot.textContent.split('\n')[0].split('Checking')[0].split('slot')[0].trim();
        slot.innerHTML = timeText;
    });
}

// Add CSS for the new states
const style = document.createElement('style');
style.textContent = `
    .time-slot.full {
        background-color: #f5f5f5;
        color: #999;
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .time-slot.checking {
        opacity: 0.7;
        pointer-events: none;
    }
    
    .time-slot small {
        display: block;
        margin-top: 5px;
        font-size: 11px;
    }
    
    .time-slot:not(.full):not(.unavailable):hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(164, 19, 60, 0.2);
    }
`;
document.head.appendChild(style);
    </script>
</body>
</html>