<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Restablece tu contraseña de SuHomes')
            ->view('emails.reset-password', [
                'resetUrl' => $resetUrl,
                'expirationMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                'logoUrl' => asset('assets/img/suhomes-app-logo.png'),
                'appName' => 'SuHomes',
            ]);
    }
}
