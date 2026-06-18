<?php
// tutor/dashboard.php
session_start();

// Strict security layer gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

// Fallback for current logged-in instructor label
$tutor_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Instructor';
$tutor_id = $_SESSION['user_id'];

// Aggregate running matrix parameters
$metrics = ['pending' => 0, 'confirmed' => 0, 'rating' => '0.0', 'earnings' => 0.00];

try {
    // 1. Calculate booking metric counts from pipeline
    $countStmt = $pdo->prepare("SELECT status, COUNT(*) FROM bookings WHERE tutor_id = ? GROUP BY status");
    $countStmt->execute([$tutor_id]);
    while ($row = $countStmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['status'] === 'scheduled') {
            $metrics['pending'] = $row['count'];
        }
        if ($row['status'] === 'confirmed' || $row['status'] === 'approved') {
            $metrics['confirmed'] = $row['count'];
        }
    }

    // 2. Fetch Average Platform Rating Metric
    $ratingStmt = $pdo->prepare("SELECT AVG(rating) FROM ratings WHERE tutor_id = ?");
    $ratingStmt->execute([$tutor_id]);
    $avgRating = $ratingStmt->fetchColumn();
    if ($avgRating) {
        $metrics['rating'] = number_format($avgRating, 1);
    }

    // 3. Fetch Accrued Earnings Balance (Using KES 500 base rate for completed sessions)
    $earningsStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tutor_id = ? AND status = 'completed'");
    $earningsStmt->execute([$tutor_id]);
    $completedCount = (int)$earningsStmt->fetchColumn();
    $metrics['earnings'] = $completedCount * 500;

} catch (PDOException $e) {
    error_log("Tutor dashboard aggregation exception: " . $e->getMessage());
}

// Fetch a quick snapshot of the next 3 upcoming sessions
$upcoming_sessions = [];
try {
    $upcomingStmt = $pdo->prepare("
        SELECT b.unit_code, b.booking_date, u.name as student_name 
        FROM bookings b 
        JOIN users u ON b.learner_id = u.id 
        WHERE b.tutor_id = ? AND b.status = 'confirmed' 
        ORDER BY b.booking_date ASC 
        LIMIT 3
    ");
    $upcomingStmt->execute([$tutor_id]);
    $upcoming_sessions = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Upcoming sessions layout query fault: " . $e->getMessage());
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
        header h1 { color: var(--primary-navy); margin: 0; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-top: 4px solid var(--primary-navy); }
        .stat-card h3 { margin: 0 0 10px 0; font-size: 13px; color: var(--slate-gray); text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card p { margin: 0; font-size: 28px; font-weight: bold; color: var(--primary-navy); }
        
        .layout-split { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 35px; }
        .panel-box { background: white; border-radius: 8px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid var(--border-color); }
        .panel-box h2 { margin-top: 0; color: var(--primary-navy); border-bottom: 1px solid var(--border-color); padding-bottom: 10px; font-size: 18px; }
        
        .hub-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
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
                <small style="color: var(--slate-gray);">Welcome back, Coach <strong><?php echo $tutor_name; ?></strong></small>
            </div>
            <a href="../auth/logout.php" style="background: #fee2e2; color: #b91c1c; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">🚪 Terminate Session</a>
        </header>

        <!-- Metrics Layer Row -->
        <section class="stats-grid">
            <div class="stat-card" style="border-top-color: var(--warning-amber);">
                <h3>Incoming Requests</h3>
                <p style="color: var(--warning-amber);"><?php echo $metrics['pending']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Upcoming Classes</h3>
                <p><?php echo $metrics['confirmed']; ?></p>
            </div>
            <div class="stat-card" style="border-top-color: var(--success-green);">
                <h3>Platform Rating</h3>
                <p style="color: var(--success-green);">★ <?php echo $metrics['rating']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Accrued Balance</h3>
                <p>KES <?php echo number_format($metrics['earnings'], 2); ?></p>
            </div>
        </section>

        <div class="layout-split">
            <!-- Left Side Column: Functional Control Matrices Navigation -->
            <div class="panel-box">
                <h2>Functional Modules Matrix</h2>
                <div class="hub-grid">
                    <a href="bookings.php" class="hub-tile">
                        <h4>📅 Requests Pipeline</h4>
                        <p>Accept or deny incoming student booking milestones and update active states.</p>
                    </a>
                    <a href="availability.php" class="hub-tile">
                        <h4>🕒 Availability Schedule</h4>
                        <p>Modify, format, and structure weekly recurring open calendar slots for learners.</p>
                    </a>
                    <a href="progress_report.php" class="hub-tile">
                        <h4>📈 Progress Reports</h4>
                        <p>Audit aggregate peer grades logs and submit performance reports.</p>
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

            <!-- Right Side Column: Upcoming Approved Calendar Deadlines Panel -->
            <div class="panel-box">
                <h2>Upcoming Schedule</h2>
                <?php if (empty($upcoming_sessions)): ?>
                    <p style="color: var(--slate-gray); font-size: 13px; font-style: italic;">No approved classes coming up immediately on your timeline.</p>
                <?php else: ?>
                    <?php foreach ($upcoming_sessions as $session): ?>
                        <div class="session-strip">
                            <div>
                                <strong style="color: var(--primary-navy);"><?php echo htmlspecialchars($session['unit_code']); ?></strong>
                                <br><small style="color: var(--slate-gray);">Student: <?php echo htmlspecialchars($session['student_name']); ?></small>
                            </div>
                            <span style="font-size: 12px; font-weight: 600; color: var(--slate-gray);"><?php echo date('M d, H:i', strtotime($session['booking_date'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>