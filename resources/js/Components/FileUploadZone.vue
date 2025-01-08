<template>
    <div class="dropzone-container">
        <div
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            class="relative"
        >
            <div
                class="mt-2 flex flex-col rounded-lg border border-dashed px-6 py-6 transition-all duration-300"
                :class="[
                    isDragging 
                        ? 'border-indigo-500 bg-indigo-50 dark:border-indigo-400 dark:bg-indigo-950' 
                        : selectedFiles.length 
                            ? 'border-gray-200 dark:border-gray-800' 
                            : 'border-gray-900/25 dark:border-gray-700'
                ]"
            >
                <!-- Empty State Upload Area -->
                <TransitionGroup 
                    name="fade"
                    tag="div"
                    class="text-center"
                >
                    <template v-if="!selectedFiles.length || allFilesUploaded">
                        <PhotoIcon key="icon" class="mx-auto h-12 w-12 text-gray-300" aria-hidden="true" />
                        <div key="upload-text" class="mt-4 flex justify-center text-sm leading-6 text-gray-600 dark:text-gray-400">
                            <label
                                for="file-upload"
                                class="relative cursor-pointer rounded-md font-semibold text-indigo-600 dark:text-indigo-400 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500"
                            >
                                <span>Upload files</span>
                                <input 
                                    id="file-upload" 
                                    name="file-upload" 
                                    type="file" 
                                    class="sr-only" 
                                    multiple
                                    @change="handleFileSelect"
                                >
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <div key="file-info" class="mt-2 flex flex-col items-center space-y-1">
                            <p class="text-xs leading-5 text-gray-600 dark:text-gray-400">
                                {{ allowedTypesText }}
                            </p>
                            <p class="text-xs leading-5 text-gray-600 dark:text-gray-400">
                                Max file size: {{ formatFileSize(maxFileSize) }}
                            </p>
                        </div>
                    </template>
                </TransitionGroup>

                <!-- Selected Files List -->
                <TransitionGroup 
                    v-if="selectedFiles.length"
                    name="file-list"
                    tag="ul"
                    class="divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <li 
                        v-for="file in selectedFiles" 
                        :key="file.name"
                        class="file-list-item py-3 first:pt-0 last:pb-0"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <component 
                                    :is="getFileIcon(file.type)"
                                    :class="[getIconColor(file.type), 'h-8 w-8 flex-shrink-0']"
                                />
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ file.name }}
                                        <span v-if="file.isDuplicateChecking" class="ml-2 text-xs text-gray-500">
                                            (checking for duplicates...)
                                        </span>
                                        <span v-else-if="file.isExactDuplicate" class="ml-2 text-xs text-red-500">
                                            (exact duplicate - will be skipped)
                                        </span>
                                        <span v-else-if="file.duplicateType === 'name'" class="ml-2 text-xs text-amber-500">
                                            (will be saved as v{{ file.nextVersion }})
                                        </span>
                                    </h4>
                                    <div class="mt-1 flex items-center space-x-2 text-xs text-gray-500">
                                        <span>{{ formatFileSize(file.size) }}</span>
                                        <span v-if="file.progress !== undefined">
                                            • {{ file.progress }}% uploaded
                                        </span>
                                        <span v-if="file.error" class="text-red-500">
                                            • {{ file.error }}
                                        </span>
                                    </div>
                                    <div v-if="file.progress !== undefined && file.progress < 100" class="mt-2 w-full max-w-xs">
                                        <div class="h-1 bg-gray-200 rounded">
                                            <div 
                                                class="h-1 bg-indigo-600 rounded transition-all duration-300"
                                                :style="{ width: `${file.progress}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button
                                v-if="!file.uploading"
                                @click="removeFile(file)"
                                class="text-gray-400 hover:text-gray-500 flex-shrink-0"
                            >
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </li>
                </TransitionGroup>

                <!-- Add Files Button -->
                <TransitionGroup name="fade">
                    <div 
                        v-if="selectedFiles.length" 
                        key="add-more"
                        class="mt-4 flex justify-center"
                    >
                        <label
                            for="file-upload-more"
                            class="relative cursor-pointer rounded-md px-3 py-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2"
                        >
                            <span>Add more files</span>
                            <input 
                                id="file-upload-more" 
                                name="file-upload-more" 
                                type="file" 
                                class="sr-only" 
                                multiple
                                @change="handleFileSelect"
                            >
                        </label>
                    </div>
                </TransitionGroup>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { PhotoIcon, DocumentIcon, DocumentTextIcon, XMarkIcon, TableCellsIcon, MusicalNoteIcon, VideoCameraIcon } from '@heroicons/vue/24/outline';
