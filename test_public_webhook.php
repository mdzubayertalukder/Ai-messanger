<?php

$ngrokUrl = "https://a9513f28c678.ngrok-free.app";
$webhookUrl = $ngrokUrl . "/webhook/facebook";

echo "=== Testing Public Webhook URL ===\n";
echo "Ngrok URL: {$ngrokUrl}\n";
echo "Webhook URL: {$webhookUrl}\n\n";

// Test webhook verification (GET request)
echo "1. Testing webhook verification...\n";
$verifyUrl = $webhookUrl . "?hub.mode=subscribe&hub.challenge=test_challenge&hub.verify_token=your_verify_token";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $verifyUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: {$httpCode}\n";
echo "   Response: {$response}\n\n";

// Test webhook message (POST request)
echo "2. Testing webhook message...\n";
$testMessage = [
    'object' => 'page',
    'entry' => [
        [
            'id' => '100645845759962',
            'time' => time() * 1000,
            'messaging' => [
                [
                    'sender' => ['id' => 'real_user_test_' . time()],
                    'recipient' => ['id' => '100645845759962'],
                    'timestamp' => time() * 1000,
                    'message' => [
                        'mid' => 'test_message_' . time(),
                        'text' => 'Hello from public webhook test!'
                    ]
                ]
            ]
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testMessage));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: facebookplatform/1.0 (+http://developers.facebook.com)'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: {$httpCode}\n";
echo "   Response: {$response}\n";

if ($httpCode == 200) {
    echo "   ✅ Webhook is working!\n";
} else {
    echo "   ❌ Webhook test failed\n";
}

echo "\n=== Test Complete ===\n";
echo "Your webhook is ready for Facebook configuration!\n";
echo "Use this URL in Facebook Developer Console: {$webhookUrl}\n";