<?php
// tutor/bookings.php
session_start();

// Security gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$tutor_id = $_SESSION['user_id'];
$status_message = '';

// =========================================================================
// PIPELINE CONTROLS LOGIC (POST Transactions handling)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $booking_id = (int)$_POST['booking_id'];
    $action = trim($_GET['action']);
    
    // Strict lookup array map to block structural mutations
    $allowed_statuses = [
        'accept'   => 'confirmed',
        'deny'     => 'denied',
        'complete' => 'completed'
    ];
    
    if (array_key_exists($action, $allowed_statuses)) {
        $target_status = $allowed_statuses[$action];
        
        try {
            // Secure query making sure a tutor can only edit their own bookings
            $updateStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND tutor_id = ?");
            $updateStmt->execute([$target_status, $booking_id, $tutor_id]);
            
            if ($updateStmt->rowCount() > 0) {
                $status_message = "Success: Booking parameter shifted to " . strtoupper($target_status) . ".";
            } else {
                $status_message = "Error: Invalid operation target or resource link mismatch.";
            }
        } catch (PDOException $e) {
            $status_message = "Pipeline error: " . $e->getMessage();
        }
    }
}

// =========================================================================
// FETCH ALL BOOKING DATA RECORDS FOR THE LOGGED-IN TUTOR
// =========================================================================
$booking_requests = [];
$booking_history = [];

try {
    $stmt = $pdo->prepare("
        SELECT b.id, b.unit_code, b.booking_date, b.status, u.name as student_name, u.email as student_email 
        FROM bookings b 
        JOIN users u ON b.learner_id = u.id 
        WHERE b.tutor_id = ? 
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$tutor_id]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['status'] === 'scheduled') {
            $booking_requests[] = $row; // Incoming pending queue
        } else {
            $booking_history[] = $row;  // Historical tracking rows
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
        
        .alert-banner { background: #e0f2fe; color: #0369a1; padding: 15px; border-radius: 6px; font-weight: 600; margin-bottom: 25px; border-left: 4px solid var(--accent-blue); }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 35px; background: white; text-align: left; }
        th, td { padding: 14px; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        th { background: #edf2f7; color: var(--primary-navy); font-weight: 600; }
        
        .btn-action { padding: 6px 14px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; font-size: 12px; color: white; display: inline-block; }
        .btn-action.accept { background: var(--success-green); }
        .btn-action.deny { background: var(--danger-red); }
        .btn-action.complete { background: var(--accent-blue); }
        
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .status-badge.confirmed { background: #d1fae5; color: #065f46; }
        .status-badge.completed { background: #f1f5f9; color: #334155; }
        .status-badge.denied { background: #fee2e2; color: #991b1b; }
        .status-badge.cancelled { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>

    <div class="container">
        <div style="margin-bottom: 20px;"><a href="dashboard.php" style="color: var(--slate-gray); text-decoration: none; font-weight: 500;">← Back to Dashboard Hub</a></div>
        <h2>Student Allocations & Booking Requests Pipeline</h2>

        <?php if (!empty($status_message)): ?>
            <div class="alert-banner"><?php echo htmlspecialchars($status_message); ?></div>
        <?php endif; ?>

        <!-- SECTION 1: INCOMING DISPATCH QUEUE -->
        <h3 style="color: var(--primary-navy); margin-top: 0;">Incoming Pending Booking Requests</h3>
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
                                    <input type="hidden" name="booking_id" value="<?php echo $req['id']; ?>">
                                    <button type="submit" class="btn-action accept">Accept Session</button>
                                </form>
                                <form method="POST" action="bookings.php?action=deny" style="display: inline-block; margin-left: 5px;" onsubmit="return confirm('Reject this student request?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $req['id']; ?>">
                                    <button type="submit" class="btn-action deny">Deny</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- SECTION 2: BOOKING HISTORY MATRIX TRACK LOGS -->
        <h3 style="color: var(--primary-navy);">Historical Tracking & Active Classes</h3>
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
                                <span class="status-badge <?php echo htmlspecialchars($hist['status']); ?>">
                                    <?php echo htmlspecialchars($hist['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($hist['status'] === 'confirmed' || $hist['status'] === 'approved'): ?>
                                    <form method="POST" action="bookings.php?action=complete" onsubmit="return confirm('Verify that this lesson has successfully concluded?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $hist['id']; ?>">
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