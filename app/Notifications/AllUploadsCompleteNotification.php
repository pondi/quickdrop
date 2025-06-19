<?php

namespace App\Notifications;

use App\Models\UploadRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AllUploadsCompleteNotification extends Notification implements ShouldQueue
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
        $fileCount = $this->uploadRequest->uploadObjects()->count();
        $totalSize = $this->uploadRequest->uploadObjects()->sum('file_size');
        
        return (new MailMessage)
            ->subject("All files uploaded successfully - {$appName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("All files have been uploaded successfully to your QuickDrop!")
            ->line("**QuickDrop Summary:**")
            ->line("- Title: {$this->uploadRequest->title}")
            ->line("- Total Files: {$fileCount}")
            ->line("- Total Size: " . $this->formatFileSize($totalSize))
            ->line("- Expires: " . $this->uploadRequest->expires_at->format('F j, Y at g:i A'))
            ->action('View QuickDrop', route('quickdrop.show', $this->uploadRequest->unique_request_id))
            ->line("You can now share this QuickDrop with others by sending them the link above.")
            ->line("Anyone with the link will be able to download the files until the expiration date.")
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
            'file_count' => $this->uploadRequest->uploadObjects()->count(),
            'total_size' => $this->uploadRequest->uploadObjects()->sum('file_size'),
        ];
    }

    /**
     * Format file size to human readable format
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
