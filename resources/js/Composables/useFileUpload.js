import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { verifyEncryptionKey, encryptFile } from '@/Services/EncryptionService';

export function useFileUpload(uploadRequest, encryptionKeyGetter, initialFiles = []) {
    const isUploading = ref(false);
    const files = ref(initialFiles);
    const uploadErrors = ref([]);
    const pendingFiles = ref([]);

    const canUpload = computed(() => {
        if (!uploadRequest.value) return false;
        return uploadRequest.value.is_active && 
               (!uploadRequest.value.max_files || files.value.length < uploadRequest.value.max_files);
    });

    const validateFile = (file) => {
        if (!canUpload.value) {
            throw new Error('Upload not allowed at this time');
        }

        if (uploadRequest.value.max_file_size && file.size > uploadRequest.value.max_file_size) {
            throw new Error(`File size exceeds the maximum allowed size of ${formatFileSize(uploadRequest.value.max_file_size)}`);
        }

        if (uploadRequest.value.allowed_mime_types?.length && 
            !uploadRequest.value.allowed_mime_types.includes(file.type)) {
            throw new Error(`File type not allowed. Allowed types: ${uploadRequest.value.allowed_mime_types.join(', ')}`);
        }
    };

    const validateEncryption = async () => {
        if (!uploadRequest.value.is_encrypted) return true;

        const key = await encryptionKeyGetter();
        if (!key) {
            throw new Error('Please enter the encryption key before uploading files');
        }
        
        const isValid = await verifyEncryptionKey(key, uploadRequest.value.key_verification_hash);
        if (!isValid) {
            throw new Error('Invalid encryption key');
        }

        return key;
    };

    const formatFileSize = (bytes) => {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
    };

    const uploadFile = async (fileData, encryptionKey) => {
        console.log('useFileUpload: Starting upload for file:', fileData.name);
        fileData.status = 'uploading';
        fileData.progress = 0;
        fileData.error = null;

        try {
            validateFile(fileData.file);
            
            let fileToUpload = fileData.file;
            if (uploadRequest.value.is_encrypted && encryptionKey) {
                fileToUpload = await encryptFile(fileData.file, encryptionKey);
            }

            const formData = new FormData();
            formData.append('file', fileToUpload);
            formData.append('verification_token', uploadRequest.value.verification_token);

            console.log('useFileUpload: Sending request for file:', fileData.name);
            
            return new Promise((resolve, reject) => {
                router.post(
                    route('quickdrop.upload', uploadRequest.value.unique_request_id),
                    formData,
                    {
                        forceFormData: true,
                        preserveState: true,
                        preserveScroll: true,
                        onProgress: (progress) => {
                            fileData.progress = Math.round(progress.percentage);
                            console.log(`useFileUpload: Upload progress for ${fileData.name}:`, fileData.progress + '%');
                        },
                        onSuccess: (page) => {
                            console.log('useFileUpload: Upload success for', fileData.name);
                            // Keep the progress bar at 100% for a moment
                            fileData.progress = 100;
                            setTimeout(() => {
                                fileData.status = 'completed';
                                if (page.props.file) {
                                    files.value = [...files.value, page.props.file];
                                    pendingFiles.value = pendingFiles.value.filter(f => f.name !== fileData.name);
                                }
                                resolve(page);
                            }, 500); // Show 100% for half a second
                        },
                        onError: (errors) => {
                            console.error('useFileUpload: Upload error for', fileData.name, errors);
                            fileData.status = 'error';
                            fileData.error = errors.error || Object.values(errors)[0];
                            reject(new Error(fileData.error));
                        }
                    }
                );
            });
        } catch (error) {
            console.error('useFileUpload: Error in uploadFile for', fileData.name, error);
            fileData.status = 'error';
            fileData.error = error.message;
            throw error;
        }
    };

    const handleMultipleFiles = async (newFiles) => {
        console.log('useFileUpload: handleMultipleFiles called with', newFiles.length, 'files');
        
        // Add all files to pending, including duplicates
        pendingFiles.value = [...pendingFiles.value, ...newFiles];
    };

    const uploadPendingFiles = async () => {
        if (pendingFiles.value.length === 0 || isUploading.value) return;

        try {
            isUploading.value = true;
            uploadErrors.value = [];

            const encryptionKey = await validateEncryption();
            let successCount = 0;
            let errorCount = 0;

            // Upload files one by one
            for (const fileData of pendingFiles.value) {
                if (fileData.status === 'completed') continue;

                try {
                    // If it's a duplicate, remove the existing file first
                    if (fileData.isDuplicate) {
                        files.value = files.value.filter(f => f.name !== fileData.name);
                    }

                    await uploadFile(fileData, encryptionKey);
                    successCount++;
                } catch (error) {
                    console.error('useFileUpload: Error uploading file:', error);
                    fileData.status = 'error';
                    fileData.error = error.message;
                    uploadErrors.value.push({ file: fileData.name, error: error.message });
                    errorCount++;
                }
            }

            // Show completion notification
            if (successCount > 0) {
                const message = successCount === 1
                    ? 'File uploaded successfully'
                    : `${successCount} files uploaded successfully`;
                window.dispatchEvent(new CustomEvent('show-notification', {
                    detail: { message, type: 'success' }
                }));
            }

            if (errorCount > 0) {
                const message = errorCount === 1
                    ? 'Failed to upload 1 file'
                    : `Failed to upload ${errorCount} files`;
                window.dispatchEvent(new CustomEvent('show-notification', {
                    detail: { message, type: 'error' }
                }));
            }

            // Clear pending files after upload
            pendingFiles.value = pendingFiles.value.filter(f => f.status === 'error');

        } catch (error) {
            console.error('useFileUpload: Upload process error:', error);
            uploadErrors.value.push({ error: error.message });
            window.dispatchEvent(new CustomEvent('show-notification', {
                detail: { 
                    message: error.message || 'Upload process failed', 
                    type: 'error' 
                }
            }));
        } finally {
            isUploading.value = false;
        }
    };

    return {
        files,
        pendingFiles,
        isUploading,
        uploadErrors,
        canUpload,
        handleMultipleFiles,
        uploadPendingFiles
    };
} 