<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordCodeNotification extends Notification
{
    use Queueable;

    protected $code;
    protected $expiresInMinutes;

    /**
     * Create a new notification instance.
     */
    public function __construct($code, $expiresInMinutes = 5)
    {
        $this->code = $code;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Código de Recuperación de Contraseña - Botica San Antonio')
            ->view('emails.reset-password-code', [
                'code' => $this->code,
                'expiresInMinutes' => $this->expiresInMinutes,
                'user' => $notifiable
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'code' => $this->code,
            'expires_in_minutes' => $this->expiresInMinutes
        ];
    }
}