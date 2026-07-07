<?php
// tutor/bookings.php
session_start();

// Strict security gate
if (!isset($_SESSION['role']) || strtolower(trim($_SESSION['role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$tutor_id = $_SESSION['user_id'];
$tutor_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Tutor';
$status_message = '';
$message_type = 'success';

// =========================================================================
// PIPELINE CONTROLS LOGIC (POST Transactions handling)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $booking_id = (int)$_POST['booking_id'];
    $action = trim($_GET['action']);
    
    // Strict lookup array map to block structural mutations
    $allowed_statuses = [
    'accept'   => 'approved',
    'deny'     => 'rejected',
    'complete' => 'completed'
];

if (array_key_exists($action, $allowed_statuses)) {
    $target_status = $allowed_statuses[$action];
    
    try {
        $updateStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND tutor_id = ?");
        $updateStmt->execute([$target_status, $booking_id, $tutor_id]);
            
            if ($updateStmt->rowCount() > 0) {
                $status_message = "Success: Booking parameter shifted to " . strtoupper($target_status) . ".";
                $message_type = 'success';
            } else {
                $status_message = "Error: Invalid operation target or resource link mismatch.";
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $status_message = "Pipeline system fault: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// =========================================================================
// FETCH ALL BOOKING DATA RECORDS FOR THE LOGGED-IN TUTOR
// =========================================================================
$booking_requests = [];
$booking_history = [];

try {
    // Fixed column identifiers here as well (b.booking_id)
    $stmt = $pdo->prepare("
    SELECT b.id AS booking_id, b.unit_code, b.booking_date, b.status, u.name as student_name, u.email as student_email 
    FROM bookings b 
    JOIN users u ON b.learner_id = u.id 
    WHERE b.tutor_id = ? 
    ORDER BY b.booking_date DESC
");
$stmt->execute([$tutor_id]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $current_status = strtolower(trim($row['status']));
    if ($current_status === 'pending') {
        $booking_requests[] = $row;
    } else {
        $booking_history[] = $row;
    }
}
} catch (PDOException $e) {
    error_log("Booking history engine lookup failures: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --primary-navy: #0f2038;
            --slate-gray: #475569;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
            --success-green: #10b981;
            --danger-red: #ef4444;
            --accent-blue: #2563eb;
        }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: var(--light-bg); margin: 0; padding: 40px; }
        .container { max-width: 1100px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--border-color); padding-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 35px; background: white; text-align: left; }
        th, td { padding: 14px; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        th { background: #edf2f7; color: var(--primary-navy); font-weight: 600; }
        
        .btn-action { padding: 8px 14px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; font-size: 12px; color: white; display: inline-block; transition: background 0.2s; }
        .btn-action.accept { background: var(--success-green); }
        .btn-action.accept:hover { background: #059669; }
        .btn-action.deny { background: var(--danger-red); }
        .btn-action.deny:hover { background: #dc2626; }
        .btn-action.complete { background: var(--accent-blue); }
        .btn-action.complete:hover { background: #1d4ed8; }
        
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .status-badge.confirmed, .status-badge.approved { background: #d1fae5; color: #065f46; }
        .status-badge.completed { background: #f1f5f9; color: #334155; }
        .status-badge.denied { background: #fee2e2; color: #991b1b; }
        .status-badge.cancelled { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>

    <div class="container">
        <header>
            <div>
                <h1 style="margin: 0; font-size: 24px; color: var(--primary-navy);">Booking & Allocations Pipeline</h1>
                <p style="margin: 5px 0 0 0; color: var(--slate-gray);">Instructor: <strong><?php echo $tutor_name; ?></strong></p>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="dashboard.php" style="color: var(--slate-gray); text-decoration: none; font-weight: 600; font-size: 13px;">← Dashboard Hub</a>
                <a href="../auth/logout.php" style="background: #fee2e2; color: #b91c1c; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">🚪 Terminate Session</a>
            </div>
        </header>

        <?php if (!empty($status_message)): ?>
            <div style="padding: 12px; margin-bottom: 25px; border-radius: 6px; font-size: 14px; font-weight: 500; background: <?php echo $message_type === 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #b91c1c;'; ?>">
                <?php echo htmlspecialchars($status_message); ?>
            </div>
        <?php endif; ?>

        <h3 style="color: var(--primary-navy); margin-top: 0; margin-bottom: 15px;">Incoming Pending Booking Requests</h3>
        <?php if (empty($booking_requests)): ?>
            <p style="color: var(--slate-gray); font-style: italic; margin-bottom: 35px;">No student requests are currently pending review in your queue.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Course Unit</th>
                        <th>Proposed Date / Time</th>
                        <th>Action Processing Controls</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($booking_requests as $req): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($req['student_name']); ?></strong><br>
                                <small style="color: var(--slate-gray);"><?php echo htmlspecialchars($req['student_email']); ?></small>
                            </td>
                            <td><span style="font-weight: 600; color: var(--primary-navy);"><?php echo htmlspecialchars($req['unit_code']); ?></span></td>
                            <td><?php echo date('F d, Y \a\t H:i', strtotime($req['booking_date'])); ?></td>
                            <td>
                                <form method="POST" action="bookings.php?action=accept" style="display: inline-block;">
                                    <input type="hidden" name="booking_id" value="<?php echo $req['booking_id']; ?>">
                                    <button type="submit" class="btn-action accept">Accept Session</button>
                                </form>
                                <form method="POST" action="bookings.php?action=deny" style="display: inline-block; margin-left: 5px;" onsubmit="return confirm('Reject this student request?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $req['booking_id']; ?>">
                                    <button type="submit" class="btn-action deny">Deny</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h3 style="color: var(--primary-navy); margin-bottom: 15px;">Historical Tracking & Active Classes</h3>
        <?php if (empty($booking_history)): ?>
            <p style="color: var(--slate-gray); font-style: italic;">No session history records are mapped to your account logs yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Course Unit</th>
                        <th>Target Date Allocation</th>
                        <th>Status Baseline</th>
                        <th>Workflow Execution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($booking_history as $hist): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($hist['student_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($hist['unit_code']); ?></td>
                            <td><?php echo date('M d, Y - H:i', strtotime($hist['booking_date'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo htmlspecialchars(strtolower($hist['status'])); ?>">
                                    <?php echo htmlspecialchars($hist['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (strtolower($hist['status']) === 'approved'): ?>
                                    <form method="POST" action="bookings.php?action=complete" onsubmit="return confirm('Verify that this lesson has successfully concluded?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $hist['booking_id']; ?>">
                                        <button type="submit" class="btn-action complete">Mark as Completed</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--slate-gray); font-size: 12px; font-style: italic;">Archived</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</body>
</html>