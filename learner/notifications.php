<?php
// learner/notifications.php
session_start();

// Strict security gate for Learners
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$learner_id = $_SESSION['user_id'];

try {
    // 1. Mark unread notifications as read automatically upon accessing the feed (PostgreSQL BOOLEAN syntax)
    $updateStmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
    $updateStmt->execute([$learner_id]);

    // 2. Fetch notification records ordered by newest alerts first
    $stmt = $pdo->prepare("SELECT title, message, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$learner_id]);
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Notification extraction layer anomaly: " . $e->getMessage());
    $alerts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Pipeline Notifications - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { background: var(--light); font-family: 'Segoe UI', sans-serif; padding: 40px; margin: 0; }
        .stage { max-width: 700px; margin: 0 auto; }
        .alert-card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 20px; margin-bottom: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.01); border-left: 4px solid #cbd5e1; position: relative; }
        /* Light green dynamic accent highlight for unread status alerts */
        .alert-card.new { border-left-color: #3b82f6; background: #f0fdf4; }
        .alert-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px; }
        .alert-title { font-weight: bold; color: var(--navy); margin: 0; font-size: 15px; }
        .alert-time { font-size: 12px; color: var(--slate); }
        .alert-msg { margin: 5px 0 0 0; color: #334155; font-size: 14px; line-height: 1.5; }
        .unread-badge { background: #3b82f6; color: white; font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 10px; text-transform: uppercase; }
    </style>
</head>
<body>

    <div class="stage">
        <div style="margin-bottom: 20px;"><a href="dashboard.php" style="color: var(--slate); text-decoration: none; font-weight: 600;">← Back to Dashboard Hub</a></div>
        <h2 style="color: var(--navy); margin-bottom: 5px;">Active Alerts & Pipeline Notifications</h2>
        <p style="color: var(--slate); font-size: 14px; margin-top: 0; margin-bottom: 30px;">Track scheduling confirmations, tutor validation metrics, and performance review logs updates.</p>

        <?php if (empty($alerts)): ?>
            <div style="text-align: center; padding: 40px; background: white; border: 1px solid var(--border); border-radius: 8px; color: var(--slate); font-style: italic;">
                Your system workspace pipeline is currently completely clear. No new notifications.
            </div>
        <?php else: ?>
            <?php foreach ($alerts as $a): 
                // Flexible check for PostgreSQL true/false boolean variants (native bool or string representation)
                $is_unread = ($a['is_read'] === false || $a['is_read'] === 'f' || $a['is_read'] == 0);
            ?>
                <div class="alert-card <?php echo $is_unread ? 'new' : ''; ?>">
                    <div class="alert-meta">
                        <h4 class="alert-title"><?php echo htmlspecialchars($a['title']); ?></h4>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php if ($is_unread): ?>
                                <span class="unread-badge">New</span>
                            <?php endif; ?>
                            <span class="alert-time"><?php echo date('M d, Y - H:i', strtotime($a['created_at'])); ?></span>
                        </div>
                    </div>
                    <p class="alert-msg"><?php echo htmlspecialchars($a['message']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>