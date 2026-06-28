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
$msg_type = 'success';

// Standard secure local storage target paths
$upload_dir = '../uploads/proofs/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit_code = strtoupper(trim($_POST['unit_code']));
    $grade_before = trim($_POST['grade_before']);
    // Ensure empty option selection translates strictly to a database NULL value
    $grade_after = !empty($_POST['grade_after']) ? trim($_POST['grade_after']) : null;
    
    $proof_before_path = '';
    $proof_after_path = null;
    $upload_ok = true;

    // Isolate multi-part file uploads safely
    $processUpload = function($file_key, $label) use ($upload_dir, &$upload_ok, &$msg, &$msg_type) {
        if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
            return '';
        }
        
        $file_name = $_FILES[$file_key]['name'];
        $file_tmp  = $_FILES[$file_key]['tmp_name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed   = ['jpg', 'jpeg', 'png'];

        if (!in_array($file_ext, $allowed)) {
            $msg = "Invalid file type extension for $label. Please upload JPG or PNG images only.";
            $msg_type = 'error';
            $upload_ok = false;
            return '';
        }

        // Generate unique cryptographically un-guessable filename tokens
        $new_filename = uniqid('proof_' . $file_key . '_', true) . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;

        if (move_uploaded_file($file_tmp, $destination)) {
            return $destination;
        } else {
            $msg = "Internal filesystem write error on $label execution.";
            $msg_type = 'error';
            $upload_ok = false;
            return '';
        }
    };

    // Process file tracking logic safely
    if (!empty($_FILES['proof_before']['name'])) {
        $proof_before_path = $processUpload('proof_before', 'Baseline Evidence');
    }
    if (!empty($_FILES['proof_after']['name']) && $upload_ok) {
        $proof_after_path = $processUpload('proof_after', 'Terminal Evidence');
    }

    // Process PostgreSQL data layers safely
    if ($upload_ok) {
        try {
            // Check if an academic entry row already exists for this unit code to update it
            $chk = $pdo->prepare("SELECT id, proof_before, proof_after FROM academic_progress WHERE learner_id = ? AND unit_code = ?");
            $chk->execute([$learner_id, $unit_code]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Keep the original proof path if a new file wasn't explicitly uploaded
                $final_before_path = !empty($proof_before_path) ? $proof_before_path : $existing['proof_before'];
                $final_after_path = !empty($proof_after_path) ? $proof_after_path : $existing['proof_after'];

                $update_sql = "UPDATE academic_progress 
                               SET grade_before = ?, grade_after = ?, proof_before = ?, proof_after = ? 
                               WHERE id = ?";
                $pdo->prepare($update_sql)->execute([$grade_before, $grade_after, $final_before_path, $final_after_path, $existing['id']]);
                $msg = "Academic unit record matrix updated cleanly!";
                $msg_type = 'success';
            } else {
                // Force verification of initial proof for brand new progress entries
                if (empty($proof_before_path)) {
                    $msg = "Baseline metrics submission requires an attached image proof file.";
                    $msg_type = 'error';
                } else {
                    $insert_sql = "INSERT INTO academic_progress (learner_id, unit_code, grade_before, grade_after, proof_before, proof_after) VALUES (?, ?, ?, ?, ?, ?)";
                    $pdo->prepare($insert_sql)->execute([$learner_id, $unit_code, $grade_before, $grade_after, $proof_before_path, $proof_after_path]);
                    $msg = "New performance progression metrics saved successfully!";
                    $msg_type = 'success';
                }
            }
        } catch (PDOException $e) {
            $msg = "Data Layer Fault: " . $e->getMessage();
            $msg_type = 'error';
        }
    }
}

// Extract unique distinct units the student has processed via bookings
$enrolled_units = [];
try {
    $unitStmt = $pdo->prepare("SELECT DISTINCT unit_code FROM bookings WHERE learner_id = ? ORDER BY unit_code ASC");
    $unitStmt->execute([$learner_id]);
    $enrolled_units = $unitStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Enrolled unit lookup execution error: " . $e->getMessage());
}

