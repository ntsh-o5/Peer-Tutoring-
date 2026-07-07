<?php
// learner/receipt.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$learner_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$booking_id) { die("No booking reference provided."); }

try {
    $stmt = $pdo->prepare("
        SELECT p.amount, p.transaction_ref, p.paid_at, b.unit_code, b.booking_date,
               u_learner.name as learner_name, u_tutor.name as tutor_name
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN users u_learner ON b.learner_id = u_learner.id
        JOIN users u_tutor ON b.tutor_id = u_tutor.id
        WHERE b.id = ? AND b.learner_id = ? AND p.status = 'completed'
    ");
    $stmt->execute([$booking_id, $learner_id]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receipt) { die("No completed payment found for this booking."); }
} catch (PDOException $e) {
    error_log("Receipt fetch error: " . $e->getMessage());
    die("Unable to load receipt.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Receipt - PeerTutor</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8fafc; padding: 40px; margin: 0; }
        .receipt-box { max-width: 450px; margin: 0 auto; background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 35px; }
        .receipt-header { text-align: center; border-bottom: 2px dashed #e2e8f0; padding-bottom: 20px; margin-bottom: 20px; }
        .receipt-header h2 { margin: 0; color: #0f2038; }
        .receipt-header p { color: #10b981; font-weight: bold; margin: 8px 0 0; }
        .row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .row span:first-child { color: #64748b; }
        .total-row { display: flex; justify-content: space-between; padding: 15px 0 0; font-size: 18px; font-weight: bold; color: #0f2038; margin-top: 10px; }
        .btn-print { display: block; width: 100%; background: #0f2038; color: white; text-align: center; padding: 12px; border-radius: 4px; text-decoration: none; font-weight: 600; margin-top: 25px; border: none; cursor: pointer; font-size: 14px; }
        @media print { .btn-print { display: none; } }
    </style>
</head>
<body>
    <div class="receipt-box">
        <div class="receipt-header">
            <h2>PeerTutor</h2>
            <p>✓ Payment Successful</p>
        </div>
        <div class="row"><span>Receipt No.</span><span><?php echo htmlspecialchars($receipt['transaction_ref']); ?></span></div>
        <div class="row"><span>Date Paid</span><span><?php echo date('M d, Y - H:i', strtotime($receipt['paid_at'])); ?></span></div>
        <div class="row"><span>Student</span><span><?php echo htmlspecialchars($receipt['learner_name']); ?></span></div>
        <div class="row"><span>Tutor</span><span><?php echo htmlspecialchars($receipt['tutor_name']); ?></span></div>
        <div class="row"><span>Unit Code</span><span><?php echo htmlspecialchars($receipt['unit_code']); ?></span></div>
        <div class="row"><span>Session Date</span><span><?php echo date('M d, Y - H:i', strtotime($receipt['booking_date'])); ?></span></div>
        <div class="total-row"><span>Amount Paid</span><span>KES <?php echo number_format($receipt['amount'], 2); ?></span></div>
        <button class="btn-print" onclick="window.print()">🖨️ Print Receipt</button>
    </div>
</body>
</html>