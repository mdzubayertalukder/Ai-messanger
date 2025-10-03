<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FACEBOOK PAGES ACCESS TOKENS ===\n\n";

$pages = \App\Models\FacebookPage::all(['id', 'page_name', 'page_id', 'access_token']);

foreach ($pages as $page) {
    echo "ID: {$page->id}\n";
    echo "Name: {$page->page_name}\n";
    echo "Page ID: {$page->page_id}\n";
    echo "Token: " . (empty($page->access_token) ? 'EMPTY' : 'SET (' . strlen($page->access_token) . ' chars)') . "\n";
    if (!empty($page->access_token)) {
        echo "Token Preview: " . substr($page->access_token, 0, 20) . "...\n";
    }
    echo "---\n";
}