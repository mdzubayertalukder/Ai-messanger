<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMessengerMessage;
use App\Models\Message;
use App\Models\FacebookPage;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

class TestMessengerFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:messenger-flow {--simulate-job : Simulate the job processing instead of dispatching}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the complete Messenger flow with image processing and product recommendations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Testing Complete Messenger Flow with Image Processing');
        $this->newLine();

        // Step 1: Check prerequisites
        $this->info('Step 1: Checking prerequisites...');
        
        $productCount = Product::count();
        $this->info("✅ Found {$productCount} products in database");
        
        $facebookPage = FacebookPage::first();
        if (!$facebookPage) {
            $this->warn('⚠️  No Facebook page found, creating a test page...');
            $facebookPage = FacebookPage::create([
                'user_id' => 1,
                'page_id' => 'test_page_123',
                'page_name' => 'Test Store Page',
                'page_access_token' => 'test_token_123',
                'is_active' => true
            ]);
        }
        $this->info("✅ Using Facebook page: {$facebookPage->page_name}");
        
        $this->newLine();

        // Step 2: Create test message with image attachment
        $this->info('Step 2: Creating test message with image attachment...');
        
        $messageData = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => $facebookPage->page_id,
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => 'test_user_123'],
                            'recipient' => ['id' => $facebookPage->page_id],
                            'timestamp' => time() * 1000,
                            'message' => [
                                'mid' => 'test_message_' . uniqid(),
                                'text' => 'Do you have this product in stock?',
                                'attachments' => [
                                    [
                                        'type' => 'image',
                                        'payload' => [
                                            'url' => 'https://via.placeholder.com/400x400/0066CC/FFFFFF?text=Blue+Shirt'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->info('✅ Test message created with image attachment');
        $this->line('Message preview:');
        $this->line("  - Text: {$messageData['entry'][0]['messaging'][0]['message']['text']}");
        $this->line("  - Image URL: {$messageData['entry'][0]['messaging'][0]['message']['attachments'][0]['payload']['url']}");
        
        $this->newLine();

        // Step 3: Store the message in database
        $this->info('Step 3: Storing message in database...');
        
        $messaging = $messageData['entry'][0]['messaging'][0];
        $message = Message::create([
            'user_id' => 1,
            'facebook_page_id' => $facebookPage->id,
            'sender_id' => $messaging['sender']['id'],
            'recipient_id' => $messaging['recipient']['id'],
            'direction' => 'incoming',
            'message_text' => $messaging['message']['text'],
            'has_attachments' => true,
            'raw_data' => $messaging,
            'attachments' => $messaging['message']['attachments'],
            'external_message_id' => $messaging['message']['mid'],
        ]);

        $this->info("✅ Message stored with ID: {$message->id}");
        
        $this->newLine();

        // Step 4: Process the message
        $this->info('Step 4: Processing message...');
        
        if ($this->option('simulate-job')) {
            $this->info('🔄 Simulating job processing...');
            
            // Simulate the job processing
            $this->line('  - Analyzing image for product features...');
            $this->line('  - Searching for matching products...');
            $this->line('  - Generating product recommendations...');
            
            // Update message with simulated results
            $message->update([
                'product_recommendations' => [
                    [
                        'name' => 'Blue Cotton Shirt',
                        'price' => '$29.99',
                        'in_stock' => true,
                        'product_url' => 'https://example.com/products/blue-shirt',
                        'image_url' => 'https://via.placeholder.com/300x300/0066CC/FFFFFF?text=Blue+Shirt',
                        'similarity_score' => 0.85
                    ],
                    [
                        'name' => 'Navy Blue Polo',
                        'price' => '$34.99',
                        'in_stock' => true,
                        'product_url' => 'https://example.com/products/navy-polo',
                        'image_url' => 'https://via.placeholder.com/300x300/000080/FFFFFF?text=Navy+Polo',
                        'similarity_score' => 0.72
                    ]
                ],
                'processed_at' => now(),
                'ai_response' => 'I found some similar products for you! Here are the best matches:',
                'responded_by_ai' => true,
                'ai_confidence' => 0.85
            ]);
            
            $this->info('✅ Message processing simulation completed');
            
        } else {
            $this->info('🔄 Dispatching ProcessMessengerMessage job...');
            
            ProcessMessengerMessage::dispatch(
                $messaging['sender']['id'],
                $facebookPage->page_id,
                $messaging,
                $facebookPage->page_access_token,
                $facebookPage->id,
                1 // user_id
            );
            
            $this->info('✅ Job dispatched successfully');
            $this->warn('⚠️  Make sure the queue worker is running to process the job');
            $this->line('   Run: php artisan queue:work');
        }
        
        $this->newLine();

        // Step 5: Display results
        $this->info('Step 5: Displaying results...');
        
        $message->refresh();
        
        if ($message->product_recommendations) {
            $this->info('✅ Product recommendations generated!');
            $recommendations = $message->product_recommendations;
            
            foreach ($recommendations as $index => $product) {
                $this->line("Product " . ($index + 1) . ":");
                $this->line("  - Name: {$product['name']}");
                $this->line("  - Price: {$product['price']}");
                $this->line("  - In Stock: " . ($product['in_stock'] ? 'Yes' : 'No'));
                $this->line("  - Similarity: " . round($product['similarity_score'] * 100, 1) . "%");
                $this->newLine();
            }
            
            if ($message->ai_response) {
                $this->info('AI Response:');
                $this->line($message->ai_response);
                $this->newLine();
            }
            
        } else {
            $this->warn('⚠️  No product recommendations found yet');
            if (!$this->option('simulate-job')) {
                $this->line('The job might still be processing. Check the queue worker logs.');
            }
        }

        // Step 6: Test Facebook response structure
        $this->info('Step 6: Testing Facebook response structure...');
        
        if ($message->product_recommendations) {
            $this->info('✅ Facebook product carousel structure would be:');
            $this->line('{');
            $this->line('  "recipient": {"id": "test_user_123"},');
            $this->line('  "message": {');
            $this->line('    "attachment": {');
            $this->line('      "type": "template",');
            $this->line('      "payload": {');
            $this->line('        "template_type": "generic",');
            $this->line('        "elements": [');
            
            foreach ($message->product_recommendations as $product) {
                $this->line('          {');
                $this->line('            "title": "' . $product['name'] . '",');
                $this->line('            "subtitle": "' . $product['price'] . ' - ' . ($product['in_stock'] ? 'In Stock' : 'Out of Stock') . '",');
                $this->line('            "image_url": "' . $product['image_url'] . '",');
                $this->line('            "buttons": [');
                $this->line('              {');
                $this->line('                "type": "web_url",');
                $this->line('                "url": "' . $product['product_url'] . '",');
                $this->line('                "title": "Buy Now"');
                $this->line('              }');
                $this->line('            ]');
                $this->line('          },');
            }
            
            $this->line('        ]');
            $this->line('      }');
            $this->line('    }');
            $this->line('  }');
            $this->line('}');
        }

        $this->newLine();
        $this->info('🎉 Complete Messenger flow test completed!');
        $this->info("📊 Message ID: {$message->id} - Check the messages table for full details");
        
        return 0;
    }
}