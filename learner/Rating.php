<?php
// learner/rating.php
session_start();

if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$learner_id = $_SESSION['user_id'];
$success_message = "";
$error_message = "";

// Capture incoming specific session context if provided
$booking_id = isset($_REQUEST['booking_id']) ? (int)$_REQUEST['booking_id'] : 0;

// Process feedback form submission natively
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'], $_POST['comments']) && $booking_id > 0) {
    $rating = (int)$_POST['rating'];
    $comments = trim($_POST['comments']);

    try {
        $pdo->beginTransaction();

        // First, fetch tutor_id linked to this booking to populate our schema requirements
        $lookup_stmt = $pdo->prepare("SELECT tutor_id FROM bookings WHERE id = ? AND learner_id = ?");
        $lookup_stmt->execute([$booking_id, $learner_id]);
        $booking_info = $lookup_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking_info) {
            throw new Exception("Invalid session authorization mapping.");
        }

        $tutor_id = $booking_info['tutor_id'];

        // 1. Insert numeric score into 'ratings' table (Ref: image_e681a5.png)
        $rate_stmt = $pdo->prepare("INSERT INTO ratings (booking_id, learner_id, tutor_id, rating, created_at) VALUES (?, ?, ?, ?, NOW())");
        $rate_stmt->execute([$booking_id, $learner_id, $tutor_id, $rating]);

        // 2. Insert written comments into 'feedback' table (Ref: image_e68229.png)
        $feed_stmt = $pdo->prepare("INSERT INTO feedback (booking_id, learner_id, tutor_id, comments, created_at) VALUES (?, ?, ?, ?, NOW())");
        $feed_stmt->execute([$booking_id, $learner_id, $tutor_id, $comments]);

        // 3. Mark the booking as reviewed
        $update = $pdo->prepare("UPDATE bookings SET status = 'reviewed' WHERE id = ? AND learner_id = ?");
        $update->execute([$booking_id, $learner_id]);

        $pdo->commit();
        $success_message = "Your evaluation and feedback have been successfully saved across historical nodes!";
        $booking_id = 0; // Clear active form context
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Failed to submit review infrastructure: " . $e->getMessage();
    }
}

// Fetch focal metadata if a booking target is active
$target_session = null;
if ($booking_id > 0) {
    $stmt = $pdo->prepare("
        SELECT b.id, b.unit_code, u.name as tutor_name 
        FROM bookings b
        JOIN users u ON b.tutor_id = u.id
        WHERE b.id = ? AND b.learner_id = ?
    ");
    $stmt->execute([$booking_id, $learner_id]);
    $target_session = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch review choices/history by joining the split structures dynamically
$review_history = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.rating, f.comments, r.created_at, b.unit_code, u.name AS tutor_name
        FROM ratings r
        JOIN feedback f ON r.booking_id = f.booking_id
        JOIN bookings b ON r.booking_id = b.id
        JOIN users u ON b.tutor_id = u.id
        WHERE r.learner_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$learner_id]);
    $review_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("History logging display error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ratings & Reviews Terminal - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --light: #f8fafc; --border: #e2e8f0; --slate: #475569; --warning: #f59e0b; }
        body { background: var(--light); font-family: 'Segoe UI', sans-serif; padding: 40px; margin: 0; }
        .wrapper { max-width: 900px; margin: 0 auto; }
        .card { background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; display: flex; flex-direction: column; gap: 6px; }
        label { font-size: 13px; font-weight: 600; color: var(--slate); }
        select, textarea { padding: 10px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; width: 100%; box-sizing: border-box; }
        .btn-submit { background: var(--navy); color: white; padding: 12px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; width: 100%; }
        .review-row { border-bottom: 1px solid var(--border); padding: 15px 0; }
        .review-row:last-child { border-bottom: none; }
        .stars { color: var(--warning); font-weight: bold; }
    </style>
</head>
<body>
    <div class="wrapper">
        <p><a href="bookings.php" style="color: var(--slate); text-decoration: none; font-weight: 600;">← Back to My Bookings</a></p>

        <?php if (!empty($success_message)): ?>
            <div style="padding: 12px; background: #dcfce7; color: #166534; margin-bottom: 20px; border-radius: 4px;"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div style="padding: 12px; background: #fee2e2; color: #b91c1c; margin-bottom: 20px; border-radius: 4px;"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- DYNAMIC EVALUATION FORM SECTION -->
        <?php if ($target_session): ?>
            <div class="card">
                <h2 style="color: var(--navy); margin-top: 0;">Submit Session Evaluation</h2>
                <p style="color: var(--slate); font-size: 14px;">Evaluating session for study unit <strong><?php echo htmlspecialchars($target_session['unit_code']); ?></strong> with Tutor <strong><?php echo htmlspecialchars($target_session['tutor_name']); ?></strong>.</p>
                
                <form method="POST" action="">
                    <input type="hidden" name="booking_id" value="<?php echo $target_session['id']; ?>">
                    
                    <div class="form-group">
                        <label for="rating">Score Assignment Metric</label>
                        <select name="rating" id="rating" required>
                            <option value="5">★★★★★ - Outstanding Performance</option>
                            <option value="4">★★★★☆ - Great Guidance</option>
                            <option value="3">★★★☆☆ - Met Standards</option>
                            <option value="2">★★☆☆☆ - Needs Work</option>
                            <option value="1">★☆☆☆☆ - Poor Quality</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="comments">Constructive Assessment Comments</label>
                        <textarea name="comments" id="comments" rows="4" placeholder="How went your learning horizon?..." required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Save Feedback Metric</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- RATINGS HISTORY PANEL -->
        <div class="card">
            <h2 style="color: var(--navy); margin-top: 0;">Your Choices & Submitted Feedback</h2>
            <?php if (empty($review_history)): ?>
                <p style="color: var(--slate); font-style: italic; text-align: center; padding: 20px 0;">You have not logged evaluations or score choices yet.</p>
            <?php else: ?>
                <?php foreach ($review_history as $rev): ?>
                    <div class="review-row">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="stars"><?php echo str_repeat("★", $rev['rating']) . str_repeat("☆", 5 - $rev['rating']); ?></span>
                            <small style="color: var(--slate);"><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></small>
                        </div>
                        <p style="margin: 6px 0; font-size: 14px; color: var(--navy);">
                            Unit <strong><?php echo htmlspecialchars($rev['unit_code']); ?></strong> Study Session — Tutor: <strong><?php echo htmlspecialchars($rev['tutor_name']); ?></strong>
                        </p>
                        <p style="margin: 0; font-size: 13px; color: var(--slate); font-style: italic;">
                            "<?php echo htmlspecialchars($rev['comments']); ?>"
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>