<?php
include '../db.php';

/**
 * Get client IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? null;
}

function logAction($username, $action, $description) {
    global $conn;
    $ip = getClientIP();
    
    $stmt = $conn->prepare("INSERT INTO audit_trail (name, action, description, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $username, $action, $description, $ip);
    $stmt->execute();
    $stmt->close();
}

/* ============================================================
   ACCOUNT ACTIVITIES
   ============================================================ */
function logLogin($username) {
    logAction($username, 'Login', "$username logged into the system.");
}

function logLogout($username) {
    logAction($username, 'Logout', "$username logged out of the system.");
}

/* ============================================================
   RESERVATION & RESCHEDULE ACTIVITIES
   ============================================================ */
function logConfirmReservation($username, $reservationID, $customerName) {
    logAction($username, 'Confirm Reservation', "Confirmed reservation ID $reservationID for customer $customerName.");
}

function logConfirmReschedule($username, $rescheduleID, $customerName) {
    logAction($username, 'Rescheduled', "Rescheduled reservation ID $rescheduleID for customer $customerName.");
}

function logCancelReservation($username, $reservationID, $customerName) {
    $desc = "Cancelled reservation ID $reservationID for customer $customerName.";
    logAction($username, 'Cancel Reservation', $desc);
}

function logDeniedRequest($username, $rescheduleID, $customerName) {
    $desc = "Denied resechedule ID $rescheduleID for customer $customerName.";
    logAction($username, 'Denied Reschedule Request', $desc);
}


/* ============================================================
   HOMEPAGE ACTIVITIES
   ============================================================ */
function logServiceImage($username, $serviceImage) {
    logAction($username, 'Update Service', "Updated service: $serviceImage");
}

function logAnnouncementImage($username) {
    logAction($username, 'Update Announcement', "Updated announcement/promotion content.");
}

function logHomepageUpdate($username, $section) {
    logAction($username, 'Update Homepage', "Updated homepage section: $section");
}

/* ============================================================
   ABOUT US ACTIVITIES
   ============================================================ */
function logAboutUsImage($username, $branch) {
    logAction($username, 'Update About Us Image', "Updated branch image for $branch");
}

function logAboutUsMap($username, $branch){
    logAction($username, 'Update About Us Map link', "Updated Maplink for $branch");
}


?>