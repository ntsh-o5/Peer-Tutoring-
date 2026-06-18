<?php
// learner/bookings.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$learner_id = $_SESSION['user_id'];
$message = '';

// Handle actions (Create, Cancel, Reschedule)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if ($_GET['action'] === 'create') {
        $tutor_id = (int)$_POST['tutor_id'];
        $unit_code = trim($_POST['unit_code']);
        $booking_date = $_POST['booking_date'];

        try {
            $stmt = $pdo->prepare("INSERT INTO bookings (learner_id, tutor_id, unit_code, booking_date, status) VALUES (?, ?, ?, ?, 'scheduled')");
            $stmt->execute([$learner_id, $tutor_id, $unit_code, $booking_date]);
            $message = "Session pipeline provisioned successfully!";
        } catch (PDOException $e) {
            $message = "Booking transaction error: " . $e->getMessage();
        }
    }
    
    if ($_GET['action'] === 'cancel') {
        $booking_id = (int)$_POST['booking_id'];
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND learner_id = ?");
            $stmt->execute([$booking_id, $learner_id]);
            $message = "Session successfully cancelled.";
        } catch (PDOException $e) {
            $message = "Cancellation fault: " . $e->getMessage();
        }
    }

    if ($_GET['action'] === 'reschedule') {
        $booking_id = (int)$_POST['booking_id'];
        $new_date = $_POST['new_booking_date'];
        try {
            $stmt = $pdo->prepare("UPDATE bookings SET booking_date = ?, status = 'scheduled' WHERE id = ? AND learner_id = ?");
            $stmt->execute([$new_date, $booking_id, $learner_id]);
            $message = "Session date metrics shifted flawlessly.";
        } catch (PDOException $e) {
            $message = "Rescheduling fault: " . $e->getMessage();
        }
    }
}

// Fetch general history stack
$history = [];
try {
    $stmt = $pdo->prepare("SELECT b.id, b.unit_code, b.booking_date, b.status, u.name as tutor_name FROM bookings b JOIN users u ON b.tutor_id = u.id WHERE b.learner_id = ? ORDER BY b.booking_date DESC");
    $stmt->execute([$learner_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("History logging fetch errors: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bookings - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { display: flex; min-height: 100vh; background: var(--light); margin: 0; font-family: 'Segoe UI', sans-serif; }
        .main-stage { flex: 1; padding: 40px; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; border: 1px solid var(--border); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #edf2f7; color: var(--navy); }
        .alert { background: #e0f2fe; color: #0369a1; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
        .btn-sm { padding: 6px 12px; font-size: 12px; border-radius: 4px; border: none; cursor: pointer; color: white; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="main-stage">
        <div style="margin-bottom: 20px;"><a href="dashboard.php" style="color: var(--slate); text-decoration: none;">← Back to Dashboard</a></div>
        <h2>Your Booking Log Framework</h2>

        <?php if (!empty($message)): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Tutor Name</th>
                    <th>Unit Code</th>
                    <th>Date Matrix Allocation</th>
                    <th>Current Status</th>
                    <th>Runtime Controls</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $row): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['tutor_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['unit_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['booking_date']); ?></td>
                        <td><span style="font-weight: 600; text-transform: uppercase; font-size: 11px;"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        <td>
                            <?php if ($row['status'] === 'scheduled'): ?>
                                <!-- inline simple modification triggers -->
                                <form method="POST" action="bookings.php?action=reschedule" style="display:inline-block;">
                                    <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                                    <input type="datetime-local" name="new_booking_date" required style="padding:4px; font-size:11px;">
                                    <button type="submit" class="btn-sm" style="background: #64748b;">Shift</button>
                                </form>
                                <form method="POST" action="bookings.php?action=cancel" style="display:inline-block;" onsubmit="return confirm('Cancel session?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn-sm" style="background: #ef4444;">Cancel</button>
                                </form>
                            <?php elseif ($row['status'] === 'completed'): ?>
                                <a href="rating.php?booking_id=<?php echo $row['id']; ?>" class="btn-sm" style="background: #f59e0b;">Review Feedback</a>
                            <?php else: ?>
                                <span style="color: var(--slate); font-size: 12px;">No Actions Available</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>