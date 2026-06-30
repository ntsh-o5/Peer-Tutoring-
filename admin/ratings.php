<?php
// admin/ratings.php
// =========================================================================
// 1. SESSION & AUTHENTICATION INITIALIZATION
// =========================================================================
session_start();

if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

$filter_rating = "";
$reviews_list = [];

// Summary Metrics Array Data Variables Default Fallbacks
$review_stats = [
    'average_score' => 0.0,
    'total_reviews' => 0
];

try {
    // =========================================================================
    // 2. LIVE METRICS AGGREGATION (Targeting the 'ratings' table)
    // =========================================================================
    $statsStmt = $pdo->query("SELECT COUNT(*) as total, AVG(rating) as average FROM ratings");
    $fetchedStats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($fetchedStats) {
        $review_stats['total_reviews'] = (int)$fetchedStats['total'];
        $review_stats['average_score'] = (float)$fetchedStats['average'] ?: 0.0;
    }

    // =========================================================================
    // 3. BACKEND RETRIEVAL PROCESSOR & RATING FILTERS (With feedback table joined)
    // =========================================================================
    if (isset($_GET['rating_filter']) && $_GET['rating_filter'] !== "") {
        $filter_rating = intval($_GET['rating_filter']);
        
        $stmt = $pdo->prepare("
            SELECT r.id, r.rating, f.comments as comment, b.unit_code,
                   u_student.name as student_name,
                   u_tutor.name as tutor_name
            FROM ratings r
            JOIN bookings b ON r.booking_id = b.id
            JOIN users u_student ON b.learner_id = u_student.id
            JOIN users u_tutor ON b.tutor_id = u_tutor.id
            LEFT JOIN feedback f ON r.booking_id = f.booking_id
            WHERE r.rating = ?
            ORDER BY r.id DESC
        ");
        $stmt->execute([$filter_rating]);
        $reviews_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query("
            SELECT r.id, r.rating, f.comments as comment, b.unit_code,
                   u_student.name as student_name,
                   u_tutor.name as tutor_name
            FROM ratings r
            JOIN bookings b ON r.booking_id = b.id
            JOIN users u_student ON b.learner_id = u_student.id
            JOIN users u_tutor ON b.tutor_id = u_tutor.id
            LEFT JOIN feedback f ON r.booking_id = f.booking_id
            ORDER BY r.id DESC
        ");
        $reviews_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Administrative rating metric load failure: " . $e->getMessage());
}

/**
 * Helper function to render numerical scores as clean visual star characters
 * @param int $score
 * @return string
 */
function renderStars(int $score): string {
    $clean_score = max(0, min(5, $score)); // Guard boundaries between 0 and 5
    $stars = str_repeat("★ ", $clean_score);
    $empty_stars = str_repeat("☆ ", 5 - $clean_score);
    return trim($stars . $empty_stars);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ratings & Reviews - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

   <?php include('../includes/sidebar.php'); ?>
    <div class="main-content">

        <header>
            <h1>Ratings & Reviews Evaluation Panel</h1>
        </header>

        <section class="cards">
            <div class="card">
                <h3>System Average Rating Score</h3>
                <p style="color: #f39c12; font-weight: bold;">
                    <?php echo number_format($review_stats['average_score'], 1); ?> / 5.0
                </p>
            </div>

            <div class="card">
                <h3>Total Platform Reviews Logged</h3>
                <p><?php echo number_format($review_stats['total_reviews']); ?></p>
            </div>
        </section>

        <div class="search-container">
            <form method="GET" action="ratings.php">
                <select name="rating_filter" style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; margin-right: 5px; font-size: 14px; background: #fff; outline: none;">
                    <option value="">All Scores View Matrix</option>
                    <option value="5" <?php echo $filter_rating === 5 ? 'selected' : ''; ?>>5 Stars</option>
                    <option value="4" <?php echo $filter_rating === 4 ? 'selected' : ''; ?>>4 Stars</option>
                    <option value="3" <?php echo $filter_rating === 3 ? 'selected' : ''; ?>>3 Stars</option>
                    <option value="2" <?php echo $filter_rating === 2 ? 'selected' : ''; ?>>2 Stars</option>
                    <option value="1" <?php echo $filter_rating === 1 ? 'selected' : ''; ?>>1 Star</option>
                </select>
                <button type="submit" style="cursor:pointer; border-radius:6px; font-weight:600;">Filter Rows</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Review ID</th>
                        <th>Student Name (Reviewer)</th>
                        <th>Tutor Assigned</th>
                        <th>Unit Code</th>
                        <th>Numerical Score</th>
                        <th>Visual Rating Matrix</th>
                        <th>Written Comment Observation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews_list)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; font-style: italic; color: #475569; padding: 20px;">
                                No system review entries match the selected filters context.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reviews_list as $row): ?>
                            <tr>
                                <td>#RAT-<?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['tutor_name']); ?></td>
                                <td><code><?php echo htmlspecialchars($row['unit_code']); ?></code></td>
                                <td><code><?php echo number_format($row['rating'], 0); ?> / 5</code></td>
                                <td style="color: #f39c12; font-size: 16px; letter-spacing: 1px; white-space: nowrap;">
                                    <?php echo renderStars((int)$row['rating']); ?>
                                </td>
                                <td style="color: #334155; font-style: italic;">
                                    "<?php echo htmlspecialchars($row['comment'] ?: 'No evaluation commentary submitted.'); ?>"
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