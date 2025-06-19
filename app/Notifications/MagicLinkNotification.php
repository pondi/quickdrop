<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $url;
    protected $purpose;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $url, string $purpose = 'login')
    {
        $this->url = $url;
        $this->purpose = $purpose;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        if ($this->purpose === 'register') {
            return (new MailMessage)
                ->subject('Complete your QuickDrop registration')
                ->greeting('Welcome to QuickDrop!')
                ->line('Click the button below to complete your registration and access your account.')
                ->action('Complete Registration', $this->url)
                ->line('This link will expire in 15 minutes.')
                ->line('If you did not create an account, no further action is required.');
        }

        if ($this->purpose === 'verify') {
            return (new MailMessage)
                ->subject('Verify your email address')
                ->greeting('Hello!')
                ->line('Please click the button below to verify your email address.')
                ->action('Verify Email', $this->url)
                ->line('This link will expire in 15 minutes.')
                ->line('If you did not create an account, no further action is required.');
        }

        // Default login email
        return (new MailMessage)
            ->subject('Your QuickDrop login link')
            ->greeting('Hello!')
            ->line('Click the button below to log in to your QuickDrop account.')
            ->action('Log In', $this->url)
            ->line('This link will expire in 15 minutes.')
            ->line('If you did not request a login link, please ignore this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'purpose' => $this->purpose,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
