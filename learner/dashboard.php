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

// Global metrics engine calculations
$metrics = ['completed' => 0, 'scheduled' => 0, 'gpa' => '0.00'];
try {
    $countStmt = $pdo->prepare("SELECT status, COUNT(*) FROM bookings WHERE learner_id = ? GROUP BY status");
    $countStmt->execute([$learner_id]);
    while ($row = $countStmt->fetch(PDO::FETCH_ASSOC)) {
        if (in_array($row['status'], ['approved', 'scheduled', 'confirmed'])) $metrics['scheduled'] += $row['count'];
        if ($row['status'] === 'completed') $metrics['completed'] = $row['count'];
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
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border-top: 4px solid var(--navy); }
        .stat-card p { margin: 5px 0 0 0; font-size: 28px; font-weight: bold; color: var(--navy); }

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
                <div style="background: #e0f2fe; color: #0369a1; padding: 10px 16px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                    🔔 2 Active System Alerts
                </div>
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
            
            <a href="search_tutors.php" class="hub-tile">
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

            <a href="rating.php" class="hub-tile">
                <h3>★ Ratings & Feedback Panel</h3>
                <p>Provide review scores and log constructive analytical evaluations for peer instructors following closed sessions.</p>
            </a>

        </div>
    </div>

</body>
</html>