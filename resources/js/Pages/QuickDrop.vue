<template>
    <AuthenticatedLayout>
        <Head title="QuickDrop" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100 relative">
                        <Notification />
                        <!-- Success Message Section -->
                        <div v-if="showSuccessMessage" class="mb-6">
                            <button 
                                @click="toggleSuccessMessage"
                                class="w-full flex items-center justify-between p-4 bg-green-50 dark:bg-green-900 rounded-lg hover:bg-green-100 dark:hover:bg-green-800 transition-colors"
                            >
                                <h3 class="text-lg font-medium text-green-800 dark:text-green-100">
                                    Your QuickDrop Box is Ready! 🎉
                                </h3>
                                <component 
                                    :is="isSuccessMessageExpanded ? ChevronUpIcon : ChevronDownIcon"
                                    class="w-5 h-5 text-green-800 dark:text-green-100"
                                />
                            </button>

                            <div 
                                v-show="isSuccessMessageExpanded"
                                class="mt-4 p-4 bg-green-50 dark:bg-green-900 rounded-lg space-y-6"
                            >
                                <div>
                                    <p class="text-sm text-green-700 dark:text-green-200 mb-4">
                                        Your secure file sharing box has been created. Here's what you need to know:
                                    </p>
                                </div>

                                <!-- Upload Link Section -->
                                <div>
                                    <label class="block text-sm font-medium text-green-800 dark:text-green-100">
                                        1. Share this Upload Link
                                    </label>
                                    <p class="text-sm text-green-700 dark:text-green-200 mb-2">
                                        Send this link to people who need to upload files to you:
                                    </p>
                                    <div class="mt-1 flex rounded-md shadow-sm">
                                        <input
                                            type="text"
                                            :value="currentUrl"
                                            readonly
                                            class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md text-sm border-green-300 bg-white dark:bg-gray-700"
                                        />
                                        <button
                                            type="button"
                                            @click="copyToClipboard(currentUrl)"
                                            class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 dark:text-green-100 dark:bg-green-800 dark:hover:bg-green-700"
                                        >
                                            Copy Link
                                        </button>
                                    </div>
                                </div>

                                <!-- Encryption Key Section -->
                                <div v-if="encryptionKey" class="mt-4">
                                    <label class="block text-sm font-medium text-green-800 dark:text-green-100">
                                        2. Save the Encryption Key
                                    </label>
                                    <p class="text-sm text-green-700 dark:text-green-200 mb-2">
                                        This key is required to decrypt the files. Save it securely:
                                    </p>
                                    <div class="mt-1 flex rounded-md shadow-sm">
                                        <input
                                            type="text"
                                            :value="encryptionKey"
                                            readonly
                                            class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md text-sm border-green-300 bg-white dark:bg-gray-700"
                                        />
                                        <button
                                            type="button"
                                            @click="copyToClipboard(encryptionKey)"
                                            class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 dark:text-green-100 dark:bg-green-800 dark:hover:bg-green-700"
                                        >
                                            Copy Key
                                        </button>
                                    </div>
                                    <div class="mt-4 p-4 bg-green-100 dark:bg-green-800 rounded">
                                        <p class="text-sm font-medium text-green-800 dark:text-green-100">
                                            Security Tip: For sensitive files, share the upload link and encryption key through different communication channels 
                                            (e.g., send the link via email and the key via message/phone).
                                        </p>
                                    </div>
                                </div>

                                <!-- How it Works Section -->
                                <div>
                                    <label class="block text-sm font-medium text-green-800 dark:text-green-100">
                                        3. How it Works
                                    </label>
                                    <div class="text-sm text-green-700 dark:text-green-200 mt-2">
                                        <p class="mb-2">For people uploading files:</p>
                                        <ol class="list-decimal ml-5 space-y-1">
                                            <li>They visit the upload link</li>
                                            <li v-if="$page.props.encryptionKey">They enter the encryption key you provided</li>
                                            <li>They can drag & drop or select files to upload</li>
                                            <li v-if="$page.props.encryptionKey">Files are automatically encrypted before upload</li>
                                            <li>You'll receive a notification when files are uploaded</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Main Content Section -->
                        <div class="mb-6">
                            <h2 class="text-lg font-semibold">QuickDrop Box</h2>
                            <TimeLeft 
                                :expires-at="uploadRequest.expires_at"
                                :is-expired="uploadRequest.is_expired"
                            />
                            
                            <EncryptionKeyInput
                                v-if="uploadRequest.is_encrypted"
                                v-model="formKey"
                                :is-public="false"
                                @verify="verifyAndStoreKey"
                            />
                        </div>

                        <FileUploadZone
                            v-if="canUpload"
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
                            :can-download="canViewFiles"
                            @download-file="downloadFile"
                            :key="`file-list-${files.length}`"
                        />

                        <div v-if="!canViewFiles" class="mt-6 text-center text-gray-500">
                            <p>Files uploaded to this QuickDrop box can only be viewed by its owner.</p>
                            <p v-if="files.length" class="mt-2">Your upload was successful, but you'll need to contact the owner to access the files.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';
import { decryptFile } from '@/Services/EncryptionService';
import FileUploadZone from '@/Components/FileUploadZone.vue';
import FileList from '@/Components/FileList.vue';
import EncryptionKeyInput from '@/Components/EncryptionKeyInput.vue';
import TimeLeft from '@/Components/TimeLeft.vue';
import { useFileUpload } from '@/Composables/useFileUpload';
import { useEncryptionKey } from '@/Composables/useEncryptionKey';
import { ChevronDownIcon, ChevronUpIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/solid';
import Notification from '@/Components/Notification.vue';

const props = defineProps({
    uploadRequest: {
        type: Object,
        required: true
    },
    canViewFiles: {
        type: Boolean,
        required: true
    },
    showNewBoxMessage: {
        type: Boolean,
        default: false
    },
    encryptionKey: {
        type: [String, Boolean],
        default: false
    },
    files: {
        type: Array,
        default: () => []
    }
});

const isSuccessMessageExpanded = ref(true);
const showSuccessMessage = ref(props.showNewBoxMessage);

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
    uploadPendingFiles 
} = useFileUpload(uploadRequestRef, getCurrentKey, props.files);

onMounted(() => {
    nextTick(() => {
        setTimeout(initializeKey, 100);
    });
});

const currentUrl = computed(() => {
    return typeof window !== 'undefined' ? window.location.href : '';
});

const downloadFile = async (file) => {
    try {
        const response = await axios.get(
            route('quickdrop.download', file.id),
            { responseType: 'blob' }
        );

        let downloadBlob = response.data;
        
        if (props.uploadRequest.is_encrypted) {
            const key = await getCurrentKey();
            if (!key) {
                throw new Error('Please enter the encryption key to download files');
            }
            downloadBlob = await decryptFile(downloadBlob, key);
        }

        const url = window.URL.createObjectURL(downloadBlob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', file.name);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Download error:', error);
        alert(error.message || 'Download failed');
    }
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        alert('Copied to clipboard!');
    } catch (err) {
        alert('Failed to copy to clipboard');
    }
};

const toggleSuccessMessage = () => {
    isSuccessMessageExpanded.value = !isSuccessMessageExpanded.value;
};

const sortedFiles = computed(() => {
    return [...files.value].sort((a, b) => {
        return new Date(b.uploaded_at) - new Date(a.uploaded_at);
    });
});
</script>
