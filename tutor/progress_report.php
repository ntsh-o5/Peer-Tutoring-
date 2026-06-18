<?php
// tutor/progress_report.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$tutor_id = $_SESSION['user_id'];
$message = '';

// Handle filing a new progress report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_report'])) {
    $booking_id = (int)$_POST['booking_id'];
    $learner_id = (int)$_POST['learner_id'];
    $academic_remarks = trim($_POST['remarks']);
    $rating_assessment = trim($_POST['assessment']);

    try {
        $stmt = $pdo->prepare("INSERT INTO progress_reports (booking_id, tutor_id, learner_id, academic_remarks, performance_assessment) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$booking_id, $tutor_id, $learner_id, $academic_remarks, $rating_assessment]);
        $message = "Student performance report committed safely!";
    } catch (PDOException $e) {
        $message = "Database logging error: " . $e->getMessage();
    }
}

// Fetch active student reference rows to review academic grades
$students_grades = [];
$past_reports = [];
try {
    // Look up historical unit performance metrics submitted by linked learners
    $stmt = $pdo->prepare("SELECT DISTINCT u.id as learner_id, u.name, ap.unit_code, ap.grade_point FROM academic_progress ap JOIN users u ON ap.learner_id = u.id WHERE u.id IN (SELECT DISTINCT learner_id FROM bookings WHERE tutor_id = ?)");
    $stmt->execute([$tutor_id]);
    $students_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch existing filed progress reports
    $rStmt = $pdo->prepare("SELECT pr.*, u.name as student_name, b.unit_code FROM progress_reports pr JOIN users u ON pr.learner_id = u.id JOIN bookings b ON pr.booking_id = b.id WHERE pr.tutor_id = ? ORDER BY pr.created_at DESC");
    $rStmt->execute([$tutor_id]);
    $past_reports = $rStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Progress metrics error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Progress Reports Matrix - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); padding: 40px; margin: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card-box { background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); height: fit-content; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #edf2f7; color: var(--navy); }
        .form-group { display: flex; flex-direction: column; gap: 5px; margin-bottom: 12px; }
        input, textarea, select { padding: 10px; border: 1px solid var(--border); border-radius: 4px; }
    </style>
</head>
<body>

    <div class="card-box">
        <p><a href="dashboard.php" style="color: var(--slate); text-decoration: none;">← Dashboard Hub</a></p>
        <h2>File Performance Progress Report</h2>
        <?php if(!empty($message)): ?><p style="color: #0369a1; font-weight: 600;"><?php echo $message; ?></p><?php endif; ?>
        
        <form method="POST" action="progress_report.php">
            <input type="hidden" name="action_report" value="1">
            <div class="form-group">
                <label>Select Target Student Transaction</label>
                <select name="booking_id" required>
                    <option value="">-- Choose Relevant Active Session --</option>
                    <?php
                    $bStmt = $pdo->prepare("SELECT b.id, b.learner_id, u.name, b.unit_code FROM bookings b JOIN users u ON b.learner_id = u.id WHERE b.tutor_id = ? AND b.status='completed'");
                    $bStmt->execute([$tutor_id]);
                    while($b = $bStmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='{$b['id']}'>{$b['name']} ({$b['unit_code']})</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Confirm Student User ID</label>
                <input type="number" name="learner_id" placeholder="Input match student ID parameter" required>
            </div>
            <div class="form-group">
                <label>Current Performance Status Rating</label>
                <select name="assessment" required>
                    <option value="Excellent Progression">Excellent Progression</option>
                    <option value="Steady Growth Needed">Steady Growth Needed</option>
                    <option value="Critical Remedial Attention Required">Critical Remedial Attention Required</option>
                </select>
            </div>
            <div class="form-group">
                <label>Academic Observation Remarks</label>
                <textarea name="remarks" rows="4" placeholder="Enter constructive feedback mapping milestones achieved..." required></textarea>
            </div>
            <button type="submit" style="background: var(--navy); color: white; padding: 12px; border: none; border-radius: 4px; font-weight: bold; width: 100%; cursor: pointer;">Commit Report Data</button>
        </form>

        <h3 style="margin-top: 30px;">Audited Learner Core Grades Baseline</h3>
        <table>
            <thead>
                <tr><th>Student</th><th>Course Unit</th><th>Grade Point Metric</th></tr>
            </thead>
            <tbody>
                <?php foreach($students_grades as $sg): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($sg['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($sg['unit_code']); ?></td>
                        <td style="color: #10b981; font-weight: bold;"><?php echo number_format($sg['grade_point'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card-box">
        <h2>Submitted Reports Log Pipeline</h2>
        <?php if(empty($past_reports)): ?>
            <p style="color: var(--slate); font-size: 14px;">No progress metrics logged to system files yet.</p>
        <?php else: ?>
            <?php foreach($past_reports as $pr): ?>
                <div style="border-bottom: 1px solid var(--border); padding: 15px 0;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <strong>Student: <?php echo htmlspecialchars($pr['student_name']); ?> (<?php echo htmlspecialchars($pr['unit_code']); ?>)</strong>
                        <span style="font-size:11px; background:#e2e8f0; padding:3px 6px; border-radius:4px; font-weight:600;"><?php echo htmlspecialchars($pr['performance_assessment']); ?></span>
                    </div>
                    <p style="margin: 5px 0; font-size: 13px; color: var(--slate); font-style: italic;">"<?php echo htmlspecialchars($pr['academic_remarks']); ?>"</p>
                    <small style="color:#94a3b8;"><?php echo $pr['created_at']; ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</body>
</html>