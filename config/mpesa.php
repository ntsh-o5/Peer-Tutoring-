<?php
define('CONSUMER_KEY',    'JHY56X7Bm0A5icQgOVpnqtyhURlLFlxJ4Atrek0fEtcHuzCL');
define('CONSUMER_SECRET', 'GXjjtrCfppmmWqYK27h90SSlXAlYHXe91kAOlmtGQ8IiyWpmAgYFA7upu3Z2rBOi');
define('SHORTCODE',       '174379');
define('PASSKEY',         'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('AMOUNT',          500);
define('CALLBACK_URL', 'https://devouring-grappling-backstab.ngrok-free.dev/peer-tutoring-main/mpesa/callback.php');

function getAccessToken() {
    $credentials = base64_encode(CONSUMER_KEY . ':' . CONSUMER_SECRET);
    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $response['access_token'];
}

function stkPush($phone, $bookingId) {
    $phone     = '254' . ltrim($phone, '0');
    $timestamp = date('YmdHis');
    $password  = base64_encode(SHORTCODE . PASSKEY . $timestamp);
    $token     = getAccessToken();

    $payload = [
        'BusinessShortCode' => SHORTCODE,
        'Password'          => $password,
        'Timestamp'         => $timestamp,
        'TransactionType'   => 'CustomerPayBillOnline',
        'Amount'            => AMOUNT,
        'PartyA'            => $phone,
        'PartyB'            => SHORTCODE,
        'PhoneNumber'       => $phone,
        'CallBackURL'       => CALLBACK_URL,
        'AccountReference'  => 'Booking-' . $bookingId,
        'TransactionDesc'   => 'Peer Tutoring Session'
    ];

    $ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $response;
}