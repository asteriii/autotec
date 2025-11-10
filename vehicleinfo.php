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

// Store branch data in array for later use (including QR codes)
$branches_data = [];
if ($branches_result->num_rows > 0) {
    $branches_result->data_seek(0);
    while($row = $branches_result->fetch_assoc()) {
        $branches_data[] = $row;
    }
    $branches_result->data_seek(0);
}

// Fetch vehicle types with their prices
$vehicle_types_query = "SELECT VehicleTypeID, Name, Price FROM vehicle_types ORDER BY Name";
$vehicle_types_result = $conn->query($vehicle_types_query);

// Fetch vehicle categories
$vehicle_categories_query = "SELECT CategoryID, Name FROM vehicle_categories ORDER BY Name";
$vehicle_categories_result = $conn->query($vehicle_categories_query);
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

        .qr-code-placeholder {
            max-width: 300px;
            margin: 0 auto;
            padding: 40px;
            background: #f5f5f5;
            border: 2px dashed #ccc;
            border-radius: 10px;
            color: #999;
            text-align: center;
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
                            $gcashQR = !empty($branch['GCashQR']) ? htmlspecialchars($branch['GCashQR']) : '';
                            $branchId = $branch['AboutID'];
                            $branchName = htmlspecialchars($branch['BranchName']);
                            
                            echo '<div class="branch-card" onclick="selectBranch(this, ' . $branchId . ', \'' . $branchName . '\', \'' . $gcashQR . '\')">';
                            echo '<div class="branch-card-header">';
                            echo '<div class="branch-name">' . $branchName . '</div>';
                            echo '<input type="radio" name="selectedBranch" value="' . $branchId . '" class="branch-radio">';
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
                        <h4 style="color: #a4133c; margin-bottom: 15px;">💳 Scan QR Code to Pay</h4>
                        <div class="qr-code-container" id="qrCodeContainer">
                            <!-- QR code will be loaded here dynamically -->
                            <div class="qr-code-placeholder">
                                <p>📱 QR Code will appear here after selecting a branch</p>
                            </div>
                        </div>
                        
                        <div class="upload-section">
                            <h4 style="color: #a4133c; margin-bottom: 10px;">📸 Upload Payment Receipt</h4>
                            <p style="color: #666; margin-bottom: 15px;">Please upload a screenshot of your payment confirmation</p>
                            
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
                                    onchange="previewReceipt(this)">
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
        // ============================================
// COMPLETE JAVASCRIPT SECTION FOR vehicleinfo.php
// Replace everything between <script> and tags with this code
// ============================================

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
let slotAvailability = {};

// Initialize calendar when page loads
document.addEventListener('DOMContentLoaded', function () {
    initializeCalendar();
});

// Branch selection function
function selectBranch(element, branchId, branchName, gcashQR) {
    console.log('Branch selected:', {branchId, branchName, gcashQR});
    
    const previousSelected = document.querySelector('.branch-card.selected');
    if (previousSelected) {
        previousSelected.classList.remove('selected');
        const previousRadio = previousSelected.querySelector('.branch-radio');
        if (previousRadio) previousRadio.checked = false;
    }
    
    element.classList.add('selected');
    const radio = element.querySelector('.branch-radio');
    if (radio) radio.checked = true;
    
    selectedBranchId = branchId;
    selectedBranchName = branchName;
    selectedBranchQR = gcashQR;
    
    updateQRCodePreview();
}

function updateQRCodePreview() {
    const qrContainer = document.getElementById('qrCodeContainer');
    if (!qrContainer) return;
    
    if (selectedBranchQR && selectedBranchQR.trim() !== '') {
        qrContainer.innerHTML = `
            <img src="${selectedBranchQR}" alt="GCash QR Code" class="qr-code-image">
            <p style="margin-top: 10px; color: #666;">Scan this QR code with your GCash app</p>
        `;
    } else {
        qrContainer.innerHTML = `
            <div class="qr-code-placeholder">
                <p>⚠️ GCash QR code not available for this branch</p>
                <p style="font-size: 12px; margin-top: 10px;">Please choose "Pay On-Site" option or contact the branch</p>
            </div>
        `;
    }
}

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

function selectPaymentMethod(method) {
    selectedPaymentMethod = method;
    
    document.querySelectorAll('.payment-card').forEach(card => {
        card.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
    
    const gcashSection = document.getElementById('gcashSection');
    const onsiteMessage = document.getElementById('onsiteMessage');
    const receiptInput = document.getElementById('paymentReceipt');
    
    if (method === 'gcash') {
        gcashSection.classList.add('active');
        onsiteMessage.classList.remove('active');
        updateQRCodePreview();
        if (receiptInput) receiptInput.setAttribute('required', 'required');
    } else {
        gcashSection.classList.remove('active');
        onsiteMessage.classList.add('active');
        if (receiptInput) receiptInput.removeAttribute('required');
    }
}

function previewReceipt(input) {
    const preview = document.getElementById('receiptPreview');
    
    console.log('previewReceipt called');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        console.log('File selected:', {
            name: file.name,
            size: file.size,
            type: file.type
        });
        
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (file.size > maxSize) {
            alert('File size exceeds 5MB. Please choose a smaller file.');
            input.value = '';
            preview.innerHTML = '';
            paymentReceiptFile = null;
            return;
        }
        
        if (file.size === 0) {
            alert('The selected file appears to be empty. Please choose a valid image.');
            input.value = '';
            preview.innerHTML = '';
            paymentReceiptFile = null;
            return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        const fileType = file.type.toLowerCase();
        
        if (!allowedTypes.includes(fileType)) {
            alert('Invalid file type. Please upload an image file (JPG, PNG, GIF, or WebP).');
            input.value = '';
            preview.innerHTML = '';
            paymentReceiptFile = null;
            return;
        }
        
        paymentReceiptFile = file;
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const fileSizeKB = (file.size / 1024).toFixed(2);
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Payment Receipt Preview" style="max-width: 100%; max-height: 300px; border-radius: 10px; border: 2px solid #e0e0e0;">
                <div class="file-name" style="margin-top: 10px; color: #4caf50; font-weight: bold;">
                    ✓ ${file.name} (${fileSizeKB} KB)
                </div>
                <div style="margin-top: 5px; font-size: 12px; color: #666;">
                    Ready to upload
                </div>
            `;
            console.log('✓ Preview generated successfully');
        };
        
        reader.onerror = function() {
            console.error('FileReader error');
            alert('Error reading file. Please try again.');
            input.value = '';
            preview.innerHTML = '';
            paymentReceiptFile = null;
        };
        
        reader.readAsDataURL(file);
        
    } else {
        console.log('No file selected');
        preview.innerHTML = '';
        paymentReceiptFile = null;
    }
}

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

function initializeCalendar() {
    updateCalendar();
}

function updateCalendar() {
    const calendar = document.getElementById('calendar');
    const today = new Date();
    
    const calendarDays = calendar.querySelectorAll('.calendar-day');
    calendarDays.forEach(day => day.remove());
    
    const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"];
    document.getElementById('currentMonth').textContent = `${monthNames[currentMonth]} ${currentYear}`;
    
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    
    for (let i = 0; i < firstDay; i++) {
        const emptyDay = document.createElement('div');
        emptyDay.className = 'calendar-day unavailable';
        calendar.appendChild(emptyDay);
    }
    
    for (let day = 1; day <= daysInMonth; day++) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        dayElement.textContent = day;
        
        const dayDate = new Date(currentYear, currentMonth, day);
        
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
    const previousSelected = document.querySelector('.calendar-day.selected');
    if (previousSelected) {
        previousSelected.classList.remove('selected');
    }
    
    element.classList.add('selected');
    selectedDate = date;
    selectedTime = null;
    
    const previousTimeSelected = document.querySelector('.time-slot.selected');
    if (previousTimeSelected) {
        previousTimeSelected.classList.remove('selected');
    }
    
    fetchSlotAvailability(date);
}

function selectTimeSlot(element, time) {
    if (element.classList.contains('full') || element.classList.contains('unavailable')) {
        return;
    }
    
    const previousSelected = document.querySelector('.time-slot.selected');
    if (previousSelected) {
        previousSelected.classList.remove('selected');
    }
    
    element.classList.add('selected');
    selectedTime = time;
}

function fetchSlotAvailability(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const formattedDate = `${year}-${month}-${day}`;
    
    const timeSlots = document.querySelectorAll('.time-slot');
    timeSlots.forEach(slot => {
        slot.classList.add('checking');
        if (!slot.dataset.originalTime) {
            slot.dataset.originalTime = slot.textContent.trim();
        }
        slot.innerHTML = slot.dataset.originalTime + '<br><small>Checking...</small>';
    });
    
    if (!selectedBranchName) {
        alert('Please select a branch first before choosing a date.');
        timeSlots.forEach(slot => {
            slot.classList.remove('checking');
            slot.innerHTML = slot.dataset.originalTime;
        });
        return;
    }
    
    const url = `check_availability.php?date=${formattedDate}&branchName=${encodeURIComponent(selectedBranchName)}`;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                console.error('Error from server:', data.error);
                alert('Error: ' + data.error);
                return;
            }
            
            slotAvailability = data.slot_counts || {};
            const maxSlots = data.max_slots || 3;
            
            timeSlots.forEach(slot => {
                slot.classList.remove('checking');
                const onclickAttr = slot.getAttribute('onclick');
                if (!onclickAttr) return;
                
                const timeMatch = onclickAttr.match(/'(\d{2}:\d{2}:\d{2})'/);
                if (!timeMatch) return;
                
                const timeValue = timeMatch[1];
                const bookedCount = slotAvailability[timeValue] || 0;
                const availableSlots = maxSlots - bookedCount;
                const timeText = slot.dataset.originalTime;
                
                if (availableSlots > 0) {
                    slot.classList.remove('unavailable', 'full');
                    slot.classList.add('available');
                    
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
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Failed to load time slot availability: ' + error.message);
            
            timeSlots.forEach(slot => {
                slot.classList.remove('checking', 'unavailable');
                slot.innerHTML = slot.dataset.originalTime;
            });
        });
}

function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            currentStep++;
            updateStepDisplay();
            updateFormDisplay();
            updateNavigationButtons();
            
            if (currentStep === 5) {
                updateSummary();
                updateQRCodePreview();
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
        case 1: return validateBranchSelection();
        case 2: return validateVehicleInfo();
        case 3: return validateOwnerDetails();
        case 4: return validateSchedule();
        case 5: return validatePaymentMethod();
        default: return true;
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
    
    const platePattern = /^[A-Z]{1,3}[-\s]?\d{3,4}$/i;
    if (!platePattern.test(plateNumber)) {
        alert('Please enter a valid plate number format (e.g., ABC-1234).');
        return false;
    }
    
    return true;
}

function validateOwnerDetails() {
    const firstName = document.querySelector('#step3 #firstName').value.trim();
    const lastName = document.querySelector('#step3 #lastName').value.trim();
    const contactNumber = document.querySelector('#step3 #contactNumber').value.trim();
    const email = document.querySelector('#step3 #email').value.trim();
    const address = document.querySelector('#step3 #address').value.trim();
    
    if (!firstName || !lastName || !contactNumber || !email || !address) {
        alert('Please fill in all required owner details fields.');
        return false;
    }
    
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        alert('Please enter a valid email address.');
        return false;
    }
    
    const phonePattern = /^(09|\+639)\d{9}$/;
    if (!phonePattern.test(contactNumber.replace(/[-\s]/g, ''))) {
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

function validatePaymentMethod() {
    console.log('Validating payment method...');
    
    if (!selectedPaymentMethod) {
        alert('Please select a payment method.');
        return false;
    }
    
    console.log('Selected payment method:', selectedPaymentMethod);
    
    if (selectedPaymentMethod === 'gcash') {
        const fileInput = document.getElementById('paymentReceipt');
        
        console.log('Checking GCash receipt upload...');
        console.log('File input exists:', !!fileInput);
        console.log('Files array:', fileInput ? fileInput.files : null);
        console.log('paymentReceiptFile variable:', paymentReceiptFile);
        
        if (!fileInput || !fileInput.files || !fileInput.files[0]) {
            alert('Please upload your GCash payment receipt.');
            return false;
        }
        
        const file = fileInput.files[0];
        
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Payment receipt file is too large. Maximum size is 5MB.');
            return false;
        }
        
        if (file.size === 0) {
            alert('The uploaded file appears to be empty. Please select a valid image.');
            return false;
        }
        
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type.toLowerCase())) {
            alert('Invalid payment receipt format. Please upload an image file (JPG, PNG, GIF, or WebP).');
            return false;
        }
        
        console.log('✓ GCash receipt validation passed');
    } else {
        console.log('✓ On-site payment - no receipt required');
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
    for (let i = 1; i <= totalSteps; i++) {
        const step = document.getElementById(`step${i}`);
        if (step) step.classList.remove('active');
    }
    
    const currentStepElement = document.getElementById(`step${currentStep}`);
    if (currentStepElement) currentStepElement.classList.add('active');
}

function updateNavigationButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    prevBtn.style.visibility = currentStep === 1 ? 'hidden' : 'visible';
    
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
    document.getElementById('summary-branch').textContent = selectedBranchName || '-';
    
    const brand = document.getElementById('brand').value;
    const vehicleType = document.getElementById('vehicleType').value;
    const vehicleCategory = document.getElementById('vehicleCategory').value;
    document.getElementById('summary-vehicle').textContent = `${brand} (${vehicleType} - ${vehicleCategory})`;
    
    document.getElementById('summary-plate').textContent = document.getElementById('plateNumber').value;
    
    const firstName = document.querySelector('#step3 #firstName').value;
    const lastName = document.querySelector('#step3 #lastName').value;
    const middleName = document.querySelector('#step3 #middleName').value;
    const fullName = middleName ? `${firstName} ${middleName} ${lastName}` : `${firstName} ${lastName}`;
    document.getElementById('summary-owner').textContent = fullName;
    
    const email = document.querySelector('#step3 #email').value;
    const contactNumber = document.querySelector('#step3 #contactNumber').value;
    document.getElementById('summary-contact').textContent = `${contactNumber} | ${email}`;
    
    if (selectedDate && selectedTime) {
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = selectedDate.toLocaleDateString('en-US', options);
        const timeString = formatTime(selectedTime);
        document.getElementById('summary-schedule').textContent = `${formattedDate} at ${timeString}`;
    }
    
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
    console.log('=== Starting submission ===');
    
    if (!validateCurrentStep()) {
        console.log('Validation failed');
        return;
    }
    
    const formData = new FormData();
    
    // Branch information
    formData.append('branchId', selectedBranchId);
    formData.append('branchName', selectedBranchName);
    
    // Vehicle information
    formData.append('plateNumber', document.getElementById('plateNumber').value.trim());
    formData.append('vehicleType', document.getElementById('vehicleType').value);
    formData.append('brand', document.getElementById('brand').value.trim());
    formData.append('vehicleCategory', document.getElementById('vehicleCategory').value);
    
    const vehicleTypeSelect = document.getElementById('vehicleType');
    const selectedOption = vehicleTypeSelect.options[vehicleTypeSelect.selectedIndex];
    if (selectedOption && selectedOption.dataset.price) {
        formData.append('price', selectedOption.dataset.price);
    }
    
    // Owner information
    formData.append('firstName', document.querySelector('#step3 #firstName').value.trim());
    formData.append('lastName', document.querySelector('#step3 #lastName').value.trim());
    formData.append('middleName', document.querySelector('#step3 #middleName').value.trim());
    formData.append('contactNumber', document.querySelector('#step3 #contactNumber').value.trim());
    formData.append('email', document.querySelector('#step3 #email').value.trim());
    formData.append('address', document.querySelector('#step3 #address').value.trim());
    
    // Schedule information
    const year = selectedDate.getFullYear();
    const month = String(selectedDate.getMonth() + 1).padStart(2, '0');
    const day = String(selectedDate.getDate()).padStart(2, '0');
    const scheduleDate = `${year}-${month}-${day}`;

    formData.append('scheduleDate', scheduleDate);
    formData.append('scheduleTime', selectedTime);
    formData.append('paymentMethod', selectedPaymentMethod);
    
    console.log('Payment method:', selectedPaymentMethod);
    
    // File upload handling
    if (selectedPaymentMethod === 'gcash') {
        console.log('Processing GCash payment receipt...');
        
        const fileInput = document.getElementById('paymentReceipt');
        
        if (!fileInput || !fileInput.files || !fileInput.files[0]) {
            console.error('No file found in input');
            alert('Please upload your GCash payment receipt before submitting.');
            return;
        }
        
        const file = fileInput.files[0];
        
        console.log('File details:', {
            name: file.name,
            size: file.size,
            type: file.type
        });
        
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Payment receipt file is too large. Maximum size is 5MB.');
            return;
        }
        
        if (file.size === 0) {
            alert('The selected file appears to be empty. Please choose a valid image file.');
            return;
        }
        
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        const fileType = file.type.toLowerCase();
        
        if (!allowedTypes.includes(fileType)) {
            alert('Invalid file format. Please upload an image file (JPG, PNG, GIF, or WebP).');
            console.error('Invalid file type:', fileType);
            return;
        }
        
        formData.append('paymentReceipt', file, file.name);
        console.log('✓ Payment receipt attached successfully');
    } else {
        console.log('On-site payment selected - no receipt required');
    }
    
    // Debug: Log FormData contents
    console.log('=== FormData Contents ===');
    let hasFile = false;
    for (let [key, value] of formData.entries()) {
        if (value instanceof File) {
            console.log(`${key}: [FILE] ${value.name} (${value.size} bytes, ${value.type})`);
            hasFile = true;
        } else {
            console.log(`${key}: ${value}`);
        }
    }
    
    if (selectedPaymentMethod === 'gcash' && !hasFile) {
        console.error('ERROR: GCash payment selected but no file in FormData!');
        alert('Error: Payment receipt was not attached properly. Please try again.');
        return;
    }
    
    console.log('========================');
    
    const nextBtn = document.getElementById('nextBtn');
    nextBtn.disabled = true;
    nextBtn.textContent = 'Submitting...';
    nextBtn.style.opacity = '0.6';
    nextBtn.style.cursor = 'not-allowed';
    
    console.log('Sending request to submit_reservation.php...');
    
    fetch('submit_reservation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response received:', {
            status: response.status,
            statusText: response.statusText,
            ok: response.ok
        });
        
        if (!response.ok) {
            throw new Error(`Server returned ${response.status}: ${response.statusText}`);
        }
        
        return response.text();
    })
    .then(text => {
        console.log('Raw response text:', text);
        
        try {
            const data = JSON.parse(text);
            console.log('Parsed JSON response:', data);
            return data;
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was not valid JSON:', text);
            throw new Error('Server returned invalid response. Please check the server logs or contact support.');
        }
    })
    .then(data => {
        if (data.success) {
            console.log('✓ Reservation successful!');
            console.log('Reference:', data.referenceNumber);
            console.log('Payment receipt path:', data.paymentReceipt);
            
            document.getElementById('referenceNumber').textContent = data.referenceNumber;
            
            const paymentMessage = document.getElementById('paymentSuccessMessage');
            
            if (selectedPaymentMethod === 'gcash') {
                paymentMessage.innerHTML = `
                    <div style="margin-top: 15px; padding: 15px; background: #e8f5e9; border-radius: 5px; border-left: 4px solid #4caf50;">
                        <strong>✅ Payment Receipt Uploaded Successfully</strong><br>
                        <span style="color: #666;">
                            Your payment receipt has been uploaded and saved.<br>
                            Status: <strong style="color: #ff9800;">${data.paymentStatus}</strong>
                        </span><br>
                        ${data.paymentReceipt ? `<span style="color: #666; font-size: 12px;">Receipt saved as: ${data.paymentReceipt}</span>` : ''}
                    </div>
                `;
            } else {
                paymentMessage.innerHTML = `
                    <div style="margin-top: 15px; padding: 15px; background: #fff3e0; border-radius: 5px; border-left: 4px solid #ff9800;">
                        <strong>📋 On-Site Payment Selected</strong><br>
                        <span style="color: #666;">
                            Please pay at the testing center before your appointment.<br>
                            Your appointment receipt is available in your profile for download.
                        </span>
                    </div>
                `;
            }
            
            document.getElementById('successModal').style.display = 'block';
            setTimeout(() => resetForm(), 2000);
            
        } else {
            console.error('✗ Reservation failed:', data.message);
            throw new Error(data.message || 'Registration failed. Please try again.');
        }
    })
    .catch(error => {
        console.error('=== Submission Error ===');
        console.error('Error:', error);
        console.error('Message:', error.message);
        console.error('======================');
        
        const errorMsg = error.message || 'Network error. Please check your connection and try again.';
        document.getElementById('errorMessage').textContent = errorMsg;
        document.getElementById('errorModal').style.display = 'block';
    })
    .finally(() => {
        nextBtn.disabled = false;
        nextBtn.textContent = 'Submit Registration';
        nextBtn.style.opacity = '1';
        nextBtn.style.cursor = 'pointer';
        console.log('=== Submission Complete ===');
    });
}

function resetForm() {
    document.querySelectorAll('input, select, textarea').forEach(element => {
        if (element.type === 'radio' || element.type === 'checkbox') {
            element.checked = false;
        } else {
            element.value = '';
        }
    });
    
    selectedBranchId = null;
    selectedBranchName = null;
    selectedBranchQR = null;
    selectedDate = null;
    selectedTime = null;
    selectedPaymentMethod = null;
    paymentReceiptFile = null;
    
    document.querySelectorAll('.selected').forEach(element => {
        element.classList.remove('selected');
    });
    
    document.getElementById('gcashSection').classList.remove('active');
    document.getElementById('onsiteMessage').classList.remove('active');
    document.getElementById('receiptPreview').innerHTML = '';
    
    currentStep = 1;
    updateStepDisplay();
    updateFormDisplay();
    updateNavigationButtons();
    
    currentMonth = new Date().getMonth();
    currentYear = new Date().getFullYear();
    updateCalendar();
}

function closeModal() {
    document.getElementById('successModal').style.display = 'none';
    window.location.href = 'vehicleinfo.php';
}

function closeErrorModal() {
    document.getElementById('errorModal').style.display = 'none';
}

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
const plateInput = document.getElementById('plateNumber');
if (plateInput) {
    plateInput.addEventListener('input', function(e) {
        let value = e.target.value.toUpperCase();
        value = value.replace(/-/g, '');
        
        if (value.length > 3) {
            value = value.substring(0, 3) + '-' + value.substring(3);
        }
        
        e.target.value = value;
    });
}
    </script>
</body>
</html>