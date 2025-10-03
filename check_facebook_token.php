<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$page = \App\Models\FacebookPage::first();

if ($page) {
    echo "Page ID: " . $page->page_id . PHP_EOL;
    echo "Access Token: " . ($page->access_token ?? 'NULL') . PHP_EOL;
    echo "Token Length: " . strlen($page->access_token ?? '') . PHP_EOL;
    echo "User ID: " . $page->user_id . PHP_EOL;
} else {
    echo "No Facebook page found" . PHP_EOL;
}