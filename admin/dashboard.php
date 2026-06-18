<?php
// =========================================================================
// 1. SESSION & AUTHENTICATION INITIALIZATION
// =========================================================================
session_start();

// Strict security layer gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'admin') {
    header("Location: ../auth/login.php");
    echo "<pre>";
print_r($_SESSION);
echo "</pre>";
    exit;
}

// =========================================================================
// 2. LIVE DATA AGGREGATION ENGINE (PostgreSQL Integration)
// =========================================================================
require_once '../config/database.php'; 

$stats = [
    'total_learners'        => 0,
    'total_tutors'          => 0,
    'pending_verifications' => 0,
    'active_bookings'       => 0
];

try {
    // Aggregation Query 1: Extract direct profile user roles counts dynamically
    $roleQuery = "SELECT role, COUNT(*) as amount FROM users GROUP BY role";
    $roleStmt = $pdo->query($roleQuery);
    while ($row = $roleStmt->fetch(PDO::FETCH_ASSOC)) {
        $normalizedRole = strtolower(trim($row['role']));
        if ($normalizedRole === 'learner') {
            $stats['total_learners'] = (int)$row['amount'];
        } elseif ($normalizedRole === 'tutor') {
            $stats['total_tutors'] = (int)$row['amount'];
        }
    }

    // Aggregation Query 2: Look up pending document or status checks (assuming a 'status' or verification tracking setup)
    // Update table or column string variants to match your exact schema extensions if needed
    $verifyStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'tutor' AND status = 'pending'");
    if ($verifyStmt) {
        $stats['pending_verifications'] = (int)$verifyStmt->fetchColumn();
    }

    // Aggregation Query 3: Live active tracking inside bookings table records
    $bookingStmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'approved' OR status = 'active'");
    if ($bookingStmt) {
        $stats['active_bookings'] = (int)$bookingStmt->fetchColumn();
    }

} catch (PDOException $e) {
    // Fail silently or fallback log errors during production debugging
die($e->getMessage());
    }

// Match the specific string written by auth/login.php
$admin_display_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

   <?php include('../includes/sidebar.php'); ?>

    <div class="main-content">

        <header>
            <h1>Admin Dashboard</h1>
            <div class="admin-profile">
                Welcome, <?php echo $admin_display_name; ?>
            </div>
        </header>

        <section class="cards">

            <div class="card">
                <h3>Total Learners</h3>
                <p><?php echo number_format($stats['total_learners']); ?></p>
            </div>

            <div class="card">
                <h3>Total Tutors</h3>
                <p><?php echo number_format($stats['total_tutors']); ?></p>
            </div>

            <div class="card">
                <h3>Pending Verifications</h3>
                <p><?php echo number_format($stats['pending_verifications']); ?></p>
            </div>

            <div class="card">
                <h3>Active Bookings</h3>
                <p><?php echo number_format($stats['active_bookings']); ?></p>
            </div>

        </section>

        <section class="recent-activity">

            <h2>Recent Activities</h2>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Activity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <tr>
                            <td>15/06/2026</td>
                            <td>Tutor James submitted credentials</td>
                            <td>
                                <span class="status-badge pending">Pending Review</span>
                            </td>
                        </tr>

                        <tr>
                            <td>15/06/2026</td>
                            <td>New tutoring session booked</td>
                            <td>
                                <span class="status-badge approved">Approved</span>
                            </td>
                        </tr>

                        <tr>
                            <td>14/06/2026</td>
                            <td>Progress report submitted</td>
                            <td>
                                <span class="status-badge reviewed">Reviewed</span>
                            </td>
                        </tr>
                        
                    </tbody>
                </table>
            </div>

        </section>

    </div>

</body>
</html>