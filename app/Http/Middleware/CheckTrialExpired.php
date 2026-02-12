<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class CheckTrialExpired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('trial.popup.enabled')) {
            return $next($request);
        }

        $endDateString = config('trial.popup.end_date');

        if (!$endDateString) {
            return $next($request);
        }

        $endDate = Carbon::createFromFormat('d.m.Y', $endDateString)->endOfDay();

        if (now()->greaterThan($endDate)) {
            if ($request->routeIs('filament.admin.pages.trial-expired')) {
                return $next($request);
            }

            if ($request->routeIs('filament.admin.auth.login')) {
                return $next($request);
            }

            if ($request->routeIs('filament.admin.auth.logout') || $request->routeIs('logout')) {
                return $next($request);
            }

            return redirect()->route('filament.admin.pages.trial-expired');
        }

        return $next($request);
    }
}
