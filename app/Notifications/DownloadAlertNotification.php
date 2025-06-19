<?php

namespace App\Notifications;

use App\Models\UploadObject;
use App\Models\UploadRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DownloadAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected UploadObject $uploadObject;
    protected UploadRequest $uploadRequest;
    protected ?string $downloaderInfo;

    /**
     * Create a new notification instance.
     */
    public function __construct(UploadObject $uploadObject, UploadRequest $uploadRequest, ?string $downloaderInfo = null)
    {
        $this->uploadObject = $uploadObject;
        $this->uploadRequest = $uploadRequest;
        $this->downloaderInfo = $downloaderInfo;
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
        
        $message = (new MailMessage)
            ->subject("File downloaded from your QuickDrop - {$appName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("A file has been downloaded from your QuickDrop.")
            ->line("**Download Details:**")
            ->line("- File Name: {$this->uploadObject->original_name}")
            ->line("- QuickDrop: {$this->uploadRequest->title}")
            ->line("- Downloaded At: " . now()->format('F j, Y at g:i A'));
            
        if ($this->downloaderInfo) {
            $message->line("- Downloaded By: {$this->downloaderInfo}");
        }
        
        return $message
            ->action('View QuickDrop', route('quickdrop.show', $this->uploadRequest->unique_request_id))
            ->line("Your QuickDrop expires on " . $this->uploadRequest->expires_at->format('F j, Y at g:i A'))
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
            'downloader_info' => $this->downloaderInfo,
        ];
    }
}
