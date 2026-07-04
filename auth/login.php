<?php
// auth/login.php
// =========================================================================
// 1. SESSION & ACTIVE REDIRECT CHECKS
// =========================================================================
session_start();

if (isset($_SESSION['user_role'])) {
    $sessionRole = strtolower(trim($_SESSION['user_role']));
    if ($sessionRole === 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($sessionRole === 'tutor') {
        header("Location: ../tutor/dashboard.php");
    } else {
        header("Location: ../learner/dashboard.php");
    }
    exit;
}

require_once '../config/database.php'; 
require_once '../classes/User.php';

$error = "";

// =========================================================================
// 2. FORM PROCESSING MUTATION CONTROLLER
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        
        // Execute authentication check routine using object data mapping
        $user = User::authenticate($email, $password, $pdo);

        if ($user) {
            // Establish systemic identity mapping indexes
            $_SESSION['user_id']   = (int)$user->getId();
            $_SESSION['user_name'] = $user->getName();
            $_SESSION['user_role'] = strtolower(trim($user->getRole()));

            // Safe architectural routing switch matrix
            if ($_SESSION['user_role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } elseif ($_SESSION['user_role'] === 'tutor') {
                header("Location: ../tutor/dashboard.php");
            } else {
                header("Location: ../learner/dashboard.php");
            }
            exit;
        } else {
            // Diagnostic fallback check to determine failure context
            try {
                $diagStmt = $pdo->prepare("SELECT id, email, user_role, password_hash FROM users WHERE email = ? LIMIT 1");
                $diagStmt->execute([$email]);
                $diagRow = $diagStmt->fetch(PDO::FETCH_ASSOC);

                if (!$diagRow) {
                    $error = "The provided email address does not exist in our system.";
                } else {
                    $error = "Invalid password. Please double-check your credentials and try again.";
                }
            } catch (PDOException $diagErr) {
                error_log("Login system diagnostic crash sequence: " . $diagErr->getMessage());
                $error = "A system connection fault occurred. Please try again later.";
            }
        }
    } else {
        $error = "Please complete all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f4f6f9; margin: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        .login-container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); width: 100%; max-width: 400px; border-top: 4px solid #0f2038; }
        .login-container h2 { color: #0f2038; margin-bottom: 25px; font-size: 24px; text-align: center; font-weight: 700; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; color: #475569; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; outline: none; }
        .form-group input:focus { border-color: #0f2038; }
        .error-banner { background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 6px; font-size: 13px; margin-bottom: 20px; font-weight: 500; border-left: 4px solid #dc2626; }
        .login-btn { width: 100%; padding: 12px; border: none; background: #0f2038; color: white; cursor: pointer; font-weight: 600; border-radius: 6px; font-size: 14px; }
        .login-btn:hover { background: #1d3557; }
        .switch-link { text-align: center; margin-top: 20px; font-size: 14px; color: #64748b; }
        .switch-link a { color: #0f2038; text-decoration: none; font-weight: 600; }
        .switch-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-container">
    <h2>PeerTutor Portal</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-banner"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="student@strathmore.edu" required autocomplete="off">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" class="login-btn">Log In</button>
    </form>

    <div class="switch-link">
        Don't have an account? <a href="register.php">Sign Up</a>
    </div>
</div>

</body>
</html>