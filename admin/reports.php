<?php
// admin/reports.php
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
$reports_list = [];
$selected_report = null;

// Summary Metrics Array Data Variables Default Fallbacks
$report_stats = [
    'total_reports'   => 0,
    'top_grades_count'=> 0
];

try {
    // =========================================================================
    // 2. LIVE METRICS AGGREGATION (Targeting 'progress_reports' table)
    // =========================================================================
    // Total Reports Count
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM progress_reports");
    $report_stats['total_reports'] = (int)$totalStmt->fetchColumn();

    // Top Performance Count (Grade Achieved is 'A')
    $topStmt = $pdo->query("SELECT COUNT(*) FROM progress_reports WHERE UPPER(TRIM(grade_achieved)) = 'A'");
    $report_stats['top_grades_count'] = (int)$topStmt->fetchColumn();


    // =========================================================================
    // 3. BACKEND RETRIEVAL PROCESSOR & SEARCH FILTERS
    // =========================================================================
    if (isset($_GET['search']) && trim($_GET['search']) !== "") {
        $search_query = trim($_GET['search']);
        
        $stmt = $pdo->prepare("
            SELECT pr.id, pr.performance_assessment, pr.academic_remarks, pr.grade_achieved, pr.created_at,
                   u_student.name as student_name,
                   u_tutor.name as tutor_name
            FROM progress_reports pr
            JOIN users u_student ON pr.learner_id = u_student.id
            JOIN users u_tutor ON pr.tutor_id = u_tutor.id
            WHERE LOWER(u_student.name) LIKE LOWER(?) 
               OR LOWER(u_tutor.name) LIKE LOWER(?) 
               OR LOWER(pr.performance_assessment) LIKE LOWER(?)
            ORDER BY pr.id DESC
        ");
        $likeQuery = "%" . $search_query . "%";
        $stmt->execute([$likeQuery, $likeQuery, $likeQuery]);
        $reports_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query("
            SELECT pr.id, pr.performance_assessment, pr.academic_remarks, pr.grade_achieved, pr.created_at,
                   u_student.name as student_name,
                   u_tutor.name as tutor_name
            FROM progress_reports pr
            JOIN users u_student ON pr.learner_id = u_student.id
            JOIN users u_tutor ON pr.tutor_id = u_tutor.id
            ORDER BY pr.id DESC
        ");
        $reports_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // 4. DETAILED SIDE INSPECTION WINDOW OVERVIEW
    // =========================================================================
    if (isset($_GET['view_id'])) {
        $view_id = (int)$_GET['view_id'];
        $viewStmt = $pdo->prepare("
            SELECT pr.id, pr.booking_id, pr.performance_assessment, pr.academic_remarks, pr.grade_achieved, pr.created_at,
                   u_student.name as student_name,
                   u_tutor.name as tutor_name
            FROM progress_reports pr
            JOIN users u_student ON pr.learner_id = u_student.id
            JOIN users u_tutor ON pr.tutor_id = u_tutor.id
            WHERE pr.id = ?
        ");
        $viewStmt->execute([$view_id]);
        $selected_report = $viewStmt->fetch(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    error_log("Administrative progress report loader failure: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Reports Evaluation - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

   <?php include('../includes/sidebar.php'); ?>
    <div class="main-content">

        <header>
            <h1>Academic Progress Reports Panel</h1>
        </header>

        <section class="cards">
            <div class="card">
                <h3>Total Reports Logged</h3>
                <p style="color: #3b82f6; font-weight: bold;">
                    <?php echo number_format($report_stats['total_reports']); ?>
                </p>
            </div>

            <div class="card">
                <h3>Excellent Elite Standings (Grade A)</h3>
                <p style="color: #10b981; font-weight: bold;">
                    <?php echo number_format($report_stats['top_grades_count']); ?>
                </p>
            </div>
        </section>

        <div class="search-container">
            <form method="GET" action="reports.php">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search student, tutor or progression status..." 
                    value="<?php echo htmlspecialchars($search_query); ?>"
                    style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; width: 300px; font-size: 14px;"
                >
                <button type="submit" style="cursor:pointer; border-radius:6px; font-weight:600;">Search Logs</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Student Name</th>
                        <th>Assigned Tutor</th>
                        <th>Performance Assessment</th>
                        <th>Grade Achieved</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports_list)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; font-style: italic; color: #475569; padding: 20px;">
                                No progress reports database metrics match the criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports_list as $row): ?>
                            <tr>
                                <td>#REP-<?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['student_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['tutor_name']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strpos(strtolower($row['performance_assessment']), 'excellent') !== false ? 'approved' : 'pending'; ?>">
                                        <?php echo htmlspecialchars($row['performance_assessment']); ?>
                                    </span>
                                </td>
                                <td>
                                    <code style="font-weight: bold; font-size: 14px;">
                                        <?php echo htmlspecialchars($row['grade_achieved'] ?: 'Pending/Null'); ?>
                                    </code>
                                </td>
                                <td>
                                    <a href="reports.php?view_id=<?php echo $row['id']; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="view-btn" style="text-decoration: none; display: inline-block;">View Analysis</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($selected_report): ?>
            <div class="table-container" style="margin-top:25px; border-left: 4px solid #3b82f6; padding-left:20px; background: #fff;">
                <h2>Detailed Progress Report Analysis</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;">
                    <p><strong>Report Reference ID:</strong> #REP-<?php echo $selected_report['id']; ?></p>
                    <p><strong>Booking Identifier Link:</strong> #BKG-<?php echo $selected_report['booking_id']; ?></p>
                    <p><strong>Target Student Profile:</strong> <?php echo htmlspecialchars($selected_report['student_name']); ?></p>
                    <p><strong>Reviewing Instructor/Tutor:</strong> <?php echo htmlspecialchars($selected_report['tutor_name']); ?></p>
                    <p><strong>Assessment Summary Benchmark:</strong> <?php echo htmlspecialchars($selected_report['performance_assessment']); ?></p>
                    <p><strong>Final Validated Grade:</strong> <?php echo htmlspecialchars($selected_report['grade_achieved'] ?: 'Not Specified'); ?></p>
                    <p><strong>Record Matrix Created At:</strong> <?php echo date('F d, Y - H:i:s', strtotime($selected_report['created_at'])); ?></p>
                </div>
                <br>
                <p><strong>Academic Observational Remarks & Commentary Logs:</strong></p>
                <blockquote style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius:6px; margin-top:5px; color:#334155; font-style:italic;">
                    "<?php echo htmlspecialchars($selected_report['academic_remarks'] ?: 'No structural remarks left for this tracking block.'); ?>"
                </blockquote>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>