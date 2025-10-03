<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\FacebookPage;

// Check existing Facebook pages
$pages = FacebookPage::all();

echo "Total Facebook pages: " . $pages->count() . "\n";

foreach ($pages as $page) {
    echo "ID: " . $page->id . ", Page ID: " . $page->page_id . ", Name: " . $page->page_name . ", User ID: " . $page->user_id . "\n";
}