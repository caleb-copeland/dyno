<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
        // Dyno copy for the framework's reset notification (styling: the dyno mail theme).
        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Reset your Dyno password')
                ->greeting('Reset your password')
                ->line('Someone asked to reset the password for your Dyno account. Tap below to choose a new one.')
                ->action('Choose a new password', $url)
                ->line("This link expires in {$minutes} minutes.")
                ->line("Didn't ask for this? Ignore this email — your password won't change.")
                ->salutation('— Dyno');
        });
    }
}
