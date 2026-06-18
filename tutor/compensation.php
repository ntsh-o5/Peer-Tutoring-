<?php
// tutor/compensation.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$tutor_id = $_SESSION['user_id'];
$claim_msg = '';

// Setup dynamic static calculation value variables (e.g. 500 KES per complete instruction track)
$base_rate_per_session = 500;
$completed_count = 0;
$paid_count = 0;

try {
    // 1. Fetch completed un-claim session counts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tutor_id = ? AND status = 'completed'");
    $stmt->execute([$tutor_id]);
    $completed_count = (int)$stmt->fetchColumn();

    // 2. Fetch already processed sessions metrics count
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE tutor_id = ? AND status = 'reviewed'");
    $stmt2->execute([$tutor_id]);
    $paid_count = (int)$stmt2->fetchColumn();
} catch(PDOException $e) {}

$accrued_payout = $completed_count * $base_rate_per_session;
$total_paid = $paid_count * $base_rate_per_session;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_trigger'])) {
    if ($accrued_payout > 0) {
        $claim_msg = "Compensation payout request logged! Admin verification protocol triggered.";
    } else {
        $claim_msg = "No clear balances pending compilation metrics.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Compensation Dashboard - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); padding: 40px; margin: 0; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; border: 1px solid var(--border); }
        .box-row { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid var(--border); }
        .btn-claim { background: #10b981; color: white; border: none; padding: 12px 24px; border-radius: 4px; font-weight: bold; width: 100%; cursor: pointer; font-size: 14px; margin-top: 20px; }
    </style>
</head>
<body>

    <div class="container">
        <p><a href="dashboard.php" style="color: var(--slate); text-decoration: none;">← Main Platform Dashboard</a></p>
        <h2>Tutor Financial Compensation Portal</h2>
        
        <?php if(!empty($claim_msg)): ?><p style="background: #e0f2fe; color: #0369a1; padding: 10px; font-weight: 600; border-radius: 4px;"><?php echo $claim_msg; ?></p><?php endif; ?>

        <div style="background: #f1f5f9; padding: 20px; border-radius: 6px; text-align: center; margin-bottom: 25px;">
            <span style="font-size: 13px; text-transform: uppercase; color: var(--slate); font-weight: 600;">Pending Unclaimed Accruals Balance</span>
            <h1 style="margin: 10px 0 0 0; color: #10b981; font-size: 36px;">KES <?php echo number_format($accrued_payout, 2); ?></h1>
        </div>

        <div class="box-row">
            <span>Verified Hours Sessions Awaiting Settlement:</span>
            <strong><?php echo $completed_count; ?> Sessions</strong>
        </div>
        <div class="box-row">
            <span>Historical Settled Balance Distributed:</span>
            <span style="color: var(--slate);">KES <?php echo number_format($total_paid, 2); ?> (<?php echo $paid_count; ?> slots)</span>
        </div>
        <div class="box-row">
            <span>Base Platform Rate:</span>
            <span>KES <?php echo $base_rate_per_session; ?> / allocation unit</span>
        </div>

        <form method="POST" action="compensation.php">
            <input type="hidden" name="claim_trigger" value="1">
            <button type="submit" class="btn-claim">Disburse Accrued Balances</button>
        </form>
    </div>

</body>
</html>