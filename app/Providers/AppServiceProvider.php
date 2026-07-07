<?php

namespace App\Providers;

use App\Mail\Transport\BrevoApiTransport;
use Illuminate\Support\Facades\Mail;
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
        // Send via Brevo's HTTP API (port 443) — avoids blocked outbound SMTP.
        Mail::extend('brevo', fn () => new BrevoApiTransport((string) config('services.brevo.key')));

        // Route replies to a real inbox even when sending from a no-reply address.
        if ($replyTo = config('mail.reply_to.address')) {
            Mail::alwaysReplyTo($replyTo, config('mail.reply_to.name'));
        }
    }
}
