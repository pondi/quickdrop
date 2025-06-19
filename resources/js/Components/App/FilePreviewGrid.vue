<template>
  <div>
    <!-- File Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <TransitionGroup name="file-grid">
        <div
          v-for="file in files"
          :key="file.id"
          class="group relative bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden hover:shadow-lg transition-all duration-200 cursor-pointer"
          @click="openPreview(file)"
        >
          <!-- File Preview Thumbnail -->
          <div class="aspect-w-16 aspect-h-9 bg-gray-100 dark:bg-gray-900 relative overflow-hidden">
            <!-- Image Thumbnail -->
            <img
              v-if="isImage(file.type) && file.thumbnail_url"
              :src="file.thumbnail_url"
              :alt="file.name"
              class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-200"
              loading="lazy"
            />
            
            <!-- File Type Icon -->
            <div v-else class="flex items-center justify-center h-full">
              <div
                :class="[
                  'w-16 h-16 rounded-xl flex items-center justify-center',
                  getFileIconBackground(file.type)
                ]"
              >
                <component :is="getFileIcon(file.type)" class="w-8 h-8 text-white" />
              </div>
            </div>

            <!-- Quick Actions Overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200">
              <div class="absolute bottom-2 right-2 flex items-center space-x-2">
                <button
                  v-if="canPreview(file.type)"
                  @click.stop="openPreview(file)"
                  class="p-2 bg-white/90 dark:bg-gray-800/90 rounded-lg hover:bg-white dark:hover:bg-gray-800 transition-colors"
                  title="Preview"
                >
                  <EyeIcon class="w-4 h-4 text-gray-700 dark:text-gray-300" />
                </button>
                <button
                  @click.stop="downloadFile(file)"
                  class="p-2 bg-white/90 dark:bg-gray-800/90 rounded-lg hover:bg-white dark:hover:bg-gray-800 transition-colors"
                  title="Download"
                >
                  <ArrowDownTrayIcon class="w-4 h-4 text-gray-700 dark:text-gray-300" />
                </button>
              </div>
            </div>
          </div>

          <!-- File Info -->
          <div class="p-4">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate" :title="file.name">
              {{ file.name }}
            </h3>
            <div class="mt-1 flex items-center justify-between">
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ formatFileSize(file.size) }}
              </p>
              <p class="text-xs text-gray-400 dark:text-gray-500">
                {{ formatRelativeTime(file.created_at) }}
              </p>
            </div>
            
            <!-- Progress indicator for processing files -->
            <div v-if="file.status === 'processing'" class="mt-2">
              <div class="h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-500 animate-pulse" style="width: 50%"></div>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Processing...</p>
            </div>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <!-- Empty State -->
    <div v-if="files.length === 0" class="text-center py-12">
      <FolderOpenIcon class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No files</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Upload some files to see them here
      </p>
    </div>

    <!-- File Preview Modal -->
    <FilePreviewModal
      :is-open="showPreviewModal"
      :file="selectedFile"
      :can-download="true"
      @close="closePreview"
      @download="downloadFile"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { TransitionGroup } from 'vue'
import {
  EyeIcon,
  ArrowDownTrayIcon,
  FolderOpenIcon,
  DocumentIcon,
  PhotoIcon,
  VideoCameraIcon,
  MusicalNoteIcon,
  DocumentTextIcon,
  CodeBracketIcon,
  ArchiveBoxIcon,
} from '@heroicons/vue/24/outline'
import FilePreviewModal from './FilePreviewModal.vue'

const props = defineProps({
  files: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['download', 'preview'])

// State
const showPreviewModal = ref(false)
const selectedFile = ref(null)

// Open preview modal
function openPreview(file) {
  if (canPreview(file.type)) {
    selectedFile.value = file
    showPreviewModal.value = true
    emit('preview', file)
  }
}

// Close preview modal
function closePreview() {
  showPreviewModal.value = false
  selectedFile.value = null
}

// Download file
function downloadFile(file) {
  emit('download', file)
  
  // Simple download implementation
  if (file.url) {
    const link = document.createElement('a')
    link.href = file.url
    link.download = file.name
    link.click()
  }
}

// Check if file type can be previewed
function canPreview(mimeType) {
  if (!mimeType) return false
  
  const previewableTypes = [
    'image/',
    'video/',
    'audio/',
    'application/pdf',
    'text/',
    'application/json',
    'application/xml',
    'application/javascript',
    'application/x-sh',
    'application/x-yaml'
  ]
  
  return previewableTypes.some(type => mimeType.includes(type))
}

// Check if file is an image
function isImage(mimeType) {
  return mimeType?.startsWith('image/')
}

// Format helpers
function formatFileSize(bytes) {
  const sizes = ['B', 'KB', 'MB', 'GB']
  if (bytes === 0) return '0 B'
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

function formatRelativeTime(dateString) {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = now - date
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) {
    const diffHours = Math.floor(diffTime / (1000 * 60 * 60))
    if (diffHours === 0) {
      const diffMinutes = Math.floor(diffTime / (1000 * 60))
      if (diffMinutes === 0) {
        return 'Just now'
      }
      return `${diffMinutes}m ago`
    }
    return `${diffHours}h ago`
  } else if (diffDays === 1) {
    return 'Yesterday'
  } else if (diffDays < 7) {
    return `${diffDays}d ago`
  } else {
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric'
    })
  }
}

function getFileIcon(mimeType) {
  if (!mimeType) return DocumentIcon
  
  if (mimeType.startsWith('image/')) return PhotoIcon
  if (mimeType.startsWith('video/')) return VideoCameraIcon
  if (mimeType.startsWith('audio/')) return MusicalNoteIcon
  if (mimeType.includes('pdf')) return DocumentTextIcon
  if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('7z')) return ArchiveBoxIcon
  if (mimeType.includes('javascript') || mimeType.includes('json') || mimeType.includes('xml')) return CodeBracketIcon
  
  return DocumentIcon
}

function getFileIconBackground(mimeType) {
  if (!mimeType) return 'bg-gray-500'
  
  if (mimeType.startsWith('image/')) return 'bg-blue-500'
  if (mimeType.startsWith('video/')) return 'bg-purple-500'
  if (mimeType.startsWith('audio/')) return 'bg-pink-500'
  if (mimeType.includes('pdf')) return 'bg-red-500'
  if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('7z')) return 'bg-yellow-500'
  if (mimeType.includes('javascript') || mimeType.includes('json') || mimeType.includes('xml')) return 'bg-green-500'
  
  return 'bg-gray-500'
}
</script>

<style scoped>
/* Grid transitions */
.file-grid-enter-active,
.file-grid-leave-active {
  transition: all 0.3s ease;
}

.file-grid-enter-from {
  opacity: 0;
  transform: scale(0.8);
}

.file-grid-leave-to {
  opacity: 0;
  transform: scale(0.8);
}

.file-grid-move {
  transition: transform 0.3s ease;
}

/* Aspect ratio utility */
.aspect-w-16 {
  position: relative;
  padding-bottom: 56.25%;
}

.aspect-h-9 {
  position: absolute;
  inset: 0;
}
</style>