<?php
include 'db.php';

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
function logLogin($username, $branch ) {
    logAction($username, 'Login', "$username logged into the $branch system.");
}

function logLogout($username, $branch) {
    logAction($username, 'Logout', "$username logged out of the $branch system.");
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

function logCancelReservation($username, $reservationID, $customerName, $reason = '') {
    $reasonText = !empty($reason) ? " Reason: $reason" : "";
    $desc = "Cancelled reservation ID $reservationID for customer $customerName.$reasonText";
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

function logAboutUsDesc($username, $branch) {
    logAction($username, 'Update About Us Description', "Updated branch description for $branch");
}

function logAboutUsMap($username, $branch){
    logAction($username, 'Update About Us Map link', "Updated Maplink for $branch");
}

/* ============================================================
 Reservation edit page  ACTIVITIES
   ============================================================ */

function logAddVehicleType($username, $vehicletype){
    logAction($username, 'Add Vehicle Type', "Added new $vehicletype");
}

function logRemoveVehicleType($username, $vehicletype){
    logAction($username, 'Remove Vehicle Type', "Removed vehicle type $vehicletype");
}

function logVehiclePrice($username, $vehicleprice){
    logAction($username, 'Edit Vehicle Price', "Added new Price for vehicle type $vehicleprice");
}

function logGcashQR($username, $branch){
    logAction($username, 'Update GcashQR', "Update Gcash QR code for $branch");
}

/* ============================================================
   CONTACT US ACTIVITIES
   ============================================================ */
function logContactUsRead($username, $customerName) {
    logAction($username, 'Marked as Read', "Marked as Read a Feedback for $customerName");
}

function logContactUsUnread($username, $customerName) {
    logAction($username, 'Marked as Unread', "Marked as Unread a Feedback for $customerName");
}

function logContactUsAll($username, $customerName) {
    logAction($username, 'Marked as All Read', "Marked as Read all the Feedback for $customerName");
}
?>