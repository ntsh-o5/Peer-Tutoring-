<?php
// Get the current page filename to dynamically set active classes
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <h2>PeerTutor</h2>
    <ul>
        <li><a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
        <li><a href="tutors.php" class="<?php echo $current_page == 'tutors.php' ? 'active' : ''; ?>">Tutor Verification</a></li>
        <li><a href="bookings.php" class="<?php echo $current_page == 'bookings.php' ? 'active' : ''; ?>">Bookings</a></li>
        <li><a href="learner_payments.php" class="<?php echo $current_page == 'learner_payments.php' ? 'active' : ''; ?>">Learner Payments</a></li>
        <li><a href="tutor_payments.php" class="<?php echo $current_page == 'tutor_payments.php' ? 'active' : ''; ?>">Tutor Payments</a></li>
        <li><a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">Progress Reports</a></li>
        <li><a href="grades.php" class="<?php echo $current_page == 'grades.php' ? 'active' : ''; ?>">Grades</a></li>
        <!-- <li><a href="feedback.php" class="<?php echo $current_page == 'feedback.php' ? 'active' : ''; ?>">Feedback</a></li> -->
        <li><a href="ratings.php" class="<?php echo $current_page == 'ratings.php' ? 'active' : ''; ?>">Ratings & Feedback</a></li>
        <li><a href="users.php" class="<?php echo $current_page == 'users.php' ? 'active' : ''; ?>">Users</a></li>
        <li><a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">Profile</a></li>
        <!-- <li><a href="../logout.php">Logout</a></li> -->
    </ul>
</div>