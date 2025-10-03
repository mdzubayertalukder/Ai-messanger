<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Facebook Pages Configuration ===\n";

$pages = App\Models\FacebookPage::all();

if ($pages->count() == 0) {
    echo "No Facebook pages found in database.\n";
} else {
    echo "Found " . $pages->count() . " Facebook page(s):\n\n";
    
    foreach ($pages as $page) {
        echo "ID: " . $page->id . "\n";
        echo "Name: " . $page->name . "\n";
        echo "Page ID: " . $page->page_id . "\n";
        echo "Access Token: " . substr($page->access_token, 0, 20) . "...\n";
        echo "Created: " . $page->created_at . "\n";
        echo "---\n";
    }
}