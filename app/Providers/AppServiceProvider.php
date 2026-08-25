<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\JWTGuard;
use App\Models\Meeting;
use App\Policies\MeetingPolicy;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

        public function boot(): void
{
    // ສອນໃຫ້ Laravel ຮູ້ຈັກ jwt guard ໂດຍກົງ
    Auth::extend('jwt', function ($app, $name, array $config) {
        $guard = new JWTGuard(
            $app['tymon.jwt'],
            Auth::createUserProvider($config['provider']),
            $app['request']
        );

        // ຕັ້ງຄ່າ Request ໃຫ້ກັບ JWT Guard
        app()->refresh('request', $guard, 'setRequest');

        return $guard;
    });

    Gate::policy(Meeting::class, MeetingPolicy::class);
    Gate::policy(QrCode::class, QrCodePolicy::class); // <-- ເພີ່ມອັນນີ້

}
}
