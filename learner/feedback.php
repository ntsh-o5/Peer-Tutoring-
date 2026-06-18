<?php
// learner/feedback.php
session_start();
if (!isset($_SESSION['user_role']) || strtolower(trim($_SESSION['user_role'])) !== 'learner') {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../config/database.php';
$learner_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_id = (int)$_POST['booking_id'];
    $rating = (int)$_POST['rating'];
    $comments = trim($_POST['comments']);

    try {
        $pdo->beginTransaction();

        // 1. Trace the tutor reference tied to this booking
        $bStmt = $pdo->prepare("SELECT tutor_id FROM bookings WHERE id = ? AND learner_id = ?");
        $bStmt->execute([$booking_id, $learner_id]);
        $tutor_id = $bStmt->fetchColumn();

        if ($tutor_id) {
            // 2. Insert into the ratings table
            $rStmt = $pdo->prepare("INSERT INTO ratings (booking_id, learner_id, tutor_id, rating) VALUES (?, ?, ?, ?)");
            $rStmt->execute([$booking_id, $learner_id, $tutor_id, $rating]);

            // 3. Insert into the feedback comments table
            $fStmt = $pdo->prepare("INSERT INTO feedback (booking_id, learner_id, tutor_id, comments) VALUES (?, ?, ?, ?)");
            $fStmt->execute([$booking_id, $learner_id, $tutor_id, $comments]);

            // 4. Update booking record flag to audited status
            $uStmt = $pdo->prepare("UPDATE bookings SET status = 'reviewed' WHERE id = ?");
            $uStmt->execute([$booking_id]);

            $pdo->commit();
            
            echo "<div style='font-family:sans-serif; text-align:center; padding: 50px;'>";
            echo "<h2 style='color:#10b981;'>Evaluation Logged Successfully!</h2>";
            echo "<p>Thank you for optimizing our community guidelines standards.</p>";
            echo "<a href='dashboard.php' style='color:#0f2038; text-decoration:none; font-weight:bold;'>Return to Command Center Portal</a>";
            echo "</div>";
            exit;
        } else {
            throw new Exception("Unauthorized or non-existent booking configuration linkage.");
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Critical operational failure processing feedback: " . $e->getMessage());
        die("An unexpected execution exception occurred. Please try again later.");
    }
} else {
    header("Location: dashboard.php");
    exit;
}