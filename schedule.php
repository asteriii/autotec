<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - AutoTEC</title>
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
            color: #007bff;
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
            color: #007bff;
            border: 1px solid #007bff;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn:hover {
            opacity: 0.8;
        }

        /* Registration Container */
        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
        }

        .registration-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .registration-header h1 {
            font-size: 2.5em;
            color: #333;
            margin-bottom: 10px;
        }

        .registration-header .highlight {
            color: #007bff;
        }

        /* Progress Steps */
        .progress-container {
            margin-bottom: 40px;
        }

        .progress-steps {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: nowrap;
            gap: 0px;
            max-width: 800px;
            margin: 0 auto 20px auto;
        }

        .step {
            display: flex;
            align-items: center;
            position: relative;
            flex: 1;
            justify-content: center;
        }

        .step:last-child {
            flex: 0 0 auto;
        }

        .step-number {
            width: 50px;
            height: 50px;
            background-color: #ccc;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .step-number.active {
            background-color: #007bff;
        }

        .step-number.completed {
            background-color: #28a745;
        }

        .step-title {
            font-weight: bold;
            color: #333;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .step-title.active {
            color: #007bff;
        }

        .step-line {
            width: 60px;
            height: 2px;
            background-color: #ccc;
            flex-grow: 1;
            max-width: 80px;
            margin: 0 15px;
        }

        .step-line.completed {
            background-color: #28a745;
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .form-step {
            display: none;
            padding: 40px;
        }

        .form-step.active {
            display: block;
        }

        .form-step h2 {
            color: #007bff;
            margin-bottom: 10px;
            font-size: 1.8em;
        }

        .form-step p {
            color: #666;
            margin-bottom: 30px;
        }

        /* Summary Info Box */
        .summary-info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 5px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .summary-row:last-child {
            margin-bottom: 0;
        }

        .summary-label {
            font-weight: bold;
            color: #333;
        }

        .summary-value {
            color: #666;
            font-style: italic;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #007bff;
        }

        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group select:focus {
            outline: none;
            border-color: #007bff;
        }

        /* Calendar Section */
        .calendar-section {
            margin-bottom: 30px;
        }

        .calendar-section h3 {
            color: #007bff;
            margin-bottom: 15px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .calendar-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .calendar-nav {
            display: flex;
            gap: 10px;
        }

        .calendar-nav button {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #007bff;
            padding: 5px 10px;
            border-radius: 4px;
        }

        .calendar-nav button:hover {
            background-color: #f0f0f0;
        }

        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            text-align: center;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }

        .calendar-weekday {
            background-color: #f8f9fa;
            padding: 10px 5px;
            font-weight: bold;
            color: #666;
            font-size: 12px;
        }

        .calendar-day {
            padding: 12px 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            border-bottom: 1px solid #f0f0f0;
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .calendar-day:hover {
            background-color: #e7f3ff;
        }

        .calendar-day.available {
            background-color: white;
            color: #333;
        }

        .calendar-day.selected {
            background-color: #007bff;
            color: white;
        }

        .calendar-day.unavailable {
            color: #ccc;
            cursor: not-allowed;
            background-color: #f8f9fa;
        }

        .calendar-day.today {
            background-color: #fff3cd;
            color: #856404;
        }

        .calendar-day.today.selected {
            background-color: #007bff;
            color: white;
        }

        /* Time Slots Section */
        .time-section {
            margin-bottom: 30px;
        }

        .time-section h3 {
            color: #007bff;
            margin-bottom: 15px;
        }

        .time-section h4 {
            color: #007bff;
            margin-bottom: 15px;
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .time-slot {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
            font-weight: 500;
        }

        .time-slot:hover {
            border-color: #007bff;
            background-color: #f8f9ff;
        }

        .time-slot.selected {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }

        .time-slot.unavailable {
            background-color: #f8f9fa;
            color: #ccc;
            cursor: not-allowed;
        }

        /* Reminder Box */
        .reminder-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-left: 4px solid #f39c12;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 5px;
        }

        .reminder-box .reminder-icon {
            color: #f39c12;
            font-weight: bold;
            margin-right: 8px;
        }

        .reminder-box .reminder-text {
            color: #856404;
            font-weight: 500;
        }

        /* Navigation Buttons */
        .form-navigation {
            display: flex;
            justify-content: space-between;
            padding: 20px 40px;
            background-color: #f8f9fa;
            border-top: 1px solid #e0e0e0;
        }

        .nav-button {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .nav-button.prev {
            background-color: #6c757d;
            color: white;
        }

        .nav-button.next {
            background-color: #007bff;
            color: white;
        }

        .nav-button:hover {
            opacity: 0.9;
        }

        .nav-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .time-slots {
                grid-template-columns: repeat(2, 1fr);
            }

            .progress-steps {
                flex-direction: column;
                align-items: flex-start;
            }

            .step-line {
                display: none;
            }

            .form-step {
                padding: 20px;
            }

            .form-navigation {
                padding: 15px 20px;
            }
        }
    </style>
</head>

<body>
    <header>
        <nav>
            <div class="logo">🏢</div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="vehicleinfo.php">Reserve Now!</a></li>
                <li><a href="aboutus.php">About Us</a></li>
                <li><a href="contactus.php">Contact Us</a></li>
            </ul>
            <div class="auth-buttons">
                <a href="#signin" class="btn btn-outline">Sign In</a>
                <a href="#register" class="btn btn-primary">Register</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <div class="registration-header">
            <h1><span class="highlight">Regis</span>tration</h1>
        </div>

        <div class="progress-container">
            <div class="progress-steps">
                <div class="step">
                    <div class="step-number completed">1</div>
                    <div class="step-title">Vehicle Info</div>
                </div>
                <div class="step-line completed"></div>
                <div class="step">
                    <div class="step-number completed">2</div>
                    <div class="step-title">Owner Details</div>
                </div>
                <div class="step-line completed"></div>
                <div class="step">
                    <div class="step-number active">3</div>
                    <div class="step-title active">Schedule</div>
                </div>
                <div class="step-line"></div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-title">Confirmation</div>
                </div>
            </div>
        </div>

        <div class="form-container">
            <!-- Schedule Step -->
            <div class="form-step active">
                <h2>Schedule Your Appointment</h2>
                <p>Select your preferred testing center and time slot.</p>

                <!-- Summary Info -->
                <div class="summary-info">
                    <div class="summary-row">
                        <span class="summary-label">Vehicle:</span>
                        <span class="summary-value">Lorem ipsum</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Owner:</span>
                        <span class="summary-value">Lorem ipsum</span>
                    </div>
                </div>

                <!-- Testing Center Selection -->
                <div class="form-group">
                    <label for="testingCenter">Testing Center</label>
                    <select id="testingCenter" name="testingCenter" required>
                        <option value="">Select Testing Center</option>
                        <option value="center1">AutoTEC - Shaw Boulevard</option>
                        <option value="center2">AutoTEC - Subic</option>
                    </select>
                </div>

                <!-- Date Selection -->
                <div class="calendar-section">
                    <h3>Select Date</h3>
                    <div class="calendar-header">
                        <span class="calendar-title">July 2025</span>
                        <div class="calendar-nav">
                            <button onclick="previousMonth()">‹</button>
                            <button onclick="nextMonth()">›</button>
                        </div>
                    </div>
                    <div class="calendar" id="calendar">
                        <div class="calendar-weekday">SUN</div>
                        <div class="calendar-weekday">MON</div>
                        <div class="calendar-weekday">TUE</div>
                        <div class="calendar-weekday">WED</div>
                        <div class="calendar-weekday">THU</div>
                        <div class="calendar-weekday">FRI</div>
                        <div class="calendar-weekday">SAT</div>
                        <!-- Calendar days will be generated by JavaScript -->
                    </div>
                </div>

                <!-- Time Slots -->
                <div class="time-section">
                    <h3>Available Time Slots</h3>

                    <h4>Morning:</h4>
                    <div class="time-slots">
                        <div class="time-slot" onclick="selectTimeSlot(this, '9:00 AM – 9:20 AM')">9:00 AM – 9:20 AM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '9:20 AM – 9:40 AM')">9:20 AM – 9:40 AM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '9:40 AM – 10:00 AM')">9:40 AM – 10:00 AM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '10:00 AM – 10:20 AM')">10:00 AM – 10:20 AM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '10:20 AM – 10:40 AM')">10:20 AM – 10:40 AM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '10:40 AM – 11:00 AM')">10:40 AM – 11:00 AM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '11:00 AM – 11:20 AM')">11:00 AM – 11:20 AM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '11:20 AM – 11:40 AM')">11:20 AM – 11:40 AM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '11:40 AM – 12:00 PM')">11:40 AM – 12:00 PM</div>
                    </div>

                    <div class="time-slot unavailable" style="margin: 20px 0; text-align: center; font-weight: bold;">Lunch Break: 12:00 PM – 1:00 PM</div>

                    <h4>Afternoon:</h4>
                    <div class="time-slots">
                        <div class="time-slot" onclick="selectTimeSlot(this, '1:00 PM – 1:20 PM')">1:00 PM – 1:20 PM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '1:20 PM – 1:40 PM')">1:20 PM – 1:40 PM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '1:40 PM – 2:00 PM')">1:40 PM – 2:00 PM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '2:00 PM – 2:20 PM')">2:00 PM – 2:20 PM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '2:20 PM – 2:40 PM')">2:20 PM – 2:40 PM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '2:40 PM – 3:00 PM')">2:40 PM – 3:00 PM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '3:00 PM – 3:20 PM')">3:00 PM – 3:20 PM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '3:20 PM – 3:40 PM')">3:20 PM – 3:40 PM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '3:40 PM – 4:00 PM')">3:40 PM – 4:00 PM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '4:00 PM – 4:20 PM')">4:00 PM – 4:20 PM</div>
                        <div class="time-slot" onclick="selectTimeSlot(this, '4:20 PM – 4:40 PM')">4:20 PM – 4:40 PM</div>
                    </div>
                </div>

                <!-- Reminder -->
                <div class="reminder-box">
                    <span class="reminder-icon">💡</span>
                    <span class="reminder-text">Reminder</span><br>
                    <span style="color: #856404; font-size: 14px;">Arrive at least 30 minutes before your scheduled
                        time.</span>
                </div>
            </div>

            <div class="form-navigation">
                <button class="nav-button prev" onclick="previousStep()">← Back to Owner Details</button>
                <button class="nav-button next" onclick="nextStep()">Continue to Confirmation →</button>
            </div>
        </div>
    </div>

    <script>
        // Initialize with current date
        const today = new Date();
        let currentMonth = today.getMonth();
        let currentYear = today.getFullYear();
        let selectedDate = null;
        let selectedTime = null;

        // Initialize calendar when page loads
        document.addEventListener('DOMContentLoaded', function () {
            generateCalendar(currentMonth, currentYear);
        });

        function generateCalendar(month, year) {
            const calendar = document.getElementById('calendar');
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];

            // Update month title
            document.querySelector('.calendar-title').textContent = `${monthNames[month]} ${year}`;

            // Clear existing calendar days
            const existingDays = calendar.querySelectorAll('.calendar-day');
            existingDays.forEach(day => day.remove());

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();

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

                const dayDate = new Date(year, month, day);
                const isToday = dayDate.toDateString() === today.toDateString();
                const isPast = dayDate < today;
                const isSunday = dayDate.getDay() === 0;

                if (isPast || isSunday) {
                    dayElement.className += ' unavailable';
                } else {
                    dayElement.className += ' available';
                    dayElement.onclick = () => selectDate(dayElement, dayDate);
                }

                if (isToday && !isPast) {
                    dayElement.className += ' today';
                }

                calendar.appendChild(dayElement);
            }
        }

        function selectDate(element, date) {
            // Remove previous selection
            document.querySelectorAll('.calendar-day.selected').forEach(day => {
                day.classList.remove('selected');
            });

            // Select new date
            element.classList.add('selected');
            selectedDate = date;
        }

        function selectTimeSlot(element, time) {
            // Remove previous selection
            document.querySelectorAll('.time-slot.selected').forEach(slot => {
                slot.classList.remove('selected');
            });

            // Select new time
            element.classList.add('selected');
            selectedTime = time;
        }

        function previousMonth() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            generateCalendar(currentMonth, currentYear);
        }

        function nextMonth() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            generateCalendar(currentMonth, currentYear);
        }

        function previousStep() {
            alert('Go back to Owner Details step');
        }

        function nextStep() {
            const testingCenter = document.getElementById('testingCenter').value;

            if (!testingCenter) {
                alert('Please select a testing center.');
                return;
            }

            if (!selectedDate) {
                alert('Please select a date.');
                return;
            }

            if (!selectedTime) {
                alert('Please select a time slot.');
                return;
            }

            alert('Proceeding to Confirmation step');
        }
    </script>
</body>

</html>