// Fetch general progression metrics to build the interactive table view
$grades = [];
try {
    $stmt = $pdo->prepare("SELECT unit_code, grade_before, grade_after, proof_before, proof_after, recorded_at FROM academic_progress WHERE learner_id = ? ORDER BY recorded_at DESC");
    $stmt->execute([$learner_id]);
    $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Grade matrix extraction breakdown: " . $e->getMessage());
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
        body { display: flex; min-height: 100vh; background: var(--light); margin: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        .main-stage { flex: 1; padding: 40px; display: grid; grid-template-columns: 1fr 2fr; gap: 30px; }
        .panel { background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border); height: fit-content; box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        .panel h3 { margin-top: 0; color: var(--navy); border-bottom: 1px solid var(--border); padding-bottom: 10px; }
        .form-row { margin-bottom: 15px; display: flex; flex-direction: column; gap: 5px; }
        .form-row label { font-size: 13px; font-weight: 600; color: var(--slate); }
        input, select { padding: 10px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; background-color: #fff; }
        input:focus, select:focus { outline: none; border-color: var(--navy); }
        .btn { background: var(--navy); color: white; padding: 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; font-size: 14px; text-transform: uppercase; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; background: white; border: 1px solid var(--border); border-radius: 6px; overflow: hidden; }
        th, td { padding: 12px 15px; border-bottom: 1px solid var(--border); text-align: left; font-size: 14px; }
        th { background: #edf2f7; color: var(--navy); }
        .proof-link { display: inline-block; padding: 2px 6px; background: #e2e8f0; color: var(--navy); border-radius: 4px; font-size: 11px; font-weight: 600; text-decoration: none; }
        .proof-link:hover { background: var(--navy); color: white; }
    </style>
</head>
<body>
    <div class="main-stage">
        
        <!-- Action Submission Interface Form Section -->
        <div class="panel">
            <div style="margin-bottom: 20px;">
                <a href="dashboard.php" style="color: var(--slate); text-decoration: none; font-weight: 600;">← Back to Dashboard</a>
            </div>
            <h3>Log Academic Outcomes</h3>
            
            <?php if (!empty($msg)): ?>
                <p style="font-weight:600; font-size:13px; padding:12px; border-radius:4px; <?php echo $msg_type === 'success' ? 'background:#e0f2fe; color:#0369a1;' : 'background:#fee2e2; color:#b91c1c;'; ?>">
                    <?php echo htmlspecialchars($msg); ?>
                </p>
            <?php endif; ?>
            
            <form method="POST" action="grades.php" enctype="multipart/form-data">
                <div class="form-row">
                    <label>Course Unit Undergoing Tutoring</label>
                    <select name="unit_code" required>
                        <option value="">-- Choose Booked Unit --</option>
                        <?php foreach ($enrolled_units as $unit): ?>
                            <option value="<?php echo htmlspecialchars($unit); ?>"><?php echo htmlspecialchars($unit); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <hr style="border: 0; border-top: 1px dashed var(--border); margin: 20px 0;">
                
                <!-- Baseline Metrics Section -->
                <div class="form-row">
                    <label>Grade Point *Before* Tutoring</label>
                    <select name="grade_before" required>
                        <option value="A">Grade A (70% - 100%)</option>
                        <option value="B">Grade B (60% - 69%)</option>
                        <option value="C">Grade C (50% - 59%)</option>
                        <option value="D">Grade D (40% - 49%)</option>
                        <option value="E/F">Grade E/F (0% - 39%)</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>Upload Proof *Before* (Initial Script/Transcript)</label>
                    <input type="file" name="proof_before" accept="image/png, image/jpeg">
                </div>

                <hr style="border: 0; border-top: 1px dashed var(--border); margin: 20px 0;">

                <!-- Terminal Progress Metrics Section -->
                <div class="form-row">
                    <label>Grade Point *After* Tutoring (Optional/Latest)</label>
                    <select name="grade_after">
                        <option value="">-- Not Completed/Evaluated Yet --</option>
                        <option value="A">Grade A (70% - 100%)</option>
                        <option value="B">Grade B (60% - 69%)</option>
                        <option value="C">Grade C (50% - 59%)</option>
                        <option value="D">Grade D (40% - 49%)</option>
                        <option value="E/F">Grade E/F (0% - 39%)</option>
                    </select>
                </div>
                <div class="form-row">
                    <label>Upload Proof *After* (Final Script/Result Image)</label>
                    <input type="file" name="proof_after" accept="image/png, image/jpeg">
                </div>

                <button type="submit" class="btn">Commit Performance Matrix</button>
            </form>
        </div>

        <!-- Metric Progression Visual Board Grid Output Panel -->
        <div class="panel">
            <h3>Performance Matrix Progression Log</h3>
            <table>
                <thead>
                    <tr>
                        <th>Unit Code</th>
                        <th>Grade Before</th>
                        <th>Proof (Pre)</th>
                        <th>Grade After</th>
                        <th>Proof (Post)</th>
                        <th>Last Modified</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($grades)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--slate); font-style: italic; padding: 20px;">No course outcomes verified or logged yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($grades as $g): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($g['unit_code']); ?></strong></td>
                                <td style="color:#ef4444; font-weight:bold;"><?php echo htmlspecialchars($g['grade_before']); ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($g['proof_before']); ?>" target="_blank" class="proof-link">View Proof</a>
                                </td>
                                <td style="color:#10b981; font-weight:bold;">
                                    <?php echo !empty($g['grade_after']) ? htmlspecialchars($g['grade_after']) : '<span style="color:#64748b; font-weight:normal; font-size:12px;">In Progress</span>'; ?>
                                </td>
                                <td>
                                    <?php if (!empty($g['proof_after'])): ?>
                                        <a href="<?php echo htmlspecialchars($g['proof_after']); ?>" target="_blank" class="proof-link">View Proof</a>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:12px;">No upload</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--slate); font-size: 13px;">
                                    <?php echo date('M d, Y', strtotime($g['recorded_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>
</body>
</html>