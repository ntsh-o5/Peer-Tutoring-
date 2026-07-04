<?php
// tutor/schedule.php
session_start();

// Strict security layer gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$tutor_id = $_SESSION['user_id'];
$tutor_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Tutor';

$message = '';
$message_type = 'success';

// 1. Handle adding a new availability slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_slot'])) {
    $day_of_week = trim($_POST['day_of_week']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);

    // Chronological safety check
    if (strtotime($start_time) >= strtotime($end_time)) {
        $message = "Error: The start time cannot occur after or match the destination end time.";
        $message_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO tutor_availability (tutor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$tutor_id, $day_of_week, $start_time, $end_time]);
            $message = "Availability slot successfully added to your operational calendar!";
            $message_type = 'success';
        } catch (PDOException $e) {
            // Catch duplicate entry constraints gently
            if ($e->getCode() == 23505 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $message = "You have already logged this exact timeframe allocation slot.";
            } else {
                $message = "Database synchronization error: " . $e->getMessage();
            }
            $message_type = 'error';
        }
    }
}

// 2. Handle deleting an availability slot
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_slot'])) {
    $availability_id = (int)$_POST['availability_id'];

    try {
        // Enforce tutor_id scope boundary constraint for protection
        $delStmt = $pdo->prepare("DELETE FROM tutor_availability WHERE availability_id = ? AND tutor_id = ?");
        $delStmt->execute([$availability_id, $tutor_id]);
        
        $message = "Availability slot successfully dropped.";
        $message_type = 'success';
    } catch (PDOException $e) {
        $message = "Failed to drop allocation line: " . $e->getMessage();
        $message_type = 'error';
    }
}

// 3. Fetch current availability slots logged for this tutor
$slots = [];
try {
    $fetchStmt = $pdo->prepare("SELECT * FROM tutor_availability WHERE tutor_id = ? ORDER BY 
        CASE 
            WHEN day_of_week = 'Monday' THEN 1
            WHEN day_of_week = 'Tuesday' THEN 2
            WHEN day_of_week = 'Wednesday' THEN 3
            WHEN day_of_week = 'Thursday' THEN 4
            WHEN day_of_week = 'Friday' THEN 5
            WHEN day_of_week = 'Saturday' THEN 6
            WHEN day_of_week = 'Sunday' THEN 7
        END ASC, start_time ASC");
    $fetchStmt->execute([$tutor_id]);
    $slots = $fetchStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Availability fetch breakdown: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Availability Schedule - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); padding: 40px; margin: 0; }
        .wrapper { max-width: 1100px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--border); padding-bottom: 20px; }
        .layout-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; }
        .card-box { background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); height: fit-content; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        input, select { padding: 10px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; font-family: inherit; }
        input:focus, select:focus { border-color: var(--navy); outline: none; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #edf2f7; color: var(--navy); font-weight: 600; }
        .btn-submit { background: var(--navy); color: white; padding: 12px; border: none; border-radius: 4px; font-weight: bold; width: 100%; cursor: pointer; }
        .btn-delete { background: #fee2e2; color: #b91c1c; border: none; padding: 6px 12px; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 12px; }
        .btn-delete:hover { background: #fca5a5; }
    </style>
</head>
<body>

    <div class="wrapper">
        <header>
            <div>
                <h1 style="margin: 0; font-size: 24px; color: var(--navy);">Availability Setup Desk</h1>
                <p style="margin: 5px 0 0 0; color: var(--slate);">Configure the days and specific times blocks you are open to take session bookings.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="dashboard.php" style="color: var(--slate); text-decoration: none; font-weight: 600; font-size: 13px;">← Dashboard Hub</a>
                <a href="../auth/logout.php" style="background: #fee2e2; color: #b91c1c; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">🚪 Terminate Session</a>
            </div>
        </header>

        <?php if(!empty($message)): ?>
            <div style="padding: 12px; margin-bottom: 25px; border-radius: 6px; font-size: 14px; font-weight: 500; background: <?php echo $message_type === 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #b91c1c;'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="layout-grid">
            <div class="card-box">
                <h3 style="color: var(--navy); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Declare New Availability</h3>
                
                <form method="POST" action="schedule.php">
                    <input type="hidden" name="action_add_slot" value="1">
                    
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600; color: var(--slate);">Target Weekday</label>
                        <select name="day_of_week" required>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600; color: var(--slate);">Available From (Start Time)</label>
                        <input type="time" name="start_time" required>
                    </div>

                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600; color: var(--slate);">Available Until (End Time)</label>
                        <input type="time" name="end_time" required>
                    </div>

                    <button type="submit" class="btn-submit">Add Availability Windows Block</button>
                </form>
            </div>

            <div class="card-box">
                <h3 style="color: var(--navy); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Your Active Availability Windows</h3>
                
                <?php if(empty($slots)): ?>
                    <p style="color: var(--slate); font-size: 14px; font-style: italic; padding: 20px 0; text-align: center;">You have not declared any open active hours blocks yet. Students cannot send you booking requests until you add schedule blocks.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Day of Week</th>
                                <th>Operational Hours Window</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($slots as $s): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($s['day_of_week']); ?></strong></td>
                                    <td>
                                        <span style="font-variant-numeric: tabular-nums;">
                                            <?php echo date('h:i A', strtotime($s['start_time'])); ?> 
                                            – 
                                            <?php echo date('h:i A', strtotime($s['end_time'])); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="schedule.php" style="margin: 0;" onsubmit="return confirm('Drop this schedule block? Pending student request links matching this window may dissolve.');">
                                            <input type="hidden" name="availability_id" value="<?php echo $s['availability_id']; ?>">
                                            <button type="submit" name="action_delete_slot" value="1" class="btn-delete">Remove Slot</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>