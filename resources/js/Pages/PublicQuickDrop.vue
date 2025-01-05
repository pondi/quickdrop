<template>
    <GuestLayout>
        <Head title="QuickDrop Upload" />

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <Notification />
                
                <div class="mb-6">
                    <h2 class="text-lg font-semibold">QuickDrop Upload</h2>
                    <TimeLeft 
                        :expires-at="uploadRequest.expires_at"
                        :is-expired="uploadRequest.is_expired"
                    />

                    <EncryptionKeyInput
                        v-if="uploadRequest.is_encrypted"
                        v-model="formKey"
                        :is-public="true"
                        @verify="verifyAndStoreKey"
                    />
                </div>

                <FileUploadZone
                    v-if="canUpload && uploadRequest.can_upload"
                    :allowed-mime-types="uploadRequest.allowed_mime_types"
                    :max-file-size="uploadRequest.max_file_size"
                    :completed-files="files"
                    :upload-request="uploadRequest"
                    @upload-files="handleMultipleFiles"
                />

                <button 
                    v-if="pendingFiles.length"
                    @click="uploadPendingFiles"
                    class="mt-4 w-full flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    :disabled="isUploading"
                >
                    <ArrowUpTrayIcon v-if="!isUploading" class="h-5 w-5 mr-2" />
                    <svg v-else class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span v-if="isUploading">Uploading...</span>
                    <span v-else>Upload {{ pendingFiles.length }} {{ pendingFiles.length === 1 ? 'File' : 'Files' }}</span>
                </button>

                <FileList
                    v-if="files.length"
                    :files="sortedFiles"
                    :canDownload="uploadRequest.can_download"
                    :canDelete="uploadRequest.can_delete"
                    @download-file="downloadFile"
                    @download-all="downloadAllFiles"
                    @delete-file="deleteFile"
                    :key="`file-list-${files.length}`"
                />

                <div v-if="!uploadRequest.can_download && files.length" class="mt-6 text-center text-gray-500">
                    <p>Files uploaded to this QuickDrop box can only be downloaded by its owner.</p>
                    <p class="mt-2">Your upload was successful, but you'll need to contact the owner to access the files.</p>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import QuickDropGuestLayout from '@/Layouts/QuickDropGuestLayout.vue';
import axios from 'axios';
import { decryptFile } from '@/Services/EncryptionService';
import FileUploadZone from '@/Components/FileUploadZone.vue';
import FileList from '@/Components/FileList.vue';
import EncryptionKeyInput from '@/Components/EncryptionKeyInput.vue';
import TimeLeft from '@/Components/TimeLeft.vue';
import { useFileUpload } from '@/Composables/useFileUpload';
import { useEncryptionKey } from '@/Composables/useEncryptionKey';
import { ArrowUpTrayIcon } from '@heroicons/vue/24/solid';
import Notification from '@/Components/Notification.vue';
import UploadProgress from '@/Components/UploadProgress.vue';

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