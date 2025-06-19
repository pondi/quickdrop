<?php

namespace App\Notifications;

use App\Models\UploadObject;
use App\Models\UploadRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UploadCompleteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected UploadObject $uploadObject;
    protected UploadRequest $uploadRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(UploadObject $uploadObject, UploadRequest $uploadRequest)
    {
        $this->uploadObject = $uploadObject;
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
        
        return (new MailMessage)
            ->subject("File uploaded successfully - {$appName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your file has been uploaded successfully to your QuickDrop.")
            ->line("**File Details:**")
            ->line("- File Name: {$this->uploadObject->original_name}")
            ->line("- File Size: " . $this->formatFileSize($this->uploadObject->file_size))
            ->line("- QuickDrop: {$this->uploadRequest->title}")
            ->line("- Expires: " . $this->uploadRequest->expires_at->format('F j, Y at g:i A'))
            ->action('View QuickDrop', route('quickdrop.show', $this->uploadRequest->unique_request_id))
            ->line("Share this QuickDrop with others by sending them the link above.")
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
            'upload_object_id' => $this->uploadObject->id,
            'upload_request_id' => $this->uploadRequest->id,
            'file_name' => $this->uploadObject->original_name,
            'file_size' => $this->uploadObject->file_size,
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
