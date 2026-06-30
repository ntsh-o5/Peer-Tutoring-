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
    'top_performers'    => 0
];

/**
 * Helper function to convert Letter Grades to numeric weights for comparison
 */
function getGradeWeight(string $grade): int {
    $grade = strtoupper(trim($grade));
    $weights = ['A' => 5, 'B' => 4, 'C' => 3, 'D' => 2, 'E' => 1, 'F' => 0];
    return $weights[$grade] ?? 0;
}

try {
    // =========================================================================
    // 2. DYNAMIC SYSTEM-WIDE METRICS AGGREGATION
    // =========================================================================
    
    // Total Records
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM academic_progress");
    $stats['total_records'] = (int)$totalStmt->fetchColumn();

    // Fetch grades raw for metrics calculation due to alphanumeric format
    $metricsStmt = $pdo->query("SELECT grade_before, grade_after FROM academic_progress");
    $allGrades = $metricsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allGrades as $g) {
        $w_before = getGradeWeight($g['grade_before']);
        $w_after  = getGradeWeight($g['grade_after']);
        
        // Improved: Weight After is greater than Weight Before
        if ($w_after > $w_before) {
            $stats['improved_learners']++;
        }
        // Top Performers: Maintained or achieved an 'A' grade after
        if (strtoupper(trim($g['grade_after'])) === 'A') {
            $stats['top_performers']++;
        }
    }

    // =========================================================================
    // 3. CAPTURE AND SANITIZE SEARCH VALUES (PostgreSQL Case-Insensitive)
    // =========================================================================
    if (isset($_GET['search']) && trim($_GET['search']) !== "") {
        $search_query = trim($_GET['search']);
        $stmt = $pdo->prepare("
            SELECT ap.id, ap.unit_code, ap.grade_before, ap.grade_after, ap.proof_before, ap.proof_after, u.name as learner_name
            FROM academic_progress ap
            JOIN users u ON ap.learner_id = u.id
            WHERE LOWER(u.name) LIKE LOWER(?) OR LOWER(ap.unit_code) LIKE LOWER(?)
            ORDER BY ap.id DESC
        ");
        $stmt->execute(["%$search_query%", "%$search_query%"]);
        $grade_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query("
            SELECT ap.id, ap.unit_code, ap.grade_before, ap.grade_after, ap.proof_before, ap.proof_after, u.name as learner_name
            FROM academic_progress ap
            JOIN users u ON ap.learner_id = u.id
            ORDER BY ap.id DESC
        ");
        $grade_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // 4. DETAILED INSPECTION ROW SELECTION OVERVIEW HANDLER
    // =========================================================================
    if (isset($_GET['view_id'])) {
        $selected_grade_id = (int)$_GET['view_id'];
        $viewStmt = $pdo->prepare("
            SELECT ap.id, ap.unit_code, ap.grade_before, ap.grade_after, ap.proof_before, ap.proof_after, u.name as learner_name
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
                <h3>Improved Standings</h3>
                <p><?php echo number_format($stats['improved_learners']); ?></p>
            </div>

            <div class="card">
                <h3>Current Top Performers (A)</h3>
                <p style="color: #10b981;"><?php echo number_format($stats['top_performers']); ?></p>
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
                        <th>Initial Grade</th>
                        <th>Current Grade</th>
                        <th>Performance Trend</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($grade_records)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; font-style: italic; color: #475569; padding: 20px;">
                                No academic progress grading profiles match the active search criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($grade_records as $row): ?>
                            <?php 
                                $w_b = getGradeWeight($row['grade_before']);
                                $w_a = getGradeWeight($row['grade_after']);
                            ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['learner_name']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['unit_code']); ?></strong></td>
                                <td><span style="color:#64748b;"><?php echo htmlspecialchars($row['grade_before']); ?></span></td>
                                <td><span style="font-weight:bold; color:#0f2038;"><?php echo htmlspecialchars($row['grade_after']); ?></span></td>
                                <td>
                                    <?php if ($w_a > $w_b): ?>
                                        <span class="status-badge approved">▲ Improved</span>
                                    <?php elseif ($w_a === $w_b): ?>
                                        <span class="status-badge pending">● Stable</span>
                                    <?php else: ?>
                                        <span class="status-badge rejected">▼ Regressed</span>
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
                <p><strong>Initial Benchmarked Grade:</strong> <?php echo htmlspecialchars($selected_record['grade_before']); ?></p>
                <p><strong>Post-Evaluation Grade:</strong> <?php echo htmlspecialchars($selected_record['grade_after']); ?></p>
                <br>
                
                <h3>Verification Documents</h3>
                <div style="display: flex; gap: 20px; margin-top: 10px;">
                    <div>
                        <p><strong>Initial Grade Proof Document:</strong></p>
                        <?php if (!empty($selected_record['proof_before'])): ?>
                            <a href="<?php echo htmlspecialchars($selected_record['proof_before']); ?>" target="_blank" class="view-btn" style="text-decoration:none; background-color:#475569;">Open Proof Before</a>
                        <?php else: ?>
                            <span style="font-style: italic; color: #94a3b8;">No initial proof uploaded.</span>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <p><strong>Updated Grade Proof Document:</strong></p>
                        <?php if (!empty($selected_record['proof_after'])): ?>
                            <a href="<?php echo htmlspecialchars($selected_record['proof_after']); ?>" target="_blank" class="view-btn" style="text-decoration:none; background-color:#475569;">Open Proof After</a>
                        <?php else: ?>
                            <span style="font-style: italic; color: #94a3b8;">No subsequent proof uploaded.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>