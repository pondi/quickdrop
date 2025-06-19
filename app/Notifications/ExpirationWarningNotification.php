<?php

namespace App\Notifications;

use App\Models\UploadRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpirationWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected UploadRequest $uploadRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(UploadRequest $uploadRequest)
    {
        $this->uploadRequest = $uploadRequest;
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
        $appName = settings('app_name', 'QuickDrop');
        $hoursRemaining = now()->diffInHours($this->uploadRequest->expires_at);
        $fileCount = $this->uploadRequest->uploadObjects()->count();
        
        return (new MailMessage)
            ->subject("QuickDrop expiring soon - {$appName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your QuickDrop is expiring in {$hoursRemaining} hours.")
            ->line("**QuickDrop Details:**")
            ->line("- Title: {$this->uploadRequest->title}")
            ->line("- Files: {$fileCount}")
            ->line("- Expires: " . $this->uploadRequest->expires_at->format('F j, Y at g:i A'))
            ->line("After expiration, the files will no longer be available for download.")
            ->action('View QuickDrop', route('quickdrop.show', $this->uploadRequest->unique_request_id))
            ->line("If you need to keep these files available, please create a new QuickDrop before this one expires.")
            ->salutation("Best regards,\nThe {$appName} Team");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'upload_request_id' => $this->uploadRequest->id,
            'title' => $this->uploadRequest->title,
            'expires_at' => $this->uploadRequest->expires_at->toDateTimeString(),
            'hours_remaining' => now()->diffInHours($this->uploadRequest->expires_at),
        ];
    }
}
