<?php

class Booking {

    private $bookingID;
    private $learnerID;
    private $tutorID;
    private $bookingDate;
    private $status;

    public function __construct($bookingID, $learnerID, $tutorID, $bookingDate) {
        $this->bookingID = $bookingID;
        $this->learnerID = $learnerID;
        $this->tutorID = $tutorID;
        $this->bookingDate = $bookingDate;
        $this->status = "Pending";
    }

    public function createBooking() {
        echo "Booking created.";
    }

    public function approveBooking() {
        $this->status = "Approved";
    }

    public function rejectBooking() {
        $this->status = "Rejected";
    }

    public function getStatus() {
        return $this->status;
    }
}

?>