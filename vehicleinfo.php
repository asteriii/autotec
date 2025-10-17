<?php
// Database connection
include 'db.php';

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch branches from about_us table
$branches_query = "SELECT AboutID, BranchName, Description, MapLink FROM about_us ORDER BY BranchName";
$branches_result = $conn->query($branches_query);

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
    <link rel="stylesheet" href="css/index.css">
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
                            echo '<div class="branch-card" onclick="selectBranch(this, ' . $branch['AboutID'] . ', \'' . htmlspecialchars($branch['BranchName']) . '\')">';
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

            <!-- Step 5: Confirmation -->
            <div class="form-step" id="step5">
                <h2>Confirmation</h2>
                <p>Please review your registration details before submitting</p>

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

                <div style="background: #ffb3c1; padding: 15px; border-radius: 5px; border-left: 4px solid #a4133c;">
                    <strong>Note:</strong> Please bring your ticket to the testing center.
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
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();

        // Initialize calendar when page loads
        document.addEventListener('DOMContentLoaded', function () {
            initializeCalendar();
        });

        // Branch selection function
        function selectBranch(element, branchId, branchName) {
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
        }

        // Toggle map display
        function toggleMap(event, button, mapLink) {
            event.stopPropagation(); // Prevent branch selection when clicking map toggle
            
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
            // Target elements specifically within the step3 form to avoid conflicts with header
            const step3Form = document.getElementById('step3');
            const firstName = step3Form.querySelector('#firstName');
            const lastName = step3Form.querySelector('#lastName');
            const contactNumber = step3Form.querySelector('#contactNumber');
            const email = step3Form.querySelector('#email');
            const address = step3Form.querySelector('#address');
            
            // Check if elements exist
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
            
            // Owner details - using querySelector to target step3 specifically
            formData.append('firstName', document.querySelector('#step3 #firstName').value);
            formData.append('lastName', document.querySelector('#step3 #lastName').value);
            formData.append('middleName', document.querySelector('#step3 #middleName').value);
            formData.append('contactNumber', document.querySelector('#step3 #contactNumber').value);
            formData.append('email', document.querySelector('#step3 #email').value);
            formData.append('address', document.querySelector('#step3 #address').value);
            
            // Schedule info
            const scheduleDate = selectedDate.toISOString().split('T')[0]; // YYYY-MM-DD format
            formData.append('scheduleDate', scheduleDate);
            formData.append('scheduleTime', selectedTime);
            
            // Disable submit button to prevent double submission
            const nextBtn = document.getElementById('nextBtn');
            nextBtn.disabled = true;
            nextBtn.textContent = 'Submitting...';
            
            // Submit to server
            fetch('submit_reservation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success modal
                    document.getElementById('referenceNumber').textContent = data.referenceNumber;
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
            selectedDate = null;
            selectedTime = null;
            
            // Remove all selected classes
            document.querySelectorAll('.selected').forEach(element => {
                element.classList.remove('selected');
            });
            
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
            // Optionally redirect to home page or appointments page
            // window.location.href = 'index.php';
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
                    let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                    
                    if (value.startsWith('63')) {
                        value = '+' + value;
                    } else if (value.startsWith('9') && value.length === 10) {
                        value = '0' + value;
                    }
                    
                    // Format as 09XX-XXX-XXXX
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
            // Remove any existing hyphens
            value = value.replace(/-/g, '');
            
            // Add hyphen after 3 characters if there are more characters
            if (value.length > 3) {
                value = value.substring(0, 3) + '-' + value.substring(3);
            }
            
            e.target.value = value;
        });
    </script>
</body>
</html>