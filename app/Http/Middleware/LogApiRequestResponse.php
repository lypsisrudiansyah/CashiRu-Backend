<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogApiRequestResponse
{
    public function handle(Request $request, Closure $next)
    {
        // Log the incoming request
        Log::info('API Request', [
            'url'     => $request->fullUrl(),
            'method'  => $request->method(),
            'headers' => $request->headers->all(),
            'body'    => $request->all(),
        ]);

        $response = $next($request);

        // Log the outgoing response
        Log::info('API Response', [
            'status'   => $response->status(),
            'content'  => method_exists($response, 'getContent') ? json_decode($response->getContent(), true) : $response,
        ]);

        return $response;
    }
}
