<?php
// =========================================================================
// 1. BACKEND USER SELECTION CONTROLLER & ACTION HANDLERS
// =========================================================================

// Placeholder for Database Connection (e.g., include('config/db.php');)

// Capture and sanitize filtration search context parameters
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = htmlspecialchars(trim($_GET['search']));
}

$role_filter = "";
if (isset($_GET['role']) && in_array($_GET['role'], ['Learner', 'Tutor'])) {
    $role_filter = $_GET['role'];
}

// Handle Admin Account Mutations (e.g., quick user activation/verification)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['target_user_id'])) {
    $target_uid = htmlspecialchars(trim($_POST['target_user_id']));
    $action = $_POST['action'];
    
    if ($action === 'verify_user') {
        // Database update routine:
        // $stmt = $pdo->prepare("UPDATE users SET status = 'Active' WHERE user_id = ?");
        // $stmt->execute([$target_uid]);
    }
    
    // Optional: PRG Pattern redirection to prevent form re-submission on refresh
    // header("Location: users.php");
    // exit;
}

// System Summary Statistics Object Arrays
$user_metrics = [
    'total_accounts'  => 201,
    'active_learners' => 156,
    'active_tutors'   => 45
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

   <?php include('../includes/sidebar.php'); ?>

    <div class="main-content">

        <header>
            <h1>User Management</h1>
        </header>

        <section class="cards">
            <div class="card">
                <h3>Total Users</h3>
                <p><?php echo number_format($user_metrics['total_accounts']); ?></p>
            </div>

            <div class="card">
                <h3>Active Learners</h3>
                <p><?php echo number_format($user_metrics['active_learners']); ?></p>
            </div>

            <div class="card">
                <h3>Active Tutors</h3>
                <p><?php echo number_format($user_metrics['active_tutors']); ?></p>
            </div>
        </section>

        <div class="search-container" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <form method="GET" action="users.php" style="display: flex; gap: 10px; width: 100%;">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search name or email..." 
                    value="<?php echo $search_query; ?>"
                    style="flex: 1;"
                >
                <select name="role" style="padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
                    <option value="">All Roles</option>
                    <option value="Learner" <?php echo $role_filter === 'Learner' ? 'selected' : ''; ?>>Learners</option>
                    <option value="Tutor" <?php echo $role_filter === 'Tutor' ? 'selected' : ''; ?>>Tutors</option>
                </select>
                <button type="submit">Filter</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email Address</th>
                        <th>Role</th>
                        <th>Account Status</th>
                        <th>Action Matrix</th>
                    </tr>
                </thead>
                <tbody>

                    <tr>
                        <td>U001</td>
                        <td>Brian Otieno</td>
                        <td>brian@mail.com</td>
                        <td><span class="status-badge standard" style="background: #eef2f7; color: #333; padding: 3px 8px; border-radius: 4px;">Learner</span></td>
                        <td><span class="status-badge approved">Active</span></td>
                        <td>
                            <a href="view_user.php?id=U001" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View</a>
                        </td>
                    </tr>

                    <tr>
                        <td>U002</td>
                        <td>Mary Njeri</td>
                        <td>mary@mail.com</td>
                        <td><span class="status-badge standard" style="background: #eef2f7; color: #333; padding: 3px 8px; border-radius: 4px;">Tutor</span></td>
                        <td><span class="status-badge pending">Pending</span></td>
                        <td>
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <a href="view_user.php?id=U002" class="view-btn" style="text-decoration: none; text-align: center;">View</a>
                                
                                <form method="POST" action="users.php" style="margin: 0; display: inline;">
                                    <input type="hidden" name="target_user_id" value="U002">
                                    <button type="submit" name="action" value="verify_user" class="approve-btn">Verify</button>
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