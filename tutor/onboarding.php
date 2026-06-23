<?php
// tutor/onboarding.php
session_start();

// 1. Strict security gate: Must be logged in as a tutor
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$tutor_id = $_SESSION['user_id'];
$tutor_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Applicant';

// =========================================================================
// AUTOMATIC APPROVAL INTERCEPT ROUTER
// =========================================================================
try {
    // Look for at least one approved unit assignment for this user
    $approvalCheck = $pdo->prepare("SELECT COUNT(*) FROM tutor_credentials WHERE tutor_id = ? AND submission_status = 'approved'");
    $approvalCheck->execute([$tutor_id]);
    $approved_count = (int)$approvalCheck->fetchColumn();

    if ($approved_count > 0) {
        // Cache verification state inside session context to bypass onboarding later
        $_SESSION['is_verified'] = true;
        
        // Push the user directly into their main dashboard workspace
        header("Location: dashboard.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Bypass handshake query failure: " . $e->getMessage());
}

// If the tutor is already cached as verified via standard session parameters, bypass onboarding completely
if (isset($_SESSION['is_verified']) && $_SESSION['is_verified'] === true) {
    header("Location: dashboard.php");
    exit;
}

$message = '';
$message_type = 'success';

// 2. Handle Document Upload Pipeline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_credentials'])) {
    $unit_code = strtoupper(trim($_POST['unit_code']));
    
    // Check if file upload slot is active
    if (isset($_FILES['transcript']) && $_FILES['transcript']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['transcript']['tmp_name'];
        $file_name = $_FILES['transcript']['name'];
        $file_size = $_FILES['transcript']['size'];
        
        // Extract extension and enforce constraints
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (!in_array($file_ext, $allowed_extensions)) {
            $message = "Invalid file type. Only PDF, JPG, and PNG uploads are accepted.";
            $message_type = 'error';
        } elseif ($file_size > 5 * 1024 * 1024) { // 5MB Limit
            $message = "File size threshold breached. Maximum limit allowed is 5MB.";
            $message_type = 'error';
        } else {
            // Setup a secure uploads directory location
            $upload_dir = '../uploads/credentials/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Rename file using a secure hashed moniker format to prevent path traversal collisions
            $new_file_name = "tutor_" . $tutor_id . "_" . md5(uniqid(rand(), true)) . "." . $file_ext;
            $destination_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $destination_path)) {
                try {
                    // Check if they already have a pending application for this specific unit
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM tutor_credentials WHERE tutor_id = ? AND unit_code = ? AND submission_status = 'pending'");
                    $checkStmt->execute([$tutor_id, $unit_code]);
                    
                    if ($checkStmt->fetchColumn() > 0) {
                        $message = "You already have a pending application request logged for this unit code.";
                        $message_type = 'error';
                    } else {
                        // Log upload token directly into database tracker
                        $stmt = $pdo->prepare("INSERT INTO tutor_credentials (tutor_id, unit_code, transcript_path) VALUES (?, ?, ?)");
                        $stmt->execute([$tutor_id, $unit_code, $destination_path]);
                        
                        $message = "Qualifications submitted successfully! Awaiting administrator vetting pipeline.";
                        $message_type = 'success';
                    }
                } catch (PDOException $e) {
                    $message = "Database synchronization error: " . $e->getMessage();
                    $message_type = 'error';
                }
            } else {
                $message = "File system move process faulted. Check server permissions.";
                $message_type = 'error';
            }
        }
    } else {
        $message = "Please upload a valid supporting transcript or certificate asset document.";
        $message_type = 'error';
    }
}

