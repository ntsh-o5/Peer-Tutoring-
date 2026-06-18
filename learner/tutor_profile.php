<?php
// learner/tutor_profile.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

$tutor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tutor = null;
$feedbacks = [];

try {
    // Fetch profile elements
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND LOWER(role) = 'tutor'");
    $stmt->execute([$tutor_id]);
    $tutor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tutor) {
        // Fetch matching feedback records
        $fStmt = $pdo->prepare("SELECT f.comments, r.rating, u.name as learner_name FROM feedback f JOIN ratings r ON f.booking_id = r.booking_id JOIN users u ON f.learner_id = u.id WHERE f.tutor_id = ?");
        $fStmt->execute([$tutor_id]);
        $feedbacks = $fStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Profile retrieval fault: " . $e->getMessage());
}

if (!$tutor) {
    die("Tutor profile parameter missing or invalid.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($tutor['name']); ?> Profile - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { display: flex; min-height: 100vh; background: var(--light); margin: 0; font-family: 'Segoe UI', sans-serif; }
        .main-stage { flex: 1; padding: 40px; max-width: 800px; }
        .profile-container { background: white; padding: 30px; border-radius: 8px; border: 1px solid var(--border); }
        .btn { background: var(--navy); color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; }
        .review-card { border-top: 1px solid var(--border); padding-top: 15px; margin-top: 15px; }
        .form-row { margin-bottom: 15px; display: flex; flex-direction: column; gap: 5px; }
    </style>
</head>
<body>
    <div class="main-stage">
        <div style="margin-bottom: 20px;"><a href="search_tutors.php" style="color: var(--slate); text-decoration: none;">← Back to Search</a></div>
        
        <div class="profile-container">
            <h2><?php echo htmlspecialchars($tutor['name']); ?></h2>
            <p style="color: var(--slate);">Contact Signature: <?php echo htmlspecialchars($tutor['email']); ?></p>
            <hr style="border: 0; border-top: 1px solid var(--border); margin: 20px 0;">

            <h3>Schedule / Book a Session</h3>
            <!-- Booking action maps to bookings processing system -->
            <form method="POST" action="bookings.php?action=create">
                <input type="hidden" name="tutor_id" value="<?php echo $tutor['id']; ?>">
                <div class="form-row">
                    <label style="font-weight: 500;">Select Unit of Study Code</label>
                    <input type="text" name="unit_code" placeholder="e.g. ICS 2201" required style="padding: 10px; border: 1px solid var(--border); border-radius: 4px;">
                </div>
                <div class="form-row">
                    <label style="font-weight: 500;">Target Appointment Date & Time Baseline</label>
                    <input type="datetime-local" name="booking_date" required style="padding: 10px; border: 1px solid var(--border); border-radius: 4px;">
                </div>
                <button type="submit" class="btn">Confirm & Book Tutor Slot</button>
            </form>

            <h3 style="margin-top: 40px;">Historical Peer Evaluations</h3>
            <?php if (empty($feedbacks)): ?>
                <p style="color: var(--slate); font-size: 14px;">No reviews logged for this peer instructor yet.</p>
            <?php else: ?>
                <?php foreach ($feedbacks as $review): ?>
                    <div class="review-card">
                        <strong style="color: #f59e0b;">★ <?php echo htmlspecialchars($review['rating']); ?>.0</strong>
                        <p style="margin: 5px 0; font-size: 14px;"><?php echo htmlspecialchars($review['comments']); ?></p>
                        <small style="color: var(--slate);">Submitted by: <?php echo htmlspecialchars($review['learner_name']); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>