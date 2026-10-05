<?php

namespace App\Http\Middleware;

use App\Services\ShiftService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireOpenShift
{
    protected ShiftService $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Super Admin can bypass POS lock unless explicitly acting as Cashier
            if ($user->hasRole('Super Admin')) {
                return $next($request);
            }

            $currentShift = $this->shiftService->getCurrentShift($user);

            if (! $currentShift) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'error' => 'An active cashier shift is required to perform POS operations.',
                        'redirect' => route('shifts.open'),
                    ], 403);
                }

                return redirect()->route('shifts.open')
                    ->with('warning', 'You must open a cashier shift with an initial float before accessing POS operations.');
            }
        }

        return $next($request);
    }
}
