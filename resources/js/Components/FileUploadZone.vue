<script setup>
import { ref, computed } from 'vue';
import { 
    DocumentIcon, 
    DocumentTextIcon,
    PhotoIcon,
    VideoCameraIcon,
    MusicalNoteIcon,
    TableCellsIcon,
    XMarkIcon,
    PlusIcon,
    CheckIcon,
    ExclamationCircleIcon
} from '@heroicons/vue/24/solid';

const props = defineProps({
    allowedMimeTypes: {
        type: Array,
        default: () => [],
    },
    maxFileSize: {
        type: Number,
        default: null,
    },
    completedFiles: {
        type: Array,
        default: () => [],
    }
});

const emit = defineEmits(['upload-files']);

const dragOver = ref(false);
const selectedFiles = ref([]);

const getFileIcon = (mimeType) => {
    if (mimeType.startsWith('image/')) return PhotoIcon;
    if (mimeType.startsWith('video/')) return VideoCameraIcon;
    if (mimeType.startsWith('audio/')) return MusicalNoteIcon;
    if (mimeType === 'application/pdf') return DocumentTextIcon;
    if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) return TableCellsIcon;
    return DocumentIcon;
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
};

const handleDrop = (e) => {
    e.preventDefault();
    dragOver.value = false;
    addFiles(Array.from(e.dataTransfer.files));
};

const handleFileSelect = (e) => {
    addFiles(Array.from(e.target.files));
};

const addFiles = (files) => {
    console.log('FileUploadZone: Files being added:', files.length, 'files');
    
    const newFiles = files.map(file => {
        const isDuplicate = props.completedFiles.some(f => f.name === file.name && f.size === file.size);
        return {
            file,
            id: Math.random().toString(36).substring(7),
            name: file.name,
            size: file.size,
            type: file.type,
            progress: 0,
            status: 'pending',
            error: null,
            isDuplicate,
            preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null
        };
    });
    
    selectedFiles.value = [...selectedFiles.value, ...newFiles];
    emit('upload-files', newFiles);
    console.log('FileUploadZone: Total selected files:', selectedFiles.value.length);
};

const removeFile = (fileId) => {
    const file = selectedFiles.value.find(f => f.id === fileId);
    if (file?.preview) {
        URL.revokeObjectURL(file.preview);
    }
    selectedFiles.value = selectedFiles.value.filter(f => f.id !== fileId);
};

const formatAllowedTypes = computed(() => {
    if (!props.allowedMimeTypes?.length) return 'Any file type';
    
    // Convert MIME types to more readable format
    return props.allowedMimeTypes.map(mime => {
        if (mime.startsWith('image/')) return mime.replace('image/', '').toUpperCase() + ' images';
        if (mime.startsWith('video/')) return mime.replace('video/', '').toUpperCase() + ' videos';
        if (mime.startsWith('audio/')) return mime.replace('audio/', '').toUpperCase() + ' audio';
        if (mime === 'application/pdf') return 'PDF files';
        if (mime.includes('spreadsheet')) return 'Spreadsheets';
        if (mime.includes('document')) return 'Documents';
        return mime;
    }).join(', ');
});

const formatMaxSize = computed(() => {
    if (!props.maxFileSize) return 'unlimited size';
    return formatFileSize(props.maxFileSize);
});
</script>

<template>
    <div class="relative">
        <!-- Main Drop Zone Container -->
        <div
            class="mt-4 border-2 border-dashed rounded-xl bg-white dark:bg-gray-800 transition-all duration-200"
            :class="{ 
                'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20': dragOver,
                'border-gray-300 dark:border-gray-600': !dragOver 
            }"
        >
            <!-- Empty State -->
            <div v-if="selectedFiles.length === 0"
                class="flex flex-col items-center justify-center px-6 pt-10 pb-8"
                @dragover.prevent="dragOver = true"
                @dragleave.prevent="dragOver = false"
                @drop="handleDrop"
            >
                <div class="mx-auto h-20 w-20 text-gray-400 dark:text-gray-500">
                    <svg
                        class="h-full w-full"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 48 48"
                        aria-hidden="true"
                    >
                        <path
                            d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>
                <div class="mt-4 flex flex-col items-center text-center">
                    <p class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Drop your files here
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        or
                    </p>
                    <label
                        class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-150"
                    >
                        Browse Files
                        <input
                            type="file"
                            class="sr-only"
                            @change="handleFileSelect"
                            multiple
                        />
                    </label>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        {{ formatAllowedTypes }} up to {{ formatMaxSize }}
                    </p>
                </div>
            </div>

            <!-- Files List -->
            <div v-else class="p-4">
                <!-- Add More Files Section -->
                <div class="mb-4 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Selected Files
                    </h3>
                    <label
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 cursor-pointer transition-colors duration-150"
                    >
                        <PlusIcon class="w-5 h-5 mr-1" />
                        Add More Files
                        <input
                            type="file"
                            class="sr-only"
                            @change="handleFileSelect"
                            multiple
                        />
                    </label>
                </div>

                <!-- Files Grid -->
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="file in selectedFiles" :key="file.id" 
                        class="relative group flex items-start space-x-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-150"
                        :class="{ 'border-l-4 border-yellow-500': file.isDuplicate }"
                    >
                        <!-- Preview/Icon -->
                        <div class="flex-shrink-0 w-10 h-10">
                            <img v-if="file.preview" :src="file.preview" class="w-10 h-10 object-cover rounded" />
                            <component v-else :is="getFileIcon(file.type)" 
                                class="w-10 h-10 text-gray-400" />
                        </div>

                        <!-- File Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                    {{ file.name }}
                                </p>
                                <span v-if="file.isDuplicate"
                                    class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200"
                                >
                                    Duplicate
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ formatFileSize(file.size) }}
                            </p>
                            
                            <!-- Progress Bar -->
                            <div v-if="file.status === 'uploading'" class="mt-2">
                                <div class="h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-600 dark:bg-indigo-500 transition-all duration-300"
                                        :style="{ width: file.progress + '%' }" />
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ file.progress }}%
                                </p>
                            </div>

                            <!-- Error Message -->
                            <p v-if="file.error" class="text-xs text-red-600 dark:text-red-400 mt-1">
                                {{ file.error }}
                            </p>
                            
                            <!-- Duplicate Warning -->
                            <p v-if="file.isDuplicate" class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                                This file will replace the existing one
                            </p>
                        </div>

                        <!-- Remove Button (Top Right) -->
                        <div class="absolute top-2 right-2">
                            <button v-if="file.status === 'pending'"
                                @click="removeFile(file.id)"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                            >
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Status Indicators (Bottom Right) -->
                        <div class="absolute bottom-2 right-2">
                            <span v-if="file.status === 'completed'"
                                class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"
                            >
                                <CheckIcon class="w-3 h-3 mr-1" />
                                Done
                            </span>
                            <span v-else-if="file.status === 'error'"
                                class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200"
                            >
                                <ExclamationCircleIcon class="w-3 h-3 mr-1" />
                                Error
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template> 