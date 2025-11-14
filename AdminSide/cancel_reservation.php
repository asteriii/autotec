<?php
session_start();

require_once '../db.php';
require_once 'audit_trail.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = $_POST['reservation_id'] ?? null;
    $reason = $_POST['reason'] ?? '';
    
    // Get username and branch from session
    $username = $_SESSION['admin_username'] ?? 'Unknown Admin';
    $admin_branch = $_SESSION['branch_filter'] ?? null;

    if (!$reservation_id) {
        echo json_encode(['success' => false, 'message' => 'Missing reservation ID']);
        exit;
    }

    try {
        // Create PDO connection for this operation
        $servername = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
        $db_username = getenv('MYSQLUSER') ?: 'root';
        $db_password = getenv('MYSQLPASSWORD') ?: 'OUJHNoEzFNhsIgRFuduLzLFWunvvMrrP';
        $dbname = getenv('MYSQLDATABASE') ?: 'railway';
        $port = getenv('MYSQLPORT') ?: '3306';

        $pdo = new PDO(
            "mysql:host=$servername;dbname=$dbname;port=$port;charset=utf8mb4",
            $db_username,
            $db_password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        // Fetch the reservation record with additional info
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE ReservationID = ?");
        $stmt->execute([$reservation_id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$res) {
            echo json_encode(['success' => false, 'message' => 'Reservation not found']);
            exit;
        }

        // Fetch Vehicle Type Name
        try {
            $stmt = $pdo->prepare("SELECT Name FROM vehicle_types WHERE VehicleTypeID = ?");
            $stmt->execute([$res['TypeID']]);
            $typeRow = $stmt->fetch();
            $res['VehicleTypeName'] = $typeRow ? $typeRow['Name'] : 'N/A';
        } catch (Exception $e) {
            $res['VehicleTypeName'] = 'N/A';
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

        // Check if branch matches admin's branch (branch-specific access control)
        if ($admin_branch && $res['BranchName'] !== $admin_branch) {
            logAction($username, 'Unauthorized Access', "Attempted to cancel reservation ID $reservation_id from {$res['BranchName']} but assigned to $admin_branch");
            
            echo json_encode([
                'success' => false, 
                'message' => 'You can only cancel reservations for your assigned branch (' . $admin_branch . ')'
            ]);
            exit;
        }

        // Insert into canceled table with Reason included
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
            ':Date' => $res['Date'],
            ':Time' => $res['Time'],
            ':Address' => $res['Address'],
            ':BranchName' => $res['BranchName'],
            ':PaymentMethod' => $res['PaymentMethod'],
            ':PaymentStatus' => $res['PaymentStatus'],
            ':PaymentReceipt' => $res['PaymentReceipt'],
            ':Price' => $res['Price'],
            ':ReferenceNumber' => $res['ReferenceNumber'],
            ':CreatedAt' => $res['CreatedAt'],
            ':Reason' => $reason
        ]);

        // Delete the reservation
        $delete = $pdo->prepare("DELETE FROM reservations WHERE ReservationID = ?");
        $delete->execute([$reservation_id]);

        // 📧 Send cancellation email to customer
        $emailSent = sendCancellationEmail($res, $reason);

        // 🧾 Log the audit trail with reason included
        $customerName = trim($res['Fname'] . ' ' . $res['Lname']);
        logCancelReservation($username, $reservation_id, $customerName, $reason);
        
        // Log email sending status
        if ($emailSent) {
            logAction($username, 'Email Sent', "Cancellation email sent to {$res['Email']} for reservation ID $reservation_id");
        } else {
            logAction($username, 'Email Failed', "Failed to send cancellation email to {$res['Email']} for reservation ID $reservation_id");
        }

        echo json_encode([
            'success' => true,
            'message' => 'Reservation cancelled and email sent successfully.',
            'reservation_id' => $reservation_id,
            'customer' => $customerName,
            'email_sent' => $emailSent
        ]);

    } catch (Exception $e) {
        logAction($username, 'Error', "Failed to cancel reservation ID $reservation_id: " . $e->getMessage());
        
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

// ============================================================================
// EMAIL FUNCTIONS (Using SendGrid Web API v3)
// ============================================================================

function sendCancellationEmail($data, $reason) {
    $apiKey = getenv('SENDGRID_API_KEY');
    $fromEmail = getenv('SMTP_FROM_EMAIL');
    
    if (!$fromEmail) {
        $fromEmail = 'noreply@autotec.com';
    }
    
    if (!$apiKey) {
        error_log("SendGrid API key not found");
        return false;
    }
    
    // Format data
    $referenceNumber = $data['ReferenceNumber'] ?: 'AT-' . $data['ReservationID'];
    $paymentMethod = $data['PaymentMethod'] === 'gcash' ? 'GCash' : 'On-Site';
    $amount = number_format($data['Price'], 2);
    $formattedDate = date('l, F d, Y', strtotime($data['Date']));
    $formattedTime = formatTime($data['Time']);
    $generatedDate = date('m/d/Y g:i:s A');
    $fullName = trim(($data['Mname'] ? $data['Fname'] . ' ' . $data['Mname'] . ' ' . $data['Lname'] : $data['Fname'] . ' ' . $data['Lname']));
    
    // Determine payment message based on method
    $isGCash = ($data['PaymentMethod'] === 'gcash');
    
    // Generate email content
    $htmlContent = generateCancellationEmailHTML(
        $data, 
        $referenceNumber, 
        $paymentMethod, 
        $amount, 
        $formattedDate, 
        $formattedTime, 
        $generatedDate,
        $fullName,
        $reason,
        $isGCash
    );
    
    $textContent = generateCancellationPlainText(
        $data, 
        $referenceNumber, 
        $paymentMethod, 
        $amount, 
        $formattedDate, 
        $formattedTime,
        $fullName,
        $reason,
        $isGCash
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
                'subject' => 'Appointment Cancelled - AutoTEC Emission Testing'
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
    
    // Send via SendGrid Web API
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
    
    // Check response
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

function generateCancellationEmailHTML($data, $refNum, $method, $amount, $date, $time, $generated, $fullName, $reason, $isGCash) {
    // Payment refund message
    $paymentMessage = $isGCash 
        ? "<p style='margin: 5px 0; color: #856404; font-size: 9px;'>• Your GCash payment of Php $amount has been <strong>forfeited</strong> and will not be refunded</p>
           <p style='margin: 5px 0; color: #856404; font-size: 9px;'>• Payment forfeitures are non-reversible</p>"
        : "<p style='margin: 5px 0; color: #856404; font-size: 9px;'>• No payment was made for this appointment</p>
           <p style='margin: 5px 0; color: #856404; font-size: 9px;'>• You may book a new appointment anytime</p>";
    
    $paymentBgColor = $isGCash ? '#fff3cd' : '#e7f3ff';
    $paymentTitle = $isGCash ? '⚠️ PAYMENT FORFEITED' : 'ℹ️ NO PAYMENT REQUIRED';
    $paymentTitleColor = $isGCash ? '#856404' : '#0c5460';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>AutoTEC Cancellation Notice</title>
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
                                <h2 style='margin: 0; color: #d32f2f; font-size: 20px; font-weight: bold;'>APPOINTMENT CANCELLED</h2>
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
                                        <td style='color: #d32f2f; font-weight: bold; font-size: 10px;'>CANCELLED</td>
                                        <td style='text-align: right; font-weight: bold; color: #333333; font-size: 10px;'>Payment Method:</td>
                                        <td style='text-align: right; color: #333333; font-size: 10px;'>$method</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Generated Date -->
                        <tr>
                            <td style='padding: 0 20px 20px 20px; text-align: right;'>
                                <p style='margin: 0; color: #666666; font-size: 9px;'>Cancelled: $generated</p>
                            </td>
                        </tr>
                        
                        <!-- Cancellation Reason -->
                        <tr>
                            <td style='padding: 20px;'>
                                <table style='width: 100%; background-color: #fff3cd; border-radius: 5px; padding: 15px; border-left: 4px solid #ffc107;'>
                                    <tr>
                                        <td>
                                            <p style='margin: 0 0 8px 0; color: #856404; font-weight: bold; font-size: 11px;'>Reason for Cancellation:</p>
                                            <p style='margin: 0; color: #856404; font-size: 10px; line-height: 1.5;'>" . ($reason ?: 'No reason provided') . "</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Vehicle Information -->
                        <tr>
                            <td style='padding: 20px 20px 10px 20px;'>
                                <h3 style='margin: 0 0 10px 0; color: #bd1e51; font-size: 14px; font-weight: bold; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px;'>CANCELLED APPOINTMENT DETAILS</h3>
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
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Scheduled Date:</td>
                                        <td style='padding: 5px 0; color: #555555;'>$date</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Scheduled Time:</td>
                                        <td style='padding: 5px 0; color: #555555;'>$time</td>
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
                                            <p style='margin: 0 0 10px 0; color: #17a2b8; font-weight: bold; font-size: 11px;'>NEED TO BOOK AGAIN?</p>
                                            <p style='margin: 5px 0; color: #333333; font-size: 9px;'>• Visit our website to schedule a new appointment</p>
                                            <p style='margin: 5px 0; color: #333333; font-size: 9px;'>• Choose a convenient date and time</p>
                                            <p style='margin: 5px 0; color: #333333; font-size: 9px;'>• We look forward to serving you again</p>
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

function generateCancellationPlainText($data, $refNum, $method, $amount, $date, $time, $fullName, $reason, $isGCash) {
    $paymentMessage = $isGCash 
        ? "⚠️ PAYMENT FORFEITED\n• Your GCash payment of Php $amount has been forfeited and will not be refunded\n• Payment forfeitures are non-reversible"
        : "ℹ️ NO PAYMENT REQUIRED\n• No payment was made for this appointment\n• You may book a new appointment anytime";
    
    return "
AutoTEC - Emission Testing Center
APPOINTMENT CANCELLED

Reference Number: $refNum
Status: CANCELLED
Payment Method: $method
Cancelled: " . date('m/d/Y g:i:s A') . "

REASON FOR CANCELLATION:
" . ($reason ?: 'No reason provided') . "

CANCELLED APPOINTMENT DETAILS
Plate Number: " . strtoupper($data['PlateNo']) . "
Vehicle Type: {$data['VehicleTypeName']}
Brand: {$data['Brand']}
Branch: {$data['BranchName']}
Scheduled Date: $date
Scheduled Time: $time

$paymentMessage

NEED TO BOOK AGAIN?
• Visit our website to schedule a new appointment
• Choose a convenient date and time
• We look forward to serving you again

We apologize for any inconvenience.
For inquiries, contact us at autotec_mandaluyong@yahoo.com or call 286527257
    ";
}
?>