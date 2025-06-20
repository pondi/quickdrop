<template>
    <div class="mt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">Uploaded Files ({{ files.length }})</h3>
            <div class="flex items-center space-x-4" v-if="canShowBulkDownload">
                <button
                    @click="$emit('download-all')"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:bg-indigo-700 dark:focus:bg-indigo-600 active:bg-indigo-900 dark:active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                >
                    <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                    Download All as ZIP
                    <span class="ml-1 text-xs opacity-75">({{ formatTotalSize }})</span>
                </button>
            </div>
        </div>

        <div v-if="files.length === 0" class="text-center text-gray-500 py-8">
            No files have been uploaded yet.
        </div>

        <TransitionGroup 
            v-else
            name="file-list"
            tag="ul"
            class="mt-4 space-y-4"
        >
            <li 
                v-for="file in files" 
                :key="file.id"
                :class="[
                    'file-list-item bg-white dark:bg-gray-700 rounded-lg shadow p-4',
                    file.isNewUpload ? 'animate-fade-in' : ''
                ]"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <component 
                                :is="getFileIcon(file.type)"
                                :class="[getIconColor(file.type), 'h-8 w-8']"
                            />
                            <LockClosedIcon 
                                v-if="file.is_encrypted"
                                class="absolute -top-1 -right-1 h-4 w-4 text-green-600 bg-white dark:bg-gray-700 rounded-full"
                            />
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ file.name }}
                                <span v-if="file.version > 1" class="ml-2 text-xs text-blue-500">
                                    (v{{ file.version }})
                                </span>
                            </h4>
                            <div class="mt-1 flex items-center space-x-2 text-xs text-gray-500">
                                <span>{{ formatFileSize(file.size) }}</span>
                                <span>• {{ formatDate(file.uploaded_at) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button
                            v-if="canDownload"
                            @click="$emit('download-file', file)"
                            class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            <ArrowDownTrayIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </li>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { PhotoIcon, DocumentIcon, DocumentTextIcon, ArrowDownTrayIcon, TableCellsIcon, MusicalNoteIcon, VideoCameraIcon } from '@heroicons/vue/24/outline';
import { LockClosedIcon } from '@heroicons/vue/24/solid';
import { watch, onMounted, toRefs, computed } from 'vue';

const props = defineProps({
    files: {
        type: Array,
        required: true
    },
    canDownload: {
        type: Boolean,
        default: false
    },
    canDelete: {
        type: Boolean,
        default: false
    }
});

// Define emits
defineEmits(['download-file', 'download-all']);


const canShowBulkDownload = computed(() => {
    
    return props.canDownload && 
           props.files.length > 0 && 
           !props.files.some(file => file.is_encrypted);
});

const { files } = toRefs(props);


const getFileIcon = (mimeType) => {
    if (mimeType.startsWith('image/')) return PhotoIcon;
    if (mimeType.startsWith('video/')) return VideoCameraIcon;
    if (mimeType.startsWith('audio/')) return MusicalNoteIcon;
    if (mimeType === 'application/pdf') return DocumentTextIcon;
    if (mimeType.includes('spreadsheet') || 
        mimeType.includes('excel') || 
        mimeType === 'text/csv' ||
        mimeType === 'application/csv' ||
        (mimeType === 'text/plain' && props.files.find(f => f.type === mimeType)?.name?.toLowerCase().endsWith('.csv'))) return TableCellsIcon;
    return DocumentIcon;
};

const getIconColor = (mimeType) => {
    if (mimeType.startsWith('image/')) return 'text-purple-500';
    if (mimeType.startsWith('video/')) return 'text-red-500';
    if (mimeType.startsWith('audio/')) return 'text-pink-500';
    if (mimeType === 'application/pdf') return 'text-red-600';
    if (mimeType.includes('spreadsheet') || 
        mimeType.includes('excel') || 
        mimeType === 'text/csv' ||
        mimeType === 'application/csv' ||
        (mimeType === 'text/plain' && props.files.find(f => f.type === mimeType)?.name?.toLowerCase().endsWith('.csv'))) return 'text-green-600';
    if (mimeType.includes('word') || mimeType.includes('document')) return 'text-blue-600';
    return 'text-gray-500';
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
};

const formatTotalSize = computed(() => {
    const totalBytes = props.files.reduce((acc, file) => acc + file.size, 0);
    if (totalBytes === 0) return '0 B';
    
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(totalBytes) / Math.log(k));
    
    return parseFloat((totalBytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
});
</script>

<style>
.file-list-enter-active,
.file-list-leave-active {
    transition: all 0.5s ease;
}

.file-list-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.file-list-leave-to {
    opacity: 0;
    transform: translateY(20px);
}

.file-list-move {
    transition: transform 0.5s ease;
}

.file-list-item {
    transition: all 0.5s ease;
}

@keyframes fade-in-up {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    50% {
        opacity: 0.5;
        transform: translateY(-10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in-up 0.5s ease-out forwards;
}

@keyframes highlight {
    0% {
        background-color: transparent;
    }
    50% {
        background-color: rgba(79, 70, 229, 0.1);
    }
    100% {
        background-color: transparent;
    }
}

.file-list-item.isNewUpload {
    animation: highlight 1s ease-in-out;
}
</style> 