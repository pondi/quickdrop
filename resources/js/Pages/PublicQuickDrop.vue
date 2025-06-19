<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import Icon from '@/Components/App/Icon.vue';
import ProgressRing from '@/Components/App/ProgressRing.vue';
import axios from 'axios';
import { decryptFile } from '@/Services/EncryptionService';
import FileUploadZone from '@/Components/FileUploadZone.vue';
import FileList from '@/Components/FileList.vue';
import EncryptionKeyInput from '@/Components/EncryptionKeyInput.vue';
import TimeLeft from '@/Components/TimeLeft.vue';
import { useFileUpload } from '@/Composables/useFileUpload';
import { useEncryptionKey } from '@/Composables/useEncryptionKey';
import Notification from '@/Components/Notification.vue';

const props = defineProps({
    uploadRequest: {
        type: Object,
        required: true,
    },
    files: {
        type: Array,
        default: () => []
    }
});

const uploadRequestRef = ref(props.uploadRequest);
watch(() => props.uploadRequest, (newVal) => {
    uploadRequestRef.value = newVal;
});

const { formKey, encryptionKey, initializeKey, verifyAndStoreKey, getCurrentKey } = useEncryptionKey(uploadRequestRef);

const { 
    files, 
    pendingFiles,
    isUploading, 
    canUpload, 
    handleMultipleFiles,
    uploadPendingFiles,
    deleteFile 
} = useFileUpload(uploadRequestRef, getCurrentKey, props.files);

onMounted(() => {
    initializeKey();
});

const storageUsed = computed(() => {
    return files.value.reduce((total, file) => total + (file.size || 0), 0);
});

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const downloadFile = async (file) => {
    if (!uploadRequestRef.value.can_download) {
        alert('You do not have permission to download files from this QuickDrop box.');
        return;
    }

    try {
        const downloadUrl = route('download.file', {
            requestId: uploadRequestRef.value.unique_request_id,
            fileUuid: file.unique_id
        });

        if (props.uploadRequest.is_encrypted) {
            const key = await getCurrentKey();
            if (!key) {
                throw new Error('Please enter the encryption key to download files');
            }

            const response = await fetch(downloadUrl);
            if (!response.ok) {
                throw new Error(`Download failed: ${response.statusText}`);
            }

            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const error = await response.json();
                throw new Error(error.message || 'Download failed');
            }

            const blob = await response.blob();
            const decryptedBlob = await decryptFile(blob, key);
            
            const url = window.URL.createObjectURL(decryptedBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = file.original_name || file.name || 'download';
            document.body.appendChild(link);
            link.click();
            
            setTimeout(() => {
                document.body.removeChild(link);
                window.URL.revokeObjectURL(url);
            }, 100);
        } else {
            window.open(downloadUrl, '_blank');
        }
    } catch (error) {
        console.error('Download error:', error);
        alert(error.message || 'Download failed. Please try again.');
    }
};

const downloadAllFiles = async () => {
    if (!uploadRequestRef.value.can_download) {
        alert('You do not have permission to download files from this QuickDrop box.');
        return;
    }

    try {
        const downloadUrl = route('download.file', {
            requestId: uploadRequestRef.value.unique_request_id
        });

        const win = window.open(downloadUrl, '_blank');
        if (!win || win.closed || typeof win.closed === 'undefined') {
            window.location.href = downloadUrl;
        }
    } catch (error) {
        console.error('Bulk download error:', error);
        alert(error.message || 'Bulk download failed. Please try again.');
    }
};

const sortedFiles = computed(() => {
    return [...files.value].sort((a, b) => {
        return new Date(b.uploaded_at) - new Date(a.uploaded_at);
    });
});
</script>

