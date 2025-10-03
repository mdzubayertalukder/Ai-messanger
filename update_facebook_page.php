<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FacebookPage;

// Real Facebook information from your API test
$realAccessToken = 'EAFbmdgnkYXwBPtWQnrPT7SrodgFFLKjFN1L0aLzg6yf0AHtBJy8WUZBiZAEasmZCuP6hlyqrVIqVtdwxGDZB4QPndJoScwW8drHpmshwtrBikITW03XwmZC67eQIjRhCoXnGnPaRE9HjIt1XptFvmLyB2csVSZCKjrO4eQoPTPS7ZCEdEeAkjBZBwnPhjhHEgRQ75ZCA5';
$realPageId = '100645845759962';

// Update the existing Facebook page record
$facebookPage = FacebookPage::first();

if ($facebookPage) {
    $facebookPage->update([
        'page_id' => $realPageId,
        'access_token' => $realAccessToken,
        'page_name' => 'Real Facebook Page'
    ]);
    
    echo "✅ Updated Facebook page successfully!" . PHP_EOL;
    echo "Page ID: {$facebookPage->page_id}" . PHP_EOL;
    echo "Page Name: {$facebookPage->page_name}" . PHP_EOL;
    echo "Access Token: " . substr($facebookPage->access_token, 0, 20) . "..." . PHP_EOL;
    echo "User ID: {$facebookPage->user_id}" . PHP_EOL;
} else {
    echo "❌ No Facebook page found in database!" . PHP_EOL;
}