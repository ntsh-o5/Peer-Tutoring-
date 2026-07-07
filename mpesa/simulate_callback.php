<?php
// mpesa/simulate_callback.php — DEMO ONLY, remove before real deployment
require_once '../config/database.php';

$bookingId = (int)($_GET['booking_id'] ?? 0);
if (!$bookingId) die("Provide ?booking_id=X");

// Find the pending payment's checkout ID for this booking
$stmt = $pdo->prepare("SELECT transaction_ref FROM payments WHERE booking_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$bookingId]);
$checkoutId = $stmt->fetchColumn();

if (!$checkoutId) die("No pending payment found for booking $bookingId. Trigger the STK push first.");

$receipt = 'QHG' . strtoupper(substr(md5(rand()), 0, 7));

$fakePayload = [
    'Body' => [
        'stkCallback' => [
            'ResultCode' => 0,
            'CheckoutRequestID' => $checkoutId,
            'CallbackMetadata' => ['Item' => [
                0 => ['Name' => 'Amount', 'Value' => 500],
                1 => ['Name' => 'MpesaReceiptNumber', 'Value' => $receipt]
            ]]
        ]
    ]
];

// Feed it into the REAL callback.php logic via internal HTTP call
$ch = curl_init('http://localhost/peer-tutoring-main/mpesa/callback.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fakePayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_exec($ch);
curl_close($ch);

echo "<h2 style='color:green;font-family:sans-serif;text-align:center;margin-top:100px'>
✅ Payment Simulated!<br><br>
<span style='font-size:18px'>KES 500 received<br>
M-Pesa Receipt: $receipt<br><br>
Booking #$bookingId marked approved & paid.</span></h2>";