<?php
// =========================================================================
// 1. BACKEND PROCESSING LOGIC & STATE HANDLING
// =========================================================================

// Placeholder for Database Connection (e.g., include('config/db.php');)

$search_query = "";
if (isset($_GET['search'])) {
    $search_query = htmlspecialchars(trim($_GET['search']));
}

// Handle Direct Payment Manual Confirmations
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['payment_id'])) {
    $payment_id = htmlspecialchars(trim($_POST['payment_id']));
    $action = $_POST['action'];
    
    if ($action === 'confirm_payment') {
        // Database update routine for incoming learner ledger items:
        // $stmt = $pdo->prepare("UPDATE learner_payments SET status = 'Paid' WHERE payment_id = ?");
        // $stmt->execute([$payment_id]);
    }
    
    // Optional: PRG Pattern redirect to prevent form re-submission
    // header("Location: learner_payments.php");
    // exit;
}

// Summary Metrics Array Data Variables
$finance_stats = [
    'total_revenue'    => 75000,
    'payments_today'   => 12,
    'pending_payments' => 5
];
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
            <h1>Learner Payments</h1>
        </header>

        <section class="cards">
            <div class="card">
                <h3>Total Revenue</h3>
                <p>KES <?php echo number_format($finance_stats['total_revenue']); ?></p>
            </div>

            <div class="card">
                <h3>Payments Today</h3>
                <p><?php echo number_format($finance_stats['payments_today']); ?></p>
            </div>

            <div class="card">
                <h3>Pending Payments</h3>
                <p><?php echo number_format($finance_stats['pending_payments']); ?></p>
            </div>
        </section>

        <div class="search-container">
            <form method="GET" action="learner_payments.php">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search learner..." 
                    value="<?php echo $search_query; ?>"
                >
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Learner</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                    <tr>
                        <td>PAY001</td>
                        <td>Brian Otieno</td>
                        <td>KES 500</td>
                        <td>16/06/2026</td>
                        <td>M-Pesa</td>
                        <td><span class="status-badge approved">Paid</span></td>
                        <td>
                            <a href="view_receipt.php?id=PAY001" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View</a>
                        </td>
                    </tr>

                    <tr>
                        <td>PAY002</td>
                        <td>Mary Njeri</td>
                        <td>KES 500</td>
                        <td>16/06/2026</td>
                        <td>M-Pesa</td>
                        <td><span class="status-badge pending">Pending</span></td>
                        <td>
                            <form method="POST" action="learner_payments.php" style="margin: 0; display: inline;">
                                <input type="hidden" name="payment_id" value="PAY002">
                                <button type="submit" name="action" value="confirm_payment" class="approve-btn">Confirm</button>
                            </form>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

    </div>

</body>
</html>