<template>
    <Head title="QuickDrop Upload" />

    <PublicLayout>
        <div class="min-h-screen flex items-center justify-center py-12">
            <div class="max-w-4xl mx-auto w-full space-y-8">
                <Notification />
                
                <!-- Header -->
                <div class="text-center">
                    <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-gradient-primary flex items-center justify-center animate-pulse-glow">
                        <Icon name="upload" :size="40" class="text-white" />
                    </div>
                    <h1 class="text-4xl font-display font-bold gradient-text mb-4">
                        {{ uploadRequest.title || 'QuickDrop Upload' }}
                    </h1>
                    <p class="text-text-secondary text-lg mb-6">
                        {{ uploadRequest.comment || 'Share your files securely' }}
                    </p>
                    
                    <!-- Time and Reference Info -->
                    <div class="flex items-center justify-center space-x-6 text-sm">
                        <div class="flex items-center space-x-2">
                            <Icon name="clock" :size="16" class="text-text-muted" />
                            <TimeLeft 
                                :expires-at="uploadRequest.expires_at"
                                :is-expired="uploadRequest.is_expired"
                            />
                        </div>
                        <div v-if="uploadRequest.reference_number" class="flex items-center space-x-2">
                            <Icon name="hash" :size="16" class="text-text-muted" />
                            <span class="font-mono">{{ uploadRequest.reference_number }}</span>
                        </div>
                        <div v-if="uploadRequest.is_encrypted" class="flex items-center space-x-2">
                            <Icon name="shield" :size="16" class="text-primary" />
                            <span class="text-primary">Encrypted</span>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card class="text-center">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gradient-primary flex items-center justify-center">
                            <Icon name="files" :size="24" class="text-white" />
                        </div>
                        <div class="text-2xl font-bold text-text-primary">{{ files.length }}</div>
                        <div class="text-sm text-text-secondary">Files Uploaded</div>
                    </Card>
                    
                    <Card class="text-center">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gradient-secondary flex items-center justify-center">
                            <Icon name="harddrive" :size="24" class="text-primary-end" />
                        </div>
                        <div class="text-2xl font-bold text-text-primary">{{ formatBytes(storageUsed) }}</div>
                        <div class="text-sm text-text-secondary">Total Size</div>
                    </Card>
                    
                    <Card class="text-center">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gradient-primary flex items-center justify-center">
                            <Icon :name="uploadRequest.is_encrypted ? 'shield' : 'unlock'" :size="24" class="text-white" />
                        </div>
                        <div class="text-lg font-bold text-text-primary">
                            {{ uploadRequest.is_encrypted ? 'Encrypted' : 'Standard' }}
                        </div>
                        <div class="text-sm text-text-secondary">Security</div>
                    </Card>
                </div>

                <!-- Encryption Key Input -->
                <Card v-if="uploadRequest.is_encrypted" class="border-accent/20 bg-accent/5">
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-accent/20 flex items-center justify-center flex-shrink-0">
                            <Icon name="key" :size="24" class="text-accent" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-accent mb-2">Encryption Key Required</h3>
                            <p class="text-sm text-text-secondary mb-4">
                                This QuickDrop is encrypted. Enter the encryption key provided by the owner to upload or download files.
                            </p>
                            <EncryptionKeyInput
                                v-model="formKey"
                                :is-public="true"
                                @verify="verifyAndStoreKey"
                            />
                        </div>
                    </div>
                </Card>

                <!-- Upload Zone -->
                <Card v-if="canUpload && uploadRequest.can_upload">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-semibold text-text-primary mb-2">Upload Your Files</h2>
                        <p class="text-text-secondary">
                            Drag and drop files here or click to browse
                        </p>
                    </div>
                    
                    <FileUploadZone
                        :allowed-mime-types="uploadRequest.allowed_mime_types"
                        :max-file-size="uploadRequest.max_file_size"
                        :completed-files="files"
                        :upload-request="uploadRequest"
                        @upload-files="handleMultipleFiles"
                    />

                    <Button 
                        v-if="pendingFiles.length"
                        @click="uploadPendingFiles"
                        variant="primary"
                        size="lg"
                        :loading="isUploading"
                        :disabled="isUploading"
                        class="mt-6 w-full animate-pulse-glow"
                    >
                        <Icon v-if="!isUploading" name="upload" :size="20" class="mr-2" />
                        <span v-if="isUploading">Uploading Files...</span>
                        <span v-else>Upload {{ pendingFiles.length }} {{ pendingFiles.length === 1 ? 'File' : 'Files' }}</span>
                    </Button>
                </Card>

                <!-- Upload Not Allowed -->
                <Card v-else-if="!uploadRequest.can_upload" class="text-center border-accent/20 bg-accent/5">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-accent/20 flex items-center justify-center">
                        <Icon name="ban" :size="32" class="text-accent" />
                    </div>
                    <h3 class="text-lg font-semibold text-accent mb-2">Upload Not Available</h3>
                    <p class="text-text-secondary">
                        File uploads are not allowed for this QuickDrop box.
                    </p>
                </Card>

                <!-- File List -->
                <Card v-if="files.length">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-text-primary">Uploaded Files</h2>
                        <Button
                            v-if="files.length > 1 && uploadRequest.can_download"
                            variant="outline"
                            icon="download"
                            @click="downloadAllFiles"
                        >
                            Download All
                        </Button>
                    </div>
                    
                    <FileList
                        :files="sortedFiles"
                        :canDownload="uploadRequest.can_download"
                        :canDelete="uploadRequest.can_delete"
                        @download-file="downloadFile"
                        @download-all="downloadAllFiles"
                        @delete-file="deleteFile"
                        :key="`file-list-${files.length}`"
                    />
                </Card>

                <!-- No Download Access -->
                <Card v-if="!uploadRequest.can_download && files.length" class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-surface flex items-center justify-center">
                        <Icon name="eyeOff" :size="32" class="text-text-muted" />
                    </div>
                    <h3 class="text-lg font-semibold text-text-primary mb-2">Files Successfully Uploaded!</h3>
                    <p class="text-text-secondary mb-4">
                        Your files have been uploaded securely, but downloads are restricted to the box owner.
                    </p>
                    <p class="text-sm text-text-secondary">
                        Contact the owner if you need to access the uploaded files.
                    </p>
                </Card>

                <!-- Empty State -->
                <Card v-if="!files.length && !canUpload" class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-surface flex items-center justify-center">
                        <Icon name="inbox" :size="32" class="text-text-muted" />
                    </div>
                    <h3 class="text-lg font-semibold text-text-primary mb-2">No Files Yet</h3>
                    <p class="text-text-secondary">
                        This QuickDrop box is ready but doesn't contain any files yet.
                    </p>
                </Card>

                <!-- Footer -->
                <div class="text-center">
                    <p class="text-xs text-text-muted">
                        Powered by <span class="gradient-text font-semibold">QuickDrop</span> - Secure File Sharing
                    </p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>