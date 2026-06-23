<?php
// admin/profile.php
// =========================================================================
// 1. SESSION & AUTHENTICATION INITIALIZATION
// =========================================================================
session_start();

if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

// Safe execution message retrieval across PRG state redirections
$message = isset($_SESSION['flash_message']) ? $_SESSION['flash_message'] : '';
$message_type = isset($_SESSION['flash_type']) ? $_SESSION['flash_type'] : 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Fallback profile object initialization to avoid undefined variable notices
$profile_data = [
    'name' => '',
    'email' => ''
];

$admin_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

try {
    // =========================================================================
    // 2. POST HANDLER - UPDATE PROFILE DATA (MUTATIONS)
    // =========================================================================
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $new_name = trim($_POST['name']);
        $new_email = trim($_POST['email']);
        
        if (empty($new_name) || empty($new_email)) {
            $_SESSION['flash_message'] = "All profile data fields are strictly required.";
            $_SESSION['flash_type'] = 'error';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_message'] = "Please provide a valid communication email address.";
            $_SESSION['flash_type'] = 'error';
        } else {
            // Check if email is already taken by another user index
            $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email ILIKE ? AND id != ?");
            $emailCheck->execute([$new_email, $admin_id]);
            
            if ($emailCheck->fetch()) {
                $_SESSION['flash_message'] = "This email address is already registered to another user.";
                $_SESSION['flash_type'] = 'error';
            } else {
                // Execute the update
                $updateStmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $updateStmt->execute([$new_name, $new_email, $admin_id]);
                
                $_SESSION['user_name'] = $new_name;
                
                $_SESSION['flash_message'] = "Administrative security profile updated successfully!";
                $_SESSION['flash_type'] = 'success';
            }
        }
        
        header("Location: profile.php");
        exit;
    }

    // =========================================================================
    // 3. RETRIEVE CURRENT PROFILE CONTEXT
    // =========================================================================
    if ($admin_id) {
        // Changed LOWER(user_role) to LOWER(role) to match your database column schema
        $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ? AND LOWER(role) = 'admin'");
        $stmt->execute([$admin_id]);
        $fetched_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fetched_data) {
            $profile_data = $fetched_data;
        } else {
            $message = "Warning: Active profile record could not be extracted from the session index context.";
            $message_type = 'error';
        }
    }

} catch (PDOException $e) {
    error_log("Administrative identity management exception: " . $e->getMessage());
    // Display the real database error context if it fails again
    $message = "Database Fault: " . $e->getMessage();
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { font-family: 'Segoe UI', sans-serif; background: var(--light); margin: 0; display: flex; }
        .main-content { flex: 1; padding: 40px; box-sizing: border-box; }
        .profile-container { background: white; padding: 30px; border-radius: 8px; border: 1px solid var(--border); max-width: 600px; box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: var(--navy); font-size: 14px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; font-size: 14px; outline: none; }
        .form-group input:focus { border-color: var(--navy); }
        .submit-btn { background: var(--navy); color: white; border: none; padding: 12px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; }
    </style>
</head>
<body>

    <?php include('../includes/sidebar.php'); ?>

    <div class="main-content">
        <header style="border-bottom: 2px solid var(--border); padding-bottom: 20px; margin-bottom: 30px;">
            <h1 style="margin: 0; font-size: 24px; color: var(--navy);">Update Profile Details</h1>
            <p style="margin: 5px 0 0 0; color: var(--slate);">Modify the administrative security profile criteria used across authorization logs.</p>
        </header>

        <?php if(!empty($message)): ?>
            <div style="padding: 12px; margin-bottom: 25px; border-radius: 6px; font-size: 14px; font-weight: 500; background: <?php echo $message_type === 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #b91c1c;'; ?>; max-width: 600px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="profile-container">
            <form method="POST" action="profile.php">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label for="name">Full Display Name:</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="<?php echo htmlspecialchars($profile_data['name'] ?? ''); ?>" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address Route:</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?php echo htmlspecialchars($profile_data['email'] ?? ''); ?>" 
                        required
                    >
                </div>

                <button type="submit" class="submit-btn">💾 Commit Profile Mutations</button>
            </form>
        </div>
    </div>

</body>
</html>