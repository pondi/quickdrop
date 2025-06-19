<?php

namespace App\Notifications;

use App\Models\UploadRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuickDropSharedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected UploadRequest $uploadRequest;
    protected ?string $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(UploadRequest $uploadRequest, ?string $message = null)
    {
        $this->uploadRequest = $uploadRequest;
        $this->message = $message;
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
        $senderName = $this->uploadRequest->quickdropUser?->name ?? 'Someone';
        $fileCount = $this->uploadRequest->uploadObjects()->count();
        $totalSize = $this->uploadRequest->uploadObjects()->sum('file_size');
        
        $mail = (new MailMessage)
            ->subject("{$senderName} shared files with you - {$appName}")
            ->greeting("Hello!")
            ->line("{$senderName} has shared a QuickDrop with you.");
            
        if ($this->message) {
            $mail->line("**Message from {$senderName}:**")
                 ->line($this->message);
        }
        
        return $mail
            ->line("**QuickDrop Details:**")
            ->line("- Title: {$this->uploadRequest->title}")
            ->line("- Files: {$fileCount}")
            ->line("- Total Size: " . $this->formatFileSize($totalSize))
            ->line("- Available Until: " . $this->uploadRequest->expires_at->format('F j, Y at g:i A'))
            ->action('Download Files', route('quickdrop.show', $this->uploadRequest->unique_request_id))
            ->line("These files will be available for download until the expiration date.")
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
            'sender_name' => $this->uploadRequest->quickdropUser?->name,
            'message' => $this->message,
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
