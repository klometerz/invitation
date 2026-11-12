<?php

namespace App\Providers;

use App\Support\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One context per request/command. Never a fresh instance per resolve,
        // otherwise middleware and models would see different tenants.
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // P3-T4 (K7): public endpoints are rate limited. Keys combine the
        // client IP with the guest code so one guest's flood neither locks out
        // other guests nor lets an attacker spread load across many codes.
        RateLimiter::for('rsvp', function (Request $request) {
            return Limit::perMinute(6)->by(
                'rsvp|'.$request->ip().'|'.(string) $request->route('code')
            );
        });

        RateLimiter::for('photobooth-capture', function (Request $request) {
            return Limit::perMinute(10)->by(
                'capture|'.$request->ip().'|'.(string) $request->input('guest_code')
            );
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by('login|'.$request->ip());
        });
    }
}
