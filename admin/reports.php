<?php
// =========================================================================
// 1. BACKEND ROUTING LOGIC & PROCESSORS
// =========================================================================

// Placeholder for Database Connection (e.g., include('config/db.php');)

// Capture and sanitize search query parameters
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = htmlspecialchars(trim($_GET['search']));
}

// Track report selection targets for preview rendering
$selected_report_id = null;
if (isset($_GET['view_report'])) {
    $selected_report_id = htmlspecialchars(trim($_GET['view_report']));
    
    // Future implementation lookup:
    // $stmt = $pdo->prepare("SELECT * FROM progress_reports WHERE report_id = ?");
    // $stmt->execute([$selected_report_id]);
    // $active_report = $stmt->fetch();
}

// Dashboard statistics array data setup
$report_stats = [
    'total_reports'   => 120,
    'this_week'       => 15,
    'pending_review'  => 7
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Reports - PeerTutor</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

    <?php include('../includes/sidebar.php'); ?>

    <div class="main-content">

        <header>
            <h1>Progress Reports</h1>
        </header>

        <section class="cards">
            <div class="card">
                <h3>Total Reports</h3>
                <p><?php echo number_format($report_stats['total_reports']); ?></p>
            </div>

            <div class="card">
                <h3>This Week</h3>
                <p><?php echo number_format($report_stats['this_week']); ?></p>
            </div>

            <div class="card">
                <h3>Pending Review</h3>
                <p><?php echo number_format($report_stats['pending_review']); ?></p>
            </div>
        </section>

        <div class="search-container">
            <form method="GET" action="reports.php">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search learner or tutor..." 
                    value="<?php echo $search_query; ?>"
                >
                <button type="submit">Search</button>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Learner</th>
                        <th>Tutor</th>
                        <th>Unit</th>
                        <th>Session Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                    <tr>
                        <td>R001</td>
                        <td>Brian Otieno</td>
                        <td>John Mwangi</td>
                        <td>Networking</td>
                        <td>15/06/2026</td>
                        <td><span class="status-badge approved">Submitted</span></td>
                        <td>
                            <a href="reports.php?view_report=R001<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View Report</a>
                        </td>
                    </tr>

                    <tr>
                        <td>R002</td>
                        <td>Mary Njeri</td>
                        <td>Grace Wanjiku</td>
                        <td>Database Systems</td>
                        <td>14/06/2026</td>
                        <td><span class="status-badge pending">Pending</span></td>
                        <td>
                            <a href="reports.php?view_report=R002<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="view-btn" style="text-decoration: none; display: inline-block; text-align: center;">View Report</a>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <?php if ($selected_report_id === 'R001'): ?>
            <div class="table-container" style="margin-top:20px;">
                <h2>Report Details: <?php echo $selected_report_id; ?></h2>

                <p><strong>Learner:</strong> Brian Otieno</p>
                <p><strong>Tutor:</strong> John Mwangi</p>
                <p><strong>Unit:</strong> Networking</p>
                <br>
                <p><strong>Topics Covered:</strong></p>
                <p>IPv4 Addressing, Subnetting, CIDR Notation, and ICMP.</p>
                <br>
                <p><strong>Performance Assessment:</strong></p>
                <p>Learner demonstrated a good understanding of subnet calculations but requires more structured practice in CIDR notation mappings.</p>
                <br>
                <p><strong>Recommendations:</strong></p>
                <p>Complete additional manual subnetting exercises and review network class ranges closely.</p>
                <br>
                <p><strong>Attendance Record:</strong></p>
                <p><span class="status-badge approved">Present</span></p>
            </div>
        <?php elseif ($selected_report_id): ?>
            <div class="table-container" style="margin-top:20px;">
                <h2>Report Details: <?php echo $selected_report_id; ?></h2>
                <p>Loading database record context details for document ID <strong><?php echo $selected_report_id; ?></strong>...</p>
                <p><em>(Database dynamic data values bind directly here)</em></p>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>