import { calculateFileHash } from '@/Services/FileHashService';

const props = defineProps({
    allowedMimeTypes: {
        type: Array,
        default: () => []
    },
    maxFileSize: {
        type: Number,
        required: false,
        default: null
    },
    completedFiles: {
        type: Array,
        default: () => []
    },
    uploadRequest: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['upload-files']);

const isDragging = ref(false);
const selectedFiles = ref([]);
const fileInput = ref(null);

const allowedTypesText = computed(() => {
    if (!props.allowedMimeTypes?.length) return 'All file types allowed';
    
    // Group mime types by category
    const typeGroups = props.allowedMimeTypes.reduce((acc, type) => {
        if (type.startsWith('image/')) acc.images = true;
        else if (type.startsWith('application/pdf')) acc.pdf = true;
        else if (type.includes('word')) acc.word = true;
        else if (type.includes('excel') || type.includes('spreadsheet')) acc.excel = true;
        else if (type.includes('zip') || type.includes('rar')) acc.archives = true;
        else if (type === 'text/plain') acc.text = true;
        return acc;
    }, {});

    // Convert to human-readable format
    const types = [];
    if (typeGroups.images) types.push('Images');
    if (typeGroups.pdf) types.push('PDF');
    if (typeGroups.word) types.push('Word documents');
    if (typeGroups.excel) types.push('Excel spreadsheets');
    if (typeGroups.archives) types.push('Archives');
    if (typeGroups.text) types.push('Text files');

    return `Allowed types: ${types.join(', ')}`;
});

const checkDuplicateStatus = (newFile) => {
    if (import.meta.env.DEV) {
        console.log('Checking duplicate status for:', {
            fileName: newFile.name,
            file_hash: newFile.file_hash,
            requestId: props.uploadRequest.id
        });

        // Log all completed files for debugging
        console.log('All completed files:', props.completedFiles.map(f => ({
            name: f.name,
            file_hash: f.file_hash,
            request_id: f.request_id
        })));
    }

    // Initialize result object
    const result = {
        isDuplicate: false,
        isExactDuplicate: false,
        nextVersion: undefined,
        message: null
    };

    // First check the filelist (uploaded files)
    const filelistMatches = props.completedFiles.filter(f => {
        const nameMatch = f.name === newFile.name;
        const requestMatch = String(f.request_id) === String(props.uploadRequest.id);
        
        if (import.meta.env.DEV) {
            console.log('Checking file:', {
                fileName: f.name,
                newFileName: newFile.name,
                nameMatch,
                fileRequestId: f.request_id,
                currentRequestId: props.uploadRequest.id,
                requestMatch,
                file_hash: f.file_hash
            });
        }
        
        return nameMatch && requestMatch;
    });

    if (import.meta.env.DEV && filelistMatches.length > 0) {
        console.log('Found filelist matches:', {
            fileName: newFile.name,
            matches: filelistMatches.map(f => ({
                name: f.name,
                file_hash: f.file_hash,
                request_id: f.request_id,
                version: f.version
            }))
        });
    }

    // Check for exact duplicates in filelist
    const exactDuplicateInFilelist = filelistMatches.some(f => {
        const isMatch = f.file_hash === newFile.file_hash;
        if (import.meta.env.DEV) {
            console.log('Checking hash match:', {
                fileName: f.name,
                existingHash: f.file_hash,
                newHash: newFile.file_hash,
                isMatch,
                requestId: f.request_id
            });
        }
        return isMatch;
    });

    if (exactDuplicateInFilelist) {
        result.isDuplicate = true;
        result.isExactDuplicate = true;
        result.message = 'Exact duplicate file already exists in uploaded files - will be skipped';
        
        if (import.meta.env.DEV) {
            console.log('Found exact duplicate in filelist:', {
                fileName: newFile.name,
                file_hash: newFile.file_hash
            });
        }
        return result;
    }

    // Then check the dropzone (only files that are already processed and not marked as duplicates)
    const currentIndex = selectedFiles.value.indexOf(newFile);
    
    // Only check files that were added before this one
    const dropzoneMatches = selectedFiles.value.filter(f => {
        const fileIndex = selectedFiles.value.indexOf(f);
        return f !== newFile && // Don't match with self
               fileIndex < currentIndex && // Only check files added before this one
               f.name === newFile.name &&
               f.file_hash !== undefined && // Only consider files that have been processed
               !f.isExactDuplicate && // Don't consider files that will be skipped
               !f.error; // Don't consider files with errors
    });

    if (import.meta.env.DEV) {
        console.log('Checking dropzone matches:', {
            fileName: newFile.name,
            currentIndex,
            dropzoneMatches: dropzoneMatches.map(f => ({
                name: f.name,
                index: selectedFiles.value.indexOf(f),
                file_hash: f.file_hash
            }))
        });
    }

    // Check for exact duplicates in dropzone
    const exactDuplicateInDropzone = dropzoneMatches.some(f => {
        const isMatch = f.file_hash === newFile.file_hash;
        if (isMatch && import.meta.env.DEV) {
            console.log('Found exact match in dropzone:', {
                fileName: newFile.name,
                file_hash: newFile.file_hash,
                matching_file_hash: f.file_hash
            });
        }
        return isMatch;
    });

    if (exactDuplicateInDropzone) {
        result.isDuplicate = true;
        result.isExactDuplicate = true;
        result.message = 'Exact duplicate file is already in the upload list - will be skipped';
        
        if (import.meta.env.DEV) {
            console.log('Found exact duplicate in dropzone:', {
                fileName: newFile.name,
                file_hash: newFile.file_hash
            });
        }
        return result;
    }

    // If we get here, check for name duplicates and calculate next version
    if (filelistMatches.length > 0 || dropzoneMatches.length > 0) {
        const filelistVersions = filelistMatches.map(f => f.version || 1);
        const dropzoneVersions = dropzoneMatches.map(f => f.nextVersion || 1);
        const allVersions = [...filelistVersions, ...dropzoneVersions];
        
        result.isDuplicate = true;
        result.nextVersion = allVersions.length > 0 ? Math.max(...allVersions) + 1 : 2;
        result.message = `Name duplicate - will be saved as v${result.nextVersion}`;

        if (import.meta.env.DEV) {
            console.log('Found name duplicate:', {
                fileName: newFile.name,
                nextVersion: result.nextVersion,
                existingVersions: allVersions
            });
        }
        return result;
    }

    if (import.meta.env.DEV) {
        console.log('No duplicates found for:', newFile.name);
    }

    return result;
};

const handleDrop = (event) => {
    isDragging.value = false;
    const files = Array.from(event.dataTransfer.files);
    addFiles(files);
};

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    addFiles(files);
    // Safely reset the input value
    if (event.target) {
        event.target.value = '';
    }
};

