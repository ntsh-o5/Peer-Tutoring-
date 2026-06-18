<?php
// learner/search_tutors.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$tutors = [];

try {
    if (!empty($search_query)) {
        // Query matching unit of study or specialization case-insensitively
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE LOWER(role) = 'tutor' AND (LOWER(name) LIKE LOWER(?) OR id IN (SELECT tutor_id FROM tutor_units WHERE LOWER(unit_code) LIKE LOWER(?)))");
        $stmt->execute(["%$search_query%", "%$search_query%"]);
        $tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fetch all tutors if no search string is provided
        $stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE LOWER(role) = 'tutor' LIMIT 12");
        $tutors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Search engine failure: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Tutors - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root { --navy: #0f2038; --slate: #475569; --light: #f8fafc; --border: #e2e8f0; }
        body { display: flex; min-height: 100vh; background: var(--light); margin: 0; font-family: 'Segoe UI', sans-serif; }
        .main-stage { flex: 1; padding: 40px; }
        .search-box { display: flex; gap: 15px; margin-bottom: 30px; }
        .search-box input { flex: 1; padding: 12px; border: 1px solid var(--border); border-radius: 6px; font-size: 14px; }
        .btn { background: var(--navy); color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; font-weight: 600; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.01); }
    </style>
</head>
<body>
    <div class="main-stage">
        <div style="margin-bottom: 20px;"><a href="dashboard.php" style="color: var(--slate); text-decoration: none;">← Back to Dashboard</a></div>
        <h2>Find & Match Tutors</h2>
        
        <form method="GET" action="search_tutors.php" class="search-box">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Input Unit of Study Code or Tutor Name (e.g., ICS 2201)...">
            <button type="submit" class="btn">Search Units</button>
        </form>

        <div class="grid">
            <?php if (empty($tutors)): ?>
                <p style="color: var(--slate);">No peer tutors found matching that search criterion.</p>
            <?php else: ?>
                <?php foreach ($tutors as $tutor): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($tutor['name']); ?></h3>
                        <p style="color: var(--slate); font-size: 14px; margin-bottom: 15px;">Peer Instructor Matrix</p>
                        <a href="tutor_profile.php?id=<?php echo $tutor['id']; ?>" class="btn" style="display: block; text-align: center; font-size: 13px;">View Profile & Book</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>