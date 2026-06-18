<?php
// =========================================================================
// 1. BACKEND PROCESSING LOGIC & RATING FILTERS
// =========================================================================

// Placeholder for Database Connection (e.g., include('config/db.php');)

// Capture and sanitize filter parameters
$filter_rating = "";
if (isset($_GET['rating_filter']) && $_GET['rating_filter'] !== "") {
    $filter_rating = intval($_GET['rating_filter']);
    // Future database execution logic:
    // $stmt = $pdo->prepare("SELECT * FROM ratings WHERE score = ?");
    // $stmt->execute([$filter_rating]);
}

// Summary Metrics Array Data Variables
$review_stats = [
    'average_score' => 4.5,
    'total_reviews' => 120
];

/**
 * Helper function to render numerical scores as clean visual star characters
 * @param int $score
 * @return string
 */
function renderStars(int $score): string {
    $stars = str_repeat("★ ", $score);
    $empty_stars = str_repeat("☆ ", 5 - $score);
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
            <h1>Ratings & Reviews</h1>
        </header>

        <section class="cards">
            <div class="card">
                <h3>Average Rating</h3>
                <p style="color: #f39c12; font-weight: bold;">
                    <?php echo number_format($review_stats['average_score'], 1); ?> / 5.0
                </p>
            </div>

            <div class="card">
                <h3>Total Reviews</h3>
                <p><?php echo number_format($review_stats['total_reviews']); ?></p>
            </div>
        </section>

        <div class="search-container">
            <form method="GET" action="ratings.php">
                <select name="rating_filter" style="padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin-right: 5px; font-size: 14px;">
                    <option value="">All Scores</option>
                    <option value="5" <?php echo $filter_rating === 5 ? 'selected' : ''; ?>>5 Stars</option>
                    <option value="4" <?php echo $filter_rating === 4 ? 'selected' : ''; ?>>4 Stars</option>
                    <option value="3" <?php echo $filter_rating === 3 ? 'selected' : ''; ?>>3 Stars</option>
                    <option value="2" <?php echo $filter_rating === 2 ? 'selected' : ''; ?>>2 Stars</option>
                    <option value="1" <?php echo $filter_rating === 1 ? 'selected' : ''; ?>>1 Star</option>
                </select>
                <button type="submit">Filter Rows</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>Numerical Score</th>
                        <th>Visual Rating</th>
                        <th>Written Comment</th>
                    </tr>
                </thead>
                <tbody>

                    <tr>
                        <td>Brian</td>
                        <td><strong>5</strong> / 5</td>
                        <td style="color: #f39c12; font-size: 16px; letter-spacing: 1px;">
                            <?php echo renderStars(5); ?>
                        </td>
                        <td>Excellent tutor support</td>
                    </tr>

                    <tr>
                        <td>Mary</td>
                        <td><strong>4</strong> / 5</td>
                        <td style="color: #f39c12; font-size: 16px; letter-spacing: 1px;">
                            <?php echo renderStars(4); ?>
                        </td>
                        <td>Good platform</td>
                    </tr>

                </tbody>
            </table>
        </div>

    </div>

</body>
</html>