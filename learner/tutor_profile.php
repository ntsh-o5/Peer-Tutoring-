<?php
// learner/tutor_profile.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

$tutor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search_unit = isset($_GET['search_unit']) ? strtoupper(trim($_GET['search_unit'])) : '';

$tutor = null;
$approved_units = [];
$availabilities = [];
$feedbacks = [];

try {
    // 1. Fetch core user table information
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, NULL as bio 
        FROM users u 
        WHERE u.id = ? AND LOWER(u.role) = 'tutor'
    ");
    $stmt->execute([$tutor_id]);
    $tutor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tutor) {
        // 2. Fetch approved unit codes
        $unitStmt = $pdo->prepare("
            SELECT DISTINCT unit_code 
            FROM tutor_credentials 
            WHERE tutor_id = ? AND LOWER(TRIM(submission_status)) = 'approved'
            ORDER BY unit_code ASC
        ");
        $unitStmt->execute([$tutor_id]);
        $approved_units = $unitStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Fetch structured availability records from tutor_availability table
        $availStmt = $pdo->prepare("
            SELECT day_of_week, start_time, end_time 
            FROM tutor_availability 
            WHERE tutor_id = ?
            ORDER BY 
                CASE LOWER(TRIM(day_of_week))
                    WHEN 'monday' then 1
                    WHEN 'tuesday' then 2
                    WHEN 'wednesday' then 3
                    WHEN 'thursday' then 4
                    WHEN 'friday' then 5
                    WHEN 'saturday' then 6
                    WHEN 'sunday' then 7
                    ELSE 8
                END ASC, start_time ASC
        ");
        $availStmt->execute([$tutor_id]);
        $availabilities = $availStmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Fetch review records safely
        $fStmt = $pdo->prepare("
            SELECT f.comments, r.rating, u.name as learner_name 
            FROM feedback f 
            JOIN ratings r ON f.booking_id = r.booking_id AND f.learner_id = r.learner_id
            JOIN users u ON f.learner_id = u.id 
            WHERE f.tutor_id = ?
            ORDER BY r.rating_id DESC
        ");
        $fStmt->execute([$tutor_id]);
        $feedbacks = $fStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Profile retrieval fault: " . $e->getMessage());
}

if (!$tutor) {
    die("Tutor profile parameter missing or invalid.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tutor['name']); ?> Profile - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { display: flex; min-height: 100vh; background: var(--light); margin: 0; font-family: 'Segoe UI', sans-serif; }
        .main-stage { flex: 1; padding: 40px; max-width: 800px; margin: 0 auto; }
        .profile-container { background: white; padding: 35px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
        .btn { background: var(--navy); color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; font-size: 14px; text-align: center; }
        .btn:hover { background: #173154; }
        .review-card { border-top: 1px solid var(--border); padding-top: 15px; margin-top: 15px; }
        .form-row { margin-bottom: 15px; display: flex; flex-direction: column; gap: 5px; }
        input, select { padding: 11px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; background: white; }
        input:focus, select:focus { outline: none; border-color: var(--navy); }
        
        .unit-display-box { background: #f1f5f9; border: 1px solid var(--border); padding: 14px; border-radius: 6px; font-size: 15px; color: var(--navy); font-weight: 600; margin-bottom: 5px; }
        
        /* Availability Grid Styling */
        .availability-section { background: #f8fafc; border: 1px solid var(--border); border-radius: 6px; padding: 20px; margin-bottom: 25px; }
        .availability-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 10px; }
        .avail-badge { background: white; border: 1px solid var(--border); padding: 10px; border-radius: 4px; font-size: 13px; color: var(--navy); box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
        .avail-day { font-weight: 700; color: #1e293b; display: block; margin-bottom: 2px; }
        .avail-time { color: var(--slate); font-size: 12px; }
    </style>
</head>
<body>
    <div class="main-stage">
        <div style="margin-bottom: 20px;">
            <a href="search_tutor.php?search=<?php echo urlencode($search_unit); ?>" style="color: var(--slate); text-decoration: none; font-weight: 600;">← Back to Search Space</a>
        </div>
        
        <div class="profile-container">
            <h2 style="color: var(--navy); margin-top: 0; margin-bottom: 5px;"><?php echo htmlspecialchars($tutor['name']); ?></h2>
            <p style="color: var(--slate); margin-top: 0; font-size: 14px; margin-bottom: 25px;">Contact Line: <strong><?php echo htmlspecialchars($tutor['email']); ?></strong></p>

            <!-- 1. Tutor Weekly Schedule Block -->
            <div class="availability-section">
                <h3 style="color: var(--navy); margin-top: 0; margin-bottom: 5px; font-size: 16px;">📅 Weekly Availability Windows</h3>
                <p style="color: var(--slate); font-size: 13px; margin-top: 0; margin-bottom: 15px;">Please book standard calendar hours within these verified active windows.</p>
                
                <?php if (empty($availabilities)): ?>
                    <p style="color: var(--slate); font-style: italic; font-size: 13px; margin: 0;">This peer coach hasn't logged recurring available blocks into the system dashboard yet.</p>
                <?php else: ?>
                    <div class="availability-grid">
                        <?php foreach ($availabilities as $slot): ?>
                            <div class="avail-badge">
                                <span class="avail-day"><?php echo htmlspecialchars(ucfirst(trim($slot['day_of_week']))); ?></span>
                                <span class="avail-time">
                                    <?php 
                                        echo date('g:i A', strtotime($slot['start_time'])) . ' - ' . date('g:i A', strtotime($slot['end_time'])); 
                                    ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 2. Booking Application Block -->
            <h3 style="color: var(--navy); margin-top: 30px;">Schedule / Book a Session</h3>
            
            <?php if (empty($approved_units)): ?>
                <p style="color: var(--slate); font-style: italic; font-size: 14px;">This tutor currently has no administrator-approved course units available for booking.</p>
            <?php else: ?>
                
                <form method="POST" action="bookings.php?action=create">
                    <input type="hidden" name="tutor_id" value="<?php echo $tutor['id']; ?>">
                    
                    <!-- Dropdown removed entirely. Replaced with clean text box and silent tracking context -->
                    <div class="form-row">
                        <label style="font-weight: 600; font-size: 13px; color: var(--slate);">Unit of Study Code</label>
                        <div class="unit-display-box">
                            📖 <?php echo htmlspecialchars($search_unit); ?>
                        </div>
                        <input type="hidden" name="unit_code" value="<?php echo htmlspecialchars($search_unit); ?>">
                    </div>
                    
                    <div class="form-row">
                        <label style="font-weight: 600; font-size: 13px; color: var(--slate);">Target Appointment Date & Time Baseline</label>
                        <input type="datetime-local" name="booking_date" min="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                    
                    <button type="submit" class="btn" style="margin-top: 5px; width: 100%;">Confirm & Book Tutor Slot</button>
                </form>
            <?php endif; ?>

            <!-- 3. Historical Evaluation Cards -->
            <h3 style="margin-top: 45px; color: var(--navy); border-bottom: 1px solid var(--border); padding-bottom: 10px;">Historical Peer Evaluations</h3>
            <?php if (empty($feedbacks)): ?>
                <p style="color: var(--slate); font-size: 14px; font-style: italic;">No reviews logged for this peer instructor yet.</p>
            <?php else: ?>
                <?php foreach ($feedbacks as $review): ?>
                    <div class="review-card">
                        <strong style="color: #f59e0b; font-size: 15px;">
                            <?php echo str_repeat('★', (int)$review['rating']) . str_repeat('☆', 5 - (int)$review['rating']); ?> 
                            (<?php echo htmlspecialchars($review['rating']); ?>.0)
                        </strong>
                        <p style="margin: 8px 0; font-size: 14px; color: #1e293b; line-height: 1.5;"><?php echo htmlspecialchars($review['comments']); ?></p>
                        <small style="color: var(--slate); display: block;">Verified Student: <?php echo htmlspecialchars($review['learner_name']); ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>