<?php
// learner/grades.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$learner_id = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Normalizing unit codes to uppercase automatically for matrix matching
    $unit_code = strtoupper(trim($_POST['unit_code']));
    $grade_point = (float)$_POST['grade_point'];

    try {
        $stmt = $pdo->prepare("INSERT INTO academic_progress (learner_id, unit_code, grade_point) VALUES (?, ?, ?)");
        $stmt->execute([$learner_id, $unit_code, $grade_point]);
        $msg = "Grade record tracked successfully!";
    } catch (PDOException $e) {
        $msg = "Error mapping score: " . $e->getMessage();
    }
}

// Fetch existing profile evaluation logs
$grades = [];
try {
    $stmt = $pdo->prepare("SELECT unit_code, grade_point, recorded_at FROM academic_progress WHERE learner_id = ? ORDER BY recorded_at DESC");
    $stmt->execute([$learner_id]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Grade collection failure: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Performance Tracker - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { display: flex; min-height: 100vh; background: var(--light); margin: 0; font-family: 'Segoe UI', sans-serif; }
        .main-stage { flex: 1; padding: 40px; display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .panel { background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border); height: fit-content; box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        .panel h3 { margin-top: 0; color: var(--navy); border-bottom: 1px solid var(--border); padding-bottom: 10px; }
        .form-row { margin-bottom: 15px; display: flex; flex-direction: column; gap: 5px; }
        .form-row label { font-size: 13px; font-weight: 600; color: var(--slate); }
        input, select { padding: 10px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: var(--navy); }
        .btn { background: var(--navy); color: white; padding: 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; background: white; border: 1px solid var(--border); border-radius: 6px; overflow: hidden; }
        th, td { padding: 12px 15px; border-bottom: 1px solid var(--border); text-align: left; font-size: 14px; }
        th { background: #edf2f7; color: var(--navy); }
    </style>
</head>
<body>
    <div class="main-stage">
        <div class="panel">
            <div style="margin-bottom: 20px;"><a href="dashboard.php" style="color: var(--slate); text-decoration: none; font-weight: 600;">← Dashboard</a></div>
            <h3>Log Course Marks</h3>
            
            <?php if (!empty($msg)): ?>
                <p style="color:#0369a1; font-weight:600; font-size:14px; background:#e0f2fe; padding:10px; border-radius:4px;"><?php echo $msg; ?></p>
            <?php endif; ?>
            
            <form method="POST" action="grades.php">
                <div class="form-row">
                    <label>Course Unit Code</label>
                    <input type="text" name="unit_code" placeholder="e.g. ICS 2201" required>
                </div>
                <div class="form-group form-row">
                    <label>Achieved Score Metric</label>
                    <select name="grade_point" required>
                        <option value="4.00">Grade A (4.00 GP)</option>
                        <option value="3.50">Grade B+ (3.50 GP)</option>
                        <option value="3.00">Grade B (3.00 GP)</option>
                        <option value="2.50">Grade C+ (2.50 GP)</option>
                        <option value="2.00">Grade C (2.00 GP)</option>
                    </select>
                </div>
                <button type="submit" class="btn">Submit Matrix</button>
            </form>
        </div>

        <div class="panel">
            <h3>Performance Matrix Progression Log</h3>
            <table>
                <thead>
                    <tr>
                        <th>Unit Code</th>
                        <th>Grade Point Obtained</th>
                        <th>Submission Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($grades)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--slate); font-style: italic;">No terminal performance items logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($grades as $g): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($g['unit_code']); ?></strong></td>
                                <td style="color:#10b981; font-weight:bold;"><?php echo number_format($g['grade_point'], 2); ?></td>
                                <td style="color: var(--slate);"><?php echo date('M d, Y - H:i', strtotime($g['recorded_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>