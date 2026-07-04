<?php
// tutor/availability.php
session_start();

// Strict security gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$tutor_id = $_SESSION['user_id'];
$tutor_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Tutor';
$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $day = trim($_POST['day_of_week']);
    $start = trim($_POST['start_time']);
    $end = trim($_POST['end_time']);

    // Preventive validation validation logic
    if (strtotime($start) >= strtotime($end)) {
        $message = "Error: Shift End must occur after the Shift Start timeline context.";
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO tutor_schedules (tutor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$tutor_id, $day, $start, $end]);
            $message = "Availability window block pushed successfully!";
            $message_type = 'success';
        } catch (PDOException $e) { 
            $message = "Database System Fault: " . $e->getMessage(); 
            $message_type = 'error';
        }
    }
}

$slots = [];
try {
    $stmt = $pdo->prepare("SELECT id, day_of_week, start_time, end_time FROM tutor_schedules WHERE tutor_id = ? ORDER BY field(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'), start_time ASC");
    // Note: If using PostgreSQL, replace the ORDER BY field query with: 
    // ORDER BY CASE day_of_week WHEN 'Monday' THEN 1 WHEN 'Tuesday' THEN 2 WHEN 'Wednesday' THEN 3 WHEN 'Thursday' THEN 4 WHEN 'Friday' THEN 5 END, start_time ASC
    $stmt->execute([$tutor_id]);
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Availability extraction error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Availability Profile - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { display: flex; min-height: 100vh; background: var(--light); margin: 0; font-family: 'Segoe UI', sans-serif; }
        .main-stage { flex: 1; padding: 40px; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--border); padding-bottom: 20px; }
        
        .workspace-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start; }
        .form-card, .table-card { background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 5px; color: var(--navy); }
        select, input[type="time"] { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; margin-top: 10px; }
        th { background: #f1f5f9; padding: 12px; font-weight: 600; color: var(--navy); border-bottom: 2px solid var(--border); }
        td { padding: 12px; border-bottom: 1px solid var(--border); font-size: 14px; color: var(--slate); }
    </style>
</head>
<body>

    <div class="main-stage">
        <header>
            <div>
                <h1>Manage Availability Slots</h1>
                <p style="margin: 5px 0 0 0; color: var(--slate);">Instructor: <strong><?php echo $tutor_name; ?></strong></p>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="dashboard.php" style="color: var(--slate); text-decoration: none; font-weight: 600; font-size: 13px;">← Dashboard Hub</a>
                <a href="../auth/logout.php" style="background: #fee2e2; color: #b91c1c; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">🚪 Terminate Session</a>
            </div>
        </header>

        <?php if($message): ?>
            <div style="padding: 12px; margin-bottom: 20px; border-radius: 6px; font-size: 14px; font-weight: 500; background: <?php echo $message_type === 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #b91c1c;'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="workspace-grid">
            <div class="form-card">
                <h3 style="margin-top: 0; margin-bottom: 15px; color: var(--navy);">Insert New Time Slot</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Target Routine Day</label>
                        <select name="day_of_week">
                            <option>Monday</option>
                            <option>Tuesday</option>
                            <option>Wednesday</option>
                            <option>Thursday</option>
                            <option>Friday</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Shift Start</label>
                        <input type="time" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label>Shift End</label>
                        <input type="time" name="end_time" required>
                    </div>
                    <button type="submit" style="background: var(--navy); color: white; border: none; width: 100%; padding: 12px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 14px;">Update Availability Schedule</button>
                </form>
            </div>

            <div class="table-card">
                <h3 style="margin-top: 0; margin-bottom: 15px; color: var(--navy);">Your Availability Timeblocks</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($slots)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: var(--slate);">No timeblocks defined yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($slots as $s): ?>
                                <tr>
                                    <strong><td><?php echo htmlspecialchars($s['day_of_week']); ?></td></strong>
                                    <td><?php echo date("g:i A", strtotime($s['start_time'])); ?></td>
                                    <td><?php echo date("g:i A", strtotime($s['end_time'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>