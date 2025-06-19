import { ref, watch, computed, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import { calculateFileHash } from '@/Services/FileHashService';
import { encryptFile } from '@/Services/EncryptionService';
import { useUploadProgress } from './useUploadProgress';

export function useFileUploadEnhanced(uploadRequest, encryptionKeyGetter, initialFiles = []) {
    const files = ref([...initialFiles]);
    const pendingFiles = ref([]);
    const isUploading = ref(false);
    
    // Use the enhanced upload progress tracking
    const {
        uploadingFiles,
        addFile,
        updateProgress,
        markCompleted,
        markError,
        removeFile,
        resetFile,
        clearCompleted,
        hasActiveUploads,
        totalProgress
    } = useUploadProgress();

    const canUpload = computed(() => {
        if (!uploadRequest.value) return false;
        if (uploadRequest.value.is_expired) return false;
        if (!uploadRequest.value.is_active) return false;
        return true;
    });

    const handleMultipleFiles = async (newFiles) => {
        // Add files to pending queue and create progress entries
        for (const fileData of newFiles) {
            fileData.progressId = addFile(fileData.file);
            pendingFiles.value.push(fileData);
        }
    };

    const uploadPendingFiles = async () => {
        if (!canUpload.value || isUploading.value) return;
        if (pendingFiles.value.length === 0) return;

        isUploading.value = true;
        let rateLimitDelay = 0;

        try {
            for (const fileData of pendingFiles.value) {
                if (fileData.uploading) continue;
                if (fileData.isHashDuplicate) {
                    console.log(`Skipping duplicate file: ${fileData.name}`);
                    removeFile(fileData.progressId);
                    continue;
                }

                if (rateLimitDelay > 0) {
                    await new Promise(resolve => setTimeout(resolve, rateLimitDelay));
                }

                fileData.uploading = true;
                try {
                    await uploadFile(fileData);
                    rateLimitDelay = 0;
                } catch (error) {
                    if (error.response?.status === 429) {
                        rateLimitDelay = (parseInt(error.response.headers['retry-after']) || 1) * 1000;
                        fileData.error = 'Upload throttled, retrying...';
                        fileData.uploading = false;
                        pendingFiles.value = [...pendingFiles.value];
                        continue;
                    }
                    throw error;
                }
            }

            pendingFiles.value = [];
        } catch (error) {
            console.error('Upload error:', error);
        } finally {
            isUploading.value = false;
        }
    };

    const uploadFile = async (fileData) => {
        let fileToUpload = fileData.file;

        // Encrypt file if encryption is enabled
        if (uploadRequest.value.has_encryption && encryptionKeyGetter()) {
            try {
                const publicKey = encryptionKeyGetter();
                const result = await encryptFile(fileData.file, publicKey);
                fileToUpload = new File([result.encryptedData], fileData.name, {
                    type: 'application/octet-stream'
                });
                fileData.encryptedMetadata = result.metadata;
            } catch (error) {
                console.error('Encryption failed:', error);
                markError(fileData.progressId, 'Encryption failed');
                throw error;
            }
        }

        // Perform the upload with progress tracking
        await new Promise((resolve, reject) => {
            router.post(
                route('quickdrop.upload', uploadRequest.value.id),
                {
                    file: fileToUpload,
                    hash: fileData.hash,
                    name: fileData.name,
                    size: fileData.size,
                    type: fileData.type,
                    display_name: fileData.display_name,
                    version_suffix: fileData.version_suffix,
                    is_version_of: fileData.is_version_of,
                    encrypted_metadata: fileData.encryptedMetadata
                },
                {
                    forceFormData: true,
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: (page) => {
                        const newFile = page.props.file;
                        if (newFile) {
                            files.value.push(newFile);
                            markCompleted(fileData.progressId);
                            
                            // Auto-remove completed files after 3 seconds
                            setTimeout(() => {
                                removeFile(fileData.progressId);
                            }, 3000);
                        }
                        resolve();
                    },
                    onError: (errors) => {
                        const errorMessage = errors.file || 'Upload failed';
                        markError(fileData.progressId, errorMessage);
                        fileData.error = errorMessage;
                        reject(new Error(errorMessage));
                    },
                    onProgress: (progress) => {
                        // Update progress with loaded and total bytes
                        if (progress.loaded !== undefined && progress.total !== undefined) {
                            updateProgress(fileData.progressId, progress.loaded, progress.total);
                        } else if (progress.percentage !== undefined) {
                            // Fallback to percentage if bytes not available
                            const total = fileData.size;
                            const loaded = Math.round((progress.percentage / 100) * total);
                            updateProgress(fileData.progressId, loaded, total);
                        }
                        
                        fileData.progress = progress.percentage || 0;
                    }
                }
            );
        });
    };

    const cancelUpload = (progressId) => {
        // Find and remove the file from pending queue
        const index = pendingFiles.value.findIndex(f => f.progressId === progressId);
        if (index !== -1) {
            pendingFiles.value.splice(index, 1);
        }
        removeFile(progressId);
    };

    const retryUpload = async (progressId) => {
        // Find the file in the upload queue
        const uploadFile = uploadingFiles.value.find(f => f.id === progressId);
        if (!uploadFile) return;

        // Reset the file status
        resetFile(progressId);

        // Create new file data for retry
        const fileData = {
            file: uploadFile.file,
            name: uploadFile.name,
            size: uploadFile.size,
            type: uploadFile.type,
            progressId: progressId,
            uploading: false
        };

        // Calculate hash for the file
        try {
            fileData.hash = await calculateFileHash(uploadFile.file);
        } catch (error) {
            console.error('Failed to calculate file hash:', error);
        }

        // Add back to pending queue and upload
        pendingFiles.value.push(fileData);
        uploadPendingFiles();
    };

    // Auto-clear completed files after delay
    let clearCompletedTimer;
    watch(uploadingFiles, (files) => {
        if (files.some(f => f.status === 'completed')) {
            clearTimeout(clearCompletedTimer);
            clearCompletedTimer = setTimeout(() => {
                clearCompleted();
            }, 5000);
        }
    }, { deep: true });

    // Cleanup
    onBeforeUnmount(() => {
        clearTimeout(clearCompletedTimer);
    });

    return {
        files,
        pendingFiles,
        isUploading,
        canUpload,
        handleMultipleFiles,
        uploadPendingFiles,
        // Enhanced progress tracking
        uploadingFiles,
        hasActiveUploads,
        totalProgress,
        cancelUpload,
        retryUpload
    };
}