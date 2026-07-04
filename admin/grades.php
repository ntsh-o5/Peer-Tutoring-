<?php
// admin/grades.php
// =========================================================================
// 1. SESSION & AUTHENTICATION INITIALIZATION
// =========================================================================
session_start();

if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';

$search_query = "";
$grade_records = [];
$selected_record = null;

// Statistics Default Fallbacks
$stats = [
    'total_records'     => 0,
    'improved_learners' => 0,
    'avg_gpa'           => '0.00'
];

try {
    // 2. DYNAMIC SYSTEM-WIDE METRICS AGGREGATION
    // Total Records
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM academic_progress");
    $stats['total_records'] = (int)$totalStmt->fetchColumn();

    // Improved Learners (Where current grade point is strictly higher than initial progress benchmarks)
    $improvedStmt = $pdo->query("SELECT COUNT(*) FROM academic_progress WHERE grade_point > 2.00");
    $stats['improved_learners'] = (int)$improvedStmt->fetchColumn();

    // Average Performance Standing
    $avgGpaStmt = $pdo->query("SELECT AVG(grade_point) FROM academic_progress");
    $avgGpaVal = $avgGpaStmt->fetchColumn();
    if ($avgGpaVal) {
        $stats['avg_gpa'] = number_format((float)$avgGpaVal, 2);
    }

    // 3. CAPTURE AND SANITIZE SEARCH VALUES (PostgreSQL Case-Insensitive)
    if (isset($_GET['search']) && trim($_GET['search']) !== "") {
        $search_query = trim($_GET['search']);
        $stmt = $pdo->prepare("
            SELECT ap.id, ap.unit_code, ap.grade_point, ap.remarks, ap.created_at, u.name as learner_name
            FROM academic_progress ap
            JOIN users u ON ap.learner_id = u.id
            WHERE LOWER(u.name) LIKE LOWER(?) OR LOWER(ap.unit_code) LIKE LOWER(?)
            ORDER BY ap.created_at DESC
        ");
        $stmt->execute(["%$search_query%", "%$search_query%"]);
        $grade_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query("
            SELECT ap.id, ap.unit_code, ap.grade_point, ap.remarks, ap.created_at, u.name as learner_name
            FROM academic_progress ap
            JOIN users u ON ap.learner_id = u.id
            ORDER BY ap.created_at DESC
        ");
        $grade_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. DETAILED INSPECTION ROW SELECTION OVERVIEW HANDLER
    if (isset($_GET['view_id'])) {
        $selected_grade_id = (int)$_GET['view_id'];
        $viewStmt = $pdo->prepare("
            SELECT ap.id, ap.unit_code, ap.grade_point, ap.remarks, ap.created_at, u.name as learner_name
            FROM academic_progress ap
            JOIN users u ON ap.learner_id = u.id
            WHERE ap.id = ?
        ");
        $viewStmt->execute([$selected_grade_id]);
        $selected_record = $viewStmt->fetch(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Administrative grading core breakdown: " . $e->getMessage());
}
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
            <h1>Learner Grades Performance Metrics</h1>
        </header>

        <section class="cards">
            <div class="card">
                <h3>Total Grade Records Logged</h3>
                <p><?php echo number_format($stats['total_records']); ?></p>
            </div>

            <div class="card">
                <h3>Satisfactory Standings</h3>
                <p><?php echo number_format($stats['improved_learners']); ?></p>
            </div>

            <div class="card">
                <h3>System Average Grade Point</h3>
                <p style="color: #10b981;"><?php echo $stats['avg_gpa']; ?></p>
            </div>
        </section>

        <div class="search-container">
            <form method="GET" action="grades.php">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search learner name or unit code..." 
                    value="<?php echo htmlspecialchars($search_query); ?>"
                >
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Record ID</th>
                        <th>Learner Name</th>
                        <th>Unit Code</th>
                        <th>Current Grade Point Metric</th>
                        <th>Logged Performance Benchmark</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($grade_records)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; font-style: italic; color: #475569; padding: 20px;">
                                No academic progress grading profiles match the active search criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($grade_records as $row): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['learner_name']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['unit_code']); ?></strong></td>
                                <td><span style="font-weight:bold; color:#0f2038;"><?php echo number_format($row['grade_point'], 2); ?></span></td>
                                <td>
                                    <?php if ($row['grade_point'] >= 3.00): ?>
                                        <span class="status-badge approved">Excellent</span>
                                    <?php elseif ($row['grade_point'] >= 2.00): ?>
                                        <span class="status-badge pending">Stable</span>
                                    <?php else: ?>
                                        <span class="status-badge rejected">Needs Review</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="grades.php?view_id=<?php echo $row['id']; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($selected_record): ?>
            <div class="table-container" style="margin-top:20px; border-left: 4px solid #10b981; padding-left:20px;">
                <h2>Grade Analysis Details</h2>
                <p><strong>Record Reference ID:</strong> #<?php echo $selected_record['id']; ?></p>
                <p><strong>Learner Target Profile:</strong> <?php echo htmlspecialchars($selected_record['learner_name']); ?></p>
                <p><strong>Unit of Study:</strong> <?php echo htmlspecialchars($selected_record['unit_code']); ?></p>
                <p><strong>Calculated Point Scale:</strong> <?php echo number_format($selected_record['grade_point'], 2); ?></p>
                <p><strong>Submission Entry Date:</strong> <?php echo date('F d, Y - H:i', strtotime($selected_record['created_at'])); ?></p>
                <br>
                <p><strong>Instructor Observational Evaluation Summary Remarks:</strong></p>
                <blockquote style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius:6px; margin:0; color:#475569; font-style:italic;">
                    "<?php echo htmlspecialchars($selected_record['remarks'] ?: 'No structural assessment logs written for this transaction tracking sequence.'); ?>"
                </blockquote>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>