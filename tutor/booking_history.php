<?php
// tutor/booking_history.php
session_start();

// Strict security layer gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$tutor_id = $_SESSION['user_id'];

// Capture dynamic filter constraints
$status_filter = isset($_GET['status']) ? trim(strtolower($_GET['status'])) : 'all';

// Setup query parameters mapping
$bindings = [$tutor_id];
$sql_query = "
    SELECT b.id AS booking_id, b.unit_code, b.booking_date, b.status, u.name as student_name 
    FROM bookings b
    JOIN users u ON b.learner_id = u.id
    WHERE b.tutor_id = ?
";

// Apply status filter rules if specific token is selected
if ($status_filter !== 'all') {
    $sql_query .= " AND b.status = ?";
    $bindings[] = $status_filter;
}

$sql_query .= " ORDER BY b.booking_date DESC";

try {
    $stmt = $pdo->prepare($sql_query);
    $stmt->execute($bindings);
    $history_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Booking history tracking breakdown: " . $e->getMessage());
    $history_records = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Archive Matrix - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); padding: 40px; margin: 0; }
        .wrapper { max-width: 1200px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--border); padding-bottom: 20px; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 25px; align-items: center; }
        .filter-link { padding: 8px 16px; background: white; border: 1px solid var(--border); border-radius: 20px; text-decoration: none; color: var(--slate); font-size: 13px; font-weight: 600; transition: all 0.2s; }
        .filter-link:hover, .filter-link.active { background: var(--navy); color: white; border-color: var(--navy); }
        .card-box { background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 14px 12px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #edf2f7; color: var(--navy); font-weight: 600; }
        .badge { font-size: 11px; padding: 4px 10px; border-radius: 12px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-approved { background: #e0f2fe; color: #0369a1; }
        .status-completed { background: #dcfce7; color: #15803d; }
        .status-claimed { background: #f3e8ff; color: #6b21a8; }
        .status-paid { background: #ccfbf1; color: #0f766e; }
        .status-rejected { background: #fee2e2; color: #b91c1c; }
        .status-reviewed { background: #e0e7ff; color: #3730a3; }
    </style>
</head>
<body>

    <div class="wrapper">
        <header>
            <div>
                <h1 style="margin: 0; font-size: 24px; color: var(--navy);">Booking History Archive</h1>
                <p style="margin: 5px 0 0 0; color: var(--slate);">Review a complete audit index log of all past and current peer allocations mapped to you.</p>
            </div>
            <a href="dashboard.php" style="color: var(--navy); text-decoration: none; font-weight: 600; font-size: 14px;">← Dashboard Hub</a>
        </header>

        <div class="filter-bar">
            <span style="font-size: 13px; font-weight: 700; color: var(--slate); margin-right: 5px;">Filter Pipeline Status:</span>
            <a href="booking_history.php?status=all" class="filter-link <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Rows</a>
            <a href="booking_history.php?status=pending" class="filter-link <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="booking_history.php?status=approved" class="filter-link <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="booking_history.php?status=completed" class="filter-link <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">Completed</a>
            <a href="booking_history.php?status=reviewed" class="filter-link <?php echo $status_filter === 'reviewed' ? 'active' : ''; ?>">Reviewed</a>
            <a href="booking_history.php?status=rejected" class="filter-link <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
        </div>

        <div class="card-box">
            <?php if(empty($history_records)): ?>
                <p style="color: var(--slate); font-size: 14px; font-style: italic; text-align: center; padding: 30px 0;">
                    No session allocation logs match the selected pipeline criteria filter.
                </p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Student Learner</th>
                            <th>Course Allocation Unit</th>
                            <th>Target Schedule Date</th>
                            <th>Current System Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($history_records as $row): ?>
                            <tr>
                                <td>#<?php echo $row['booking_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                                <td><span style="font-family: monospace; font-weight: bold; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($row['unit_code']); ?></span></td>
                                <td><?php echo date('M d, Y – h:i A', strtotime($row['booking_date'])); ?></td>
                                <td>
                                    <?php 
                                    $st = strtolower(trim($row['status']));
                                    echo "<span class='badge status-{$st}'>" . htmlspecialchars($row['status']) . "</span>";
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>