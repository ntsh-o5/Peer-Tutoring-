<?php
require_once '../config/database.php';

$data   = json_decode(file_get_contents('php://input'), true);
$result = $data['Body']['stkCallback'];

$resultCode = $result['ResultCode'];
$checkoutId = $result['CheckoutRequestID'];

if ($resultCode == 0) {
    $receipt = $result['CallbackMetadata']['Item'][1]['Value'];

    // Update payment record to paid
    $stmt = $pdo->prepare("UPDATE payments SET status='completed', transaction_ref=?, paid_at=NOW() 
                           WHERE transaction_ref=?");
    $stmt->execute([$receipt, $checkoutId]);

    // Also update booking status to confirmed
    $stmt2 = $pdo->prepare("UPDATE bookings SET status='approved' 
                            WHERE id=(SELECT booking_id FROM payments WHERE transaction_ref=?)");
    $stmt2->execute([$receipt]);
} else {
    // Payment failed — mark as failed
    $stmt = $pdo->prepare("UPDATE payments SET status='failed' WHERE transaction_ref=?");
    $stmt->execute([$checkoutId]);
}

http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);