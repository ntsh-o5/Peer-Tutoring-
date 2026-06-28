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
$message_type = 'success';

// Handle actions (Create, Cancel, Reschedule)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if ($_GET['action'] === 'create') {
        $tutor_id = (int)$_POST['tutor_id'];
        $unit_code = strtoupper(trim($_POST['unit_code']));
        $booking_date = $_POST['booking_date'];

        try {
            // Using 'pending' status value to synchronize perfectly with your tracking schemas
            $stmt = $pdo->prepare("INSERT INTO bookings (learner_id, tutor_id, unit_code, booking_date, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$learner_id, $tutor_id, $unit_code, $booking_date]);
            $message = "Session pipeline provisioned successfully! Awaiting tutor approval.";
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = "Booking transaction error: " . $e->getMessage();
            $message_type = 'error';
        }
    }
    
    if ($_GET['action'] === 'cancel') {
        $booking_id = (int)$_POST['booking_id'];
        try {
            // Reconciled structural reference identifier column to match table structure: id
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND learner_id = ?");
            $stmt->execute([$booking_id, $learner_id]);
            $message = "Session successfully cancelled.";
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = "Cancellation fault: " . $e->getMessage();
            $message_type = 'error';
        }
    }

    if ($_GET['action'] === 'reschedule') {
        $booking_id = (int)$_POST['booking_id'];
        $new_date = $_POST['new_booking_date'];
        try {
            // Reset to pending so the tutor re-verifies the newly shifted date matrix
            $stmt = $pdo->prepare("UPDATE bookings SET booking_date = ?, status = 'pending' WHERE id = ? AND learner_id = ?");
            $stmt->execute([$new_date, $booking_id, $learner_id]);
            $message = "Session date metrics shifted flawlessly! Re-sent for approval.";
            $message_type = 'success';
        } catch (PDOException $e) {
            $message = "Rescheduling fault: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Fetch general history stack
$history = [];
try {
    // Reconciled table selection reference: b.id as booking_id
    $stmt = $pdo->prepare("
        SELECT b.id as booking_id, b.unit_code, b.booking_date, b.status, u.name as tutor_name 
        FROM bookings b 
        JOIN users u ON b.tutor_id = u.id 
        WHERE b.learner_id = ? 
        ORDER BY b.booking_date DESC
    ");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { 
            --navy: #0f2038; 
            --slate: #475569; 
            --light: #f8fafc; 
            --border: #e2e8f0; 
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        body { background: var(--light); margin: 0; font-family: 'Segoe UI', Arial, sans-serif; padding: 40px; }
        .container { max-width: 1100px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        
        h2 { color: var(--navy); margin-top: 0; margin-bottom: 25px; border-bottom: 2px solid var(--border); padding-bottom: 15px; }
        
        table { width: 100%; border-collapse: collapse; background: white; margin-bottom: 20px; text-align: left; }
        th, td { padding: 14px; border-bottom: 1px solid var(--border); font-size: 14px; vertical-align: middle; }
        th { background: #edf2f7; color: var(--navy); font-weight: 600; }
        
        .alert { padding: 12px; margin-bottom: 25px; border-radius: 6px; font-size: 14px; font-weight: 500; }
        .alert.success { background: #dcfce7; color: #166534; }
        .alert.error { background: #fee2e2; color: #b91c1c; }
        
        .btn-sm { padding: 6px 12px; font-size: 12px; border-radius: 4px; border: none; cursor: pointer; color: white; text-decoration: none; font-weight: bold; display: inline-block; transition: opacity 0.2s; }
        .btn-sm:hover { opacity: 0.9; }
        
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .status-badge.approved, .status-badge.confirmed { background: #d1fae5; color: #065f46; }
        .status-badge.pending { background: #fef3c7; color: #92400e; }
        .status-badge.completed { background: #f1f5f9; color: #334155; }
        .status-badge.cancelled, .status-badge.rejected, .status-badge.denied { background: #fee2e2; color: #991b1b; }
        
        .action-form { display: inline-block; margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="dashboard.php" style="color: var(--slate); text-decoration: none; font-weight: 600; font-size: 14px;">← Back to Dashboard Hub</a>
        </div>
        
        <h2>Your Booking Log Framework</h2>

        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
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
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--slate); font-style: italic; padding: 25px;">You haven't requested any tutor sessions yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $row): ?>
                        <?php $status = strtolower(trim($row['status'])); ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['tutor_name']); ?></strong></td>
                            <td><span style="font-weight: 600; color: var(--navy);"><?php echo htmlspecialchars($row['unit_code']); ?></span></td>
                            <td><?php echo date('F d, Y \a\t H:i', strtotime($row['booking_date'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $status; ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($status === 'pending' || $status === 'approved' || $status === 'confirmed'): ?>
                                    <form method="POST" action="bookings.php?action=reschedule" class="action-form">
                                        <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                                        <input type="datetime-local" name="new_booking_date" required style="padding: 5px; font-size: 12px; border: 1px solid var(--border); border-radius: 4px;">
                                        <button type="submit" class="btn-sm" style="background: var(--slate);">Reschedule</button>
                                    </form>
                                    
                                    <form method="POST" action="bookings.php?action=cancel" class="action-form" style="margin-left: 5px;" onsubmit="return confirm('Are you sure you want to cancel this peer session?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                                        <button type="submit" class="btn-sm" style="background: var(--danger);">Cancel</button>
                                    </form>
                                <?php elseif ($status === 'completed'): ?>
                                    <a href="ratings.php?booking_id=<?php echo $row['booking_id']; ?>" class="btn-sm" style="background: var(--warning);">Leave Review</a>
                                <?php else: ?>
                                    <span style="color: var(--slate); font-size: 13px; font-style: italic;">Archived</span>
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