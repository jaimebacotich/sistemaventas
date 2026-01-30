<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $transactionId = $request->header('X-Transaction-ID', (string) Str::uuid());

        $context = [
            'env' => config('app.env'),
            'release' => $this->getReleaseId(),
            'transaction_id' => $transactionId,
        ];

        if (Auth::check()) {
            $context['user_id'] = Auth::id();
        }

        Log::withContext($context);

        $response = $next($request);
        $response->headers->set('X-Transaction-ID', $transactionId);

        return $response;
    }

    /**
     * Obtiene el ID del release desde el archivo RELEASE_ID si existe.
     */
    protected function getReleaseId(): string
    {
        $path = base_path('RELEASE_ID');

        if (file_exists($path)) {
            $content = file_get_contents($path);

            // Tomamos la primera línea (timestamp) o el contenido completo limpio
            return trim(explode("\n", (string) $content)[0]);
        }

        return config('app.release', 'local');
    }
}
