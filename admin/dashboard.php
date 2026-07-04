<?php
// admin/dashboard.php
// =========================================================================
// 1. SESSION & AUTHENTICATION INITIALIZATION
// =========================================================================
session_start();

// Strict security layer gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'admin') {
    header("Location: ../auth/login.php");
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
$recent_activities = [];

try {
    // 1. Extract profile counts dynamically using verified system column 'role'
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

    // 2. Look up pending document checks from tutor credentials ledger
    // Using verification_status matching your schema pattern
    $verifyStmt = $pdo->query("SELECT COUNT(*) FROM tutor_credentials WHERE LOWER(verification_status) = 'pending'");
    if ($verifyStmt) {
        $stats['pending_verifications'] = (int)$verifyStmt->fetchColumn();
    }

    // 3. Live active tracking inside bookings table records
    $bookingStmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE LOWER(status) IN ('approved', 'scheduled', 'confirmed', 'pending', 'active')");
    if ($bookingStmt) {
        $stats['active_bookings'] = (int)$bookingStmt->fetchColumn();
    }

    // 4. Live Recent Activities log generation via Union query layout
    $activityStmt = $pdo->query("
        (SELECT booking_date as activity_date, 'Session Booked for Unit: ' || unit_code as activity_desc, status 
         FROM bookings 
         ORDER BY booking_date DESC 
         LIMIT 3)
        UNION ALL
        (SELECT created_at as activity_date, 'Tutor verification application submitted' as activity_desc, verification_status as status 
         FROM tutor_credentials 
         ORDER BY created_at DESC 
         LIMIT 2)
        ORDER BY activity_date DESC 
        LIMIT 5
    ");
    if ($activityStmt) {
        $recent_activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Admin metric engine anomaly: " . $e->getMessage());
}

$admin_display_name = isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .main-content header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #e2e8f0; 
            padding-bottom: 20px; 
        }
        .admin-actions { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
        }
        .admin-profile { 
            color: #475569; 
            font-size: 14px; 
        }
        .logout-btn { 
            background: #fee2e2; 
            color: #b91c1c; 
            padding: 10px 20px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 13px; 
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: #fca5a5;
        }
    </style>
</head>
<body>

   <?php include('../includes/sidebar.php'); ?>

    <div class="main-content">

        <header>
            <div>
                <h1>Admin Dashboard</h1>
                <p style="margin: 5px 0 0 0; color: #475569;">System Control Room</p>
            </div>

            <div class="admin-actions">
                <div class="admin-profile">
                    Welcome back, <strong><?php echo $admin_display_name; ?></strong>
                </div>

                <a href="../auth/logout.php" class="logout-btn">
                    🚪 Terminate Session
                </a>
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
            <h2>Recent Activities Log</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date / Timestamp</th>
                            <th>Activity Event Description</th>
                            <th>Pipeline Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_activities)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; font-style: italic; color: #475569; padding: 15px;">
                                    No historical activity operations captured in the system pipeline logs yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $act): ?>
                                <tr>
                                    <td><?php echo date('M d, Y - H:i', strtotime($act['activity_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($act['activity_desc']); ?></td>
                                    <td>
                                        <?php 
                                            $st = strtolower(trim($act['status']));
                                            $badge_class = 'pending';
                                            if (in_array($st, ['approved', 'completed', 'active', 'reviewed', 'confirmed'])) { $badge_class = 'approved'; }
                                            elseif (in_array($st, ['rejected', 'cancelled'])) { $badge_class = 'rejected'; }
                                        ?>
                                        <span class="status-badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars(ucfirst($st)); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

</body>
</html>