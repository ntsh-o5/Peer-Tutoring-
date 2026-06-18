<?php
// =========================================================================
// 1. BACKEND COMPENSATION CONTROLLER LOGIC & POST PROCESSING
// =========================================================================

// Placeholder for Database Connection (e.g., include('config/db.php');)

// Capture and sanitize standard query parameters
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = htmlspecialchars(trim($_GET['search']));
}

// Handle Admin Action: Processing a Tutor Compensation Release
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['tutor_id'])) {
    $tutor_id = htmlspecialchars(trim($_POST['tutor_id']));
    $action = $_POST['action'];
    
    if ($action === 'process_payout') {
        // Database update routine for tutor ledger payouts:
        // $stmt = $pdo->prepare("UPDATE tutor_payouts SET status = 'Paid' WHERE tutor_id = ?");
        // $stmt->execute([$tutor_id]);
    }
    
    // Optional: PRG Pattern redirection to prevent form re-submission on refresh
    // header("Location: tutor_payments.php");
    // exit;
}

// Top-Level Financial Metrics Structure 
$payout_stats = [
    'total_payouts'   => 45000,
    'pending_payouts' => 8500,
    'active_tutors'   => 45
];
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
            <h1>Tutor Compensation</h1>
        </header>

        <section class="cards">
            <div class="card">
                <h3>Total Tutor Payouts</h3>
                <p>KES <?php echo number_format($payout_stats['total_payouts']); ?></p>
            </div>

            <div class="card">
                <h3>Pending Payouts</h3>
                <p>KES <?php echo number_format($payout_stats['pending_payouts']); ?></p>
            </div>

            <div class="card">
                <h3>Active Tutors</h3>
                <p><?php echo number_format($payout_stats['active_tutors']); ?></p>
            </div>
        </section>

        <div class="search-container">
            <form method="GET" action="tutor_payments.php">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search tutor..." 
                    value="<?php echo $search_query; ?>"
                >
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tutor ID</th>
                        <th>Tutor Name</th>
                        <th>Completed Sessions</th>
                        <th>Amount Due</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                    <tr>
                        <td>T001</td>
                        <td>John Mwangi</td>
                        <td>12</td>
                        <td>KES 2,400</td>
                        <td><span class="status-badge pending">Pending</span></td>
                        <td>
                            <form method="POST" action="tutor_payments.php" style="margin: 0; display: inline;">
                                <input type="hidden" name="tutor_id" value="T001">
                                <button type="submit" name="action" value="process_payout" class="approve-btn">Pay Tutor</button>
                            </form>
                        </td>
                    </tr>

                    <tr>
                        <td>T002</td>
                        <td>Mary Wanjiku</td>
                        <td>20</td>
                        <td>KES 4,000</td>
                        <td><span class="status-badge approved">Paid</span></td>
                        <td>
                            <a href="view_payout_receipt.php?id=T002" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View</a>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

    </div>

</body>
</html>