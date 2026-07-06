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

        // 1. Trace the tutor reference tied to this booking (Updated column from 'id' to 'booking_id')
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

            // 4. Update booking record flag to audited status (Updated column from 'id' to 'booking_id')
            $uStmt = $pdo->prepare("UPDATE bookings SET status = 'reviewed' WHERE id = ?");
            $uStmt->execute([$booking_id]);

            $pdo->commit();
            
            // Clean styled execution output page
            echo "<div style='font-family:\"Segoe UI\", sans-serif; text-align:center; padding: 60px 20px; background:#f8fafc; min-height:100vh; box-sizing:border-box;'>";
            echo "<div style='max-width:500px; margin:40px auto; background:white; border:1px solid #e2e8f0; padding:40px; border-radius:8px; box-shadow: 0 4px 6px rgba(0,0,0,0.01);'>";
            echo "<div style='font-size: 48px; margin-bottom: 15px;'>★</div>";
            echo "<h2 style='color:#10b981; margin:0 0 10px 0;'>Evaluation Logged Successfully!</h2>";
            echo "<p style='color:#475569; font-size:14px; line-height:1.5; margin-bottom:25px;'>Thank you for your response. Your input optimizes peer tracking community quality across Strathmore campus networks.</p>";
            echo "<a href='dashboard.php' style='display:inline-block; background:#0f2038; color:white; text-decoration:none; padding:12px 24px; border-radius:4px; font-weight:600; font-size:14px;'>Return to Command Center</a>";
            echo "</div></div>";
            exit;
        } else {
            throw new Exception("Unauthorized or non-existent booking configuration linkage.");
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Critical operational failure processing feedback: " . $e->getMessage());
        die("An unexpected execution exception occurred. Please try again later.");
    }
} else {
    header("Location: dashboard.php");
    exit;
}