<?php
// admin/users.php
session_start();

if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

$message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : '';
$message_type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

$search_query = "";
$role_filter = "";
$users_list = [];

$user_metrics = [
    'total_accounts'  => 0,
    'active_learners' => 0,
    'active_tutors'   => 0
];

try {
    // 1. HANDLE MUTATION ACTIONS
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'], $_POST['target_user_id'])) {
        $target_uid = (int)$_POST['target_user_id'];
        $action = trim($_POST['action']);
        
        // Note: If you don't have an is_verified column in 'users', you can adjust this action or table column
        if ($action === 'verify_user') {
            // Check if column exists, or update status depending on your schema.
            // For now, executing fallback simulation or status update safely
            $updateStmt = $pdo->prepare("UPDATE users SET role = 'tutor' WHERE id = ?");
            $updateStmt->execute([$target_uid]);
            
            $_SESSION['flash_message'] = "User status updated successfully.";
            $_SESSION['flash_type'] = 'success';
        }
        
        header("Location: users.php" . (!empty($_GET['role']) ? "?role=".$_GET['role'] : ""));
        exit;
    }

    // 2. LIVE DYNAMIC COUNTERS USING THE CORRECT COLUMN 'role'
    $totalAccountsStmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_metrics['total_accounts'] = (int)$totalAccountsStmt->fetchColumn();

    $learnersStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE LOWER(role) = 'learner'");
    $user_metrics['active_learners'] = (int)$learnersStmt->fetchColumn();

    $tutorsStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE LOWER(role) = 'tutor'");
    $user_metrics['active_tutors'] = (int)$tutorsStmt->fetchColumn();

    // 3. BUILD FILTERED QUERY MATRIX
    $conditions = [];
    $bindings = [];

    if (isset($_GET['search']) && trim($_GET['search']) !== "") {
        $search_query = trim($_GET['search']);
        $conditions[] = "(name ILIKE ? OR email ILIKE ?)";
        $bindings[] = "%$search_query%";
        $bindings[] = "%$search_query%";
    }

    if (isset($_GET['role']) && in_array(strtolower(trim($_GET['role'])), ['learner', 'tutor', 'admin'])) {
        $role_filter = strtolower(trim($_GET['role']));
        $conditions[] = "LOWER(role) = ?";
        $bindings[] = $role_filter;
    }

    $sql_build = "SELECT id, name, email, role FROM users";
    if (!empty($conditions)) {
        $sql_build .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql_build .= " ORDER BY id ASC";

    $stmt = $pdo->prepare($sql_build);
    $stmt->execute($bindings);
    $users_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Administrative identity extraction crash: " . $e->getMessage());
}
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
            <h1>User Provisioning & Core Accounts Management</h1>
        </header>

        <?php if(!empty($message)): ?>
            <div style="padding: 12px; margin-bottom: 25px; border-radius: 6px; font-size: 14px; background: #dcfce7; color: #166534;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="cards">
            <div class="card">
                <h3>Total Ecosystem Accounts</h3>
                <p><?php echo number_format($user_metrics['total_accounts']); ?></p>
            </div>
            <div class="card">
                <h3>Registered Learners</h3>
                <p><?php echo number_format($user_metrics['active_learners']); ?></p>
            </div>
            <div class="card">
                <h3>Registered Tutors</h3>
                <p><?php echo number_format($user_metrics['active_tutors']); ?></p>
            </div>
        </section>

        <div class="search-container" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 20px;">
            <form method="GET" action="users.php" style="display: flex; gap: 10px; width: 100%;">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search accounts by legal name or email..." 
                    value="<?php echo htmlspecialchars($search_query); ?>"
                    style="flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;"
                >
                <select name="role" style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; background:#fff;">
                    <option value="">All Account Profiles</option>
                    <option value="learner" <?php echo strtolower($role_filter) === 'learner' ? 'selected' : ''; ?>>Learners Matrix</option>
                    <option value="tutor" <?php echo strtolower($role_filter) === 'tutor' ? 'selected' : ''; ?>>Instructors / Tutors</option>
                    <option value="admin" <?php echo strtolower($role_filter) === 'admin' ? 'selected' : ''; ?>>Administrators</option>
                </select>
                <button type="submit" style="padding: 10px 20px; background: #0f2038; color: white; border: none; border-radius:6px; cursor:pointer; font-weight:600;">Filter System Accounts</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Profile Full Name</th>
                        <th>Email Address Log</th>
                        <th>System Permissions Role</th>
                        <th>Administrative Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users_list)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; font-style: italic; padding: 20px; color: #64748b;">
                                No system records found matching data parameters.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users_list as $user): ?>
                            <tr>
                                <td>#USR-<?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                <td><code><?php echo htmlspecialchars($user['email']); ?></code></td>
                                <td>
                                    <?php 
                                        $role_clean = ucfirst(strtolower(trim($user['role']))); 
                                        $role_bg = ($role_clean === 'Tutor') ? '#e0f2fe' : (($role_clean === 'Admin') ? '#fef3c7' : '#f1f5f9');
                                        $role_fg = ($role_clean === 'Tutor') ? '#0369a1' : (($role_clean === 'Admin') ? '#b45309' : '#334155');
                                    ?>
                                    <span style="background: <?php echo $role_bg; ?>; color: <?php echo $role_fg; ?>; padding: 4px 10px; border-radius: 6px; font-weight:600; font-size:13px;">
                                        <?php echo $role_clean; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_user.php?id=<?php echo $user['id']; ?>" style="text-decoration: none; background: #0f2038; color: white; padding: 6px 12px; border-radius: 4px; font-size: 13px;">View Profile</a>
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