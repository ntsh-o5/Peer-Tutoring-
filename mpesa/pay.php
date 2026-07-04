<?php
require_once '../config/database.php';
require_once '../config/mpesa.php';
header('Content-Type: application/json');

$phone     = $_POST['phone'];
$bookingId = $_POST['booking_id'];
$userId    = $_POST['user_id'];

$result = stkPush($phone, $bookingId);

if (isset($result['ResponseCode']) && $result['ResponseCode'] == '0') {
    $checkoutId = $result['CheckoutRequestID'];

    // Save checkout ID to payments table so callback can match it
    $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, transaction_ref, status, created_at) 
                           VALUES (?, 350, ?, 'pending', NOW())");
    $stmt->execute([$userId, $checkoutId]);

    echo json_encode(['success' => true, 'message' => 'Check your phone for the M-Pesa PIN prompt.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not send payment request. Try again.']);
}