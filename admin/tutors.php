<?php
// =========================================================================
// 1. BACKEND PROCESSING LOGIC & APPLICATION VERIFICATION HANDLERS
// =========================================================================

// Placeholder for Database Connection (e.g., include('config/db.php');)

// Capture and sanitize standard query parameters
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = htmlspecialchars(trim($_GET['search']));
}

// Handle Admin Verification State Mutations
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['application_id'])) {
    $app_id = htmlspecialchars(trim($_POST['application_id']));
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        // Database update routine for approval:
        // $stmt = $pdo->prepare("UPDATE tutors SET status = 'Approved' WHERE id = ?");
        // $stmt->execute([$app_id]);
    } elseif ($action === 'reject') {
        // Database update routine for rejection:
        // $stmt = $pdo->prepare("UPDATE tutors SET status = 'Rejected' WHERE id = ?");
        // $stmt->execute([$app_id]);
    }
    
    // Optional: PRG Pattern redirection to prevent form re-submission on refresh
    // header("Location: tutors.php");
    // exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Verification - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

    <?php include('../includes/sidebar.php'); ?>

    <div class="main-content">

        <header>
            <h1>Tutor Verification</h1>
        </header>

        <div class="search-container">
            <form method="GET" action="tutors.php">
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
                        <th>ID</th>
                        <th>Tutor Name</th>
                        <th>Unit</th>
                        <th>Credentials</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>

                    <tr>
                        <td>1</td>
                        <td>John Mwangi</td>
                        <td>Object Oriented Programming</td>
                        <td>
                            <a href="view_credentials.php?id=1" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View</a>
                        </td>
                        <td>
                            <span class="status-badge pending">Pending</span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <form method="POST" action="tutors.php" style="margin: 0;">
                                    <input type="hidden" name="application_id" value="1">
                                    <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                                </form>

                                <form method="POST" action="tutors.php" style="margin: 0;">
                                    <input type="hidden" name="application_id" value="1">
                                    <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td>2</td>
                        <td>Mary Wanjiku</td>
                        <td>Database Systems</td>
                        <td>
                            <a href="view_credentials.php?id=2" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View</a>
                        </td>
                        <td>
                            <span class="status-badge approved">Approved</span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <form method="POST" action="tutors.php" style="margin: 0;">
                                    <input type="hidden" name="application_id" value="2">
                                    <button type="submit" name="action" value="reject" class="reject-btn">Revoke / Reject</button>
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