<?php
/**
 * Class Notification
 * Handles data mapping and status delivery definitions for systemic alerts.
 */
class Notification {

    // =========================================================================
    // PROPERTY DECLARATIONS WITH TYPE HINTING
    // =========================================================================
    private string $notificationID;
    private int $userID;
    private string $message;
    private string $status;
    private string $createdAt;

    /**
     * Notification constructor.
     * * @param string $notificationID
     * @param int $userID
     * @param string $message
     */
    public function __construct(string $notificationID, int $userID, string $message) {
        $this->notificationID = $notificationID;
        $this->userID = $userID;
        $this->message = htmlspecialchars($message); // Defend against persistent XSS
        $this->status = 'Unread';
        $this->createdAt = date('Y-m-d H:i:s');
    }

    // =========================================================================
    // CORE FUNCTIONAL METHODS
    // =========================================================================

    /**
     * Simulates notification state commitment.
     * Returns true if successfully handled.
     * * @return bool
     */
    public function sendNotification(): bool {
        // In production, execute your database insert or email push routine here:
        // $stmt = $pdo->prepare("INSERT INTO notifications ... ");
        // return $stmt->execute([...]);
        
        return true; 
    }

    // =========================================================================
    // GETTERS & ENCAPSULATION ACCESSORS
    // =========================================================================

    public function getNotificationID(): string {
        return $this->notificationID;
    }

    public function getUserID(): int {
        return $this->userID;
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function getStatus(): string {
        return $this->status;
    }
    
    public function getCreatedAt(): string {
        return $this->createdAt;
    }
}
?>