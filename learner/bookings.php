<?php
// learner/bookings.php
session_start();

// Security Gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$learner_id = $_SESSION['user_id'];
$message = '';

// Handle cancel/reschedule actions if needed post-form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if ($_GET['action'] === 'cancel') {
        $b_id = (int)$_POST['booking_id'];
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND learner_id = ?");
            $stmt->execute([$b_id, $learner_id]);
            $message = "Session successfully cancelled.";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch all bookings for this learner
$bookings = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.id AS booking_id, b.unit_code, b.booking_date, b.status, b.progress, u.name AS tutor_name 
        FROM bookings b 
        JOIN users u ON b.tutor_id = u.id 
        WHERE b.learner_id = ? 
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$learner_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fetch bookings failure: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; --warning: #f59e0b; --danger: #ef4444; }
        body { background: var(--light); font-family: 'Segoe UI', sans-serif; padding: 40px; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; border: 1px solid var(--border); }
        .header-section { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid var(--border); padding-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px; border-bottom: 1px solid var(--border); font-size: 14px; }
        th { background: #edf2f7; color: var(--navy); }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .badge.approved, .badge.completed { background: #d1fae5; color: #065f46; }
        .badge.pending, .badge.ongoing { background: #fef3c7; color: #92400e; }
        .badge.cancelled { background: #fee2e2; color: #991b1b; }
        .btn-review { background: var(--warning); color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div style="margin-bottom: 20px; display: flex; justify-content: space-between;">
            <a href="dashboard.php" style="color: var(--slate); text-decoration: none; font-weight: 600;">← Dashboard</a>
            <a href="rating.php" style="color: var(--navy); text-decoration: none; font-weight: 600;">⭐ View Past Feedback Logs</a>
        </div>

        <div class="header-section">
            <h2 style="color: var(--navy); margin: 0;">Your Booking Logistics Hub</h2>
        </div>

        <?php if (!empty($message)): ?>
            <div style="padding: 12px; background: #e0f2fe; color: #0369a1; margin-bottom: 20px; border-radius: 6px;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Tutor Name</th>
                    <th>Unit Code</th>
                    <th>Scheduled Date & Time</th>
                    <th>Approval Status</th>
                    <th>Session Progress</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 25px; color: var(--slate);">No bookings recorded yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($bookings as $row): ?>
                        <?php 
                        $status = strtolower(trim($row['status']));
                        $progress = strtolower(trim($row['progress']));
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['tutor_name']); ?></strong></td>
                            <td><span style="font-weight: 600; color: var(--navy);"><?php echo htmlspecialchars($row['unit_code']); ?></span></td>
                            <td><?php echo date('F d, Y \a\t H:i', strtotime($row['booking_date'])); ?></td>
                            <td><span class="badge <?php echo $status; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td><span class="badge <?php echo $progress; ?>"><?php echo htmlspecialchars($row['progress']); ?></span></td>
                            <td>
                                <?php if ($progress === 'completed' && $status !== 'reviewed'): ?>
                                    <a href="rating.php?booking_id=<?php echo $row['booking_id']; ?>" class="btn-review">Rate Tutor</a>
                                <?php elseif ($status === 'reviewed'): ?>
                                    <span style="color: var(--slate); font-style: italic; font-size: 13px;">Feedback Submitted</span>
                                <?php elseif ($status === 'pending' || $status === 'approved'): ?>
                                    <form method="POST" action="bookings.php?action=cancel" onsubmit="return confirm('Cancel this session?');" style="display:inline;">
                                        <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                                        <button type="submit" style="background: var(--danger); color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px;">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--slate); font-size: 13px;">Closed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>