<template>
  <TransitionGroup name="upload-list" tag="div" class="space-y-3">
    <div
      v-for="file in uploadingFiles"
      :key="file.id"
      class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden"
    >
      <div class="p-4">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center space-x-3 flex-1 min-w-0">
            <div class="flex-shrink-0">
              <div
                :class="[
                  'w-10 h-10 rounded-lg flex items-center justify-center',
                  getFileIconBackground(file.type)
                ]"
              >
                <component :is="getFileIcon(file.type)" class="w-5 h-5 text-white" />
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                {{ file.name }}
              </p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ formatFileSize(file.size) }}
              </p>
            </div>
          </div>
          
          <div class="flex items-center space-x-2">
            <TransitionGroup name="status" mode="out-in">
              <div
                v-if="file.status === 'uploading'"
                key="uploading"
                class="flex items-center space-x-2"
              >
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ file.progress }}%
                </span>
                <button
                  @click="$emit('cancel', file.id)"
                  class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                >
                  <XMarkIcon class="w-4 h-4 text-gray-500" />
                </button>
              </div>
              
              <div
                v-else-if="file.status === 'completed'"
                key="completed"
                class="flex items-center space-x-2"
              >
                <CheckCircleIcon class="w-5 h-5 text-green-500 animate-scale-in" />
                <span class="text-sm text-green-600 dark:text-green-400">Complete</span>
              </div>
              
              <div
                v-else-if="file.status === 'error'"
                key="error"
                class="flex items-center space-x-2"
              >
                <XCircleIcon class="w-5 h-5 text-red-500" />
                <button
                  @click="$emit('retry', file.id)"
                  class="text-sm text-red-600 dark:text-red-400 hover:underline"
                >
                  Retry
                </button>
              </div>
            </TransitionGroup>
          </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="relative">
          <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
            <div
              class="h-full transition-all duration-300 ease-out relative overflow-hidden"
              :class="[
                file.status === 'error' ? 'bg-red-500' : 
                file.status === 'completed' ? 'bg-green-500' : 
                'bg-gradient-to-r from-indigo-500 to-purple-600'
              ]"
              :style="{ width: `${file.progress}%` }"
            >
              <!-- Animated shimmer effect -->
              <div
                v-if="file.status === 'uploading'"
                class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer"
              ></div>
            </div>
          </div>
          
          <!-- Speed and time remaining -->
          <div
            v-if="file.status === 'uploading' && file.speed"
            class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400"
          >
            <span>{{ formatSpeed(file.speed) }}</span>
            <span v-if="file.timeRemaining">{{ formatTimeRemaining(file.timeRemaining) }} remaining</span>
          </div>
        </div>
        
        <!-- Error message -->
        <p
          v-if="file.status === 'error' && file.error"
          class="mt-2 text-xs text-red-600 dark:text-red-400"
        >
          {{ file.error }}
        </p>
      </div>
    </div>
  </TransitionGroup>
  
  <!-- Overall progress summary -->
  <div v-if="uploadingFiles.length > 1" class="mt-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
    <div class="flex items-center justify-between mb-2">
      <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
        Total Progress
      </h4>
      <span class="text-sm font-medium text-gray-900 dark:text-white">
        {{ overallProgress }}%
      </span>
    </div>
    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
      <div
        class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 transition-all duration-300"
        :style="{ width: `${overallProgress}%` }"
      ></div>
    </div>
    <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
      <span>{{ completedCount }} of {{ uploadingFiles.length }} files</span>
      <span v-if="overallSpeed > 0">{{ formatSpeed(overallSpeed) }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  DocumentIcon,
  PhotoIcon,
  VideoCameraIcon,
  MusicalNoteIcon,
  DocumentTextIcon,
  CodeBracketIcon,
  ArchiveBoxIcon,
  CheckCircleIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  uploadingFiles: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['cancel', 'retry'])

// Computed properties
const overallProgress = computed(() => {
  if (props.uploadingFiles.length === 0) return 0
  const totalProgress = props.uploadingFiles.reduce((sum, file) => sum + file.progress, 0)
  return Math.round(totalProgress / props.uploadingFiles.length)
})

const completedCount = computed(() => {
  return props.uploadingFiles.filter(file => file.status === 'completed').length
})

const overallSpeed = computed(() => {
  const uploadingFiles = props.uploadingFiles.filter(file => file.status === 'uploading' && file.speed)
  if (uploadingFiles.length === 0) return 0
  return uploadingFiles.reduce((sum, file) => sum + file.speed, 0)
})

// Helper functions
function formatFileSize(bytes) {
  const sizes = ['B', 'KB', 'MB', 'GB']
  if (bytes === 0) return '0 B'
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

function formatSpeed(bytesPerSecond) {
  return formatFileSize(bytesPerSecond) + '/s'
}

function formatTimeRemaining(seconds) {
  if (seconds < 60) return `${Math.round(seconds)}s`
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m`
  const hours = Math.floor(minutes / 60)
  return `${hours}h ${minutes % 60}m`
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
/* Upload list transitions */
.upload-list-enter-active,
.upload-list-leave-active {
  transition: all 0.3s ease;
}

.upload-list-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}

.upload-list-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

.upload-list-move {
  transition: transform 0.3s ease;
}

/* Status transitions */
.status-enter-active,
.status-leave-active {
  transition: all 0.2s ease;
}

.status-enter-from,
.status-leave-to {
  opacity: 0;
  transform: scale(0.8);
}

/* Shimmer animation */
@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-shimmer {
  animation: shimmer 1.5s infinite;
}

/* Scale in animation */
@keyframes scale-in {
  0% {
    transform: scale(0);
    opacity: 0;
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.animate-scale-in {
  animation: scale-in 0.3s ease-out;
}
</style>