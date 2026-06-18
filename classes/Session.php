<?php

class Session {

    private $sessionID;
    private $bookingID;
    private $sessionDate;
    private $progressReport;

    public function __construct($sessionID, $bookingID, $sessionDate) {
        $this->sessionID = $sessionID;
        $this->bookingID = $bookingID;
        $this->sessionDate = $sessionDate;
    }

    public function submitProgressReport($report) {
        $this->progressReport = $report;
    }

    public function getProgressReport() {
        return $this->progressReport;
    }

    public function completeSession() {
        echo "Session completed.";
    }
}

?>