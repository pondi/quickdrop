<script setup>
import { ref, watch, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import FileUploadZone from '@/Components/FileUploadZone.vue';
import UploadProgress from '@/Components/UploadProgress.vue';
import FileList from '@/Components/FileList.vue';
import EncryptionKeyInput from '@/Components/EncryptionKeyInput.vue';
import TimeLeft from '@/Components/TimeLeft.vue';
import { useFileUpload } from '@/Composables/useFileUpload';
import { useEncryptionKey } from '@/Composables/useEncryptionKey';

const props = defineProps({
    uploadRequest: {
        type: Object,
        required: true,
    },
    canViewFiles: {
        type: Boolean,
        required: true,
    },
});

// Convert props.uploadRequest to ref for reactivity in composables
const uploadRequestRef = ref(props.uploadRequest);
watch(() => props.uploadRequest, (newVal) => {
    uploadRequestRef.value = newVal;
});

// Initialize encryption key management
const { formKey, initializeKey, verifyAndStoreKey, getCurrentKey } = useEncryptionKey(uploadRequestRef);

// Initialize file upload management
const { files, isUploading, uploadProgress, canUpload, handleMultipleFiles } = useFileUpload(uploadRequestRef, getCurrentKey);

onMounted(initializeKey);
</script>

<template>
    <GuestLayout>
        <Head title="QuickDrop Upload" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
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
                            v-if="canUpload"
                            :allowed-mime-types="uploadRequest.allowed_mime_types"
                            :max-file-size="uploadRequest.max_file_size"
                            @file-select="handleMultipleFiles"
                        />

                        <UploadProgress
                            v-if="isUploading"
                            :progress="uploadProgress"
                        />

                        <FileList
                            v-if="files.length"
                            :files="files"
                            :can-download="false"
                        />
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template> 