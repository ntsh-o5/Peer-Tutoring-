<?php
// learner/rating.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if ($booking_id === 0) {
    die("Booking reference key context required.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Tutor Evaluation - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --light: #f8fafc; --border: #e2e8f0; }
        body { background: var(--light); font-family: 'Segoe UI', sans-serif; padding: 40px; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: 0 auto; border: 1px solid var(--border); }
        .form-row { margin-bottom: 20px; display: flex; flex-direction: column; gap: 8px; }
        select, textarea { padding: 12px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; width: 100%; box-sizing: border-box; }
        .btn { background: var(--navy); color: white; padding: 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Rate Session Experience</h2>
        <p style="color: #475569; font-size: 14px; margin-bottom: 25px;">Your feedback helps maintain quality across the PeerTutor platform.</p>
        
        <!-- Action target dispatches payload directly into feedback.php processing module -->
        <form method="POST" action="feedback.php">
            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
            
            <div class="form-row">
                <label style="font-weight: 600;">Assign Metric Score</label>
                <select name="rating" required>
                    <option value="5">Excellent Performance (5 Stars)</option>
                    <option value="4">Good Support (4 Stars)</option>
                    <option value="3">Satisfactory standard (3 Stars)</option>
                    <option value="2">Suboptimal execution (2 Stars)</option>
                    <option value="1">Unacceptable service (1 Star)</option>
                </select>
            </div>

            <div class="form-row">
                <label style="font-weight: 600;">Constructive Review Comments</label>
                <textarea name="comments" rows="5" placeholder="Share details about your learning experience..." required></textarea>
            </div>

            <button type="submit" class="btn" style="width: 100%;">Commit Evaluation & Feedback</button>
        </form>
    </div>
</body>
</html>