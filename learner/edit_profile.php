<?php
// learner/edit_profile.php
session_start();

// Strict security gate for Learners
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$learner_id = $_SESSION['user_id'];
$status_message = '';

// Handle changes submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $new_password = $_POST['password'];

    try {
        if (!empty($new_password)) {
            // Include password update utilizing the verified password_hash column name
            $hashed_pass = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$new_name, $new_email, $hashed_pass, $learner_id]);
        } else {
            // Name and Email update only
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$new_name, $new_email, $learner_id]);
        }
        
        // Refresh temporary global session values
        $_SESSION['user_name'] = $new_name;
        $status_message = "Profile parameters updated safely!";
    } catch (PDOException $e) {
        $status_message = "Profile update failure: " . $e->getMessage();
    }
}

// Fetch existing details
try {
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$learner_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Profile retrieval fault: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); padding: 40px; margin: 0; }
        .profile-box { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        .form-group { display: flex; flex-direction: column; gap: 5px; margin-bottom: 15px; }
        .form-group label { font-size: 13px; font-weight: 600; color: var(--slate); }
        input { padding: 10px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; }
        input:focus { outline: none; border-color: var(--navy); }
    </style>
</head>
<body>

    <div class="profile-box">
        <p><a href="dashboard.php" style="color: var(--slate); text-decoration: none; font-weight: 600;">← Dashboard Hub</a></p>
        <h2 style="color: var(--navy); margin-top: 10px;">Modify Student Account Details</h2>
        
        <?php if(!empty($status_message)): ?>
            <p style="color: #10b981; font-weight: bold; font-size:14px;"><?php echo $status_message; ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Display Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Institutional Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Account Password (Leave blank to keep current)</label>
                <input type="password" name="password" placeholder="••••••••">
            </div>
            <button type="submit" style="background: var(--navy); color: white; border: none; padding: 12px; width: 100%; font-weight: bold; border-radius: 4px; cursor: pointer; margin-top: 10px; font-size: 14px;">Save Modifications</button>
        </form>
    </div>

</body>
</html>