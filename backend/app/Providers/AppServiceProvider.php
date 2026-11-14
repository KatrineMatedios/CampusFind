<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            $frontend = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');

            return $frontend.'/reset-password?token='.urlencode($token)
                .'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
