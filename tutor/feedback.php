<?php
// tutor/feedback.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$tutor_id = $_SESSION['user_id'];
$feedbacks = [];

try {
    // Joint query targeting both ratings integers and text feedback entries
    $stmt = $pdo->prepare("SELECT f.comments, r.rating, u.name as student_name, b.unit_code, b.booking_date FROM feedback f JOIN ratings r ON f.booking_id = r.booking_id JOIN users u ON f.learner_id = u.id JOIN bookings b ON f.booking_id = b.id WHERE f.tutor_id = ? ORDER BY b.booking_date DESC");
    $stmt->execute([$tutor_id]);
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Feedback index query breakdown: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Evaluations Logs - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); padding: 40px; margin:0; }
        .stage-box { max-width: 800px; margin: 0 auto; background: white; padding: 35px; border-radius: 8px; border: 1px solid var(--border); }
        .feedback-item { border-bottom: 1px solid var(--border); padding: 20px 0; }
        .feedback-item:last-child { border-bottom: none; }
        .stars { color: #f59e0b; font-weight: bold; font-size: 16px; }
    </style>
</head>
<body>

    <div class="stage-box">
        <p><a href="dashboard.php" style="color: var(--slate); text-decoration: none;">← Back to Command Hub</a></p>
        <h2>Received Ratings & Evaluations Metric</h2>
        <p style="color: var(--slate); margin-bottom: 30px;">Your overall platform weight calculations are modified by real-time incoming student evaluations.</p>

        <?php if(empty($feedbacks)): ?>
            <p style="color: var(--slate); font-style: italic;">No student performance metrics or structural evaluations recorded yet.</p>
        <?php else: ?>
            <?php foreach($feedbacks as $f): ?>
                <div class="feedback-item">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div>
                            <strong style="color: var(--navy);"><?php echo htmlspecialchars($f['student_name']); ?></strong>
                            <span style="color: var(--slate); font-size: 13px;"> on Unit: <strong><?php echo htmlspecialchars($f['unit_code']); ?></strong></span>
                        </div>
                        <span class="stars">★ <?php echo htmlspecialchars($f['rating']); ?>.0</span>
                    </div>
                    <p style="margin: 5px 0; font-size: 14px; line-height: 1.5; color: #334155;">"<?php echo htmlspecialchars($f['comments']); ?>"</p>
                    <small style="color: #94a3b8;">Class Session Date: <?php echo $f['booking_date']; ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>