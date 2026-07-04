<?php
// admin/feedback.php
// =========================================================================
// 1. SESSION & AUTHENTICATION INITIALIZATION
// =========================================================================
session_start();

// Strict security layer gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php'; 

$feedbacks = [];

try {
    // =========================================================================
    // 2. BACKEND PROCESSORS & POST ACTION HANDLERS
    // =========================================================================
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['feedback_id'])) {
        $feedback_id = (int)$_POST['feedback_id'];
        $action = $_POST['action'];
        
        if ($action === 'mark_reviewed') {
            // Adjust status mapping cleanly to your schema criteria
            $stmt = $pdo->prepare("UPDATE feedback SET status = 'Reviewed' WHERE id = ?");
            $stmt->execute([$feedback_id]);
        }
        
        // PRG Pattern redirection to prevent form re-submission on refresh
        header("Location: feedback.php");
        exit;
    }

    // 3. FETCH STREAM DATA FROM BACKEND JOINING SYSTEM USER PROFILES
    $query = "
        SELECT f.id, f.message, f.status, f.created_at, u.name as user_name 
        FROM feedback f
        JOIN users u ON f.user_id = u.id
        ORDER BY f.created_at DESC
    ";
    $feedbacks = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Administrative feedback extraction failure: " . $e->getMessage());
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
                    <?php if (empty($feedbacks)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #475569; font-style: italic; padding: 20px;">
                                No system feedback entries have been submitted to the platform yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($feedbacks as $f): ?>
                            <tr>
                                <td>#<?php echo $f['id']; ?></td>
                                <td><?php echo htmlspecialchars($f['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($f['message']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($f['created_at'])); ?></td>
                                <td>
                                    <?php 
                                        $status = strtolower(trim($f['status']));
                                        $badge_class = ($status === 'reviewed' || $status === 'approved') ? 'approved' : 'pending';
                                    ?>
                                    <span class="status-badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($f['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px; align-items: center;">
                                        <a href="view_feedback.php?id=<?php echo $f['id']; ?>" class="view-btn" style="text-decoration: none; text-align: center;">View</a>
                                        
                                        <?php if ($status !== 'reviewed'): ?>
                                            <form method="POST" action="feedback.php" style="margin: 0; display: inline;">
                                                <input type="hidden" name="feedback_id" value="<?php echo $f['id']; ?>">
                                                <button type="submit" name="action" value="mark_reviewed" class="approve-btn">Mark Reviewed</button>
                                            </form>
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