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
    
    // Get username from session (matches login.php session variables)
    $username = $_SESSION['admin_username'] ?? 'Unknown Admin';
    $admin_branch = $_SESSION['branch_filter'] ?? null;

    if (!$reservation_id) {
        echo json_encode(['success' => false, 'message' => 'Missing reservation ID']);
        exit;
    }

    try {
        // Fetch reservation details with all needed information
        $stmt = $conn->prepare("
            SELECT r.*
            FROM reservations r
            WHERE r.ReservationID = ?
        ");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Reservation not found']);
            exit;
        }

        $reservation = $result->fetch_assoc();
        $stmt->close();
        
        // Update payment status to 'paid' when admin confirms
        $reservation['PaymentStatus'] = 'paid';
        
        // Fetch Vehicle Type Name - try different possible column names
        try {
            $stmt = $conn->prepare("SELECT Name FROM vehicle_types WHERE VehicleTypeID = ?");
            $stmt->bind_param("i", $reservation['TypeID']);
            $stmt->execute();
            $typeResult = $stmt->get_result();
            if ($typeRow = $typeResult->fetch_assoc()) {
                $reservation['VehicleTypeName'] = $typeRow['Name'];
            } else {
                $reservation['VehicleTypeName'] = 'N/A';
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error fetching vehicle type: " . $e->getMessage());
            $reservation['VehicleTypeName'] = 'N/A';
        }
        
        // Fetch Category Name - try different possible column names
        try {
            $stmt = $conn->prepare("SELECT Name FROM vehicle_categories WHERE CategoryID = ?");
            $stmt->bind_param("i", $reservation['CategoryID']);
            $stmt->execute();
            $catResult = $stmt->get_result();
            if ($catRow = $catResult->fetch_assoc()) {
                $reservation['CategoryName'] = $catRow['Name'];
            } else {
                $reservation['CategoryName'] = 'N/A';
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error fetching category: " . $e->getMessage());
            $reservation['CategoryName'] = 'N/A';
        }

        // Check if branch matches admin's branch (branch-specific access control)
        if ($admin_branch && $reservation['BranchName'] !== $admin_branch) {
            // Log unauthorized attempt
            logAction($username, 'Unauthorized Access', "Attempted to confirm reservation ID $reservation_id from {$reservation['BranchName']} but assigned to $admin_branch");
            
            echo json_encode([
                'success' => false, 
                'message' => 'You can only confirm reservations for your assigned branch (' . $admin_branch . ')'
            ]);
            exit;
        }

        // Prepare insert into completed table
        $sql_insert = "INSERT INTO completed (
            ReservationID, UserID, PlateNo, Brand, TypeID, CategoryID, 
            Fname, Lname, Mname, PhoneNum, Email, Date, Time, Address, BranchName, 
            PaymentMethod, PaymentStatus, PaymentReceipt, Price, ReferenceNumber, CreatedAt, CompletedAt
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param(
            "iissiiisssssssssssdss",
            $reservation['ReservationID'],
            $reservation['UserID'],
            $reservation['PlateNo'],
            $reservation['Brand'],
            $reservation['TypeID'],
            $reservation['CategoryID'],
            $reservation['Fname'],
            $reservation['Lname'],
            $reservation['Mname'],
            $reservation['PhoneNum'],
            $reservation['Email'],
            $reservation['Date'],
            $reservation['Time'],
            $reservation['Address'],
            $reservation['BranchName'],
            $reservation['PaymentMethod'],
            $reservation['PaymentStatus'],
            $reservation['PaymentReceipt'],
            $reservation['Price'],
            $reservation['ReferenceNumber'],
            $reservation['CreatedAt']
        );

        if (!$stmt_insert->execute()) {
            throw new Exception("Insert to completed failed: " . $stmt_insert->error);
        }
        $stmt_insert->close();

        // Delete from reservations table
        $stmt_delete = $conn->prepare("DELETE FROM reservations WHERE ReservationID = ?");
        $stmt_delete->bind_param("i", $reservation_id);
        
        if (!$stmt_delete->execute()) {
            throw new Exception("Delete from reservations failed: " . $stmt_delete->error);
        }
        $stmt_delete->close();

        // 📧 Send confirmation email to customer
        $emailSent = sendConfirmationEmail($reservation);
        
        // 🧾 Log the audit trail
        $customerName = trim($reservation['Fname'] . ' ' . $reservation['Lname']);
        logConfirmReservation($username, $reservation_id, $customerName);
        
        // Log email sending status
        if ($emailSent) {
            logAction($username, 'Email Sent', "Confirmation email sent to {$reservation['Email']} for reservation ID $reservation_id");
        } else {
            logAction($username, 'Email Failed', "Failed to send confirmation email to {$reservation['Email']} for reservation ID $reservation_id");
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Reservation confirmed and email sent successfully.',
            'reservation_id' => $reservation_id,
            'customer' => $customerName,
            'email_sent' => $emailSent
        ]);
        
    } catch (Exception $e) {
        // Log error to audit trail
        logAction($username, 'Error', "Failed to confirm reservation ID $reservation_id: " . $e->getMessage());
        
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

// ============================================================================
// EMAIL FUNCTIONS (Using SendGrid Web API v3)
// ============================================================================

function sendConfirmationEmail($data) {
    // Get credentials from environment
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
    $paymentStatus = strtoupper($data['PaymentStatus'] ?: 'VERIFIED');
    $paymentMethod = $data['PaymentMethod'] === 'gcash' ? 'GCash' : 'On-Site';
    $amount = number_format($data['Price'], 2);
    $formattedDate = date('l, F d, Y', strtotime($data['Date']));
    $formattedTime = formatTime($data['Time']);
    $generatedDate = date('m/d/Y g:i:s A');
    $fullName = trim(($data['Mname'] ? $data['Fname'] . ' ' . $data['Mname'] . ' ' . $data['Lname'] : $data['Fname'] . ' ' . $data['Lname']));
    
    // Generate email content
    $htmlContent = generateEmailHTML(
        $data, 
        $referenceNumber, 
        $paymentStatus, 
        $paymentMethod, 
        $amount, 
        $formattedDate, 
        $formattedTime, 
        $generatedDate,
        $fullName
    );
    
    $textContent = generatePlainText(
        $data, 
        $referenceNumber, 
        $paymentStatus, 
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
                'subject' => 'Appointment Confirmed - AutoTEC Emission Testing'
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
        // Success - SendGrid accepted the email
        return true;
    } else {
        // Error - log details
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

function generateEmailHTML($data, $refNum, $status, $method, $amount, $date, $time, $generated, $fullName) {
    $statusColor = ($status === 'VERIFIED' || $status === 'PAID') ? '#d4edda' : '#fff3cd';
    $statusTextColor = ($status === 'VERIFIED' || $status === 'PAID') ? '#155724' : '#856404';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>AutoTEC Receipt</title>
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
                                <h2 style='margin: 0; color: #333333; font-size: 20px; font-weight: bold;'>APPOINTMENT CONFIRMED</h2>
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
                        
                        <!-- Payment Status -->
                        <tr>
                            <td style='padding: 10px 20px;'>
                                <table style='width: 100%; background-color: $statusColor; border-radius: 5px; padding: 10px;'>
                                    <tr>
                                        <td style='font-weight: bold; color: #333333; font-size: 10px;'>Payment Status:</td>
                                        <td style='color: $statusTextColor; font-weight: bold; font-size: 10px;'>$status</td>
                                        <td style='text-align: right; font-weight: bold; color: #333333; font-size: 10px;'>Payment Method:</td>
                                        <td style='text-align: right; color: #333333; font-size: 10px;'>$method</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Generated Date -->
                        <tr>
                            <td style='padding: 0 20px 20px 20px; text-align: right;'>
                                <p style='margin: 0; color: #666666; font-size: 9px;'>Generated: $generated</p>
                            </td>
                        </tr>
                        
                        <!-- Vehicle Information -->
                        <tr>
                            <td style='padding: 20px 20px 10px 20px;'>
                                <h3 style='margin: 0 0 10px 0; color: #bd1e51; font-size: 14px; font-weight: bold; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px;'>VEHICLE INFORMATION</h3>
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
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Category:</td>
                                        <td style='padding: 5px 0; color: #555555;'>{$data['CategoryName']}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Owner Information -->
                        <tr>
                            <td style='padding: 20px 20px 10px 20px;'>
                                <h3 style='margin: 0 0 10px 0; color: #bd1e51; font-size: 14px; font-weight: bold; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px;'>OWNER INFORMATION</h3>
                                <table style='width: 100%; font-size: 11px;'>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333; width: 35%;'>Full Name:</td>
                                        <td style='padding: 5px 0; color: #555555;'>$fullName</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Contact Number:</td>
                                        <td style='padding: 5px 0; color: #555555;'>{$data['PhoneNum']}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Email Address:</td>
                                        <td style='padding: 5px 0; color: #555555;'>{$data['Email']}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Address:</td>
                                        <td style='padding: 5px 0; color: #555555;'>{$data['Address']}</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Appointment Details -->
                        <tr>
                            <td style='padding: 20px 20px 10px 20px;'>
                                <h3 style='margin: 0 0 10px 0; color: #bd1e51; font-size: 14px; font-weight: bold; border-bottom: 2px solid #e0e0e0; padding-bottom: 5px;'>APPOINTMENT DETAILS</h3>
                                <table style='width: 100%; font-size: 11px;'>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333; width: 35%;'>Branch:</td>
                                        <td style='padding: 5px 0; color: #555555;'>{$data['BranchName']}</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Date:</td>
                                        <td style='padding: 5px 0; color: #555555;'>$date</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Time:</td>
                                        <td style='padding: 5px 0; color: #555555;'>$time</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px 0; font-weight: bold; color: #333333;'>Duration:</td>
                                        <td style='padding: 5px 0; color: #555555;'>Approximately 20 minutes</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Fee Summary -->
                        <tr>
                            <td style='padding: 20px 20px 10px 20px;'>
                                <table style='width: 100%; background-color: #f8f9fa; border-radius: 5px; padding: 15px;'>
                                    <tr>
                                        <td style='font-weight: bold; color: #bd1e51; font-size: 14px; padding-bottom: 10px;' colspan='2'>FEE SUMMARY</td>
                                    </tr>
                                    <tr>
                                        <td style='color: #333333; font-size: 11px;'>{$data['VehicleTypeName']} Emission Testing</td>
                                        <td style='text-align: right; font-weight: bold; color: #333333; font-size: 13px;'>Php $amount</td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Confirmation Message -->
                        <tr>
                            <td style='padding: 20px;'>
                                <table style='width: 100%; background-color: #d4edda; border-radius: 5px; padding: 15px;'>
                                    <tr>
                                        <td>
                                            <p style='margin: 0 0 10px 0; color: #155724; font-weight: bold; font-size: 11px;'>✓ PAYMENT CONFIRMED</p>
                                            <p style='margin: 5px 0; color: #155724; font-size: 9px;'>• Your payment has been verified and confirmed</p>
                                            <p style='margin: 5px 0; color: #155724; font-size: 9px;'>• Your appointment is now confirmed and ready</p>
                                            <p style='margin: 5px 0; color: #155724; font-size: 9px;'>• Please arrive on time for your scheduled appointment</p>
                                        </td>
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
                                            <p style='margin: 0 0 10px 0; color: #17a2b8; font-weight: bold; font-size: 11px;'>IMPORTANT REMINDERS:</p>
                                            <p style='margin: 5px 0; color: #333333; font-size: 9px;'>• Please arrive 15 minutes before your scheduled time</p>
                                            <p style='margin: 5px 0; color: #333333; font-size: 9px;'>• Bring this receipt and your vehicle registration documents</p>
                                            <p style='margin: 5px 0; color: #333333; font-size: 9px;'>• Vehicle must be physically present for testing</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style='padding: 20px; text-align: center; border-top: 1px solid #e0e0e0;'>
                                <p style='margin: 0 0 5px 0; color: #666666; font-size: 10px; font-style: italic;'>Thank you for choosing AutoTEC Emission Testing Center</p>
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

function generatePlainText($data, $refNum, $status, $method, $amount, $date, $time, $fullName) {
    return "
AutoTEC - Emission Testing Center
APPOINTMENT CONFIRMED

Reference Number: $refNum
Payment Status: $status
Payment Method: $method
Generated: " . date('m/d/Y g:i:s A') . "

VEHICLE INFORMATION
Plate Number: " . strtoupper($data['PlateNo']) . "
Vehicle Type: {$data['VehicleTypeName']}
Brand: {$data['Brand']}
Category: {$data['CategoryName']}

OWNER INFORMATION
Full Name: $fullName
Contact Number: {$data['PhoneNum']}
Email Address: {$data['Email']}
Address: {$data['Address']}

APPOINTMENT DETAILS
Branch: {$data['BranchName']}
Date: $date
Time: $time
Duration: Approximately 20 minutes

FEE SUMMARY
{$data['VehicleTypeName']} Emission Testing: Php $amount

✓ PAYMENT CONFIRMED
• Your payment has been verified and confirmed
• Your appointment is now confirmed and ready
• Please arrive on time for your scheduled appointment

IMPORTANT REMINDERS:
• Please arrive 15 minutes before your scheduled time
• Bring this receipt and your vehicle registration documents
• Vehicle must be physically present for testing

Thank you for choosing AutoTEC Emission Testing Center
For inquiries, contact us at autotec_mandaluyong@yahoo.com or call 286527257
    ";
}
?>