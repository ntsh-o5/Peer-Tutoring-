<?php
// learner/rating.php
session_start();

// Strict security gate for Learners
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$learner_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if ($booking_id === 0) {
    die("Booking reference key context required.");
}

// Security Boundary Check: Ensure this booking actually belongs to the active learner
try {
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.unit_code, u.name as tutor_name 
        FROM bookings b
        JOIN users u ON b.tutor_id = u.id
        WHERE b.booking_id = ? AND b.learner_id = ?
    ");
    $stmt->execute([$booking_id, $learner_id]);
    $session_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session_details) {
        die("Error: Access denied or invalid booking parameter context mapping.");
    }
} catch (PDOException $e) {
    error_log("Rating validation anomaly: " . $e->getMessage());
    die("A critical internal data pipeline layer failure occurred.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Tutor Evaluation - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --light: #f8fafc; --border: #e2e8f0; --slate: #475569; }
        body { background: var(--light); font-family: 'Segoe UI', sans-serif; padding: 40px; margin: 0; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: 40px auto; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        .form-row { margin-bottom: 20px; display: flex; flex-direction: column; gap: 8px; }
        .form-row label { font-size: 13px; font-weight: 600; color: var(--slate); }
        select, textarea { padding: 12px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; width: 100%; box-sizing: border-box; }
        select:focus, textarea:focus { outline: none; border-color: var(--navy); }
        .btn { background: var(--navy); color: white; padding: 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px; transition: background 0.2s; }
        .btn:hover { background: #173154; }
    </style>
</head>
<body>
    <div class="container">
        <p><a href="bookings.php" style="color: var(--slate); text-decoration: none; font-size: 14px; font-weight: 600;">← Cancel Evaluation</a></p>
        <h2 style="color: var(--navy); margin-top: 10px;">Rate Session Experience</h2>
        <p style="color: var(--slate); font-size: 14px; margin-bottom: 25px;">
            Share your experience for unit <strong><?php echo htmlspecialchars($session_details['unit_code']); ?></strong> with Coach <strong><?php echo htmlspecialchars($session_details['tutor_name']); ?></strong>.
        </p>
        
        <form method="POST" action="feedback.php">
            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
            
            <div class="form-row">
                <label for="rating">Assign Metric Score</label>
                <select name="rating" id="rating" required>
                    <option value="5">★★★★★ - Excellent Performance</option>
                    <option value="4">★★★★☆ - Good Support</option>
                    <option value="3">★★★☆☆ - Satisfactory Standard</option>
                    <option value="2">★★☆☆☆ - Suboptimal Execution</option>
                    <option value="1">★☆☆☆☆ - Unacceptable Service</option>
                </select>
            </div>

            <div class="form-row">
                <label for="comments">Constructive Review Comments</label>
                <textarea name="comments" id="comments" rows="5" placeholder="Share details about your learning experience..." required></textarea>
            </div>

            <button type="submit" class="btn" style="width: 100%;">Commit Evaluation & Feedback</button>
        </form>
    </div>
</body>
</html>