<?php
// tutor/compensation.php
session_start();

// Strict security gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$tutor_id = $_SESSION['user_id'];
$tutor_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Tutor';
$claim_msg = '';
$message_type = 'success';

// Setup platform rate (500 KES per complete session)
$base_rate_per_session = 500;
$completed_count = 0;
$paid_count = 0;

// Dynamic check logic wrapped into an executable transaction block
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_trigger'])) {
    try {
        // Double check balances before committing pipeline state changes
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tutor_id = ? AND status = 'completed'");
        $checkStmt->execute([$tutor_id]);
        $active_balance_count = (int)$checkStmt->fetchColumn();

        if ($active_balance_count > 0) {
            $pdo->beginTransaction();
            
            // Advance status from completed to claimed so it moves out of the active accruals pool
            $updateStatus = $pdo->prepare("UPDATE bookings SET status = 'claimed' WHERE tutor_id = ? AND status = 'completed'");
            $updateStatus->execute([$tutor_id]);
            
            $pdo->commit();
            $claim_msg = "Compensation payout request logged! Admin verification protocol triggered.";
            $message_type = 'success';
        } else {
            $claim_msg = "No clear balances pending compilation metrics.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $claim_msg = "Compensation System Fault: " . $e->getMessage();
        $message_type = 'error';
    }
}

// Fetch up-to-date baseline values for dashboard display
try {
    // 1. Unclaimed sessions 
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tutor_id = ? AND status = 'completed'");
    $stmt->execute([$tutor_id]);
    $completed_count = (int)$stmt->fetchColumn();

    // 2. Historically settled sessions (matching 'reviewed' or paid tokens)
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tutor_id = ? AND status IN ('reviewed', 'paid')");
    $stmt2->execute([$tutor_id]);
    $paid_count = (int)$stmt2->fetchColumn();
} catch(PDOException $e) {
    error_log("Compensation computation error: " . $e->getMessage());
}

$accrued_payout = $completed_count * $base_rate_per_session;
$total_paid = $paid_count * $base_rate_per_session;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compensation Dashboard - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); padding: 40px; margin: 0; display: flex; min-height: 100vh; }
        .main-stage { flex: 1; max-width: 800px; margin: 0 auto; }
        .container { background: white; padding: 30px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--border); padding-bottom: 20px; }
        .box-row { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
        .btn-claim { background: #10b981; color: white; border: none; padding: 14px; border-radius: 4px; font-weight: bold; width: 100%; cursor: pointer; font-size: 14px; margin-top: 20px; transition: background 0.2s; }
        .btn-claim:hover { background: #059669; }
    </style>
</head>
<body>

    <div class="main-stage">
        <header>
            <div>
                <h1 style="margin: 0; font-size: 24px; color: var(--navy);">Financial Compensation</h1>
                <p style="margin: 5px 0 0 0; color: var(--slate);">Instructor: <strong><?php echo $tutor_name; ?></strong></p>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="dashboard.php" style="color: var(--slate); text-decoration: none; font-weight: 600; font-size: 13px;">← Dashboard Hub</a>
                <a href="../auth/logout.php" style="background: #fee2e2; color: #b91c1c; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px;">🚪 Terminate Session</a>
            </div>
        </header>

        <div class="container">
            <?php if(!empty($claim_msg)): ?>
                <div style="padding: 12px; margin-bottom: 20px; border-radius: 6px; font-size: 14px; font-weight: 500; background: <?php echo $message_type === 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #b91c1c;'; ?>">
                    <?php echo $claim_msg; ?>
                </div>
            <?php endif; ?>

            <div style="background: #f1f5f9; padding: 25px; border-radius: 6px; text-align: center; margin-bottom: 25px;">
                <span style="font-size: 12px; text-transform: uppercase; color: var(--slate); font-weight: 700; letter-spacing: 0.5px;">Pending Unclaimed Accruals Balance</span>
                <h1 style="margin: 10px 0 0 0; color: #10b981; font-size: 38px; font-weight: 800;">KES <?php echo number_format($accrued_payout, 2); ?></h1>
            </div>

            <div class="box-row">
                <span>Verified Sessions Awaiting Settlement:</span>
                <strong><?php echo $completed_count; ?> Sessions</strong>
            </div>
            <div class="box-row">
                <span>Historical Settled Balance Distributed:</span>
                <span style="color: var(--slate); font-weight: 600;">KES <?php echo number_format($total_paid, 2); ?> (<?php echo $paid_count; ?> slots)</span>
            </div>
            <div class="box-row">
                <span>Base Platform Hourly Instruction Rate:</span>
                <span style="color: var(--navy); font-weight: 600;">KES <?php echo number_format($base_rate_per_session, 2); ?> / unit</span>
            </div>

            <form method="POST" action="compensation.php">
                <input type="hidden" name="claim_trigger" value="1">
                <button type="submit" class="btn-claim" <?php echo ($accrued_payout <= 0) ? 'disabled style="background: #cbd5e1; cursor: not-allowed;"' : ''; ?>>
                    Disburse Accrued Balances
                </button>
            </form>
        </div>
    </div>

</body>
</html>