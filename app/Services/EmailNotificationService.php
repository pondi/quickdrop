<?php

namespace App\Services;

use App\Models\EmailNotificationLog;
use App\Models\QuickDropUser;
use App\Models\UploadRequest;
use App\Models\UploadObject;
use App\Notifications\DownloadAlertNotification;
use App\Notifications\ExpirationWarningNotification;
use App\Notifications\QuickDropSharedNotification;
use App\Notifications\UploadCompleteNotification;
use App\Notifications\AllUploadsCompleteNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class EmailNotificationService
{
    /**
     * Send upload complete notification
     */
    public function sendUploadCompleteNotification(UploadObject $uploadObject)
    {
        try {
            if (!$this->shouldSendNotification('enable_email_notifications')) {
                return;
            }

            $uploadRequest = $uploadObject->uploadRequest;
            $user = $uploadRequest->quickdropUser;

            if (!$user || !$user->notify_on_upload_complete) {
                return;
            }

            // Create log entry
            $log = EmailNotificationLog::create([
                'notification_type' => EmailNotificationLog::TYPE_UPLOAD_COMPLETE,
                'recipient_email' => $user->email,
                'quickdrop_user_id' => $user->id,
                'upload_request_id' => $uploadRequest->id,
                'upload_object_id' => $uploadObject->id,
                'subject' => 'File uploaded successfully',
                'data' => [
                    'file_name' => $uploadObject->original_name,
                    'file_size' => $uploadObject->file_size,
                    'quickdrop_title' => $uploadRequest->title,
                ],
                'status' => EmailNotificationLog::STATUS_PENDING,
            ]);

            // Send notification
            $user->notify(new UploadCompleteNotification($uploadObject, $uploadRequest));
            
            $log->markAsSent();
            
            Log::info('Upload complete notification sent', [
                'user_id' => $user->id,
                'upload_object_id' => $uploadObject->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send upload complete notification', [
                'error' => $e->getMessage(),
                'upload_object_id' => $uploadObject->id,
            ]);
            
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
        }
    }

    /**
     * Send all uploads complete notification
     */
    public function sendAllUploadsCompleteNotification(UploadRequest $uploadRequest)
    {
        try {
            if (!$this->shouldSendNotification('enable_email_notifications')) {
                return;
            }

            $user = $uploadRequest->quickdropUser;

            if (!$user || !$user->notify_on_upload_complete) {
                return;
            }

            $fileCount = $uploadRequest->uploadObjects()->count();
            $totalSize = $uploadRequest->uploadObjects()->sum('file_size');

            // Create log entry
            $log = EmailNotificationLog::create([
                'notification_type' => EmailNotificationLog::TYPE_ALL_UPLOADS_COMPLETE,
                'recipient_email' => $user->email,
                'quickdrop_user_id' => $user->id,
                'upload_request_id' => $uploadRequest->id,
                'subject' => 'All files uploaded successfully',
                'data' => [
                    'quickdrop_title' => $uploadRequest->title,
                    'file_count' => $fileCount,
                    'total_size' => $totalSize,
                    'share_url' => route('quickdrop.show', $uploadRequest->unique_request_id),
                ],
                'status' => EmailNotificationLog::STATUS_PENDING,
            ]);

            // Send notification
            $user->notify(new AllUploadsCompleteNotification($uploadRequest));
            
            $log->markAsSent();
            
            Log::info('All uploads complete notification sent', [
                'user_id' => $user->id,
                'upload_request_id' => $uploadRequest->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send all uploads complete notification', [
                'error' => $e->getMessage(),
                'upload_request_id' => $uploadRequest->id,
            ]);
            
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
        }
    }

    /**
     * Send download alert notification
     */
    public function sendDownloadAlertNotification(UploadObject $uploadObject, string $downloaderInfo = null)
    {
        try {
            if (!$this->shouldSendNotification('enable_email_notifications')) {
                return;
            }

            $uploadRequest = $uploadObject->uploadRequest;
            $user = $uploadRequest->quickdropUser;

            if (!$user || !$user->notify_on_download) {
                return;
            }

            // Create log entry
            $log = EmailNotificationLog::create([
                'notification_type' => EmailNotificationLog::TYPE_DOWNLOAD_ALERT,
                'recipient_email' => $user->email,
                'quickdrop_user_id' => $user->id,
                'upload_request_id' => $uploadRequest->id,
                'upload_object_id' => $uploadObject->id,
                'subject' => 'File downloaded from your QuickDrop',
                'data' => [
                    'file_name' => $uploadObject->original_name,
                    'quickdrop_title' => $uploadRequest->title,
                    'downloader_info' => $downloaderInfo,
                    'downloaded_at' => now()->toDateTimeString(),
                ],
                'status' => EmailNotificationLog::STATUS_PENDING,
            ]);

            // Send notification
            $user->notify(new DownloadAlertNotification($uploadObject, $uploadRequest, $downloaderInfo));
            
            $log->markAsSent();
            
            Log::info('Download alert notification sent', [
                'user_id' => $user->id,
                'upload_object_id' => $uploadObject->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send download alert notification', [
                'error' => $e->getMessage(),
                'upload_object_id' => $uploadObject->id,
            ]);
            
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
        }
    }

    /**
     * Send expiration warning notifications
     */
    public function sendExpirationWarnings()
    {
        try {
            if (!$this->shouldSendNotification('enable_email_notifications')) {
                return;
            }

            // Get upload requests expiring in the next 24 hours
            $expiringRequests = UploadRequest::where('status', 'active')
                ->where('expires_at', '>', now())
                ->where('expires_at', '<=', now()->addHours(24))
                ->whereDoesntHave('emailNotificationLogs', function ($query) {
                    $query->where('notification_type', EmailNotificationLog::TYPE_EXPIRATION_WARNING)
                          ->where('created_at', '>', now()->subHours(24));
                })
                ->with('quickdropUser')
                ->get();

            foreach ($expiringRequests as $uploadRequest) {
                $this->sendExpirationWarningNotification($uploadRequest);
            }

            Log::info('Expiration warnings sent', [
                'count' => $expiringRequests->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send expiration warnings', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send expiration warning for a single upload request
     */
    protected function sendExpirationWarningNotification(UploadRequest $uploadRequest)
    {
        try {
            $user = $uploadRequest->quickdropUser;

            if (!$user || !$user->notify_on_expiration_warning) {
                return;
            }

            $hoursRemaining = now()->diffInHours($uploadRequest->expires_at);

            // Create log entry
            $log = EmailNotificationLog::create([
                'notification_type' => EmailNotificationLog::TYPE_EXPIRATION_WARNING,
                'recipient_email' => $user->email,
                'quickdrop_user_id' => $user->id,
                'upload_request_id' => $uploadRequest->id,
                'subject' => "QuickDrop expiring in {$hoursRemaining} hours",
                'data' => [
                    'quickdrop_title' => $uploadRequest->title,
                    'expires_at' => $uploadRequest->expires_at->toDateTimeString(),
                    'hours_remaining' => $hoursRemaining,
                    'file_count' => $uploadRequest->uploadObjects()->count(),
                ],
                'status' => EmailNotificationLog::STATUS_PENDING,
            ]);

            // Send notification
            $user->notify(new ExpirationWarningNotification($uploadRequest));
            
            $log->markAsSent();
        } catch (\Exception $e) {
            Log::error('Failed to send expiration warning notification', [
                'error' => $e->getMessage(),
                'upload_request_id' => $uploadRequest->id,
            ]);
            
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
        }
    }

    /**
     * Send QuickDrop shared notification
     */
    public function sendQuickDropSharedNotification(string $recipientEmail, UploadRequest $uploadRequest, string $message = null)
    {
        try {
            if (!$this->shouldSendNotification('enable_email_notifications')) {
                return;
            }

            // Create log entry
            $log = EmailNotificationLog::create([
                'notification_type' => EmailNotificationLog::TYPE_QUICKDROP_SHARED,
                'recipient_email' => $recipientEmail,
                'upload_request_id' => $uploadRequest->id,
                'subject' => 'Someone shared a QuickDrop with you',
                'data' => [
                    'quickdrop_title' => $uploadRequest->title,
                    'sender_name' => $uploadRequest->quickdropUser?->name ?? 'Someone',
                    'message' => $message,
                    'share_url' => route('quickdrop.show', $uploadRequest->unique_request_id),
                    'expires_at' => $uploadRequest->expires_at->toDateTimeString(),
                ],
                'status' => EmailNotificationLog::STATUS_PENDING,
            ]);

            // Send notification to email address
            Notification::route('mail', $recipientEmail)
                ->notify(new QuickDropSharedNotification($uploadRequest, $message));
            
            $log->markAsSent();
            
            Log::info('QuickDrop shared notification sent', [
                'recipient' => $recipientEmail,
                'upload_request_id' => $uploadRequest->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send QuickDrop shared notification', [
                'error' => $e->getMessage(),
                'upload_request_id' => $uploadRequest->id,
            ]);
            
            if (isset($log)) {
                $log->markAsFailed($e->getMessage());
            }
        }
    }

    /**
     * Check if notifications should be sent based on system settings
     */
    protected function shouldSendNotification(string $settingKey): bool
    {
        return settings($settingKey, true);
    }

    /**
     * Get email notification statistics for a user
     */
    public function getUserNotificationStats(QuickDropUser $user)
    {
        return [
            'total_sent' => EmailNotificationLog::where('quickdrop_user_id', $user->id)
                ->where('status', EmailNotificationLog::STATUS_SENT)
                ->count(),
            'by_type' => EmailNotificationLog::where('quickdrop_user_id', $user->id)
                ->where('status', EmailNotificationLog::STATUS_SENT)
                ->selectRaw('notification_type, count(*) as count')
                ->groupBy('notification_type')
                ->pluck('count', 'notification_type'),
            'last_sent' => EmailNotificationLog::where('quickdrop_user_id', $user->id)
                ->where('status', EmailNotificationLog::STATUS_SENT)
                ->latest('sent_at')
                ->value('sent_at'),
        ];
    }
}