<script setup>
import { computed } from 'vue';
import { 
    DocumentIcon, 
    DocumentTextIcon,
    PhotoIcon,
    VideoCameraIcon,
    MusicalNoteIcon,
    TableCellsIcon,
    PresentationChartBarIcon,
    ArchiveBoxIcon,
    CodeBracketIcon,
    DocumentArrowDownIcon
} from '@heroicons/vue/24/solid';

const props = defineProps({
    files: {
        type: Array,
        required: true
    }
});

// Sort files by date, newest first
const sortedFiles = computed(() => {
    return [...props.files].sort((a, b) => {
        return new Date(b.uploaded_at) - new Date(a.uploaded_at);
    });
});

const getFileIcon = (mimeType) => {
    // Documents
    if (mimeType === 'application/pdf') return DocumentTextIcon;
    if (mimeType.includes('word') || mimeType.includes('document')) return DocumentIcon;
    if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) return TableCellsIcon;
    if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) return PresentationChartBarIcon;
    
    // Media
    if (mimeType.startsWith('image/')) return PhotoIcon;
    if (mimeType.startsWith('video/')) return VideoCameraIcon;
    if (mimeType.startsWith('audio/')) return MusicalNoteIcon;
    
    // Archives
    if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('tar') || mimeType.includes('7z')) {
        return ArchiveBoxIcon;
    }
    
    // Code
    if (mimeType.includes('code') || mimeType.includes('text/plain') || 
        mimeType.includes('json') || mimeType.includes('xml')) {
        return CodeBracketIcon;
    }
    
    // Default
    return DocumentArrowDownIcon;
};

const getIconColor = (mimeType) => {
    // Documents
    if (mimeType === 'application/pdf') return 'text-red-500';
    if (mimeType.includes('word') || mimeType.includes('document')) return 'text-blue-500';
    if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) return 'text-green-500';
    if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) return 'text-orange-500';
    
    // Media
    if (mimeType.startsWith('image/')) return 'text-purple-500';
    if (mimeType.startsWith('video/')) return 'text-pink-500';
    if (mimeType.startsWith('audio/')) return 'text-yellow-500';
    
    // Archives
    if (mimeType.includes('zip') || mimeType.includes('rar') || 
        mimeType.includes('tar') || mimeType.includes('7z')) {
        return 'text-amber-500';
    }
    
    // Code
    if (mimeType.includes('code') || mimeType.includes('text/plain') || 
        mimeType.includes('json') || mimeType.includes('xml')) {
        return 'text-emerald-500';
    }
    
    // Default
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
</script>

<template>
    <div class="mt-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Uploaded Files</h3>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <ul v-if="sortedFiles.length" class="divide-y divide-gray-200 dark:divide-gray-700">
                <li v-for="file in sortedFiles" :key="file.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <div class="flex items-center space-x-4">
                        <component 
                            :is="getFileIcon(file.type)" 
                            class="h-8 w-8"
                            :class="getIconColor(file.type)"
                        />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                {{ file.name }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ formatFileSize(file.size) }} • Uploaded {{ formatDate(file.uploaded_at) }}
                            </p>
                        </div>
                        <div>
                            <a
                                :href="route('quickdrop.download', file.id)"
                                class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 dark:text-indigo-100 dark:bg-indigo-800 dark:hover:bg-indigo-700 focus:outline-none transition ease-in-out duration-150"
                            >
                                Download
                            </a>
                        </div>
                    </div>
                </li>
            </ul>
            <div v-else class="p-4 text-center text-gray-500 dark:text-gray-400">
                No files uploaded yet
            </div>
        </div>
    </div>
</template> 