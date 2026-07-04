<?php

class Payment {

    private $paymentID;
    private $amount;
    private $paymentDate;
    private $status;

    public function __construct($paymentID, $amount, $paymentDate) {
        $this->paymentID = $paymentID;
        $this->amount = $amount;
        $this->paymentDate = $paymentDate;
        $this->status = "Pending";
    }

    public function processPayment() {
        $this->status = "Paid";
    }

    public function getStatus() {
        return $this->status;
    }
}

?>