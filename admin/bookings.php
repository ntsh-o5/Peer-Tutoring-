<?php
// admin/bookings.php
session_start();

// Strict security gate for Administrators
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

$search_query = "";
$bookings = [];

try {
    // 1. Handle Admin Action Submissions (Approve / Reject)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['booking_id'])) {
        $booking_id = (int)$_POST['booking_id'];
        $action = $_POST['action'];
        $new_status = null;

        if ($action === 'approve') {
            $new_status = 'Approved';
        } elseif ($action === 'reject') {
            $new_status = 'Rejected';
        }

        if ($new_status) {
            $updateStmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $updateStmt->execute([$new_status, $booking_id]);
            
            // Optional Pipeline Extension: Notify the learner instantly
            $learnerStmt = $pdo->prepare("SELECT learner_id, unit_code FROM bookings WHERE id = ?");
            $learnerStmt->execute([$booking_id]);
            $b_info = $learnerStmt->fetch(PDO::FETCH_ASSOC);
            if ($b_info) {
                $notifStmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, booking_id, title, message) 
                    VALUES (?, ?, ?, ?)
                ");
                $notifStmt->execute([
                    $b_info['learner_id'], 
                    $booking_id, 
                    "Booking Update: " . $new_status, 
                    "Your session request for unit " . $b_info['unit_code'] . " has been " . strtolower($new_status) . " by the administration panel."
                ]);
            }
        }
    }

    // 2. Fetch data using case-insensitive PostgreSQL text searches
    if (isset($_GET['search']) && trim($_GET['search']) !== "") {
        $search_query = trim($_GET['search']);
        $stmt = $pdo->prepare("
            SELECT b.id, b.unit_code, b.booking_date, b.status, 
                   u1.name as learner_name, u2.name as tutor_name
            FROM bookings b
            JOIN users u1 ON b.learner_id = u1.id
            JOIN users u2 ON b.tutor_id = u2.id
            WHERE LOWER(u1.name) LIKE LOWER(?) 
               OR LOWER(u2.name) LIKE LOWER(?) 
               OR LOWER(b.unit_code) LIKE LOWER(?)
            ORDER BY b.booking_date DESC
        ");
        $stmt->execute(["%$search_query%", "%$search_query%", "%$search_query%"]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fetch everything if no active search token is explicitly parsed
        $stmt = $pdo->query("
            SELECT b.id, b.unit_code, b.booking_date, b.status, 
                   u1.name as learner_name, u2.name as tutor_name
            FROM bookings b
            JOIN users u1 ON b.learner_id = u1.id
            JOIN users u2 ON b.tutor_id = u2.id
            ORDER BY b.booking_date DESC
        ");
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Administrative audit core failure: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

    <?php include('../includes/sidebar.php'); ?>

    <div class="main-content">

        <header>
            <h1>Booking Management</h1>
        </header>

        <div class="search-container">
            <form method="GET" action="bookings.php">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search unit code, learner or tutor..." 
                    value="<?php echo htmlspecialchars($search_query); ?>"
                >
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Learner</th>
                        <th>Tutor</th>
                        <th>Unit Code</th>
                        <th>Date & Baseline Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #475569; font-style: italic; padding: 20px;">
                                No system booking logistics records match the current view parameters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $row): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['learner_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['tutor_name']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['unit_code']); ?></strong></td>
                                <td><?php echo date('M d, Y - H:i', strtotime($row['booking_date'])); ?></td>
                                <td>
                                    <?php 
                                        $status_class = strtolower($row['status']);
                                        if (in_array($status_class, ['approved', 'confirmed', 'completed'])) { $status_class = 'approved'; }
                                        elseif ($status_class === 'pending') { $status_class = 'pending'; }
                                        else { $status_class = 'rejected'; }
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-container">
                                        <?php if (strtolower($row['status']) === 'pending'): ?>
                                            <form method="POST" action="bookings.php" style="display: flex; gap: 8px; margin: 0; align-items: center;">
                                                <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                                                <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <a href="view_booking.php?id=<?php echo $row['id']; ?>" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View Details</a>
                                        <?php endif; ?>
                                    </div>
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