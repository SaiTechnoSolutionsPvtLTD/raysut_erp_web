<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * Usage in routes: middleware('permission:System, Security & Setup,VIEW')
     */
    public function handle(Request $request, Closure $next, ...$params): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Action code is the last parameter (e.g. 'VIEW')
        $actionCode = trim(array_pop($params));

        // Recombine remaining params in case comma split occurred
        $fullModuleString = trim(implode(',', $params));

        // Support multiple fallback modules separated by '|'
        $moduleNames = array_map('trim', explode('|', $fullModuleString));

        $hasAccess = false;
        foreach ($moduleNames as $moduleName) {
            if ($user->hasPermission($moduleName, $actionCode)) {
                $hasAccess = true;
                break;
            }
        }

        if (! $hasAccess) {
            if ($request->expectsJson()) {
                abort(403, 'Unauthorized action for module ' . $fullModuleString);
            }

            return redirect()->route('dashboard')->with('warning', 'Access Restricted: You do not have permission to view the requested module.');
        }

        return $next($request);
    }
}
