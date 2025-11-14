<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rescheduleID = $_POST['rescheduleID'] ?? '';
    $cancelReason = $_POST['cancelReason'] ?? null;

    // Get username from session for logging
    $username = $_SESSION['admin_username'] ?? 'Unknown Admin';

    if (empty($action) || empty($rescheduleID)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }

    // Fetch the reschedule record
    $stmt = $pdo->prepare("SELECT r.*, vt.Name as VehicleTypeName 
                           FROM reschedule r 
                           LEFT JOIN vehicle_types vt ON r.TypeID = vt.VehicleTypeID 
                           WHERE r.RescheduleID = ?");
    $stmt->execute([$rescheduleID]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$res) {
        echo json_encode(['success' => false, 'message' => 'Record not found.']);
        exit;
    }

    // Fetch Category Name
    try {
        $stmt = $pdo->prepare("SELECT Name FROM vehicle_categories WHERE CategoryID = ?");
        $stmt->execute([$res['CategoryID']]);
        $catRow = $stmt->fetch();
        $res['CategoryName'] = $catRow ? $catRow['Name'] : 'N/A';
    } catch (Exception $e) {
        $res['CategoryName'] = 'N/A';
    }

    // Decide which date/time to use
    if ($action === 'confirm') {
        $finalDate = $res['NewDate'];
        $finalTime = $res['NewTime'];
    } else { // deny
        $finalDate = $res['Date'];
        $finalTime = $res['Time'];
    }

    // Calculate if payment should be forfeited (for GCash only)
    $isGcash = strtolower($res['PaymentMethod']) === 'gcash';
    $shouldForfeit = false;
    
    if ($action === 'deny' && $isGcash) {
        // Check if reschedule was requested less than 1 day before the original appointment
        $originalDate = new DateTime($res['Date']);
        $today = new DateTime();
        $daysDifference = $today->diff($originalDate)->days;
        
        // If the appointment is tomorrow or today, forfeit the payment
        if ($daysDifference <= 1) {
            $shouldForfeit = true;
        }
    }

    try {
        $pdo->beginTransaction();

        if ($action === 'deny' && !empty($cancelReason)) {
            // Move to canceled table if reason is provided (matches cancel_reservation.php structure)
            $insert = $pdo->prepare("
                INSERT INTO canceled (
                    ReservationID, UserID, PlateNo, Brand, TypeID, CategoryID,
                    Fname, Lname, Mname, PhoneNum, Email,
                    Date, Time, Address, BranchName, PaymentMethod,
                    PaymentStatus, PaymentReceipt, Price, ReferenceNumber, CreatedAt, Reason
                ) VALUES (
                    :ReservationID, :UserID, :PlateNo, :Brand, :TypeID, :CategoryID,
                    :Fname, :Lname, :Mname, :PhoneNum, :Email,
                    :Date, :Time, :Address, :BranchName, :PaymentMethod,
                    :PaymentStatus, :PaymentReceipt, :Price, :ReferenceNumber, :CreatedAt, :Reason
                )
            ");

            $insert->execute([
                ':ReservationID' => $res['ReservationID'],
                ':UserID' => $res['UserID'],
                ':PlateNo' => $res['PlateNo'],
                ':Brand' => $res['Brand'],
                ':TypeID' => $res['TypeID'],
                ':CategoryID' => $res['CategoryID'],
                ':Fname' => $res['Fname'],
                ':Lname' => $res['Lname'],
                ':Mname' => $res['Mname'],
                ':PhoneNum' => $res['PhoneNum'],
                ':Email' => $res['Email'],
                ':Date' => $finalDate,
                ':Time' => $finalTime,
                ':Address' => $res['Address'],
                ':BranchName' => $res['BranchName'],
                ':PaymentMethod' => $res['PaymentMethod'],
                ':PaymentStatus' => $res['PaymentStatus'],
                ':PaymentReceipt' => $res['PaymentReceipt'],
                ':Price' => $res['Price'],
                ':ReferenceNumber' => $res['ReferenceNumber'],
                ':CreatedAt' => $res['CreatedAt'] ?? date('Y-m-d H:i:s'),
                ':Reason' => $cancelReason
            ]);

            // Send cancellation email with reason
            $emailSent = sendRescheduleDeniedEmail($res, $cancelReason, $shouldForfeit);
            
            $message = 'Reschedule denied and moved to canceled list.';
        } else {
            // Move to completed table (for confirm or deny without reason)
            $insert = $pdo->prepare("
                INSERT INTO completed (
                    ReservationID, UserID, PlateNo, Brand, TypeID, CategoryID,
                    Date, Time, PaymentMethod, PaymentReceipt, PaymentStatus,
                    Fname, Mname, Lname, Email, PhoneNum, Address, ReferenceNumber, Price, BranchName
                ) VALUES (
                    :ReservationID, :UserID, :PlateNo, :Brand, :TypeID, :CategoryID,
                    :Date, :Time, :PaymentMethod, :PaymentReceipt, :PaymentStatus,
                    :Fname, :Mname, :Lname, :Email, :PhoneNum, :Address, :ReferenceNumber, :Price, :BranchName
                )
            ");

            $insert->execute([
                ':ReservationID' => $res['ReservationID'],
                ':UserID' => $res['UserID'],
                ':PlateNo' => $res['PlateNo'],
                ':Brand' => $res['Brand'],
                ':TypeID' => $res['TypeID'],
                ':CategoryID' => $res['CategoryID'],
                ':Date' => $finalDate,
                ':Time' => $finalTime,
                ':PaymentMethod' => $res['PaymentMethod'],
                ':PaymentReceipt' => $res['PaymentReceipt'],
                ':PaymentStatus' => 'verified',
                ':Fname' => $res['Fname'],
                ':Mname' => $res['Mname'],
                ':Lname' => $res['Lname'],
                ':Email' => $res['Email'],
                ':PhoneNum' => $res['PhoneNum'],
                ':Address' => $res['Address'],
                ':ReferenceNumber' => $res['ReferenceNumber'],
                ':Price' => $res['Price'],
                ':BranchName' => $res['BranchName']
            ]);

            // Send appropriate email based on action
            if ($action === 'confirm') {
                $emailSent = sendRescheduleConfirmedEmail($res);
                $message = 'Reschedule confirmed! Appointment moved to completed with new date/time.';
            } else {
                $emailSent = sendRescheduleDeniedEmail($res, null, false);
                $message = 'Reschedule denied! Appointment moved to completed with original date/time.';
            }
        }

        // Delete from reschedule table
        $delete = $pdo->prepare("DELETE FROM reschedule WHERE RescheduleID = ?");
        $delete->execute([$rescheduleID]);

        // Delete from reservations table
        $deleteRes = $pdo->prepare("DELETE FROM reservations WHERE ReservationID = ?");
        $deleteRes->execute([$res['ReservationID']]);

        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'email_sent' => $emailSent ?? false
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// ============================================================================
// EMAIL FUNCTIONS (Using SendGrid Web API v3)
// ============================================================================

function sendRescheduleConfirmedEmail($data) {
    $apiKey = getenv('SENDGRID_API_KEY');
    $fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'noreply@autotec.com';
    
    if (!$apiKey) {
        error_log("SendGrid API key not found");
        return false;
    }
    
    // Format data
    $referenceNumber = $data['ReferenceNumber'] ?: 'AT-' . $data['ReservationID'];
    $paymentMethod = $data['PaymentMethod'] === 'gcash' ? 'GCash' : 'On-Site';
    $amount = number_format($data['Price'], 2);
    
    // Use NEW date/time for confirmed reschedule
    $formattedDate = date('l, F d, Y', strtotime($data['NewDate']));
    $formattedTime = formatTime($data['NewTime']);
    
    $generatedDate = date('m/d/Y g:i:s A');
    $fullName = trim(($data['Mname'] ? $data['Fname'] . ' ' . $data['Mname'] . ' ' . $data['Lname'] : $data['Fname'] . ' ' . $data['Lname']));
    
    // Generate email content
    $htmlContent = generateRescheduleConfirmedHTML(
        $data, 
        $referenceNumber, 
        $paymentMethod, 
        $amount, 
        $formattedDate, 
        $formattedTime, 
        $generatedDate,
        $fullName
    );
    
    $textContent = generateRescheduleConfirmedPlainText(
        $data, 
        $referenceNumber, 
        $paymentMethod, 
        $amount, 
        $formattedDate, 
        $formattedTime,
        $fullName
    );
    
    // SendGrid API v3 payload
    $payload = [
        'personalizations' => [
            [
                'to' => [
                    [
                        'email' => $data['Email'],
                        'name' => $fullName
                    ]
                ],
                'subject' => 'Reschedule Request Approved - AutoTEC Emission Testing'
            ]
        ],
        'from' => [
            'email' => $fromEmail,
            'name' => 'AutoTEC Emission Testing'
        ],
        'reply_to' => [
            'email' => $fromEmail,
            'name' => 'AutoTEC Support'
        ],
        'content' => [
            [
                'type' => 'text/plain',
                'value' => $textContent
            ],
            [
                'type' => 'text/html',
                'value' => $htmlContent
            ]
        ]
    ];
    
    return sendViaSendGrid($payload);
}

function sendRescheduleDeniedEmail($data, $reason, $shouldForfeit) {
    $apiKey = getenv('SENDGRID_API_KEY');
    $fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'noreply@autotec.com';
    
    if (!$apiKey) {
        error_log("SendGrid API key not found");
        return false;
    }
    
    // Format data
    $referenceNumber = $data['ReferenceNumber'] ?: 'AT-' . $data['ReservationID'];
    $paymentMethod = $data['PaymentMethod'] === 'gcash' ? 'GCash' : 'On-Site';
    $amount = number_format($data['Price'], 2);
    
    // Use ORIGINAL date/time for denied reschedule
    $formattedDate = date('l, F d, Y', strtotime($data['Date']));
    $formattedTime = formatTime($data['Time']);
    
    $generatedDate = date('m/d/Y g:i:s A');
    $fullName = trim(($data['Mname'] ? $data['Fname'] . ' ' . $data['Mname'] . ' ' . $data['Lname'] : $data['Fname'] . ' ' . $data['Lname']));
    
    $isGcash = strtolower($data['PaymentMethod']) === 'gcash';
    
    // Generate email content
    $htmlContent = generateRescheduleDeniedHTML(
        $data, 
        $referenceNumber, 
        $paymentMethod, 
        $amount, 
        $formattedDate, 
        $formattedTime, 
        $generatedDate,
        $fullName,
        $reason,
        $isGcash,
        $shouldForfeit
    );
    
    $textContent = generateRescheduleDeniedPlainText(
        $data, 
        $referenceNumber, 
        $paymentMethod, 
        $amount, 
        $formattedDate, 
        $formattedTime,
        $fullName,
        $reason,
        $isGcash,
        $shouldForfeit
    );
    
    // SendGrid API v3 payload
    $payload = [
        'personalizations' => [
            [
                'to' => [
                    [
                        'email' => $data['Email'],
                        'name' => $fullName
                    ]
                ],
                'subject' => 'Reschedule Request Denied - AutoTEC Emission Testing'
            ]
        ],
        'from' => [
            'email' => $fromEmail,
            'name' => 'AutoTEC Emission Testing'
        ],
        'reply_to' => [
            'email' => $fromEmail,
            'name' => 'AutoTEC Support'
        ],
        'content' => [
            [
                'type' => 'text/plain',
                'value' => $textContent
            ],
            [
                'type' => 'text/html',
                'value' => $htmlContent
            ]
        ]
    ];
    
    return sendViaSendGrid($payload);
}

function sendViaSendGrid($payload) {
    $apiKey = getenv('SENDGRID_API_KEY');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 202) {
        return true;
    } else {
        error_log("SendGrid API Error - HTTP Code: $httpCode");
        error_log("Response: $response");
        error_log("Curl Error: $curlError");
        return false;
    }
}

function formatTime($timeString) {
    if (!$timeString) return 'N/A';
    if (strpos($timeString, 'AM') !== false || strpos($timeString, 'PM') !== false) {
        return $timeString;
    }
    
    $parts = explode(':', $timeString);
    $hour = intval($parts[0]);
    $minute = $parts[1] ?? '00';
    
    if ($hour == 0) {
        return "12:$minute AM";
    } elseif ($hour < 12) {
        return "$hour:$minute AM";
    } elseif ($hour == 12) {
        return "12:$minute PM";
    } else {
        return ($hour - 12) . ":$minute PM";
    }
}

// ============================================================================
// RESCHEDULE CONFIRMED EMAIL TEMPLATES
// ============================================================================

function generateRescheduleConfirmedHTML($data, $refNum, $method, $amount, $date, $time, $generated, $fullName) {
    $oldDate = date('l, F d, Y', strtotime($data['Date']));
    $oldTime = formatTime($data['Time']);
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>AutoTEC Reschedule Approved</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
        <table role='presentation' style='width: 100%; border-collapse: collapse;'>
            <tr>
                <td style='padding: 20px 0;'>
                    <table role='presentation' style='width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>
                        
                        <!-- Header -->
                        <tr>
                            <td style='background-color: #bd1e51; padding: 30px 20px; text-align: left;'>
                                <h1 style='margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;'>AutoTEC</h1>
                                <p style='margin: 5px 0 0 0; color: #ffffff; font-size: 14px;'>Emission Testing Center</p>
                            </td>
                        </tr>
                        
                        <!-- Title -->
                        <tr>
                            <td style='padding: 30px 20px 10px 20px; text-align: center;'>
                                <h2 style='margin: 0; color: #48bb78; font-size: 20px; font-weight: bold;'>✅ RESCHEDULE REQUEST APPROVED</h2>
                            </td>
                        </tr>
                        
                        <!-- Reference Number -->
                        <tr>
                            <td style='padding: 0 20px;'>
                                <table style='width: 100%; background-color: #f8f9fa; border-radius: 5px; padding: 12px;'>
                                    <tr>
                                        <td style='font-weight: bold; color: #333333; font-size: 11px;'>Reference Number:</td>
                                        <td style='text-align: right; color: #bd1e51; font-weight: bold; font-size: 12px;'>$refNum</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Status -->
                        <tr>
                            <td style='padding: 10px 20px;'>
                                <table style='width: 100%; background-color: #d4edda; border-radius: 5px; padding: 10px;'>
                                    <tr>
                                        <td style='font-weight: bold; color: #333333; font-size: 10px;'>Status:</td>
                                        <td style='color: #155724; font-weight: bold; font-size: 10px;'>RESCHEDULED</td>
                                        <td style='text-align: right; font-weight: bold; color: #333333; font-size: 10px;'>Payment Method:</td>
                                        <td style='text-align: right; color: #333333; font-size: 10px;'>$method</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Generated Date -->
                        <tr>
                            <td style='padding: 0 20px 20px 20px; text-align: right;'>
                                <p style='margin: 0; color: #666666; font-size: 9px;'>Approved: $generated</p>
                            </td>
                        </tr>
                        
                        <!-- Success Message -->
                        <tr>
                            <td style='padding: 20px;'>
                                <table style='width: 100%; background-color: #d4edda; border-radius: 5px; padding: 15px; border-left: 4px solid #48bb78;'>
                                    <tr>
                                        <td>
                                            <p style='margin: 0 0 8px 0; color: #155724; font-weight: bold; font-size: 11px;'>✅ Your reschedule request has been approved!</p>
                                            <p style='margin: 0; color: #155724; font-size: 10px; line-height: 1.5;'>Your appointment has been successfully rescheduled to the new date and time below.</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Date Change Comparison -->
                        <tr>
                            <td style='padding: 20px 20px 10px 20px;'>
                                <h3 style='margin: 0 0 10px 0; color: #bd1e51; font-size: 14px; font-weight: bold; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px;'>APPOINTMENT SCHEDULE CHANGE</h3>
                                <table style='width: 100%; font-size: 11px;'>
                                    <tr>
                                        <td style='padding: 10px; background-color: #ffebee; border-radius: 5px; width: 48%;'>
                                            <p style='margin: 0 0 5px 0; color: #d32f2f; font-weight: bold; font-size: 10px;'>❌ Old Schedule:</p>
                                            <p style='margin: 3px 0; color: #555555; font-size: 10px;'><strong>Date:</strong> $oldDate</p>
                                            <p style='margin: 3px 0; color: #555555; font-size: 10px;'><strong>Time:</strong> $oldTime</p>
                                        </td>
                                        <td style='width: 4%;'></td>
                                        <td style='padding: 10px; background-color: #d4edda; border-radius: 5px; width: 48%;'>
                                            <p style='margin: 0 0 5px 0; color: #155724; font-weight: bold; font-size: 10px;'>✅ New Schedule:</p>
                                            <p style='margin: 3px 0; color: #155724; font-size: 10px;'><strong>Date:</strong> $date</p>
                                            <p style='margin: 3px 0; color: #155724; font-size: 10px;'><strong>Time:</strong> $time</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Vehicle Information -->
                        <tr>
                            <td style='padding: 20px 20px 10px 20px;'>
                                <h3 style='margin: 0 0 10px 0; color: #bd1e51; font-size: 14px; font-weight: bold; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px;'>VEHICLE DETAILS</h3>
                                <table style='width: 100%; font-size: 11px;'>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333; width: 35%;'>Plate Number:</td>
                                        <td style='padding: 5px 0; color: #555555;'>" . strtoupper($data['PlateNo']) . "</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Vehicle Type:</td>
                                        <td style='padding: 5px 0; color: #555555;'>{$data['VehicleTypeName']}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Brand:</td>
                                        <td style='padding: 5px 0; color: #555555;'>{$data['Brand']}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Branch:</td>
                                        <td style='padding: 5px 0; color: #555555;'>{$data['BranchName']}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Important Reminders -->
                        <tr>
                            <td style='padding: 10px 20px 20px 20px;'>
                                <table style='width: 100%; background-color: #e8f4f8; border-radius: 5px; padding: 15px;'>
                                    <tr>
                                        <td>
                                            <p style='margin: 0 0 10px 0; color: #17a2b8; font-weight: bold; font-size: 11px;'>📋 IMPORTANT REMINDERS</p>
                                            <p style='margin: 5px 0; color: #333333; font-size: 9px;'>• Please arrive 10 minutes before your scheduled time</p>
                                            <p style='margin: 5px 0; color: #333333; font-size: 9px;'>• Bring your vehicle registration and valid ID</p>
                                            <p style='margin: 5px 0; color: #333333; font-size: 9px;'>• Your payment has been transferred to the new schedule</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style='padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;'>
                                <p style='margin: 0 0 5px 0; color: #666666; font-size: 10px;'>Thank you for choosing AutoTEC!</p>
                                <p style='margin: 0; color: #999999; font-size: 9px;'>For inquiries, contact us at autotec_mandaluyong@yahoo.com or call 286527257</p>
                            </td>
                        </tr>
                        
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";
}

function generateRescheduleConfirmedPlainText($data, $refNum, $method, $amount, $date, $time, $fullName) {
    $oldDate = date('l, F d, Y', strtotime($data['Date']));
    $oldTime = formatTime($data['Time']);
    
    return "
AutoTEC - Emission Testing Center
✅ RESCHEDULE REQUEST APPROVED

Reference Number: $refNum
Status: RESCHEDULED
Payment Method: $method
Approved: " . date('m/d/Y g:i:s A') . "

✅ Your reschedule request has been approved!
Your appointment has been successfully rescheduled to the new date and time below.

APPOINTMENT SCHEDULE CHANGE
❌ Old Schedule:
Date: $oldDate
Time: $oldTime

✅ New Schedule:
Date: $date
Time: $time

VEHICLE DETAILS
Plate Number: " . strtoupper($data['PlateNo']) . "
Vehicle Type: {$data['VehicleTypeName']}
Brand: {$data['Brand']}
Branch: {$data['BranchName']}

📋 IMPORTANT REMINDERS
• Please arrive 10 minutes before your scheduled time
• Bring your vehicle registration and valid ID
• Your payment has been transferred to the new schedule

Thank you for choosing AutoTEC!
For inquiries, contact us at autotec_mandaluyong@yahoo.com or call 286527257
    ";
}

// ============================================================================
// RESCHEDULE DENIED EMAIL TEMPLATES
// ============================================================================

function generateRescheduleDeniedHTML($data, $refNum, $method, $amount, $date, $time, $generated, $fullName, $reason, $isGcash, $shouldForfeit) {
    $requestedDate = date('l, F d, Y', strtotime($data['NewDate']));
    $requestedTime = formatTime($data['NewTime']);
    
    // Payment forfeiture message
    $paymentMessage = '';
    $paymentBgColor = '#e7f3ff';
    $paymentTitle = 'ℹ️ PAYMENT STATUS';
    $paymentTitleColor = '#0c5460';
    
    if ($shouldForfeit) {
        $paymentMessage = "<p style='margin: 5px 0; color: #856404; font-size: 9px;'>• Your GCash payment of Php $amount has been <strong>forfeited</strong> due to late reschedule request</p>
           <p style='margin: 5px 0; color: #856404; font-size: 9px;'>• Reschedule requests must be made at least 1 day before the appointment</p>
           <p style='margin: 5px 0; color: #856404; font-size: 9px;'>• Payment forfeitures are non-reversible</p>";
        $paymentBgColor = '#fff3cd';
        $paymentTitle = '⚠️ PAYMENT FORFEITED';
        $paymentTitleColor = '#856404';
    } elseif ($isGcash) {
        $paymentMessage = "<p style='margin: 5px 0; color: #0c5460; font-size: 9px;'>• Your GCash payment of Php $amount has been <strong>refunded</strong></p>
           <p style='margin: 5px 0; color: #0c5460; font-size: 9px;'>• Please allow 3-5 business days for the refund to reflect</p>
           <p style='margin: 5px 0; color: #0c5460; font-size: 9px;'>• You may book a new appointment anytime</p>";
    } else {
        $paymentMessage = "<p style='margin: 5px 0; color: #0c5460; font-size: 9px;'>• No payment was made for this appointment</p>
           <p style='margin: 5px 0; color: #0c5460; font-size: 9px;'>• You may book a new appointment anytime</p>";
    }
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>AutoTEC Reschedule Denied</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
        <table role='presentation' style='width: 100%; border-collapse: collapse;'>
            <tr>
                <td style='padding: 20px 0;'>
                    <table role='presentation' style='width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);'>
                        
                        <!-- Header -->
                        <tr>
                            <td style='background-color: #bd1e51; padding: 30px 20px; text-align: left;'>
                                <h1 style='margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;'>AutoTEC</h1>
                                <p style='margin: 5px 0 0 0; color: #ffffff; font-size: 14px;'>Emission Testing Center</p>
                            </td>
                        </tr>
                        
                        <!-- Title -->
                        <tr>
                            <td style='padding: 30px 20px 10px 20px; text-align: center;'>
                                <h2 style='margin: 0; color: #d32f2f; font-size: 20px; font-weight: bold;'>❌ RESCHEDULE REQUEST DENIED</h2>
                            </td>
                        </tr>
                        
                        <!-- Reference Number -->
                        <tr>
                            <td style='padding: 0 20px;'>
                                <table style='width: 100%; background-color: #f8f9fa; border-radius: 5px; padding: 12px;'>
                                    <tr>
                                        <td style='font-weight: bold; color: #333333; font-size: 11px;'>Reference Number:</td>
                                        <td style='text-align: right; color: #bd1e51; font-weight: bold; font-size: 12px;'>$refNum</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Status -->
                        <tr>
                            <td style='padding: 10px 20px;'>
                                <table style='width: 100%; background-color: #ffebee; border-radius: 5px; padding: 10px;'>
                                    <tr>
                                        <td style='font-weight: bold; color: #333333; font-size: 10px;'>Status:</td>
                                        <td style='color: #d32f2f; font-weight: bold; font-size: 10px;'>DENIED</td>
                                        <td style='text-align: right; font-weight: bold; color: #333333; font-size: 10px;'>Payment Method:</td>
                                        <td style='text-align: right; color: #333333; font-size: 10px;'>$method</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Generated Date -->
                        <tr>
                            <td style='padding: 0 20px 20px 20px; text-align: right;'>
                                <p style='margin: 0; color: #666666; font-size: 9px;'>Processed: $generated</p>
                            </td>
                        </tr>
                        
                        <!-- Denial Reason -->
                        <tr>
                            <td style='padding: 20px;'>
                                <table style='width: 100%; background-color: #fff3cd; border-radius: 5px; padding: 15px; border-left: 4px solid #ffc107;'>
                                    <tr>
                                        <td>
                                            <p style='margin: 0 0 8px 0; color: #856404; font-weight: bold; font-size: 11px;'>Reason for Denial:</p>
                                            <p style='margin: 0; color: #856404; font-size: 10px; line-height: 1.5;'>" . ($reason ?: 'No reason provided') . "</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Schedule Comparison -->
                        <tr>
                            <td style='padding: 20px 20px 10px 20px;'>
                                <h3 style='margin: 0 0 10px 0; color: #bd1e51; font-size: 14px; font-weight: bold; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px;'>SCHEDULE STATUS</h3>
                                <table style='width: 100%; font-size: 11px;'>
                                    <tr>
                                        <td style='padding: 10px; background-color: #d4edda; border-radius: 5px; width: 48%;'>
                                            <p style='margin: 0 0 5px 0; color: #155724; font-weight: bold; font-size: 10px;'>✅ Current Schedule:</p>
                                            <p style='margin: 3px 0; color: #155724; font-size: 10px;'><strong>Date:</strong> $date</p>
                                            <p style='margin: 3px 0; color: #155724; font-size: 10px;'><strong>Time:</strong> $time</p>
                                        </td>
                                        <td style='width: 4%;'></td>
                                        <td style='padding: 10px; background-color: #ffebee; border-radius: 5px; width: 48%;'>
                                            <p style='margin: 0 0 5px 0; color: #d32f2f; font-weight: bold; font-size: 10px;'>❌ Requested (Denied):</p>
                                            <p style='margin: 3px 0; color: #d32f2f; font-size: 10px;'><strong>Date:</strong> $requestedDate</p>
                                            <p style='margin: 3px 0; color: #d32f2f; font-size: 10px;'><strong>Time:</strong> $requestedTime</p>
                                        </td>
                                    </tr>
                                </table>
                                <table style='width: 100%; background-color: #e8f4f8; border-radius: 5px; padding: 10px; margin-top: 10px;'>
                                    <tr>
                                        <td>
                                            <p style='margin: 0; color: #0c5460; font-size: 9px; line-height: 1.5;'>ℹ️ Your appointment remains scheduled at the original date and time above. Please arrive on time or cancel if you cannot attend.</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Vehicle Information -->
                        <tr>
                            <td style='padding: 20px 20px 10px 20px;'>
                                <h3 style='margin: 0 0 10px 0; color: #bd1e51; font-size: 14px; font-weight: bold; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px;'>VEHICLE DETAILS</h3>
                                <table style='width: 100%; font-size: 11px;'>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333; width: 35%;'>Plate Number:</td>
                                        <td style='padding: 5px 0; color: #555555;'>" . strtoupper($data['PlateNo']) . "</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Vehicle Type:</td>
                                        <td style='padding: 5px 0; color: #555555;'>{$data['VehicleTypeName']}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Brand:</td>
                                        <td style='padding: 5px 0; color: #555555;'>{$data['Brand']}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Branch:</td>
                                        <td style='padding: 5px 0; color: #555555;'>{$data['BranchName']}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Payment Information -->
                        <tr>
                            <td style='padding: 20px;'>
                                <table style='width: 100%; background-color: $paymentBgColor; border-radius: 5px; padding: 15px;'>
                                    <tr>
                                        <td>
                                            <p style='margin: 0 0 10px 0; color: $paymentTitleColor; font-weight: bold; font-size: 11px;'>$paymentTitle</p>
                                            $paymentMessage
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Next Steps -->
                        <tr>
                            <td style='padding: 10px 20px 20px 20px;'>
                                <table style='width: 100%; background-color: #e8f4f8; border-radius: 5px; padding: 15px;'>
                                    <tr>
                                        <td>
                                            <p style='margin: 0 0 10px 0; color: #17a2b8; font-weight: bold; font-size: 11px;'>WHAT HAPPENS NEXT?</p>
                                            <p style='margin: 5px 0; color: #333333; font-size: 9px;'>• Your original appointment remains active</p>
                                            <p style='margin: 5px 0; color: #333333; font-size: 9px;'>• Please arrive at the scheduled time: $date at $time</p>
                                            <p style='margin: 5px 0; color: #333333; font-size: 9px;'>• If you need to cancel, please do so at least 1 day in advance</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style='padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;'>
                                <p style='margin: 0 0 5px 0; color: #666666; font-size: 10px;'>We apologize for any inconvenience</p>
                                <p style='margin: 0; color: #999999; font-size: 9px;'>For inquiries, contact us at autotec_mandaluyong@yahoo.com or call 286527257</p>
                            </td>
                        </tr>
                        
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";
}

function generateRescheduleDeniedPlainText($data, $refNum, $method, $amount, $date, $time, $fullName, $reason, $isGcash, $shouldForfeit) {
    $requestedDate = date('l, F d, Y', strtotime($data['NewDate']));
    $requestedTime = formatTime($data['NewTime']);
    
    $paymentMessage = '';
    if ($shouldForfeit) {
        $paymentMessage = "⚠️ PAYMENT FORFEITED\n• Your GCash payment of Php $amount has been forfeited due to late reschedule request\n• Reschedule requests must be made at least 1 day before the appointment\n• Payment forfeitures are non-reversible";
    } elseif ($isGcash) {
        $paymentMessage = "ℹ️ PAYMENT STATUS\n• Your GCash payment of Php $amount has been refunded\n• Please allow 3-5 business days for the refund to reflect\n• You may book a new appointment anytime";
    } else {
        $paymentMessage = "ℹ️ PAYMENT STATUS\n• No payment was made for this appointment\n• You may book a new appointment anytime";
    }
    
    return "
AutoTEC - Emission Testing Center
❌ RESCHEDULE REQUEST DENIED

Reference Number: $refNum
Status: DENIED
Payment Method: $method
Processed: " . date('m/d/Y g:i:s A') . "

REASON FOR DENIAL:
" . ($reason ?: 'No reason provided') . "

SCHEDULE STATUS
✅ Current Schedule (Active):
Date: $date
Time: $time

❌ Requested Schedule (Denied):
Date: $requestedDate
Time: $requestedTime

ℹ️ Your appointment remains scheduled at the original date and time above. Please arrive on time or cancel if you cannot attend.

VEHICLE DETAILS
Plate Number: " . strtoupper($data['PlateNo']) . "
Vehicle Type: {$data['VehicleTypeName']}
Brand: {$data['Brand']}
Branch: {$data['BranchName']}

$paymentMessage

WHAT HAPPENS NEXT?
• Your original appointment remains active
• Please arrive at the scheduled time: $date at $time
• If you need to cancel, please do so at least 1 day in advance

We apologize for any inconvenience.
For inquiries, contact us at autotec_mandaluyong@yahoo.com or call 286527257
    ";
}
?>