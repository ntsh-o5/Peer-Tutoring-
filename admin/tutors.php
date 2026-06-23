<?php
// admin/tutors.php
session_start();

// Strict security gate: Enforce administrative authorization parameters
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

// Safe extraction of alert states across immediate workflow redirections
$message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : '';
$message_type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// 1. Handle Admin Verification State Mutations
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'], $_POST['credential_id'])) {
    $credential_id = (int)$_POST['credential_id'];
    $action = trim($_POST['action']);
    
    try {
        $pdo->beginTransaction();
        
        if ($action === 'approve') {
            // Update the specific application status to approved
            $updateStmt = $pdo->prepare("UPDATE tutor_credentials SET submission_status = 'approved' WHERE credential_id = ?");
            $updateStmt->execute([$credential_id]);

            // Elevate the user global system role to tutor
            $userStmt = $pdo->prepare("
                UPDATE users SET role = 'tutor' WHERE id = (
                    SELECT tutor_id FROM tutor_credentials WHERE credential_id = ?
                )
            ");
            $userStmt->execute([$credential_id]);
            $msg = "Application approved! User promoted to Tutor status.";
        } elseif ($action === 'decline') {
            // Update the application status to declined
            $updateStmt = $pdo->prepare("UPDATE tutor_credentials SET submission_status = 'declined' WHERE credential_id = ?");
            $updateStmt->execute([$credential_id]);

            // Revert or keep user as learner
            $userStmt = $pdo->prepare("
                UPDATE users SET role = 'learner' WHERE id = (
                    SELECT tutor_id FROM tutor_credentials WHERE credential_id = ?
                )
            ");
            $userStmt->execute([$credential_id]);
            $msg = "Application declined. User profile reverted to Learner.";
        }
        
        $pdo->commit();
        
        $_SESSION['flash_message'] = $msg;
        $_SESSION['flash_type'] = 'success';
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = "Database transactional error: " . $e->getMessage();
        $_SESSION['flash_type'] = 'error';
    }
    
    header("Location: tutors.php" . (!empty($_GET['search']) ? '?search=' . urlencode($_GET['search']) : ''));
    exit;
}

// 2. Capture and query dataset with the correct column maps
$search_query = "";
$bindings = [];

// FIXED: Using tc.submission_status directly from your updated table
$sql_query = "
    SELECT tc.credential_id, tc.tutor_id, tc.unit_code, tc.transcript_path, tc.submission_status, u.name as tutor_name
    FROM tutor_credentials tc 
    JOIN users u ON tc.tutor_id = u.id
";

if (isset($_GET['search']) && trim($_GET['search']) !== "") {
    $search_query = trim($_GET['search']);
    $sql_query .= " WHERE u.name ILIKE ? OR tc.unit_code ILIKE ?"; 
    $bindings = ["%$search_query%", "%$search_query%"];
}

$sql_query .= " ORDER BY tc.credential_id DESC";

try {
    $stmt = $pdo->prepare($sql_query);
    $stmt->execute($bindings);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Lookup failure: " . $e->getMessage());
    $applications = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Verification Desk - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 40px; box-sizing: border-box; }
        header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--border); padding-bottom: 20px; margin-bottom: 30px; }
        .search-container form { display: flex; gap: 10px; margin-bottom: 25px; }
        .search-container input { padding: 10px; border: 1px solid var(--border); border-radius: 6px; width: 300px; font-size: 14px; outline: none; }
        .search-container button { background: var(--navy); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .table-container { background: white; padding: 20px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); vertical-align: middle; }
        th { background: #f1f5f9; color: var(--navy); font-weight: 600; }
        .status-badge { font-size: 11px; padding: 4px 10px; border-radius: 12px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-declined { background: #fee2e2; color: #b91c1c; }
        .btn-action { padding: 6px 14px; border: none; border-radius: 4px; font-weight: 600; font-size: 12px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-view { background: #e0f2fe; color: #0369a1; }
        .btn-approve { background: #10b981; color: white; }
        .btn-approve:hover { background: #059669; }
        .btn-decline { background: #ef4444; color: white; }
        .btn-decline:hover { background: #dc2626; }
    </style>
</head>
<body>

    <?php include('../includes/sidebar.php'); ?>

    <div class="main-content">
        <header>
            <div>
                <h1 style="margin: 0; font-size: 24px; color: var(--navy);">Instructor Verification Pipeline</h1>
                <p style="margin: 5px 0 0 0; color: var(--slate);">Review submitted credentials and authorize applicant portal routing privileges.</p>
            </div>
            <a href="../auth/logout.php" style="background: #fee2e2; color: #b91c1c; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">Log Out</a>
        </header>

        <?php if(!empty($message)): ?>
            <div style="padding: 12px; margin-bottom: 25px; border-radius: 6px; font-size: 14px; font-weight: 500; background: <?php echo $message_type === 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #b91c1c;'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="search-container">
            <form method="GET" action="tutors.php">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search by instructor name or unit code..." 
                    value="<?php echo htmlspecialchars($search_query); ?>"
                >
                <button type="submit">Search Filter</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Submission ID</th>
                        <th>Tutor Candidate</th>
                        <th>Requested Course Unit</th>
                        <th>Credentials Asset Document</th>
                        <th>Pipeline Status</th>
                        <th>System Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($applications)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--slate); font-style: italic; padding: 30px 0;">No active verification records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($applications as $app): ?>
                            <tr>
                                <td>#CRED-<?php echo $app['credential_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($app['tutor_name']); ?></strong></td>
                                <td><span style="font-family: monospace; font-size: 13px; font-weight: bold; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($app['unit_code']); ?></span></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($app['transcript_path']); ?>" target="_blank" class="btn-action btn-view">📄 View File</a>
                                </td>
                                <td>
                                    <?php 
                                    $status = strtolower(trim($app['submission_status'] ?? 'pending'));
                                    if($status === 'approved') {
                                        echo '<span class="status-badge status-approved">Approved</span>';
                                    } elseif($status === 'declined') {
                                        echo '<span class="status-badge status-declined">Declined</span>';
                                    } else {
                                        echo '<span class="status-badge status-pending">Pending Audit</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; align-items: center;">
                                        <?php if($status !== 'approved'): ?>
                                            <form method="POST" action="tutors.php<?php echo !empty($search_query) ? '?search=' . urlencode($search_query) : ''; ?>" style="margin: 0; display: inline;" onsubmit="return confirm('Approve verification for this unit?');">
                                                <input type="hidden" name="credential_id" value="<?php echo $app['credential_id']; ?>">
                                                <button type="submit" name="action" value="approve" class="btn-action btn-approve">Approve</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if($status !== 'declined'): ?>
                                            <form method="POST" action="tutors.php<?php echo !empty($search_query) ? '?search=' . urlencode($search_query) : ''; ?>" style="margin: 0; display: inline;" onsubmit="return confirm('Decline verification for this unit?');">
                                                <input type="hidden" name="credential_id" value="<?php echo $app['credential_id']; ?>">
                                                <button type="submit" name="action" value="decline" class="btn-action btn-decline">Decline</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
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