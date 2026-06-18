<?php
// =========================================================================
// 1. BACKEND LOGIC & PROCESSORS (Executes before any HTML is sent)
// =========================================================================

// Placeholder for Database Connection (e.g., include('config/db.php');)
// Assumes you'll use PDO for handling your database connection safely.

$search_query = "";
if (isset($_GET['search'])) {
    $search_query = htmlspecialchars(trim($_GET['search']));
    // Future implementation: 
    // $stmt = $pdo->prepare("SELECT * FROM bookings WHERE learner_name LIKE ? OR tutor_name LIKE ?");
    // $stmt->execute(["%$search_query%", "%$search_query%"]);
}

// Handle Admin Action Submissions (Approve / Reject)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['booking_id'])) {
    $booking_id = htmlspecialchars(trim($_POST['booking_id']));
    $action = $_POST['action']; // 'approve' or 'reject'
    
    if ($action === 'approve') {
        $new_status = 'Approved';
        // Database update routine:
        // $stmt = $pdo->prepare("UPDATE bookings SET status = 'Approved' WHERE booking_id = ?");
        // $stmt->execute([$booking_id]);
    } elseif ($action === 'reject') {
        $new_status = 'Rejected';
        // Database update routine:
        // $stmt = $pdo->prepare("UPDATE bookings SET status = 'Rejected' WHERE booking_id = ?");
        // $stmt->execute([$booking_id]);
    }
    
    // Optional: Set a session message here to display a success alert banner
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
                    placeholder="Search learner or tutor..." 
                    value="<?php echo $search_query; ?>"
                >
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Learner</th>
                        <th>Tutor</th>
                        <th>Unit</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>

                    <tr>
                        <td>BK001</td>
                        <td>Brian Otieno</td>
                        <td>John Mwangi</td>
                        <td>Networking</td>
                        <td>20/06/2026</td>
                        <td>2:00 PM</td>
                        <td>
                            <span class="status-badge pending">Pending</span>
                        </td>
                        <td>
                            <div class="action-container">
                                <form method="POST" action="bookings.php" style="display: flex; gap: 8px; margin: 0; align-items: center;">
                                    <input type="hidden" name="booking_id" value="BK001">
                                    <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                                    <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td>BK002</td>
                        <td>Mary Njeri</td>
                        <td>Grace Wanjiku</td>
                        <td>Database Systems</td>
                        <td>21/06/2026</td>
                        <td>10:00 AM</td>
                        <td>
                            <span class="status-badge approved">Approved</span>
                        </td>
                        <td>
                            <div class="action-container">
                                <a href="view_booking.php?id=BK002" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View</a>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td>BK003</td>
                        <td>Kevin Maina</td>
                        <td>Peter Kimani</td>
                        <td>OOP</td>
                        <td>18/06/2026</td>
                        <td>4:00 PM</td>
                        <td>
                            <span class="status-badge rejected">Rejected</span>
                        </td>
                        <td>
                            <div class="action-container">
                                <a href="view_booking.php?id=BK003" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View</a>
                            </div>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

    </div>

</body>
</html>