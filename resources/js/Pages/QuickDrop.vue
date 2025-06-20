<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import Icon from '@/Components/App/Icon.vue';
import ProgressRing from '@/Components/App/ProgressRing.vue';
import DropZone from '@/Components/App/DropZone.vue';
import Modal from '@/Components/App/Modal.vue';
import ShareLinkModal from '@/Components/ShareLinkModal.vue';
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
const showShareModal = ref(false);
const copySuccess = ref(false);
const shareUrl = ref('');
const shareData = ref(null);
const loadingShare = ref(false);

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
        alert(error.message || 'Download failed. Please try again.');
    }
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        copySuccess.value = true;
        setTimeout(() => copySuccess.value = false, 2000);
    } catch (err) {
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

const downloadAllFiles = async () => {
    try {
        const downloadUrl = route('download.file', {
            requestId: uploadRequestRef.value.unique_request_id
        });

        const win = window.open(downloadUrl, '_blank');
        if (!win || win.closed || typeof win.closed === 'undefined') {
            window.location.href = downloadUrl;
        }
    } catch (error) {
        alert(error.message || 'Bulk download failed. Please try again.');
    }
};

const openShareModal = async () => {
    loadingShare.value = true;
    
    try {
        const response = await axios.get(route('quickdrop.share-link', uploadRequestRef.value.unique_request_id));
        shareUrl.value = response.data.share_url;
        shareData.value = response.data;
        showShareModal.value = true;
    } catch (error) {
        // Fallback to using the current URL
        shareUrl.value = currentUrl.value;
        shareData.value = {
            title: uploadRequestRef.value.title,
            expires_at: uploadRequestRef.value.expires_at,
            is_encrypted: uploadRequestRef.value.is_encrypted
        };
        showShareModal.value = true;
    } finally {
        loadingShare.value = false;
    }
};
</script>

<template>
    <Head title="QuickDrop Box" />

    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-8">
            <Notification />
            
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                <div>
                    <h1 class="text-3xl font-display font-bold gradient-text">
                        {{ uploadRequest.title || 'QuickDrop Box' }}
                    </h1>
                    <div class="flex items-center space-x-4 mt-2">
                        <TimeLeft 
                            :expires-at="uploadRequest.expires_at"
                            :is-expired="uploadRequest.is_expired"
                        />
                        <div v-if="uploadRequest.reference_number" class="flex items-center space-x-1 text-text-secondary">
                            <Icon name="hash" :size="16" />
                            <span class="font-mono text-sm">{{ uploadRequest.reference_number }}</span>
                        </div>
                    </div>
                </div>
                
                <div class="flex space-x-3">
                    <Button
                        variant="outline"
                        icon="barChart"
                        as="Link"
                        :href="route('quickdrop.analytics', uploadRequestRef.unique_request_id)"
                    >
                        Analytics
                    </Button>
                    <Button
                        variant="outline"
                        icon="share2"
                        @click="openShareModal"
                    >
                        Share
                    </Button>
                    <Button
                        v-if="files.length > 1"
                        variant="primary"
                        icon="download"
                        @click="downloadAllFiles"
                    >
                        Download All
                    </Button>
                </div>
            </div>

            <!-- Success Message -->
            <Card v-if="showSuccessMessage" class="border-green-500/20 bg-green-500/5">
                <button 
                    @click="toggleSuccessMessage"
                    class="w-full flex items-center justify-between"
                >
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-full bg-green-500/20 flex items-center justify-center">
                            <Icon name="checkCircle" :size="24" class="text-green-400" />
                        </div>
                        <div class="text-left">
                            <h3 class="text-lg font-semibold text-green-400">
                                Your QuickDrop Box is Ready! 🎉
                            </h3>
                            <p class="text-sm text-text-secondary">
                                Share the link and start collecting files
                            </p>
                        </div>
                    </div>
                    <Icon 
                        :name="isSuccessMessageExpanded ? 'chevronUp' : 'chevronDown'" 
                        :size="20" 
                        class="text-green-400" 
                    />
                </button>

                <div v-show="isSuccessMessageExpanded" class="mt-6 space-y-6">
                    <!-- Upload Link -->
                    <div>
                        <label class="block text-sm font-medium text-text-primary mb-2">
                            1. Share Upload Link
                        </label>
                        <div class="flex space-x-2">
                            <input
                                :value="currentUrl"
                                readonly
                                class="flex-1 px-4 py-2 rounded-lg bg-surface border border-white/10 text-text-primary text-sm"
                            />
                            <Button
                                variant="primary"
                                size="sm"
                                icon="copy"
                                @click="copyToClipboard(currentUrl)"
                            >
                                {{ copySuccess ? 'Copied!' : 'Copy' }}
                            </Button>
                        </div>
                    </div>

                    <!-- Encryption Key -->
                    <div v-if="encryptionKey" class="p-4 rounded-lg bg-amber-500/10 border border-amber-500/20">
                        <label class="block text-sm font-medium text-amber-400 mb-2">
                            <Icon name="shield" :size="16" class="inline mr-1" />
                            2. Save Encryption Key
                        </label>
                        <div class="flex space-x-2 mb-3">
                            <input
                                :value="encryptionKey"
                                readonly
                                class="flex-1 px-3 py-2 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-400 text-sm font-mono"
                            />
                            <Button
                                variant="outline"
                                size="sm"
                                icon="copy"
                                @click="copyToClipboard(encryptionKey)"
                            >
                                Copy Key
                            </Button>
                        </div>
                        <p class="text-xs text-amber-400">
                            This key is required to decrypt uploaded files. Share it securely through a different channel than the upload link.
                        </p>
                    </div>

                    <!-- How it Works -->
                    <div class="p-4 rounded-lg bg-surface">
                        <h4 class="font-medium text-text-primary mb-3">3. How It Works</h4>
                        <div class="space-y-2 text-sm text-text-secondary">
                            <div class="flex items-start space-x-2">
                                <div class="w-5 h-5 rounded-full bg-primary/20 flex items-center justify-center mt-0.5">
                                    <span class="text-xs text-primary font-semibold">1</span>
                                </div>
                                <span>Recipients visit the upload link</span>
                            </div>
                            <div v-if="uploadRequest.is_encrypted" class="flex items-start space-x-2">
                                <div class="w-5 h-5 rounded-full bg-primary/20 flex items-center justify-center mt-0.5">
                                    <span class="text-xs text-primary font-semibold">2</span>
                                </div>
                                <span>They enter the encryption key you provided</span>
                            </div>
                            <div class="flex items-start space-x-2">
                                <div class="w-5 h-5 rounded-full bg-primary/20 flex items-center justify-center mt-0.5">
                                    <span class="text-xs text-primary font-semibold">{{ uploadRequest.is_encrypted ? '3' : '2' }}</span>
                                </div>
                                <span>They can drag & drop or select files to upload</span>
                            </div>
                            <div v-if="uploadRequest.is_encrypted" class="flex items-start space-x-2">
                                <div class="w-5 h-5 rounded-full bg-primary/20 flex items-center justify-center mt-0.5">
                                    <span class="text-xs text-primary font-semibold">4</span>
                                </div>
                                <span>Files are automatically encrypted before upload</span>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <Card>
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-primary flex items-center justify-center">
                            <Icon name="files" :size="24" class="text-white" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-text-primary">{{ files.length }}</div>
                            <div class="text-sm text-text-secondary">Files Uploaded</div>
                        </div>
                    </div>
                </Card>
                
                <Card>
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-secondary flex items-center justify-center">
                            <Icon name="harddrive" :size="24" class="text-white" />
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-text-primary">{{ formatBytes(storageUsed) }}</div>
                            <div class="text-sm text-text-secondary">Total Size</div>
                        </div>
                    </div>
                </Card>
                
                <Card>
                    <div class="flex items-center space-x-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-primary flex items-center justify-center">
                            <Icon :name="uploadRequest.is_encrypted ? 'shield' : 'unlock'" :size="24" class="text-white" />
                        </div>
                        <div>
                            <div class="text-lg font-bold text-text-primary">
                                {{ uploadRequest.is_encrypted ? 'Encrypted' : 'Standard' }}
                            </div>
                            <div class="text-sm text-text-secondary">Security Level</div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Encryption Key Input -->
            <Card v-if="uploadRequest.is_encrypted">
                <EncryptionKeyInput
                    v-model="formKey"
                    :is-public="false"
                    @verify="verifyAndStoreKey"
                />
            </Card>

            <!-- File Upload Zone -->
            <Card v-if="canUpload">
                <div class="text-center mb-6">
                    <h2 class="text-xl font-semibold text-text-primary mb-2">Upload Files</h2>
                    <p class="text-text-secondary">
                        Drag and drop files or click to browse
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
                    class="mt-6 w-full"
                >
                    <Icon v-if="!isUploading" name="upload" :size="20" class="mr-2" />
                    <span v-if="isUploading">Uploading...</span>
                    <span v-else>Upload {{ pendingFiles.length }} {{ pendingFiles.length === 1 ? 'File' : 'Files' }}</span>
                </Button>
            </Card>

            <!-- File List -->
            <Card v-if="files.length">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-text-primary">Uploaded Files</h2>
                    <Button
                        v-if="files.length > 1 && canViewFiles"
                        variant="outline"
                        icon="download"
                        @click="downloadAllFiles"
                    >
                        Download All
                    </Button>
                </div>
                
                <FileList
                    :files="sortedFiles"
                    :can-download="canViewFiles"
                    @download-file="downloadFile"
                    @download-all="downloadAllFiles"
                    :key="`file-list-${files.length}`"
                />
            </Card>

            <!-- No Files Access Message -->
            <Card v-if="!canViewFiles && files.length" class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-surface flex items-center justify-center">
                    <Icon name="eyeOff" :size="32" class="text-text-muted" />
                </div>
                <h3 class="text-lg font-semibold text-text-primary mb-2">Files Not Accessible</h3>
                <p class="text-text-secondary mb-4">
                    Files uploaded to this QuickDrop box can only be viewed by its owner.
                </p>
                <p class="text-sm text-text-secondary">
                    Your upload was successful, but you'll need to contact the owner to access the files.
                </p>
            </Card>
        </div>

        <!-- Share Modal -->
        <ShareLinkModal
            :show="showShareModal"
            :share-url="shareUrl"
            :share-data="shareData"
            @close="showShareModal = false"
        />
    </AppLayout>
</template>