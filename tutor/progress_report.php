<?php
// tutor/progress_report.php
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

// Handle filing a new progress report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_report'])) {
    $booking_id = (int)$_POST['booking_id'];
    $academic_remarks = trim($_POST['remarks']);
    $rating_assessment = trim($_POST['assessment']);

    try {
        // Securely fetch the actual learner_id mapped to this booking to prevent manual parameter injection
        $verifyStmt = $pdo->prepare("SELECT learner_id FROM bookings WHERE booking_id = ? AND tutor_id = ?");
        $verifyStmt->execute([$booking_id, $tutor_id]);
        $learner_id = $verifyStmt->fetchColumn();

        if ($learner_id) {
            $stmt = $pdo->prepare("INSERT INTO progress_reports (booking_id, tutor_id, learner_id, academic_remarks, performance_assessment) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$booking_id, $tutor_id, $learner_id, $academic_remarks, $rating_assessment]);
            
            $message = "Student performance report committed safely!";
            $message_type = 'success';
        } else {
            $message = "Error: Booking verification cross-check dropped. Access denied.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "Database logging error: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch active student reference rows to review academic grades
$students_grades = [];
$past_reports = [];
try {
    // Look up historical unit performance metrics submitted by linked learners (Fixed column mapping: booking_id)
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id as learner_id, u.name, ap.unit_code, ap.grade_point 
        FROM academic_progress ap 
        JOIN users u ON ap.learner_id = u.id 
        WHERE u.id IN (SELECT DISTINCT learner_id FROM bookings WHERE tutor_id = ?)
    ");
    $stmt->execute([$tutor_id]);
    $students_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch existing filed progress reports (Fixed column mapping: booking_id)
    $rStmt = $pdo->prepare("
        SELECT pr.*, u.name as student_name, b.unit_code 
        FROM progress_reports pr 
        JOIN users u ON pr.learner_id = u.id 
        JOIN bookings b ON pr.booking_id = b.booking_id 
        WHERE pr.tutor_id = ? 
        ORDER BY pr.created_at DESC
    ");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Reports Matrix - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); padding: 40px; margin: 0; }
        .wrapper { max-width: 1200px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--border); padding-bottom: 20px; }
        .layout-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .card-box { background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); height: fit-content; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #edf2f7; color: var(--navy); font-weight: 600; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        input, textarea, select { padding: 10px; border: 1px solid var(--border); border-radius: 4px; font-family: inherit; font-size: 14px; }
        input:focus, textarea:focus, select:focus { border-color: var(--navy); outline: none; }
        .btn-submit { background: var(--navy); color: white; padding: 12px; border: none; border-radius: 4px; font-weight: bold; width: 100%; cursor: pointer; transition: opacity 0.2s; }
        .btn-submit:hover { opacity: 0.9; }
    </style>
</head>
<body>

    <div class="wrapper">
        <header>
            <div>
                <h1 style="margin: 0; font-size: 24px; color: var(--navy);">Student Progress Metrics</h1>
                <p style="margin: 5px 0 0 0; color: var(--slate);">Instructor: <strong><?php echo $tutor_name; ?></strong></p>
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
                <h3 style="color: var(--navy); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 10px;">File Performance Report</h3>
                
                <form method="POST" action="progress_report.php">
                    <input type="hidden" name="action_report" value="1">
                    
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600; color: var(--slate);">Target Student & Class Allocation</label>
                        <select name="booking_id" required>
                            <option value="">-- Choose Concluded Active Session --</option>
                            <?php
                            // Updated condition: Checks for both completed and claimed status tokens
                            $bStmt = $pdo->prepare("
                                SELECT b.booking_id, b.learner_id, u.name, b.unit_code 
                                FROM bookings b 
                                JOIN users u ON b.learner_id = u.id 
                                WHERE b.tutor_id = ? AND b.status IN ('completed', 'claimed')
                            ");
                            $bStmt->execute([$tutor_id]);
                            while($b = $bStmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='{$b['booking_id']}'>{$b['name']} ({$b['unit_code']})</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600; color: var(--slate);">Current Performance Status Rating</label>
                        <select name="assessment" required>
                            <option value="Excellent Progression">Excellent Progression</option>
                            <option value="Steady Growth Needed">Steady Growth Needed</option>
                            <option value="Critical Remedial Attention Required">Critical Remedial Attention Required</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600; color: var(--slate);">Academic Observation Remarks</label>
                        <textarea name="remarks" rows="4" placeholder="Enter constructive milestones tracking feedback here..." required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Commit Report Data</button>
                </form>

                <h3 style="margin-top: 35px; color: var(--navy); border-bottom: 1px solid var(--border); padding-bottom: 10px;">Audited Learner Core Grades</h3>
                <table>
                    <thead>
                        <tr><th>Student</th><th>Course Unit</th><th>Grade Point</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students_grades)): ?>
                            <tr><td colspan="3" style="text-align: center; color: var(--slate); font-style: italic;">No student tracking records found.</td></tr>
                        <?php else: ?>
                            <?php foreach($students_grades as $sg): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($sg['name']); ?></strong></td>
                                    <td><span style="font-weight: 600; color: var(--navy);"><?php echo htmlspecialchars($sg['unit_code']); ?></span></td>
                                    <td style="color: #10b981; font-weight: bold;"><?php echo number_format($sg['grade_point'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-box">
                <h3 style="color: var(--navy); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Submitted Reports Log Pipeline</h3>
                <?php if(empty($past_reports)): ?>
                    <p style="color: var(--slate); font-size: 14px; font-style: italic; padding: 20px 0; text-align: center;">No progress metrics logged to system files yet.</p>
                <?php else: ?>
                    <?php foreach($past_reports as $pr): ?>
                        <div style="border-bottom: 1px solid var(--border); padding: 15px 0;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                                <span style="font-size: 14px; color: var(--navy);">
                                    Student: <strong><?php echo htmlspecialchars($pr['student_name']); ?></strong> <br>
                                    Unit Code: <span style="font-weight: 600;"><?php echo htmlspecialchars($pr['unit_code']); ?></span>
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
    </div>

</body>
</html>