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
    $unit_code = strtoupper(trim($_POST['unit_code']));
    $grade_before = trim($_POST['grade_before']);
    $grade_after = trim($_POST['grade_after']);

    $upload_dir = '../uploads/grades/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $proof_before_path = null;
    $proof_after_path = null;

    if (isset($_FILES['proof_before']) && $_FILES['proof_before']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['proof_before']['name'], PATHINFO_EXTENSION));
        $name = "before_" . $learner_id . "_" . md5(uniqid(rand(), true)) . "." . $ext;
        move_uploaded_file($_FILES['proof_before']['tmp_name'], $upload_dir . $name);
        $proof_before_path = $upload_dir . $name;
    }
    if (isset($_FILES['proof_after']) && $_FILES['proof_after']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['proof_after']['name'], PATHINFO_EXTENSION));
        $name = "after_" . $learner_id . "_" . md5(uniqid(rand(), true)) . "." . $ext;
        move_uploaded_file($_FILES['proof_after']['tmp_name'], $upload_dir . $name);
        $proof_after_path = $upload_dir . $name;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO academic_progress (learner_id, unit_code, grade_before, grade_after, proof_before, proof_after) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$learner_id, $unit_code, $grade_before, $grade_after ?: null, $proof_before_path, $proof_after_path]);
        $msg = "Grade record tracked successfully!";
    } catch (PDOException $e) {
        $msg = "Error mapping score: " . $e->getMessage();
    }
}

// Fetch existing profile evaluation logs
$grades = [];
try {
    $stmt = $pdo->prepare("SELECT unit_code, grade_before, grade_after, recorded_at FROM academic_progress WHERE learner_id = ? ORDER BY recorded_at DESC");
    $stmt->execute([$learner_id]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Grade collection failure: " . $e->getMessage());
}
$progress_reports = [];
try {
    $prStmt = $pdo->prepare("
        SELECT pr.*, u.name as tutor_name, b.unit_code 
        FROM progress_reports pr 
        JOIN users u ON pr.tutor_id = u.id 
        JOIN bookings b ON pr.booking_id = b.id 
        WHERE pr.learner_id = ? 
        ORDER BY pr.created_at DESC
    ");
    $prStmt->execute([$learner_id]);
    $progress_reports = $prStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Progress report fetch error: " . $e->getMessage());
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
        .main-stage { flex: 1; padding: 40px; display: grid; grid-template-columns: 1fr 2fr; gap: 30px; grid-template-rows: auto auto; }
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
            
            
<form method="POST" action="grades.php" enctype="multipart/form-data">
    <div class="form-row">
        <label>Course Unit Code</label>
        <input type="text" name="unit_code" placeholder="e.g. ICS 2201" required>
    </div>
    <div class="form-row">
        <label>Grade Before Tutoring</label>
        <select name="grade_before" required>
            <option value="A">Grade A (70% - 100%)</option>
            <option value="B+">Grade B+ (65% - 69%)</option>
            <option value="B">Grade B (60% - 64%)</option>
            <option value="C+">Grade C+ (55% - 59%)</option>
            <option value="C">Grade C (50% - 54%)</option>
        </select>
    </div>
    <div class="form-row">
        <label>Proof Before Tutoring(Initial Script|Image)</label>
        <input type="file" name="proof_before" accept=".pdf,.jpg,.jpeg,.png" required>
    </div>
    <div class="form-row">
        <label>Grade After Tutoring(Final Script|Result Image)</label>
        <select name="grade_after">
            <option value="">-- Not Completed/Evaluated Yet --</option>
            <option value="A">Grade A (70% - 100%)</option>
            <option value="B+">Grade B+ (65% - 69%)</option>
            <option value="B">Grade B (60% - 64%)</option>
            <option value="C+">Grade C+ (55% - 59%)</option>
            <option value="C">Grade C (50% - 54%)</option>
        </select>
    </div>
    <div class="form-row">
        <label>Proof After (optional)</label>
        <input type="file" name="proof_after" accept=".pdf,.jpg,.jpeg,.png">
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
                                <td style="color:#10b981; font-weight:bold;"><?php echo htmlspecialchars($g['grade_after'] ?? 'Pending'); ?></td>
                                <td style="color: var(--slate);"><?php echo date('M d, Y - H:i', strtotime($g['recorded_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="panel">
    <h3>Tutor Feedback & Progress Reports</h3>
    <?php if (empty($progress_reports)): ?>
        <p style="color: var(--slate); font-style: italic; text-align: center; padding: 20px 0;">No progress reports filed by your tutors yet.</p>
    <?php else: ?>
        <?php foreach ($progress_reports as $pr): ?>
            <div style="border-bottom: 1px solid var(--border); padding: 15px 0;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                    <span style="font-size: 14px; color: var(--navy);">
                        Tutor: <strong><?php echo htmlspecialchars($pr['tutor_name']); ?></strong> <br>
                        Unit: <span style="font-weight: 600;"><?php echo htmlspecialchars($pr['unit_code']); ?></span>
                    </span>
                    <span style="font-size: 11px; background: #f1f5f9; color: var(--navy); padding: 4px 8px; border-radius: 12px; font-weight: 700; text-transform: uppercase; white-space: nowrap;">
                        <?php echo htmlspecialchars($pr['performance_assessment']); ?>
                    </span>
                </div>
                <p style="margin: 10px 0 5px 0; font-size: 13px; color: var(--slate); font-style: italic; background: #f8fafc; padding: 10px; border-radius: 4px; line-height: 1.5;">
                    "<?php echo htmlspecialchars($pr['academic_remarks']); ?>"
                </p>
                <small style="color: #94a3b8; font-weight: 500;">Filed on: <?php echo date('M d, Y - H:i', strtotime($pr['created_at'])); ?></small>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
    </div>
</body>
</html>