const addFiles = async (files) => {
    console.log('Adding files:', {
        fileCount: files.length,
        currentRequestId: props.uploadRequest.id,
        existingFiles: selectedFiles.value.length
    });

    const validFiles = files.filter(file => {
        // Check file size
        if (props.maxFileSize && file.size > props.maxFileSize) {
            alert(`File ${file.name} is too large. Maximum size is ${formatFileSize(props.maxFileSize)}`);
            return false;
        }

        // Check mime type
        if (props.allowedMimeTypes?.length && !props.allowedMimeTypes.includes(file.type)) {
            alert(`File type ${file.type} is not allowed. ${allowedTypesText.value}`);
            return false;
        }

        return true;
    });

    // Process files one at a time in sequence
    for (const file of validFiles) {
        const fileData = {
            file,
            name: file.name,
            size: file.size,
            type: file.type,
            progress: undefined,
            error: undefined,
            uploading: false,
            file_hash: undefined,
            nextVersion: undefined,
            isDuplicateChecking: true,
            isExactDuplicate: false,
            duplicateType: null
        };

        // Add file to the list immediately to show progress
        selectedFiles.value.push(fileData);

        try {
            // Calculate hash first
            fileData.file_hash = await calculateFileHash(fileData.file);
            
            if (import.meta.env.DEV) {
                console.log('Calculated hash for file:', {
                    fileName: fileData.name,
                    file_hash: fileData.file_hash
                });
            }
            
            // Check duplicate status
            const duplicateStatus = checkDuplicateStatus(fileData);
            
            // Update file metadata based on duplicate status
            fileData.isExactDuplicate = duplicateStatus.isExactDuplicate;
            fileData.nextVersion = duplicateStatus.nextVersion;
            fileData.error = duplicateStatus.isExactDuplicate ? duplicateStatus.message : undefined;
            fileData.duplicateType = duplicateStatus.isDuplicate && !duplicateStatus.isExactDuplicate ? 'name' : null;
            
        } catch (error) {
            console.error('Error processing file:', error);
            fileData.error = 'Error processing file';
        } finally {
            fileData.isDuplicateChecking = false;
        }

        // Update the UI after each file is processed
        selectedFiles.value = [...selectedFiles.value];
    }
    
    // Only emit non-exact-duplicate files
    emit('upload-files', selectedFiles.value.filter(f => !f.isExactDuplicate));
};

