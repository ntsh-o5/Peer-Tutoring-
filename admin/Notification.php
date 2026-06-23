<?php
// admin/notifications.php
// =========================================================================
// 1. SESSION & AUTHENTICATION INITIALIZATION
// =========================================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Strict security layer gate for administrative files
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

/**
 * Class Notification
 * Handles data mapping and status delivery definitions for systemic alerts.
 */
class Notification {

    // =========================================================================
    // PROPERTY DECLARATIONS WITH TYPE HINTING
    // =========================================================================
    private ?int $id = null; // Aligns with serial auto-increment in PostgreSQL
    private int $userID;
    private ?int $bookingID;
    private string $title;
    private string $message;
    private string $status;
    private string $createdAt;

    /**
     * Notification constructor.
     * @param int $userID
     * @param string $title
     * @param string $message
     * @param int|null $bookingID
     */
    public function __construct(int $userID, string $title, string $message, ?int $bookingID = null) {
        $this->userID = $userID;
        $this->title = htmlspecialchars(trim($title));
        $this->message = htmlspecialchars(trim($message)); // Defend against persistent XSS
        $this->bookingID = $bookingID;
        $this->status = 'Unread';
        $this->createdAt = date('Y-m-d H:i:s');
    }

    // =========================================================================
    // CORE FUNCTIONAL METHODS (Active Records)
    // =========================================================================

    /**
     * Persists notification state into the system PostgreSQL database.
     * @param PDO $pdo
     * @return bool
     */
    public function sendNotification(PDO $pdo): bool {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, booking_id, title, message, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $this->userID,
                $this->bookingID,
                $this->title,
                $this->message,
                $this->status,
                $this->createdAt
            ]);
        } catch (PDOException $e) {
            error_log("Notification dispatch crash: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // GETTERS & ENCAPSULATION ACCESSORS
    // =========================================================================

    public function getID(): ?int { return $this->id; }
    public function getUserID(): int { return $this->userID; }
    public function getBookingID(): ?int { return $this->bookingID; }
    public function getTitle(): string { return $this->title; }
    public function getMessage(): string { return $this->message; }
    public function getStatus(): string { return $this->status; }
    public function getCreatedAt(): string { return $this->createdAt; }
}

// =========================================================================
// 2. ADMINISTRATIVE LOGIC INTERACTION MATRIX (Processing view panel context)
// =========================================================================
$system_users = [];
$broadcast_status = "";

try {
    // Process form submissions when admin pushes a custom application alert
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'broadcast') {
        $target_user = (int)$_POST['user_id'];
        $alert_title = $_POST['title'];
        $alert_msg = $_POST['message'];

        if (!empty($alert_title) && !empty($alert_msg)) {
            $notification = new Notification($target_user, $alert_title, $alert_msg);
            if ($notification->sendNotification($pdo)) {
                $broadcast_status = "success";
            } else {
                $broadcast_status = "failure";
            }
        }
    }

    // Grab target users to populate choice selectors inside the template dropdown
    $userQuery = $pdo->query("SELECT id, name, user_role FROM users WHERE LOWER(user_role) != 'admin' ORDER BY name ASC");
    $system_users = $userQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Notification view loader error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Notifications - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

   <?php include('../includes/sidebar.php'); ?>

    <div class="main-content">

        <header>
            <h1>System Broadcast Dispatches</h1>
        </header>

        <?php if ($broadcast_status === "success"): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 600;">
                ✓ Operational notification payload successfully delivered to user timeline channels.
            </div>
        <?php elseif ($broadcast_status === "failure"): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 600;">
                ✗ Critical anomaly. Verification sequence could not complete database entry transaction parameters.
            </div>
        <?php endif; ?>

        <div class="table-container" style="max-width: 700px; padding: 25px;">
            <h2>Draft Alert Payload</h2>
            <p style="color:#475569; font-size: 14px; margin-top:-5px; margin-bottom: 20px;">Issue instantaneous messages down through user-specific dashboard timelines.</p>
            
            <form method="POST" action="notifications.php" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="action" value="broadcast">

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600; color: #0f2038; font-size:14px;">Select Target User Profile:</label>
                    <select name="user_id" required style="padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; outline:none; background:#fff;">
                        <?php foreach ($system_users as $usr): ?>
                            <option value="<?php echo $usr['id']; ?>">
                                <?php echo htmlspecialchars($usr['name']) . " (" . ucfirst(htmlspecialchars($usr['user_role'])) . ")"; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600; color: #0f2038; font-size:14px;">Notification Title Header:</label>
                    <input type="text" name="title" required placeholder="e.g. Schedule Verification Audit Update" style="padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; outline:none;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600; color: #0f2038; font-size:14px;">Detailed Timeline Message Body:</label>
                    <textarea name="message" required rows="4" placeholder="Type out full content constraints..." style="padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; outline:none; resize: vertical; font-family:inherit;"></textarea>
                </div>

                <button type="submit" class="approve-btn" style="padding: 12px; font-weight: bold; border-radius: 6px; align-self: flex-start; cursor: pointer;">
                    🚀 Transmit Notification
                </button>
            </form>
        </div>

    </div>

</body>
</html>