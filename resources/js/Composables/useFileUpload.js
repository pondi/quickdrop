import { ref, watch, computed, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import { calculateFileHash } from '@/Services/FileHashService';

export function useFileUpload(uploadRequest, encryptionKeyGetter, initialFiles = []) {
    const files = ref([...initialFiles]);
    const pendingFiles = ref([]);
    const isUploading = ref(false);

    const canUpload = computed(() => {
        if (!uploadRequest.value) return false;
        if (uploadRequest.value.is_expired) return false;
        if (!uploadRequest.value.is_active) return false;
        return true;
    });

    const handleMultipleFiles = async (newFiles) => {
        pendingFiles.value.push(...newFiles);
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
                    import.meta.env.DEV && console.log(`Skipping duplicate file: ${fileData.name}`);
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
        } catch (error) {
            console.error('Upload error:', error);
        } finally {
            isUploading.value = false;
            pendingFiles.value = pendingFiles.value.filter(f => !f.uploading && !f.isHashDuplicate);
        }
    };

    const uploadFile = async (fileData) => {
        if (!canUpload.value) return;

        const formData = new FormData();
        formData.append('file', fileData.file);
        formData.append('verification_token', uploadRequest.value.verification_token);
        formData.append('file_hash', fileData.hash);
        formData.append('next_version', fileData.nextVersion?.toString());

        if (uploadRequest.value.is_encrypted) {
            const key = await encryptionKeyGetter();
            if (!key) {
                fileData.error = 'Encryption key required';
                return;
            }
        }

        return new Promise((resolve, reject) => {
            const fileElement = document.querySelector(`[data-file-id="${fileData.name}"]`);
            
            router.post(
                route('quickdrop.upload', uploadRequest.value.unique_request_id),
                formData,
                {
                    forceFormData: true,
                    preserveState: true,
                    preserveScroll: true,
                    onProgress: (progress) => {
                        if (fileData.uploading) {
                            fileData.progress = Math.round(progress.percentage);
                            import.meta.env.DEV && console.log(`Upload progress for ${fileData.name}: ${fileData.progress}%`);
                        }
                    },
                    onSuccess: (page) => {
                        if (!fileData.uploading) return;

                        import.meta.env.DEV && console.log('Upload success response:', page);
                        fileData.progress = 100;
                        
                        if (fileElement) {
                            fileElement.classList.add('moving-to-uploaded');
                        }

                        setTimeout(() => {
                            if (!fileData.uploading) return;

                            const uploadedFileData = page.props.files?.find(f => 
                                f.hash === fileData.hash || 
                                f.name === fileData.name
                            );

                            if (uploadedFileData) {
                                pendingFiles.value = pendingFiles.value.filter(f => f !== fileData);
                                
                                const uploadedFile = {
                                    ...uploadedFileData,
                                    hash: fileData.hash,
                                    isNewUpload: true
                                };

                                files.value = page.props.files.map(f => ({
                                    ...f,
                                    isNewUpload: f.id === uploadedFileData.id
                                }));

                                setTimeout(() => {
                                    if (!files.value) return;
                                    const index = files.value.findIndex(f => f.id === uploadedFile.id);
                                    if (index !== -1) {
                                        files.value = [
                                            ...files.value.slice(0, index),
                                            { ...files.value[index], isNewUpload: false },
                                            ...files.value.slice(index + 1)
                                        ];
                                    }
                                }, 500);
                            } else {
                                console.error('No file data in response:', page);
                                fileData.error = 'Upload failed - please try again';
                            }
                            resolve(page);
                        }, 500);
                    },
                    onError: (errors) => {
                        if (!fileData.uploading) return;
                        
                        if (errors.response?.status === 429) {
                            reject(errors);
                            return;
                        }

                        console.error('Upload error:', errors);
                        fileData.error = errors.error || 'Upload failed';
                        fileData.uploading = false;
                        fileData.progress = undefined;
                        reject(errors);
                    },
                    onCancel: () => {
                        fileData.uploading = false;
                        fileData.progress = undefined;
                        resolve();
                    }
                }
            );
        });
    };

    const removeFile = (file) => {
        file.uploading = false;
        pendingFiles.value = pendingFiles.value.filter(f => f !== file);
    };

    watch(() => initialFiles, (newFiles) => {
        files.value = [...newFiles];
    }, { deep: true });

    onBeforeUnmount(() => {
        pendingFiles.value.forEach(file => {
            file.uploading = false;
        });
        pendingFiles.value = [];
    });

    return {
        files,
        pendingFiles,
        isUploading,
        canUpload,
        handleMultipleFiles,
        uploadPendingFiles,
        removeFile
    };
} 