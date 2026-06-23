<?php
// learner/search_tutor.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$tutors = [];

try {
    // The subquery ensures we only fetch tutors with at least one 'approved' credential,
    // effectively filtering out 'pending' statuses that cause duplicate results.
    $query = "
        SELECT DISTINCT u.id, u.name, u.email, tp.hourly_rate
        FROM users u
        LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
        WHERE u.id IN (
            SELECT tutor_id 
            FROM tutor_credentials 
            WHERE LOWER(submission_status) = 'approved'
        )
        AND LOWER(u.user_role) = 'tutor'
    ";

    if (!empty($search_query)) {
        // Search by name or by specific approved unit codes
        $query .= " AND (LOWER(u.name) LIKE ? OR u.id IN (
            SELECT tutor_id FROM tutor_credentials 
            WHERE LOWER(unit_code) LIKE ? AND LOWER(submission_status) = 'approved'
        ))";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute(["%$search_query%", "%$search_query%"]);
    } else {
        // Default view: Show unique tutors with approved status
        $stmt = $pdo->prepare($query . " LIMIT 12");
        $stmt->execute();
    }
    
    $tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Search engine failure: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Tutors - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { display: flex; min-height: 100vh; background: var(--light); margin: 0; font-family: 'Segoe UI', sans-serif; }
        .main-stage { flex: 1; padding: 40px; }
        .search-box { display: flex; gap: 15px; margin-bottom: 30px; }
        .search-box input { flex: 1; padding: 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; }
        .search-box input:focus { outline: none; border-color: var(--navy); }
        .btn { background: var(--navy); color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-weight: 600; font-size: 14px; text-align: center; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .card { background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); display: flex; flex-direction: column; justify-content: space-between; }
        .badge { background: #d1fae5; color: #065f46; font-size: 11px; font-weight: bold; padding: 4px 8px; border-radius: 4px; align-self: flex-start; margin-bottom: 10px; text-transform: uppercase; }
        .rate-tag { font-size: 14px; font-weight: 700; color: var(--navy); margin: 10px 0 15px 0; }
    </style>
</head>
<body>
    <div class="main-stage">
        <div style="margin-bottom: 20px;"><a href="dashboard.php" style="color: var(--slate); text-decoration: none; font-weight: 600;">← Back to Dashboard</a></div>
        <h2 style="color: var(--navy); margin-bottom: 5px;">Find & Match Tutors</h2>
        <p style="color: var(--slate); font-size: 14px; margin-top: 0; margin-bottom: 25px;">Search through vetted peer instructors verified by the administrator panel.</p>
        
        <form method="GET" action="search_tutor.php" class="search-box">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Input Unit of Study Code or Tutor Name (e.g., ICS 1204)...">
            <button type="submit" class="btn">Search Units</button>
        </form>

        <div class="grid">
            <?php if (empty($tutors)): ?>
                <p style="color: var(--slate); font-style: italic;">No verified peer tutors found matching that search criterion.</p>
            <?php else: ?>
                <?php foreach ($tutors as $tutor): ?>
                    <div class="card">
                        <div>
                            <span class="badge">✓ Verified Coach</span>
                            <h3 style="margin: 5px 0; color: var(--navy);"><?php echo htmlspecialchars($tutor['name']); ?></h3>
                            <p style="color: var(--slate); font-size: 13px; margin: 0;"><?php echo htmlspecialchars($tutor['email']); ?></p>
                            <div class="rate-tag">
                                <?php echo !empty($tutor['hourly_rate']) ? "KES " . number_format($tutor['hourly_rate'], 2) . " / hr" : "Rate not set"; ?>
                            </div>
                        </div>
                        <a href="tutor_profile.php?id=<?php echo $tutor['id']; ?>" class="btn" style="display: block; font-size: 13px; padding: 10px;">View Profile & Book</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>