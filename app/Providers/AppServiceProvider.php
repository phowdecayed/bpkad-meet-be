<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Database\Eloquent\Model::shouldBeStrict(! app()->isProduction());

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            $verifyUrl = $frontendUrl.'/verify-email?url='.$url;

            return (new MailMessage)
                ->subject('Verify Email Address')
                ->line('Click the button below to verify your email address.')
                ->action('Verify Email Address', $verifyUrl);
        });
    }
}
