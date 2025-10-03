<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogWebhookRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log all webhook requests for debugging
        Log::info('Webhook Request Received', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $request->headers->all(),
            'query_params' => $request->query(),
            'body' => $request->getContent(),
            'timestamp' => now()->toISOString(),
        ]);

        $response = $next($request);

        // Log the response as well
        Log::info('Webhook Response Sent', [
            'status_code' => $response->getStatusCode(),
            'content' => $response->getContent(),
            'headers' => $response->headers->all(),
        ]);

        return $response;
    }
}