// 3. Fetch current verification application history logs
$my_submissions = [];
try {
    $subStmt = $pdo->prepare("SELECT * FROM tutor_credentials WHERE tutor_id = ? ORDER BY submitted_at DESC");
    $subStmt->execute([$tutor_id]);
    $my_submissions = $subStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Onboarding history query failure: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Verification Onboarding - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; --amber: #d97706; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); padding: 40px; margin: 0; }
        .wrapper { max-width: 1100px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--border); padding-bottom: 20px; }
        .grid-split { display: grid; grid-template-columns: 1fr 1.2fr; gap: 40px; }
        .box-card { background: white; padding: 30px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); height: fit-content; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        input, select { padding: 11px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; }
        input:focus, select:focus { border-color: var(--navy); outline: none; }
        .btn-upload { background: var(--navy); color: white; border: none; padding: 14px; border-radius: 4px; font-weight: bold; width: 100%; cursor: pointer; font-size: 14px; transition: opacity 0.2s; }
        .btn-upload:hover { opacity: 0.9; }
        .status-badge { font-size: 12px; padding: 4px 10px; border-radius: 12px; font-weight: bold; text-transform: uppercase; }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-rejected { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

    <div class="wrapper">
        <header>
            <div>
                <h1 style="margin: 0; font-size: 24px; color: var(--navy);">Instructor Verification Desk</h1>
                <p style="margin: 5px 0 0 0; color: var(--slate);">Welcome, <strong><?php echo $tutor_name; ?></strong>. Please submit your academic alignment records below.</p>
            </div>
            <a href="../auth/logout.php" style="background: #fee2e2; color: #b91c1c; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">\uD83D\uDEAA Log Out</a>
        </header>

        <?php if(!empty($message)): ?>
            <div style="padding: 12px; margin-bottom: 25px; border-radius: 6px; font-size: 14px; font-weight: 500; background: <?php echo $message_type === 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #b91c1c;'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid-split">
            <div class="box-card">
                <h3 style="color: var(--navy); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Upload Competency Unit</h3>
                <p style="color: var(--slate); font-size: 13px; margin-bottom: 20px; line-height: 1.5;">
                    To protect academic standards on PeerTutor, you must upload your university transcript or official proof of qualification for each specific unit code you want to teach.
                </p>

                <form method="POST" action="onboarding.php" enctype="multipart/form-data">
                    <input type="hidden" name="submit_credentials" value="1">
                    
                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600; color: var(--slate);">Target Course Unit Code</label>
                        <input type="text" name="unit_code" placeholder="e.g. ICS 2104, BIT 2201" required maxlength="15">
                    </div>

                    <div class="form-group">
                        <label style="font-size: 13px; font-weight: 600; color: var(--slate);">Transcript / Certificate Proof (PDF, JPG, PNG)</label>
                        <input type="file" name="transcript" accept=".pdf, .jpg, .jpeg, .png" required style="border: 1px dashed var(--slate); background: #fafafa; cursor: pointer;">
                        <small style="color: #94a3b8; font-size: 11px;">Maximum file upload limit allocation: 5MB</small>
                    </div>

                    <button type="submit" class="btn-upload">Submit Credentials for Review</button>
                </form>
            </div>

            <div class="box-card">
                <h3 style="color: var(--navy); margin-top: 0; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Verification Stream Status Logs</h3>
                
                <?php if (empty($my_submissions)): ?>
                    <p style="color: var(--slate); font-size: 14px; font-style: italic; text-align: center; padding: 30px 0;">
                        No qualification submissions mapped to your profile yet.
                    </p>
                <?php else: ?>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($my_submissions as $sub): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding: 15px 0;">
                                <div>
                                    <strong style="color: var(--navy); font-size: 15px;"><?php echo htmlspecialchars($sub['unit_code']); ?></strong>
                                    <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">
                                        Filed on: <?php echo date('M d, Y', strtotime($sub['submitted_at'])); ?>
                                    </div>
                                </div>
                                <div>
                                    <?php 
                                    $status = strtolower(trim($sub['submission_status']));
                                    if ($status === 'pending') {
                                        echo '<span class="status-badge status-pending">Awaiting Review</span>';
                                    } elseif ($status === 'approved') {
                                        echo '<span class="status-badge status-approved">Approved \u2705</span>';
                                    } else {
                                        echo '<span class="status-badge status-rejected">Rejected \u274C</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 6px; padding: 12px; margin-top: 25px; font-size: 13px; font-weight: 500; line-height: 1.4;">
                        \uD83D\uDCA1 <strong>Note:</strong> Once an administrator verifies and marks at least one of your unit credential submissions as <strong>Approved</strong>, your system role constraints will refresh to grant access to your primary operations dashboard hub.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>