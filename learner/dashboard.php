<?php
// learner/dashboard.php
session_start();

// Strict security gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$learner_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Learner';
$learner_id = $_SESSION['user_id'];

// Global metrics engine configurations
$metrics = ['completed' => 0, 'scheduled' => 0, 'gpa' => '0.00', 'payment_status' => 'Pending'];
$unread_alerts_count = 0; // ADD THIS LINE

try {
    // 1. Calculate Active and Completed booking metrics safely
    $countStmt = $pdo->prepare("SELECT status, COUNT(*) as status_count FROM bookings WHERE learner_id = ? GROUP BY status");
    $countStmt->execute([$learner_id]);
    
    while ($row = $countStmt->fetch(PDO::FETCH_ASSOC)) {
        $status = strtolower($row['status']);
        if (in_array($status, ['approved', 'scheduled', 'confirmed', 'pending'])) {
            $metrics['scheduled'] += (int)$row['status_count'];
        }
        if (in_array($status, ['completed', 'reviewed'])) {
            $metrics['completed'] += (int)$row['status_count'];
        }
    }

    // 2. Real-Time Dynamic Aggregate GPA Calculation Engine
    $gpaStmt = $pdo->prepare("
    SELECT AVG(
        CASE grade_after
            WHEN 'A' THEN 4.00
            WHEN 'B+' THEN 3.50
            WHEN 'B' THEN 3.00
            WHEN 'C+' THEN 2.50
            WHEN 'C' THEN 2.00
            ELSE NULL
        END
    ) as computed_gpa
    FROM academic_progress 
    WHERE learner_id = ?
");
    $gpaStmt->execute([$learner_id]);
    $gpaResult = $gpaStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($gpaResult && !is_null($gpaResult['computed_gpa'])) {
        $metrics['gpa'] = number_format((float)$gpaResult['computed_gpa'], 2);
    }

    // 3. Notification Count Pipeline
    $notifCountStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $notifCountStmt->execute([$learner_id]);
    $unread_alerts_count = (int)$notifCountStmt->fetchColumn();

    // 4. Verification Check for Payment Track Gateways
    $payStmt = $pdo->prepare("SELECT status FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $payStmt->execute([$learner_id]);
    $paymentRow = $payStmt->fetch(PDO::FETCH_ASSOC);
    if ($paymentRow) {
        $metrics['payment_status'] = $paymentRow['status'];
    }

} catch (PDOException $e) {
    error_log("Metrics tracking engine fault: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Hub - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { display: flex; min-height: 100vh; background: var(--light); margin: 0; font-family: 'Segoe UI', sans-serif; }
        .main-stage { flex: 1; padding: 40px; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--border); padding-bottom: 20px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-top: 4px solid var(--navy); }
        .stat-card h3 { margin: 0; font-size: 14px; color: var(--slate); text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card p { margin: 5px 0 0 0; font-size: 28px; font-weight: bold; color: var(--navy); }

        .pay-banner { background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #d97706; padding: 15px 20px; border-radius: 6px; margin-bottom: 30px; font-size: 14px; color: #92400e; display: flex; justify-content: space-between; align-items: center; }

        /* Navigation Grid */
        .hub-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }
        .hub-tile { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 25px; text-decoration: none; color: inherit; transition: all 0.2s ease; }
        .hub-tile:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); border-color: var(--navy); }
        .hub-tile h3 { margin: 0 0 10px 0; color: var(--navy); font-size: 18px; display: flex; align-items: center; gap: 10px; }
        .hub-tile p { margin: 0; color: var(--slate); font-size: 14px; line-height: 1.5; }
    </style>
</head>
<body>

    <div class="main-stage">
        <header>
            <div>
                <h1>Learner Command Center</h1>
                <p style="margin: 5px 0 0 0; color: var(--slate);">Welcome back, <strong><?php echo $learner_name; ?></strong></p>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="notifications.php" style="text-decoration: none; display: inline-block;">
                    <div style="background: <?php echo $unread_alerts_count > 0 ? '#fee2e2' : '#e0f2fe'; ?>; color: <?php echo $unread_alerts_count > 0 ? '#991b1b' : '#0369a1'; ?>; padding: 10px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                        🔔 System Pipeline <?php echo $unread_alerts_count > 0 ? "({$unread_alerts_count} New Alerts)" : "(Clear)"; ?>
                    </div>
                </a>
                <a href="../auth/logout.php" style="background: #fee2e2; color: #b91c1c; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">🚪 Terminate Session</a>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card"><h3>Active Bookings</h3><p><?php echo $metrics['scheduled']; ?></p></div>
            <div class="stat-card"><h3>Completed Horizons</h3><p><?php echo $metrics['completed']; ?></p></div>
            <div class="stat-card"><h3>Academic GPA Standing</h3><p style="color: #10b981;"><?php echo $metrics['gpa']; ?></p></div>
        </section>

        <h2>Functional Modules</h2>
        <div class="hub-grid">
            
            <a href="search_tutor.php" class="hub-tile">
                <h3>🔍 Match & Find Tutors</h3>
                <p>Input your unit of study code, browse verified peer instructors, and look up real-time structural availabilities.</p>
            </a>

            <a href="bookings.php" class="hub-tile">
                <h3>📅 Booking Logistics Log</h3>
                <p>Track scheduling parameters, monitor confirmation pipelines, or systematically execute cancellations and rescheduling.</p>
            </a>

            <a href="grades.php" class="hub-tile">
                <h3>📈 Performance Tracker</h3>
                <p>Log your completed terminal course scores, update performance metrics, and audit your aggregate GPA trends.</p>
            </a>

            <a href="bookings.php" class="hub-tile">
                <h3>★ Ratings & Feedback Panel</h3>
                <p>Provide review scores and log constructive analytical evaluations for peer instructors following closed sessions.</p>
            </a>

        </div>
    </div>

</body>
</html>