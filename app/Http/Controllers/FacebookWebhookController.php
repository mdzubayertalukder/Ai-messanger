<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FacebookWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        if ($mode === 'subscribe' && $token && $token === config('services.facebook.verify_token')) {
            return response($challenge, Response::HTTP_OK);
        }

        return response('Forbidden', Response::HTTP_FORBIDDEN);
    }

    public function handle(Request $request)
    {
        // Verify signature
        $signatureHeader = $request->header('X-Hub-Signature-256');
        $appSecret = config('services.facebook.app_secret');
        if ($appSecret && $signatureHeader) {
            $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);
            if (!hash_equals($expected, $signatureHeader)) {
                return response('Invalid signature', Response::HTTP_FORBIDDEN);
            }
        }

        $payload = $request->all();
        Log::info('FB Webhook payload', ['payload' => $payload]);

        // Respond 200 immediately; processing will be added via jobs later
        return response()->json(['status' => 'ok']);
    }
}
