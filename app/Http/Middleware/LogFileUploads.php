<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogFileUploads
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log file upload requests
        if ($request->is('api/files') && $request->isMethod('POST')) {
            $logData = [
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
                'file_name' => $request->input('name'),
                'is_public' => $request->boolean('is_public'),
                'original_filename' => $request->file('file')?->getClientOriginalName(),
                'file_size' => $request->file('file')?->getSize(),
                'mime_type' => $request->file('file')?->getMimeType(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString(),
            ];

            Log::info('File upload request', $logData);
        }

        return $next($request);
    }
}
