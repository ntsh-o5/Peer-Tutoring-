<?php
// tutor/feedback.php
session_start();

// Strict security layer gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$tutor_id = $_SESSION['user_id'];
$tutor_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Tutor';
$feedbacks = [];

try {
    // Patched column mapping (b.booking_id) and forced constraint scoping (b.tutor_id)
    $stmt = $pdo->prepare("
        SELECT f.comments, r.rating, u.name as student_name, b.unit_code, b.booking_date 
        FROM feedback f 
        JOIN ratings r ON f.booking_id = r.booking_id 
        JOIN users u ON f.learner_id = u.id 
        JOIN bookings b ON f.booking_id = b.id 
        WHERE b.tutor_id = ? 
        ORDER BY b.booking_date DESC
    ");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluations Logs - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); padding: 40px; margin: 0; display: flex; min-height: 100vh; }
        .main-stage { flex: 1; max-width: 900px; margin: 0 auto; }
        .stage-box { background: white; padding: 35px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--border); padding-bottom: 20px; }
        .feedback-item { border-bottom: 1px solid var(--border); padding: 20px 0; }
        .feedback-item:last-child { border-bottom: none; }
        .stars { color: #f59e0b; font-weight: bold; font-size: 15px; background: #fef3c7; padding: 4px 10px; border-radius: 12px; }
    </style>
</head>
<body>

    <div class="main-stage">
        <header>
            <div>
                <h1 style="margin: 0; font-size: 24px; color: var(--navy);">Evaluations Received</h1>
                <p style="margin: 5px 0 0 0; color: var(--slate);">Instructor: <strong><?php echo $tutor_name; ?></strong></p>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="dashboard.php" style="color: var(--slate); text-decoration: none; font-weight: 600; font-size: 13px;">← Dashboard Hub</a>
                <a href="../auth/logout.php" style="background: #fee2e2; color: #b91c1c; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">🚪 Terminate Session</a>
            </div>
        </header>

        <div class="stage-box">
            <h3 style="color: var(--navy); margin-top: 0; margin-bottom: 10px;">Reviews & Performance Metrics</h3>
            <p style="color: var(--slate); font-size: 14px; margin-bottom: 25px; margin-top: 0;">
                Your overall ranking metrics are calculated using real-time student evaluation submissions.
            </p>

            <?php if(empty($feedbacks)): ?>
                <p style="color: var(--slate); font-style: italic; padding: 20px 0; text-align: center;">No student performance metrics or evaluations recorded yet.</p>
            <?php else: ?>
                <?php foreach($feedbacks as $f): ?>
                    <div class="feedback-item">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <div>
                                <strong style="color: var(--navy); font-size: 15px;"><?php echo htmlspecialchars($f['student_name']); ?></strong>
                                <span style="color: var(--slate); font-size: 13px;"> on Course Unit: <strong style="color: var(--navy);"><?php echo htmlspecialchars($f['unit_code']); ?></strong></span>
                            </div>
                            <span class="stars">
                                <?php 
                                $rating_val = (int)$f['rating'];
                                echo str_repeat('★', $rating_val) . str_repeat('☆', 5 - $rating_val);
                                echo " (" . $rating_val . ".0)";
                                ?>
                            </span>
                        </div>
                        <p style="margin: 8px 0; font-size: 14px; line-height: 1.6; color: #334155; font-style: italic; background: #f8fafc; padding: 12px; border-radius: 6px; border-left: 3px solid var(--border);">
                            "<?php echo htmlspecialchars($f['comments']); ?>"
                        </p>
                        <div style="margin-top: 8px; font-size: 12px; color: #94a3b8; font-weight: 500;">
                            Class Concluded: <?php echo date('M d, Y', strtotime($f['booking_date'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>