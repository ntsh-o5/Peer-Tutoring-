<?php
// =========================================================================
// 1. BACKEND PROCESSING LOGIC & GET SEARCH HANDLERS
// =========================================================================

// Placeholder for Database Connection (e.g., include('config/db.php');)

// Capture and sanitize search values
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = htmlspecialchars(trim($_GET['search']));
}

// Track if a specific row has been selected for detail inspection
$selected_grade_id = null;
if (isset($_GET['view_id'])) {
    $selected_grade_id = htmlspecialchars(trim($_GET['view_id']));
    
    // In production, you would fetch details for the single record here:
    // $stmt = $pdo->prepare("SELECT * FROM grades WHERE grade_id = ?");
    // $stmt->execute([$selected_grade_id]);
    // $selected_analysis = $stmt->fetch();
}

// Statistics Overview Card Metric Data variables placeholders
$stats = [
    'total_records'       => 185,
    'improved_learners'   => 82,
    'avg_improvement'     => '+12%'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades Management - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

   <?php include('../includes/sidebar.php'); ?>

    <div class="main-content">

        <header>
            <h1>Learner Grades</h1>
        </header>

        <section class="cards">
            <div class="card">
                <h3>Total Grade Records</h3>
                <p><?php echo number_format($stats['total_records']); ?></p>
            </div>

            <div class="card">
                <h3>Improved Learners</h3>
                <p><?php echo number_format($stats['improved_learners']); ?></p>
            </div>

            <div class="card">
                <h3>Average Improvement</h3>
                <p><?php echo $stats['avg_improvement']; ?></p>
            </div>
        </section>

        <div class="search-container">
            <form method="GET" action="grades.php">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search learner..." 
                    value="<?php echo $search_query; ?>"
                >
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Grade ID</th>
                        <th>Learner</th>
                        <th>Unit</th>
                        <th>Before Tutoring</th>
                        <th>After Tutoring</th>
                        <th>Improvement</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                    <tr>
                        <td>G001</td>
                        <td>Brian Otieno</td>
                        <td>Networking</td>
                        <td>C</td>
                        <td>B+</td>
                        <td><span class="status-badge approved">↑ Improved</span></td>
                        <td>
                            <a href="grades.php?view_id=G001<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View</a>
                        </td>
                    </tr>

                    <tr>
                        <td>G002</td>
                        <td>Mary Njeri</td>
                        <td>Database Systems</td>
                        <td>B</td>
                        <td>A-</td>
                        <td><span class="status-badge approved">↑ Improved</span></td>
                        <td>
                            <a href="grades.php?view_id=G002<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View</a>
                        </td>
                    </tr>

                    <tr>
                        <td>G003</td>
                        <td>Kevin Maina</td>
                        <td>Programming</td>
                        <td>C+</td>
                        <td>C+</td>
                        <td><span class="status-badge pending">No Change</span></td>
                        <td>
                            <a href="grades.php?view_id=G003<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View</a>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <?php if ($selected_grade_id === 'G001'): ?>
            <div class="table-container" style="margin-top:20px;">
                <h2>Grade Analysis Details</h2>
                <p><strong>Record Reference ID:</strong> <?php echo $selected_grade_id; ?></p>
                <p><strong>Learner:</strong> Brian Otieno</p>
                <p><strong>Unit:</strong> Networking</p>
                <p><strong>Before Tutoring:</strong> C</p>
                <p><strong>After Tutoring:</strong> B+</p>
                <br>
                <p><strong>Observation Summary:</strong></p>
                <p>
                    Significant improvement after four tutoring sessions. Learner demonstrated a stronger 
                    understanding of core structural tasks, subnetting matrices, and logical network troubleshooting concepts.
                </p>
            </div>
        <?php elseif ($selected_grade_id): ?>
            <div class="table-container" style="margin-top:20px;">
                <h2>Grade Analysis Details</h2>
                <p>Displaying data analysis overview for requested reference item: <strong><?php echo $selected_grade_id; ?></strong>.</p>
                <p><em>(Database payload information loop connects directly here)</em></p>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>