const removeFile = (file) => {
    selectedFiles.value = selectedFiles.value.filter(f => f !== file);
};

const formatFileSize = (bytes) => {
    if (!bytes && bytes !== 0) return 'No limit';
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
};

const getFileIcon = (mimeType) => {
    if (mimeType.startsWith('image/')) return PhotoIcon;
    if (mimeType.startsWith('video/')) return VideoCameraIcon;
    if (mimeType.startsWith('audio/')) return MusicalNoteIcon;
    if (mimeType === 'application/pdf') return DocumentTextIcon;
    if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) return TableCellsIcon;
    return DocumentIcon;
};

const getIconColor = (mimeType) => {
    if (mimeType.startsWith('image/')) return 'text-purple-500';
    if (mimeType.startsWith('video/')) return 'text-red-500';
    if (mimeType.startsWith('audio/')) return 'text-pink-500';
    if (mimeType === 'application/pdf') return 'text-red-600';
    if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) return 'text-green-600';
    if (mimeType.includes('word') || mimeType.includes('document')) return 'text-blue-600';
    return 'text-gray-500';
};

// Computed property to check if all files are uploaded
const allFilesUploaded = computed(() => {
    return selectedFiles.value.length === 0 || 
           selectedFiles.value.every(file => file.progress === 100);
});

// Watch for completed files to update UI
watch(() => props.completedFiles, (newFiles) => {
    if (import.meta.env.DEV) {
        console.log('Completed files updated:', {
            files: newFiles.map(f => ({
                name: f.name,
                request_id: f.request_id
            })),
            currentRequestId: props.uploadRequest.id
        });
    }
}, { immediate: true, deep: true });

// Watch for completed uploads to remove files
watch(selectedFiles, (files) => {
    files.forEach(file => {
        if (file.progress === 100) {
            setTimeout(() => {
                selectedFiles.value = selectedFiles.value.filter(f => f !== file);
            }, 500); // Matches the animation duration
        }
    });
}, { deep: true });
</script>

<style>
/* Base transitions for file list */
.file-list-enter-active,
.file-list-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.file-list-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.file-list-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

.file-list-move {
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Moving files animation */
.file-list-item.moving-to-uploaded {
    animation: move-to-filelist 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    pointer-events: none;
}

@keyframes move-to-filelist {
    0% {
        transform: translateY(0);
        opacity: 1;
    }
    100% {
        transform: translateY(200%);
        opacity: 0;
    }
}

/* Upload area fade transitions - synchronized with file movements */
.fade-enter-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    transition-delay: 0.4s; /* Slightly shorter delay to start fading in as files are moving out */
    max-height: 200px;
    opacity: 1;
    margin-top: 0;
}

.fade-leave-active {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    max-height: 200px;
    opacity: 1;
    margin-top: 0;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    max-height: 0;
    margin-top: -20px;
    transform: translateY(-10px);
}

.fade-move {
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Container transitions */
.dropzone-container > div > div {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Ensure the container has a proper z-index context */
.dropzone-container {
    position: relative;
    z-index: 10;
}

/* Ensure the file list appears below the dropzone */
.filelist-container {
    position: relative;
    z-index: 5;
}

@keyframes highlight {
    0%, 100% {
        background-color: transparent;
    }
    50% {
        background-color: rgba(79, 70, 229, 0.1);
    }
}

.file-list-item:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.dark .file-list-item:hover {
    background-color: rgba(255, 255, 255, 0.02);
}

/* Adjust padding when files are present */
.dropzone-container > div > div:has(.file-list-item) {
    padding-top: 1rem;
    padding-bottom: 1rem;
}

/* Ensure smooth transitions for all moving elements */
.text-center {
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
</style> 