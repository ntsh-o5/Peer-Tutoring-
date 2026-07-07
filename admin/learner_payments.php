<?php
// admin/learner_payments.php
// =========================================================================
// 1. SESSION & AUTHENTICATION INITIALIZATION
// =========================================================================
session_start();

if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

$search_query = "";
$payment_records = [];

// Summary Metrics Defaults
$finance_stats = [
    'total_revenue'    => 0,
    'payments_today'   => 0,
    'pending_payments' => 0
];

try {
    // =========================================================================
    // 2. POST HANDLERS - MANUAL CONFIRMATIONS
    // =========================================================================
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['payment_id'])) {
        $payment_id = (int)$_POST['payment_id'];
        $action = $_POST['action'];
        
        if ($action === 'confirm_payment') {
            $stmt = $pdo->prepare("UPDATE payments SET status = 'completed' WHERE payment_id = ?");
            $stmt->execute([$payment_id]);
        }
        
        header("Location: learner_payments.php");
        exit;
    }

    // =========================================================================
    // 3. LIVE DATA AGGREGATION METRICS (PostgreSQL)
    // =========================================================================
    // Total Completed Revenue (Assumes amount field exists in payments table)
    $revStmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE LOWER(status) = 'completed'");
    $finance_stats['total_revenue'] = (float)$revStmt->fetchColumn() ?: 0.00;

    // Payments Received Today
    $todayStmt = $pdo->query("SELECT COUNT(*) FROM payments WHERE created_at::date = CURRENT_DATE");
    $finance_stats['payments_today'] = (int)$todayStmt->fetchColumn();

    // Total System Pending Processing Items
    $pendingStmt = $pdo->query("SELECT COUNT(*) FROM payments WHERE LOWER(status) = 'pending'");
    $finance_stats['pending_payments'] = (int)$pendingStmt->fetchColumn();

    // =========================================================================
    // 4. DATA RETRIEVAL (With Case-Insensitive Search Filter)
    // =========================================================================
    if (isset($_GET['search']) && trim($_GET['search']) !== "") {
        $search_query = trim($_GET['search']);
        $stmt = $pdo->prepare("
    SELECT p.payment_id, p.amount, p.status, p.created_at, u.name as learner_name
    FROM payments p
    JOIN users u ON p.user_id = u.id
    WHERE LOWER(u.role) = 'learner' AND LOWER(u.name) LIKE LOWER(?)
    ORDER BY p.created_at DESC
");
        $stmt->execute(["%$search_query%"]);
        $payment_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query("
    SELECT p.payment_id, p.amount, p.status, p.created_at, u.name as learner_name
    FROM payments p
    JOIN users u ON p.user_id = u.id
    WHERE LOWER(u.role) = 'learner'
    ORDER BY p.created_at DESC
");
        $payment_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Administrative financial core breakdown: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Payments - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

   <?php include('../includes/sidebar.php'); ?>

    <div class="main-content">

        <header>
            <h1>Learner Payments Audit Ledger</h1>
        </header>

        <section class="cards">
            <div class="card">
                <h3>Total Collected Revenue</h3>
                <p>KES <?php echo number_format($finance_stats['total_revenue'], 2); ?></p>
            </div>

            <div class="card">
                <h3>Transactions Logged Today</h3>
                <p><?php echo number_format($finance_stats['payments_today']); ?></p>
            </div>

            <div class="card">
                <h3>Awaiting Verification Clearing</h3>
                <p style="color: <?php echo $finance_stats['pending_payments'] > 0 ? '#b91c1c' : 'inherit'; ?>;">
                    <?php echo number_format($finance_stats['pending_payments']); ?>
                </p>
            </div>
        </section>

        <div class="search-container">
            <form method="GET" action="learner_payments.php">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search learner name..." 
                    value="<?php echo htmlspecialchars($search_query); ?>"
                >
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Learner Name</th>
                        <th>Amount</th>
                        <th>Transaction Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payment_records)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; font-style: italic; color: #475569; padding: 20px;">
                                No learner financial transactions match the selected logs view.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payment_records as $pay): ?>
                            <tr>
                                <td>#PAY-<?php echo $pay['payment_id']; ?></td>
                                <td><?php echo htmlspecialchars($pay['learner_name']); ?></td>
                                <td><strong>KES <?php echo number_format($pay['amount'], 2); ?></strong></td>
                                <td><?php echo date('M d, Y - H:i', strtotime($pay['created_at'])); ?></td>
                                <td><span style="text-transform: uppercase; font-size:12px; font-weight:bold; color:#0f2038;">M-PESA</span></td>
                                <td>
                                    <?php 
                                        $st = strtolower(trim($pay['status']));
                                        $badge_class = ($st === 'completed' || $st === 'paid') ? 'approved' : 'pending';
                                    ?>
                                    <span class="status-badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($pay['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($st === 'pending'): ?>
                                        <form method="POST" action="learner_payments.php" style="margin: 0; display: inline;">
                                            <input type="hidden" name="payment_id" value="<?php echo $pay['payment_id']; ?>">
                                            <button type="submit" name="action" value="confirm_payment" class="approve-btn">Confirm Receipt</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="view_receipt.php?id=<?php echo $pay['payment_id']; ?>" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View Receipt</a>
                                    <?php endif; ?>
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