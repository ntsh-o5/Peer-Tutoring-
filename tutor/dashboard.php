<?php
// tutor/dashboard.php
session_start();

// Strict security layer gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION['is_verified']) || $_SESSION['is_verified'] !== true) {
    header("Location: onboarding.php"); // Redirect to upload credentials
    exit;
}

require_once '../config/database.php';

$tutor_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Instructor';
$tutor_id = $_SESSION['user_id'];

$message = '';
$message_type = 'success';

// 1. Handle Booking Status Updates (Accept / Deny)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_booking'])) {
    $booking_id = (int)$_POST['booking_id'];
    $action = trim($_POST['action_booking']);
    $new_status = ($action === 'accept') ? 'approved' : 'rejected';
    
    try {
        $updateStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE booking_id = ? AND tutor_id = ? AND status = 'pending'");
        $updateStmt->execute([$new_status, $booking_id, $tutor_id]);
        
        if ($updateStmt->rowCount() > 0) {
            $message = "Booking request successfully " . ($action === 'accept' ? 'approved' : 'rejected') . "!";
            $message_type = 'success';
        } else {
            $message = "Action failed. Booking may have already been updated.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        error_log("Booking response processing failure: " . $e->getMessage());
        $message = "Database modification error occurred.";
        $message_type = 'error';
    }
}

// 2. Handle Marking a Session as Completed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_complete_session'])) {
    $booking_id = (int)$_POST['booking_id'];
    
    try {
        $completeStmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE booking_id = ? AND tutor_id = ? AND status = 'approved'");
        $completeStmt->execute([$booking_id, $tutor_id]);
        
        if ($completeStmt->rowCount() > 0) {
            $message = "Session marked as completed! Moved to your reporting and payouts track.";
            $message_type = 'success';
        } else {
            $message = "Unable to update session. It may already be completed.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        error_log("Session completion error: " . $e->getMessage());
        $message = "Database status transition error occurred.";
        $message_type = 'error';
    }
}

