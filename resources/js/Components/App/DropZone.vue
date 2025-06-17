<template>
    <div 
        class="drop-zone"
        :class="{ 'active': isDragging }"
        @drop="handleDrop"
        @dragover.prevent
        @dragenter.prevent="handleDragEnter"
        @dragleave.prevent="handleDragLeave"
    >
        <input 
            ref="fileInput"
            type="file"
            :multiple="multiple"
            :accept="accept"
            class="hidden"
            @change="handleFileSelect"
        />
        
        <div class="p-8 text-center">
            <div class="mb-4 animate-float">
                <Icon 
                    :name="isDragging ? 'download' : 'upload'" 
                    :size="48" 
                    class="mx-auto text-text-secondary"
                />
            </div>
            
            <h3 class="text-lg font-medium text-text-primary mb-2">
                {{ isDragging ? 'Drop files here' : 'Drop files or click to browse' }}
            </h3>
            
            <p class="text-sm text-text-secondary mb-4">
                {{ description || `Supports ${accept || 'all file types'}` }}
            </p>
            
            <Button 
                variant="primary"
                icon="upload"
                @click="$refs.fileInput.click()"
            >
                Select Files
            </Button>
            
            <div v-if="maxSize" class="mt-4 text-xs text-text-muted">
                Maximum file size: {{ formatFileSize(maxSize) }}
            </div>
        </div>
        
        <TransitionGroup 
            v-if="files.length > 0 && showFiles"
            name="file-list"
            tag="div"
            class="border-t border-white/10 p-4 space-y-2"
        >
            <FileCard 
                v-for="(file, index) in files"
                :key="file.name + index"
                :file="file"
                :show-actions="false"
                :show-progress="true"
                :data-index="index"
                class="!p-3"
            />
        </TransitionGroup>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import Icon from './Icon.vue'
import Button from './Button.vue'
import FileCard from './FileCard.vue'

const props = defineProps({
    multiple: {
        type: Boolean,
        default: true
    },
    accept: {
        type: String,
        default: null
    },
    maxSize: {
        type: Number,
        default: null
    },
    description: {
        type: String,
        default: null
    },
    showFiles: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['files-added', 'file-removed'])

const fileInput = ref(null)
const isDragging = ref(false)
const files = ref([])
const dragCounter = ref(0)

const handleDragEnter = () => {
    dragCounter.value++
    isDragging.value = true
}

const handleDragLeave = () => {
    dragCounter.value--
    if (dragCounter.value === 0) {
        isDragging.value = false
    }
}

const handleDrop = (e) => {
    e.preventDefault()
    isDragging.value = false
    dragCounter.value = 0
    
    const droppedFiles = Array.from(e.dataTransfer.files)
    processFiles(droppedFiles)
}

const handleFileSelect = (e) => {
    const selectedFiles = Array.from(e.target.files)
    processFiles(selectedFiles)
}

const processFiles = (newFiles) => {
    const validFiles = newFiles.filter(file => {
        if (props.maxSize && file.size > props.maxSize) {
            console.warn(`File ${file.name} exceeds maximum size`)
            return false
        }
        return true
    })
    
    const processedFiles = validFiles.map(file => ({
        name: file.name,
        size: file.size,
        type: file.type,
        progress: 0,
        file: file
    }))
    
    if (props.multiple) {
        files.value = [...files.value, ...processedFiles]
    } else {
        files.value = processedFiles.slice(0, 1)
    }
    
    emit('files-added', processedFiles)
    
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

const removeFile = (index) => {
    const removed = files.value.splice(index, 1)
    emit('file-removed', removed[0])
}

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

defineExpose({
    files,
    removeFile,
    clearFiles: () => files.value = []
})
</script>

<style scoped>
.file-list-enter-active,
.file-list-leave-active {
    transition: all 0.3s ease;
}

.file-list-enter-from {
    opacity: 0;
    transform: translateY(-20px);
}

.file-list-leave-to {
    opacity: 0;
    transform: translateX(20px);
}

.file-list-move {
    transition: transform 0.3s ease;
}
</style>