<?php
// tutor/availability.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$tutor_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $day = $_POST['day_of_week'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];

    try {
        $stmt = $pdo->prepare("INSERT INTO tutor_schedules (tutor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tutor_id, $day, $start, $end]);
        $message = "Availability block pushed successfully!";
    } catch (PDOException $e) { 
        $message = "Error: " . $e->getMessage(); 
    }
}

$slots = [];
try {
    $stmt = $pdo->prepare("SELECT id, day_of_week, start_time, end_time FROM tutor_schedules WHERE tutor_id = ? ORDER BY id DESC");
    $stmt->execute([$tutor_id]);
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Manage Availability Profile - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>body { font-family: sans-serif; background:#f8fafc; padding:40px; display:grid; grid-template-columns:1fr 2fr; gap:30px; }</style>
</head>
<body>
    <div style="background:white; padding:25px; border-radius:8px; border:1px solid #e2e8f0; height:fit-content;">
        <a href="dashboard.php" style="color:#64748b; text-decoration:none; font-size:13px;">← Dashboard Hub</a>
        <h3>Insert New Time Slot</h3>
        <?php if($message): ?><p style="color:green; font-size:13px;"><?php echo $message; ?></p><?php endif; ?>
        <form method="POST">
            <p><label>Target Routine Day</label><br>
            <select name="day_of_week" style="width:100%; padding:8px; margin-top:5px;"><option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option></select></p>
            <p><label>Shift Start</label><br><input type="time" name="start_time" required style="width:100%; padding:8px; margin-top:5px;"></p>
            <p><label>Shift End</label><br><input type="time" name="end_time" required style="width:100%; padding:8px; margin-top:5px;"></p>
            <button type="submit" style="background:#0f2038; color:white; border:none; width:100%; padding:10px; border-radius:4px; font-weight:bold; cursor:pointer;">Update Availability Schedule</button>
        </form>
    </div>
    <div style="background:white; padding:25px; border-radius:8px; border:1px solid #e2e8f0;">
        <h3>Your Availability Timeblocks</h3>
        <table style="width:100%; border-collapse:collapse; text-align:left;">
            <thead><tr style="background:#edf2f7;"><th style="padding:10px;">Day</th><th style="padding:10px;">Start Time</th><th style="padding:10px;">End Time</th></tr></thead>
            <tbody>
                <?php foreach($slots as $s): ?>
                <tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:10px;"><?php echo $s['day_of_week']; ?></td><td style="padding:10px;"><?php echo $s['start_time']; ?></td><td style="padding:10px;"><?php echo $s['end_time']; ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>