// 3. Fetch all active pending requests
$pending_requests = [];
try {
    $reqStmt = $pdo->prepare("
        SELECT b.booking_id, b.unit_code, b.booking_date, u.name as student_name 
        FROM bookings b
        JOIN users u ON b.learner_id = u.id
        WHERE b.tutor_id = ? AND b.status = 'pending'
        ORDER BY b.booking_date ASC
    ");
    $reqStmt->execute([$tutor_id]);
    $pending_requests = $reqStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Pending request fetch failure: " . $e->getMessage());
}

// 4. Aggregate running metrics
$metrics = ['pending' => count($pending_requests), 'confirmed' => 0, 'rating' => '0.0', 'earnings' => 0.00];
try {
    $countStmt = $pdo->prepare("SELECT status, COUNT(*) as count_metric FROM bookings WHERE tutor_id = ? GROUP BY status");
    $countStmt->execute([$tutor_id]);
    while ($row = $countStmt->fetch(PDO::FETCH_ASSOC)) {
        $status = strtolower(trim($row['status']));
        if ($status === 'confirmed' || $status === 'approved') {
            $metrics['confirmed'] += (int)$row['count_metric'];
        }
    }

    $ratingStmt = $pdo->prepare("SELECT AVG(r.rating) as avg_score FROM ratings r JOIN bookings b ON r.booking_id = b.booking_id WHERE b.tutor_id = ?");
    $ratingStmt->execute([$tutor_id]);
    $avgRating = $ratingStmt->fetchColumn();
    if ($avgRating) {
        $metrics['rating'] = number_format($avgRating, 1);
    }

    $earningsStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tutor_id = ? AND status = 'completed'");
    $earningsStmt->execute([$tutor_id]);
    $completedCount = (int)$earningsStmt->fetchColumn();
    $metrics['earnings'] = $completedCount * 500;
} catch (PDOException $e) {
    error_log("Tutor dashboard aggregation exception: " . $e->getMessage());
}

// 5. Fetch next 5 active approved sessions for main snapshot loop
$upcoming_classes = [];
try {
    $upStmt = $pdo->prepare("
        SELECT b.booking_id, b.unit_code, b.booking_date, u.name as student_name 
        FROM bookings b 
        JOIN users u ON b.learner_id = u.id 
        WHERE b.tutor_id = ? AND b.status = 'approved' 
        ORDER BY b.booking_date ASC 
        LIMIT 5
    ");
    $upStmt->execute([$tutor_id]);
    $upcoming_classes = $upStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Upcoming query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Command Center - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --primary-navy: #0f2038;
            --slate-gray: #475569;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
            --success-green: #10b981;
            --warning-amber: #f59e0b;
        }
        body { display: flex; min-height: 100vh; background: var(--light-bg); margin: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        .main-stage { flex: 1; padding: 40px; box-sizing: border-box; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--border-color); padding-bottom: 20px; }
        header h1 { color: var(--primary-navy); margin: 0; font-size: 24px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-top: 4px solid var(--primary-navy); }
        .stat-card h3 { margin: 0 0 10px 0; font-size: 13px; color: var(--slate-gray); text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card p { margin: 0; font-size: 28px; font-weight: bold; color: var(--primary-navy); }
        
        .layout-split { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 35px; }
        .panel-box { background: white; border-radius: 8px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid var(--border-color); height: fit-content; }
        .panel-box h2 { margin-top: 0; color: var(--primary-navy); border-bottom: 1px solid var(--border-color); padding-bottom: 10px; font-size: 18px; }
        
        .hub-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .hub-tile { background: #f8fafc; border: 1px solid var(--border-color); border-radius: 6px; padding: 15px; text-decoration: none; color: inherit; transition: all 0.2s ease; }
        .hub-tile:hover { border-color: var(--primary-navy); background: #fff; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.04); }
        .hub-tile h4 { margin: 0 0 5px 0; color: var(--primary-navy); font-size: 15px; }
        .hub-tile p { margin: 0; color: var(--slate-gray); font-size: 12px; line-height: 1.4; }
        
        .session-strip { display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f8fafc; border-left: 4px solid var(--success-green); border-radius: 4px; margin-bottom: 10px; font-size: 14px; }
    </style>
</head>
<body>

    <div class="main-stage">
        <header>
            <div>
                <h1>Instructor Operations Hub</h1>
                <p style="margin: 5px 0 0 0; color: var(--slate-gray);">Welcome back, Coach <strong><?php echo $tutor_name; ?></strong></p>
            </div>
            <a href="../auth/logout.php" style="background: #fee2e2; color: #b91c1c; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">🚪 Terminate Session</a>
        </header>

        <?php if(!empty($message)): ?>
            <div style="padding: 12px; margin-bottom: 25px; border-radius: 6px; font-size: 14px; font-weight: 500; background: <?php echo $message_type === 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #b91c1c;'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="stats-grid">
            <div class="stat-card" style="border-top-color: var(--warning-amber);">
                <h3>Incoming Requests</h3>
                <p style="color: var(--warning-amber);"><?php echo $metrics['pending']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Upcoming Classes</h3>
                <p><?php echo $metrics['confirmed']; ?></p>
            </div>
            <div class="stat-card" style="border-top-color: var(--warning-amber);">
                <h3>Platform Rating</h3>
                <p style="color: var(--warning-amber);">★ <?php echo $metrics['rating']; ?></p>
            </div>
            <div class="stat-card" style="border-top-color: var(--success-green);">
                <h3>Accrued Balance</h3>
                <p style="color: var(--success-green);">KES <?php echo number_format($metrics['earnings'], 2); ?></p>
            </div>
        </section>

        <div style="margin-bottom: 35px;">
            <div class="panel-box" style="margin-bottom: 30px; width: 100%; box-sizing: border-box;">
                <h2 style="color: var(--primary-navy); border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-top: 0;">
                    Incoming Booking Requests (<?php echo count($pending_requests); ?>)
                </h2>
                <?php if (empty($pending_requests)): ?>
                    <p style="color: var(--slate-gray); font-size: 14px; font-style: italic; padding: 10px 0;">No pending student session requests open at the moment.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 15px;">
                        <?php foreach ($pending_requests as $req): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8fafc; border: 1px solid var(--border-color); border-radius: 6px;">
                                <div>
                                    <strong style="color: var(--primary-navy); font-size: 15px;"><?php echo htmlspecialchars($req['student_name']); ?></strong>
                                    <span style="color: var(--slate-gray); font-size: 13px;"> requests unit: <strong style="color: var(--primary-navy);"><?php echo htmlspecialchars($req['unit_code']); ?></strong></span>
                                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                                        Proposed Date: <strong><?php echo date('M d, Y - H:i', strtotime($req['booking_date'])); ?></strong>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <form method="POST" action="dashboard.php" style="margin: 0;">
                                        <input type="hidden" name="booking_id" value="<?php echo $req['booking_id']; ?>">
                                        <button type="submit" name="action_booking" value="accept" style="background: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 4px; font-weight: 600; font-size: 13px; cursor: pointer;">Accept</button>
                                    </form>
                                    <form method="POST" action="dashboard.php" style="margin: 0;" onsubmit="return confirm('Decline booking request?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $req['booking_id']; ?>">
                                        <button type="submit" name="action_booking" value="deny" style="background: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 4px; font-weight: 600; font-size: 13px; cursor: pointer;">Deny</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="layout-split">
            <div class="panel-box">
                <h2>Functional Modules Matrix</h2>
                <div class="hub-grid">
                    <a href="booking_history.php" class="hub-tile">
                        <h4>📜 Booking History Log</h4>
                        <p>Review a complete record index log of all historical past and processed peer allocations.</p>
                    </a>
                    <a href="schedule.php" class="hub-tile">
                        <h4>🗓️ Update Availability</h4>
                        <p>Modify, format, and structure weekly recurring open calendar slots for learners.</p>
                    </a>
                    <a href="edit_profile.php" class="hub-tile">
                        <h4>👤 Edit Profile Settings</h4>
                        <p>Keep your contact details, biography statement descriptions, and expertise matrices current.</p>
                    </a>
                    <a href="progress_report.php" class="hub-tile">
                        <h4>📈 Progress Reports</h4>
                        <p>Audit aggregate peer grades logs and submit performance evaluations.</p>
                    </a>
                    <a href="compensation.php" class="hub-tile">
                        <h4>💰 Compensation Claims</h4>
                        <p>Track your verified instruction hours and request pending monetary payouts.</p>
                    </a>
                    <a href="feedback.php" class="hub-tile">
                        <h4>💬 Evaluations Received</h4>
                        <p>Read detailed reviews, performance ratings, and text critiques from students.</p>
                    </a>
                </div>
            </div>

            <div class="panel-box">
                <h2>Upcoming Active Schedule</h2>
                <?php if (empty($upcoming_classes)): ?>
                    <p style="color: var(--slate-gray); font-size: 13px; font-style: italic;">No approved classes coming up immediately on your timeline.</p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 15px;">
                        <?php foreach ($upcoming_classes as $class): ?>
                            <div class="session-strip">
                                <div>
                                    <strong style="color: var(--primary-navy);"><?php echo htmlspecialchars($class['unit_code']); ?></strong>
                                    <br><small style="color: var(--slate-gray);">Student: <?php echo htmlspecialchars($class['student_name']); ?></small>
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 3px;">
                                        <?php echo date('M d, h:i A', strtotime($class['booking_date'])); ?>
                                    </div>
                                </div>
                                <form method="POST" action="dashboard.php" style="margin: 0;" onsubmit="return confirm('Mark this lesson session as ended successfully?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $class['booking_id']; ?>">
                                    <button type="submit" name="action_complete_session" value="1" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 6px 12px; border-radius: 4px; font-weight: 600; font-size: 12px; cursor: pointer;">Done ✓</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>