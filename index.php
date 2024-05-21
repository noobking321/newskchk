<?php

$botToken = '6485104551:AAEbuKXce1cMXcq33_Qrhi64NrEqhTlZFoY';
$apiUrl = "https://api.telegram.org/bot$botToken/";
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $messageId = $message['message_id'];
    $text = $message['text'];

    if (strpos($text, '/skBal ') === 0) {
        $secretKey = trim(str_replace('/skBal ', '', $text));
        $balanceDetails = getStripeBalanceDetails($secretKey, $secretKey);
        sendMessage($chatId, $balanceDetails, $messageId);
    }
}

function getStripeBalanceDetails($secretKey, $originalKey) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/balance');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $secretKey . ':');
    $balanceResponse = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/account');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $secretKey . ':');
    $accountResponse = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (isset($balanceResponse['error']) || isset($accountResponse['error'])) {
        return '❌ *Invalid secret key or error fetching data.*';
    }

    $availableBalance = 0;
    foreach ($balanceResponse['available'] as $available) {
        $availableBalance += $available['amount'];
    }
    $availableBalance = $availableBalance / 100;
    $currency = strtoupper($balanceResponse['available'][0]['currency']);

    $pendingBalance = 0;
    foreach ($balanceResponse['pending'] as $pending) {
        $pendingBalance += $pending['amount'];
    }
    $pendingBalance = $pendingBalance / 100;

    $country = strtoupper($accountResponse['country']);
    $isLive = $accountResponse['livemode'] ? 'Live' : 'Test';

    return "💳 *Stripe API key information:*\n"
        . "_Key:_ `$originalKey`\n"
        . "_Balance:_ *$availableBalance $currency* (available), *$pendingBalance $currency* (pending)\n"
        . "_Mode:_ *$isLive*\n"
        . "_Country:_ *$country*";
}

function sendMessage($chatId, $message, $replyToMessageId) {
    global $apiUrl;
    $url = $apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode($message) . "&reply_to_message_id=$replyToMessageId&parse_mode=Markdown";
    file_get_contents($url);
}

function setWebhook($url) {
    global $apiUrl;
    $webhookUrl = $apiUrl . "setWebhook?url=" . urlencode($url);
    file_get_contents($webhookUrl);
}

// Uncomment to set the webhook URL
setWebhook('https://e47ff98b-3def-45f2-bca4-48a2e0381733-00-c3dyvth7o6el.spock.replit.dev/');
