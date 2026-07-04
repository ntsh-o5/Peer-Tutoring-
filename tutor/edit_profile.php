<?php
// tutor/edit_profile.php
session_start();

// Strict security layer gate
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'tutor') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$tutor_id = $_SESSION['user_id'];

$message = '';
$message_type = 'success';

// 1. Handle Profile Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $bio = trim($_POST['bio']);
    $hourly_rate = (float)$_POST['hourly_rate'];
    $skills = trim($_POST['skills']); // Comma-separated unit codes or expertise keywords

    try {
        $pdo->beginTransaction();

        // Update core user credentials
        $userStmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $userStmt->execute([$name, $email, $tutor_id]);

        // Check if a tutor profile sub-record exists
        $checkStmt = $pdo->prepare("SELECT user_id FROM tutor_profiles WHERE user_id = ?");
        $checkStmt->execute([$tutor_id]);
        
        if ($checkStmt->rowCount() > 0) {
            // Update existing profile details
            $profileStmt = $pdo->prepare("UPDATE tutor_profiles SET phone = ?, bio = ?, hourly_rate = ?, skills = ? WHERE user_id = ?");
            $profileStmt->execute([$phone, $bio, $hourly_rate, $skills, $tutor_id]);
        } else {
            // Create a profile record if it doesn't exist yet
            $profileStmt = $pdo->prepare("INSERT INTO tutor_profiles (user_id, phone, bio, hourly_rate, skills) VALUES (?, ?, ?, ?, ?)");
            $profileStmt->execute([$tutor_id, $phone, $bio, $hourly_rate, $skills]);
        }

        $pdo->commit();
        
        // Update session state variables
        $_SESSION['user_name'] = $name;
        
        $message = "Your profile configurations have been updated successfully!";
        $message_type = 'success';
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Profile synchronization failure: " . $e->getMessage());
        $message = "Failed to update profile: " . $e->getMessage();
        $message_type = 'error';
    }
}

// 2. Fetch Current Profile Details
$profile = [];
try {
    $fetchStmt = $pdo->prepare("
        SELECT u.name, u.email, tp.phone, tp.bio, tp.hourly_rate, tp.skills 
        FROM users u
        LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
        WHERE u.id = ?
    ");
    $fetchStmt->execute([$tutor_id]);
    $profile = $fetchStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Profile retrieval error: " . $e->getMessage());
}

// Set up fallbacks for empty profile fields to handle PHP 8.1+ gracefully
$phone_val = isset($profile['phone']) ? htmlspecialchars($profile['phone']) : '';
$bio_val = isset($profile['bio']) ? htmlspecialchars($profile['bio']) : '';
$rate_val = isset($profile['hourly_rate']) ? (float)$profile['hourly_rate'] : 0.00;
$skills_val = isset($profile['skills']) ? htmlspecialchars($profile['skills']) : '';
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
        .wrapper { max-width: 700px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid var(--border); padding-bottom: 20px; }
        .card-box { background: white; padding: 30px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 20px; }
        label { font-size: 13px; font-weight: 600; color: var(--slate); }
        input, select, textarea { padding: 12px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; font-family: inherit; }
        input:focus, textarea:focus { border-color: var(--navy); outline: none; }
        textarea { resize: vertical; min-height: 100px; }
        .btn-submit { background: var(--navy); color: white; padding: 14px; border: none; border-radius: 4px; font-weight: bold; width: 100%; cursor: pointer; font-size: 15px; }
        .btn-submit:hover { opacity: 0.9; }
    </style>
</head>
<body>

    <div class="wrapper">
        <header>
            <div>
                <h1 style="margin: 0; font-size: 24px; color: var(--navy);">Profile Settings</h1>
                <p style="margin: 5px 0 0 0; color: var(--slate);">Keep your bio, expert units, and rates up to date for your students.</p>
            </div>
            <a href="dashboard.php" style="color: var(--navy); text-decoration: none; font-weight: 600; font-size: 14px;">← Dashboard Hub</a>
        </header>

        <?php if(!empty($message)): ?>
            <div style="padding: 12px; margin-bottom: 25px; border-radius: 6px; font-size: 14px; font-weight: 500; background: <?php echo $message_type === 'success' ? '#dcfce7; color: #166534;' : '#fee2e2; color: #b91c1c;'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="card-box">
            <form method="POST" action="edit_profile.php">
                <input type="hidden" name="action_update_profile" value="1">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($profile['name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?php echo $phone_val; ?>" placeholder="e.g., +254 700 000000">
                </div>

                <div class="form-group">
                    <label>Session Rate per Hour (KES)</label>
                    <input type="number" name="hourly_rate" value="<?php echo $rate_val; ?>" step="50" min="0" required>
                </div>

                <div class="form-group">
                    <label>Target Units & Skills (Comma-separated)</label>
                    <input type="text" name="skills" value="<?php echo $skills_val; ?>" placeholder="e.g., ICS 2104, BBIT 1102, MySQL, React">
                    <small style="color: #64748b; font-size: 11px; margin-top: 4px;">List the course unit codes you are qualified to tutor so students can find you.</small>
                </div>

                <div class="form-group">
                    <label>Tutor Biography</label>
                    <textarea name="bio" placeholder="Tell students about your academic strengths, teaching approach, or when you are usually free..."><?php echo $bio_val; ?></textarea>
                </div>

                <button type="submit" class="btn-submit">Save Changes</button>
            </form>
        </div>
    </div>

</body>
</html>