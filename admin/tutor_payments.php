<?php
// admin/tutor_payments.php
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
$tutor_ledgers = [];

// Top-Level Financial Metrics Defaults
$payout_stats = [
    'total_payouts'   => 0.00,
    'pending_payouts' => 0.00,
    'active_tutors'   => 0
];

try {
    // =========================================================================
    // 2. POST HANDLERS - MANUALLY RELEASING PAYOUTS
    // =========================================================================
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['payment_id'])) {
        $payment_id = (int)$_POST['payment_id'];
        $action = $_POST['action'];
        
        if ($action === 'process_payout') {
            // Mark the escrow/payout transaction as settled
            $stmt = $pdo->prepare("UPDATE payments SET status = 'Completed' WHERE id = ?");
            $stmt->execute([$payment_id]);
        }
        
        header("Location: tutor_payments.php");
        exit;
    }

    // =========================================================================
    // 3. LIVE CALCULATED METRICS
    // =========================================================================
    // Total Released Compensation to Tutors
    $totalPaidStmt = $pdo->query("
        SELECT SUM(p.amount) FROM payments p
        JOIN users u ON p.user_id = u.id
        WHERE LOWER(u.user_role) = 'tutor' AND LOWER(p.status) = 'completed'
    ");
    $payout_stats['total_payouts'] = (float)$totalPaidStmt->fetchColumn() ?: 0.00;

    // Remaining Unsettled Payout balances
    $pendingPaidStmt = $pdo->query("
        SELECT SUM(p.amount) FROM payments p
        JOIN users u ON p.user_id = u.id
        WHERE LOWER(u.user_role) = 'tutor' AND LOWER(p.status) = 'pending'
    ");
    $payout_stats['pending_payouts'] = (float)$pendingPaidStmt->fetchColumn() ?: 0.00;

    // Distinct Tutors with System Records
    $tutorCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE LOWER(user_role) = 'tutor'");
    $payout_stats['active_tutors'] = (int)$tutorCountStmt->fetchColumn();

    // =========================================================================
    // 4. RETRIEVAL PROCESSOR & SEARCH FILTERS
    // =========================================================================
    // We aggregate completed historical booking counts linked to the payments record
    $base_sql = "
        SELECT p.id as payment_id, p.amount, p.status, p.payment_method,
               u.id as tutor_id, u.name as tutor_name,
               (SELECT COUNT(*) FROM bookings b WHERE b.tutor_id = u.id AND LOWER(b.status) = 'completed') as completed_sessions
        FROM payments p
        JOIN users u ON p.user_id = u.id
        WHERE LOWER(u.user_role) = 'tutor'
    ";

    if (isset($_GET['search']) && trim($_GET['search']) !== "") {
        $search_query = trim($_GET['search']);
        $stmt = $pdo->prepare($base_sql . " AND LOWER(u.name) LIKE LOWER(?) ORDER BY p.id DESC");
        $stmt->execute(["%$search_query%"]);
        $tutor_ledgers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query($base_sql . " ORDER BY p.id DESC");
        $tutor_ledgers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Administrative compensation layer runtime crash: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Compensation - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

   <?php include('../includes/sidebar.php'); ?>

    <div class="main-content">

        <header>
            <h1>Tutor Compensation Disbursal Panel</h1>
        </header>

        <section class="cards">
            <div class="card">
                <h3>Total Paid Out</h3>
                <p>KES <?php echo number_format($payout_stats['total_payouts'], 2); ?></p>
            </div>

            <div class="card">
                <h3>Pending Remittance</h3>
                <p style="color: <?php echo $payout_stats['pending_payouts'] > 0 ? '#b91c1c' : 'inherit'; ?>;">
                    KES <?php echo number_format($payout_stats['pending_payouts'], 2); ?>
                </p>
            </div>

            <div class="card">
                <h3>Registered Tutors</h3>
                <p><?php echo number_format($payout_stats['active_tutors']); ?></p>
            </div>
        </section>

        <div class="search-container">
            <form method="GET" action="tutor_payments.php">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search tutor by name..." 
                    value="<?php echo htmlspecialchars($search_query); ?>"
                >
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Tutor Name</th>
                        <th>Completed Sessions</th>
                        <th>Net Amount Due</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tutor_ledgers)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; font-style: italic; color: #475569; padding: 20px;">
                                No system tutor payouts match the requested log filters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tutor_ledgers as $ledger): ?>
                            <tr>
                                <td>#PAY-<?php echo $ledger['payment_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($ledger['tutor_name']); ?></strong></td>
                                <td><code><?php echo $ledger['completed_sessions']; ?> Sessions</code></td>
                                <td><strong>KES <?php echo number_format($ledger['amount'], 2); ?></strong></td>
                                <td>
                                    <?php 
                                        $st = strtolower(trim($ledger['status']));
                                        $badge_class = ($st === 'completed' || $st === 'paid') ? 'approved' : 'pending';
                                    ?>
                                    <span class="status-badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($ledger['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($st === 'pending'): ?>
                                        <form method="POST" action="tutor_payments.php" style="margin: 0; display: inline;">
                                            <input type="hidden" name="payment_id" value="<?php echo $ledger['payment_id']; ?>">
                                            <button type="submit" name="action" value="process_payout" class="approve-btn">Release Funds</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="view_payout_receipt.php?id=<?php echo $ledger['payment_id']; ?>" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View Receipt</a>
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