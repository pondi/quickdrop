<template>
    <div 
        class="file-card-3d glass-card p-4 cursor-pointer group"
        @click="$emit('click')"
    >
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0">
                <div 
                    class="w-12 h-12 rounded-lg flex items-center justify-center"
                    :style="{ background: fileTypeGradient }"
                >
                    <Icon :name="fileIcon" :size="24" class="text-white" />
                </div>
            </div>
            
            <div class="flex-1 min-w-0">
                <h3 class="text-sm font-medium text-text-primary truncate group-hover:text-white transition-colors">
                    {{ file.name }}
                </h3>
                <div class="mt-1 flex items-center space-x-3 text-xs text-text-secondary">
                    <span>{{ formatFileSize(file.size) }}</span>
                    <span>•</span>
                    <span>{{ fileType }}</span>
                    <span v-if="file.uploadedAt">•</span>
                    <span v-if="file.uploadedAt">{{ formatDate(file.uploadedAt) }}</span>
                </div>
                
                <div v-if="file.isEncrypted" class="mt-2">
                    <span class="inline-flex items-center space-x-1 text-xs text-green-400">
                        <Icon name="lock" :size="12" />
                        <span>Encrypted</span>
                    </span>
                </div>
            </div>
            
            <div v-if="showActions" class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                <Button 
                    variant="ghost" 
                    size="sm" 
                    icon="download"
                    @click.stop="$emit('download')"
                    class="!p-2"
                />
                <Button 
                    v-if="showDelete"
                    variant="ghost" 
                    size="sm" 
                    icon="trash2"
                    @click.stop="$emit('delete')"
                    class="!p-2 text-red-400 hover:text-red-300"
                />
            </div>
        </div>
        
        <div v-if="showProgress && file.progress !== undefined" class="mt-3">
            <div class="w-full bg-white/10 rounded-full h-1.5 overflow-hidden">
                <div 
                    class="h-full bg-gradient-primary transition-all duration-300 ease-out"
                    :style="{ width: `${file.progress}%` }"
                />
            </div>
            <p class="mt-1 text-xs text-text-secondary">
                {{ file.progress }}% uploaded
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import Icon from './Icon.vue'
import Button from './Button.vue'

const props = defineProps({
    file: {
        type: Object,
        required: true
    },
    showActions: {
        type: Boolean,
        default: true
    },
    showDelete: {
        type: Boolean,
        default: false
    },
    showProgress: {
        type: Boolean,
        default: false
    }
})

defineEmits(['click', 'download', 'delete'])

const fileTypeMap = {
    'application/pdf': { icon: 'fileText', type: 'PDF', gradient: 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' },
    'image/jpeg': { icon: 'image', type: 'Image', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    'image/jpg': { icon: 'image', type: 'Image', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    'image/png': { icon: 'image', type: 'Image', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    'image/gif': { icon: 'image', type: 'GIF', gradient: 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)' },
    'video/mp4': { icon: 'video', type: 'Video', gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)' },
    'video/avi': { icon: 'video', type: 'Video', gradient: 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)' },
    'audio/mpeg': { icon: 'music', type: 'Audio', gradient: 'linear-gradient(135deg, #d299c2 0%, #fef9d7 100%)' },
    'audio/mp3': { icon: 'music', type: 'Audio', gradient: 'linear-gradient(135deg, #d299c2 0%, #fef9d7 100%)' },
    'application/zip': { icon: 'archive', type: 'Archive', gradient: 'linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%)' },
    'application/x-zip-compressed': { icon: 'archive', type: 'Archive', gradient: 'linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%)' },
    'text/plain': { icon: 'fileText', type: 'Text', gradient: 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)' },
    'application/msword': { icon: 'fileText', type: 'Document', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': { icon: 'fileText', type: 'Document', gradient: 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)' },
    'application/vnd.ms-excel': { icon: 'sheet', type: 'Spreadsheet', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' },
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': { icon: 'sheet', type: 'Spreadsheet', gradient: 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)' }
}

const fileInfo = computed(() => {
    return fileTypeMap[props.file.type] || { 
        icon: 'file', 
        type: 'File', 
        gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' 
    }
})

const fileIcon = computed(() => fileInfo.value.icon)
const fileType = computed(() => fileInfo.value.type)
const fileTypeGradient = computed(() => fileInfo.value.gradient)

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (date) => {
    const d = new Date(date)
    const now = new Date()
    const diff = now - d
    
    if (diff < 60000) return 'Just now'
    if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`
    if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`
    if (diff < 604800000) return `${Math.floor(diff / 86400000)}d ago`
    
    return d.toLocaleDateString()
}
</script>