<?php
// classes/Admin.php
require_once 'User.php';

class Admin extends User {
    
    // Explicitly pass role value back up to the parent container constructor
    public function __construct($id, $name, $email) {
        parent::__construct($id, $name, $email, 'admin');
    }

    /**
     * Admin specific domain method
     * Updates booking status paths safely using encapsulated object rules
     */
    public function updateBookingStatus($bookingId, $status, $pdo) {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
        return $stmt->execute([$status, $bookingId]);
    }
}