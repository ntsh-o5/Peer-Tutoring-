<?php
// =========================================================================
// 1. BACKEND PROCESSORS & POST ACTION HANDLERS
// =========================================================================

// Placeholder for Database Connection (e.g., include('config/db.php');)

// Handle Admin Actions (e.g., Marking feedback item as reviewed)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['feedback_id'])) {
    $feedback_id = htmlspecialchars(trim($_POST['feedback_id']));
    $action = $_POST['action'];
    
    if ($action === 'mark_reviewed') {
        // Database update routine:
        // $stmt = $pdo->prepare("UPDATE feedback SET status = 'Reviewed' WHERE feedback_id = ?");
        // $stmt->execute([$feedback_id]);
    }
    
    // Optional: PRG Pattern redirection to prevent form re-submission on refresh
    // header("Location: feedback.php");
    // exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Feedback - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

   <?php include('../includes/sidebar.php'); ?>
    <div class="main-content">

        <header>
            <h1>Feedback Management</h1>
        </header>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                    <tr>
                        <td>F001</td>
                        <td>Brian Otieno</td>
                        <td>Great platform, very helpful!</td>
                        <td>16/06/2026</td>
                        <td>
                            <span class="status-badge approved">Reviewed</span>
                        </td>
                        <td>
                            <a href="view_feedback.php?id=F001" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View</a>
                        </td>
                    </tr>

                    <tr>
                        <td>F002</td>
                        <td>Mary Njeri</td>
                        <td>Need more tutors for math.</td>
                        <td>15/06/2026</td>
                        <td>
                            <span class="status-badge pending">New</span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <a href="view_feedback.php?id=F002" class="view-btn" style="text-decoration: none; text-align: center;">View</a>
                                
                                <form method="POST" action="feedback.php" style="margin: 0; display: inline;">
                                    <input type="hidden" name="feedback_id" value="F002">
                                    <button type="submit" name="action" value="mark_reviewed" class="approve-btn">Mark Reviewed</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

    </div>

</body>
</html>