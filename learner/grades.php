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
    $unit_code = trim($_POST['unit_code']);
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
    <title>Academic Performance Tracker</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { display: flex; min-height: 100vh; background: var(--light); margin: 0; font-family: 'Segoe UI', sans-serif; }
        .main-stage { flex: 1; padding: 40px; display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .panel { background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border); height: fit-content; }
        .form-row { margin-bottom: 15px; display: flex; flex-direction: column; gap: 5px; }
        input, select { padding: 10px; border: 1px solid var(--border); border-radius: 4px; }
        .btn { background: var(--navy); color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; }
        table { width: 100%; border-collapse: collapse; background: white; border: 1px solid var(--border); }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: left; }
    </style>
</head>
<body>
    <div class="main-stage">
        <div class="panel">
            <div style="margin-bottom: 20px;"><a href="dashboard.php" style="color: var(--slate); text-decoration: none;">← Dashboard</a></div>
            <h3>Log Course Marks</h3>
            <?php if (!empty($msg)): ?><p style="color:#0369a1; font-weight:600;"><?php echo $msg; ?></p><?php endif; ?>
            <form method="POST" action="grades.php">
                <div class="form-row">
                    <label>Course Unit Code</label>
                    <input type="text" name="unit_code" placeholder="e.g. ICS 2201" required>
                </div>
                <div class="form-row">
                    <label>Achieved Score Metric</label>
                    <select name="grade_point" required>
                        <option value="4.00">Grade A (4.00 GP)</option>
                        <option value="3.50">Grade B+ (3.50 GP)</option>
                        <option value="3.00">Grade B (3.00 GP)</option>
                        <option value="2.50">Grade C+ (2.50 GP)</option>
                    </select>
                </div>
                <button type="submit" class="btn">Submit Matrix</button>
            </form>
        </div>

        <div class="panel">
            <h3>Performance Matrix Progression Log</h3>
            <table>
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th>Unit Code</th>
                        <th>Grade Point Obtained</th>
                        <th>Submission Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grades as $g): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($g['unit_code']); ?></strong></td>
                            <td style="color:#10b981; font-weight:bold;"><?php echo number_format($g['grade_point'], 2); ?></td>
                            <td><?php echo $g['recorded_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>