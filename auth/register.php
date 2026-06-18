<?php
// auth/register.php
session_start();

// 1. Redirect if the user session is already active (Send them directly to their homes)
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($_SESSION['user_role'] === 'tutor') {
        header("Location: ../tutor/dashboard.php");
    } else {
        header("Location: ../learner/dashboard.php");
    }
    exit;
}

// 2. Load PostgreSQL configuration
require_once '../config/database.php';

$error = "";
$success = "";

// 3. BACKEND PROCESSING LAYER: Runs only on POST submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = htmlspecialchars(trim($_POST['name']));
    $email    = htmlspecialchars(trim($_POST['email']));
    $password = trim($_POST['password']);
    $role     = trim($_POST['role']);

    // Basic validation
    if (!empty($name) && !empty($email) && !empty($password) && !empty($role)) {
        
        // 💡 EXPLICIT SECURITY CHECK: Only allow learner and tutor strings from the public form
        if (in_array($role, ['learner', 'tutor'])) {
            try {
                // Check if email already exists in PostgreSQL
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $checkStmt->execute([$email]);
                
                if ($checkStmt->fetch()) {
                    $error = "This email address is already registered.";
                } else {
                    // Cryptographically hash the raw password input string
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                    // Insert the new account record
                    $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
                    $insertStmt->execute([$name, $email, $hashedPassword, $role]);

                    $success = "Account created successfully! You can now sign in.";
                }
            } catch (PDOException $e) {
                $error = "Registration system failure: " . $e->getMessage();
            }
        } else {
            $error = "Invalid role structure selected.";
        }
    } else {
        $error = "Please fill in all configuration parameters.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - PeerTutor</title>
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
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 450px;
            border-top: 4px solid #0f2038;
        }
        .register-container h2 {
            color: #0f2038;
            margin-bottom: 25px;
            font-size: 24px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #475569;
            font-weight: 500;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
            background-color: white;
        }
        .form-group input:focus, .form-group select:focus {
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
        }
        .success-banner {
            background: #dcfce7;
            color: #15803d;
            padding: 12px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
            font-weight: 500;
            border-left: 4px solid #16a34a;
        }
        .register-btn {
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
        .register-btn:hover {
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

<div class="register-container">
    <h2>Join PeerTutor</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error-banner"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success-banner"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" placeholder="John Doe" required autocomplete="off">
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="student@strathmore.edu" required autocomplete="off">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Min. 8 characters" minlength="8" required>
        </div>

        <div class="form-group">
            <label for="role">Account Type</label>
            <select id="role" name="role" required>
                <option value="" disabled selected>Select your system role...</option>
                <option value="learner">Learner (Seeking Tutoring)</option>
                <option value="tutor">Tutor (Offering Support)</option>
            </select>
        </div>

        <button type="submit" class="register-btn">Create Account</button>
    </form>

    <div class="switch-link">
        Already have an account? <a href="login.php">Sign In</a>
    </div>
</div>

</body>
</html>