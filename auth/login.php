<?php
// auth/login.php
session_start();

// 1. Redirect if the user session is already active (Don't let logged-in users see the form)
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

// 2. Pull in the database connection file (instantiates $pdo)
require_once '../config/database.php'; 

// 3. Pull in your object-oriented class blueprints
require_once '../classes/User.php';

$error = "";

// 4. BACKEND PROCESSING LAYER: Runs only on form submission (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        
        // Execute your OOAD static check routine passing the instantiated PDO connection
        $user = User::authenticate($email, $password, $pdo);

        // -----------------------------------------------------------------
        // 🛑 RUNTIME DEBUGGER ENGINE COUPLING
        // -----------------------------------------------------------------
        if ($user) {
            $_SESSION['user_id']   = $user->getId();
            $_SESSION['user_name'] = $user->getName();
            $_SESSION['user_role'] = strtolower(trim($user->getRole()));

            echo "<div style='font-family: Consolas, monospace; background: #1e1e2e; color: #a6e3a1; padding: 30px; margin: 50px auto; max-width: 700px; border-radius: 8px; border-left: 5px solid #23d160; box-shadow: 0 4px 15px rgba(0,0,0,0.3);'>";
            echo "<h2 style='color: #ffffff; margin-top: 0; border-bottom: 1px solid #45475a; padding-bottom: 10px;'>🔓 Authentication Milestones Succeeded!</h2>";
            echo "<p><strong>User Object Blueprint:</strong> " . htmlspecialchars(get_class($user)) . "</p>";
            echo "<p><strong>Assigned Session Name:</strong> " . htmlspecialchars($_SESSION['user_name']) . "</p>";
            echo "<p><strong>Assigned Session Role:</strong> " . htmlspecialchars($_SESSION['user_role']) . "</p>";
            echo "<p><strong>Calculated Redirect URL:</strong> <span style='color: #f9e2af;'>../" . htmlspecialchars($_SESSION['user_role']) . "/dashboard.php</span></p>";
            echo "<hr style='border: 0; border-top: 1px solid #45475a; margin: 20px 0;'>";
            echo "<p style='color: #cdd6f4; font-size: 13px;'>If your login sequence still loops after removal, check for folder name layout variations or strict security gates in your destination dashboard file layout block.</p>";
            echo "<a href='../" . htmlspecialchars($_SESSION['user_role']) . "/dashboard.php' style='display: inline-block; background: #23d160; color: #11111b; padding: 10px 20px; text-decoration: none; font-weight: bold; border-radius: 4px; margin-top: 10px;'>Force Manual Redirect Forward</a>";
            echo "</div>";
            exit;
        } else {
            // Diagnostic intercept fallback when User::authenticate maps back a null value
            // We want to verify if the email address itself exists in the repository configuration framework
            try {
                $diagStmt = $pdo->prepare("SELECT id, email, role, password_hash FROM users WHERE email = ? LIMIT 1");
                $diagStmt->execute([$email]);
                $diagRow = $diagStmt->fetch(PDO::FETCH_ASSOC);

                if (!$diagRow) {
                    $error = "Debugger Report: Query returned 0 rows. The email '" . htmlspecialchars($email) . "' does not exist in the database table grid infrastructure.";
                } else {
                    $error = "Debugger Report: Email match discovered for ID (" . $diagRow['id'] . ") with Role [" . $diagRow['role'] . "]. However, password_verify() returned FALSE against the hash code index parameters.";
                }
            } catch (PDOException $diagErr) {
                $error = "Debugger Connection Exception error: " . htmlspecialchars($diagErr->getMessage());
            }
        }
        // -----------------------------------------------------------------
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
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #f4f6f9;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 400px;
            border-top: 4px solid #0f2038;
        }
        .login-container h2 {
            color: #0f2038;
            margin-bottom: 25px;
            font-size: 24px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #475569;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.15s ease;
        }
        .form-group input:focus {
            border-color: #0f2038;
        }
        .error-banner {
            background: #fee2e2;
            color: #b91c1c;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
            font-weight: 500;
            border-left: 4px solid #dc2626;
            word-wrap: break-word;
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            border: none;
            background: #0f2038;
            color: white;
            cursor: pointer;
            font-weight: 600;
            border-radius: 6px;
            font-size: 14px;
            transition: background 0.15s ease;
        }
        .login-btn:hover {
            background: #1d3557;
        }
        .switch-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #64748b;
        }
        .switch-link a {
            color: #0f2038;
            text-decoration: none;
            font-weight: 600;
        }
        .switch-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>PeerTutor Portal</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-banner"><?php echo $error; ?></div>
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