<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" @close="close" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-6xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-900 shadow-2xl transition-all">
              <!-- Header -->
              <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-800">
                <div class="flex items-center space-x-3">
                  <div
                    :class="[
                      'w-10 h-10 rounded-lg flex items-center justify-center',
                      getFileIconBackground(file.type)
                    ]"
                  >
                    <component :is="getFileIcon(file.type)" class="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <DialogTitle class="text-lg font-semibold text-gray-900 dark:text-white">
                      {{ file.name }}
                    </DialogTitle>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                      {{ formatFileSize(file.size) }} · {{ file.type }}
                    </p>
                  </div>
                </div>
                
                <div class="flex items-center space-x-2">
                  <button
                    v-if="canDownload"
                    @click="download"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                    title="Download"
                  >
                    <ArrowDownTrayIcon class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                  </button>
                  <button
                    @click="close"
                    class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                  >
                    <XMarkIcon class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                  </button>
                </div>
              </div>

              <!-- Preview Content -->
              <div class="relative bg-gray-50 dark:bg-gray-950" style="max-height: 70vh;">
                <!-- Loading State -->
                <div v-if="isLoading" class="flex items-center justify-center h-96">
                  <div class="text-center">
                    <svg class="animate-spin h-8 w-8 text-indigo-600 dark:text-indigo-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Loading preview...</p>
                  </div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="flex items-center justify-center h-96">
                  <div class="text-center">
                    <ExclamationTriangleIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ error }}</p>
                    <button
                      v-if="canDownload"
                      @click="download"
                      class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                    >
                      <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                      Download file instead
                    </button>
                  </div>
                </div>

                <!-- Image Preview -->
                <div v-else-if="isImage" class="flex items-center justify-center p-8" style="max-height: 70vh;">
                  <img
                    :src="previewUrl"
                    :alt="file.name"
                    class="max-w-full max-h-full object-contain rounded-lg shadow-lg"
                    @load="isLoading = false"
                    @error="handlePreviewError"
                  />
                </div>

                <!-- Video Preview -->
                <div v-else-if="isVideo" class="flex items-center justify-center p-8" style="max-height: 70vh;">
                  <video
                    :src="previewUrl"
                    controls
                    class="max-w-full max-h-full rounded-lg shadow-lg"
                    @loadeddata="isLoading = false"
                    @error="handlePreviewError"
                  >
                    Your browser does not support the video tag.
                  </video>
                </div>

                <!-- Audio Preview -->
                <div v-else-if="isAudio" class="flex items-center justify-center p-8">
                  <div class="w-full max-w-md">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                      <div class="flex items-center justify-center mb-6">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center animate-pulse">
                          <MusicalNoteIcon class="w-12 h-12 text-white" />
                        </div>
                      </div>
                      <audio
                        :src="previewUrl"
                        controls
                        class="w-full"
                        @loadeddata="isLoading = false"
                        @error="handlePreviewError"
                      >
                        Your browser does not support the audio tag.
                      </audio>
                    </div>
                  </div>
                </div>

                <!-- PDF Preview -->
                <div v-else-if="isPDF" class="h-full">
                  <iframe
                    :src="previewUrl"
                    class="w-full h-full"
                    style="min-height: 600px;"
                    @load="isLoading = false"
                    @error="handlePreviewError"
                  />
                </div>

                <!-- Code Preview -->
                <div v-else-if="isCode && isText" class="overflow-auto" style="max-height: 70vh;">
                  <div class="bg-gray-900 dark:bg-black">
                    <div class="flex items-center justify-between px-4 py-2 bg-gray-800 dark:bg-gray-900 border-b border-gray-700">
                      <span class="text-sm text-gray-400">{{ props.file.name }}</span>
                      <span class="text-xs text-gray-500">{{ props.file.type }}</span>
                    </div>
                    <pre class="p-6 overflow-x-auto"><code class="text-sm text-gray-100 font-mono">{{ textContent }}</code></pre>
                  </div>
                </div>

                <!-- Text Preview -->
                <div v-else-if="isText" class="p-8 overflow-auto" style="max-height: 70vh;">
                  <pre class="whitespace-pre-wrap font-mono text-sm text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-800 rounded-lg p-6 shadow-inner">{{ textContent }}</pre>
                </div>

                <!-- No Preview Available -->
                <div v-else class="flex items-center justify-center h-96">
                  <div class="text-center">
                    <DocumentIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                      Preview not available for this file type
                    </p>
                    <button
                      v-if="canDownload"
                      @click="download"
                      class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                    >
                      <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                      Download file
                    </button>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="p-6 border-t border-gray-200 dark:border-gray-800">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                    <span>Created: {{ formatDate(file.created_at) }}</span>
                    <span>·</span>
                    <span>Downloads: {{ file.download_count || 0 }}</span>
                  </div>
                  <Button @click="close" variant="secondary">
                    Close
                  </Button>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue'
import {
  XMarkIcon,
  ArrowDownTrayIcon,
  DocumentIcon,
  PhotoIcon,
  VideoCameraIcon,
  MusicalNoteIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import Button from '@/Components/App/Button.vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  file: {
    type: Object,
    required: true
  },
  canDownload: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['close', 'download'])

// State
const isLoading = ref(true)
const error = ref(null)
const previewUrl = ref(null)
const textContent = ref(null)

// File type checks
const isImage = computed(() => {
  return props.file.type?.startsWith('image/')
})

const isVideo = computed(() => {
  return props.file.type?.startsWith('video/')
})

const isAudio = computed(() => {
  return props.file.type?.startsWith('audio/')
})

const isPDF = computed(() => {
  return props.file.type === 'application/pdf'
})

const isText = computed(() => {
  const textTypes = [
    'text/plain',
    'text/html',
    'text/css',
    'text/javascript',
    'application/json',
    'application/xml',
    'text/xml',
    'application/javascript',
    'text/csv',
    'text/markdown',
    'application/x-sh',
    'application/x-yaml',
    'text/yaml'
  ]
  return textTypes.includes(props.file.type) || props.file.type?.startsWith('text/')
})

const isCode = computed(() => {
  const codeTypes = [
    'text/javascript',
    'application/javascript',
    'application/json',
    'text/css',
    'text/html',
    'application/xml',
    'text/xml',
    'text/x-python',
    'text/x-java',
    'text/x-c',
    'text/x-cpp',
    'text/x-csharp',
    'text/x-php',
    'text/x-ruby',
    'text/x-go',
    'text/x-rust',
    'text/x-swift',
    'text/x-kotlin',
    'text/x-typescript',
    'application/x-httpd-php',
    'application/x-sh',
    'application/x-yaml',
    'text/yaml'
  ]
  return codeTypes.includes(props.file.type) || 
         props.file.name?.match(/\.(js|jsx|ts|tsx|json|css|html|xml|py|java|c|cpp|cs|php|rb|go|rs|swift|kt|sh|bash|yml|yaml)$/i)
})

// Load preview
async function loadPreview() {
  isLoading.value = true
  error.value = null
  
  try {
    if (props.file.url) {
      previewUrl.value = props.file.url
      
      // For text files, fetch the content
      if (isText.value) {
        const response = await fetch(props.file.url)
        if (!response.ok) throw new Error('Failed to load file')
        textContent.value = await response.text()
        isLoading.value = false
      }
    } else {
      throw new Error('No preview URL available')
    }
  } catch (err) {
    error.value = err.message || 'Failed to load preview'
    isLoading.value = false
  }
}

// Handle preview error
function handlePreviewError() {
  error.value = 'Failed to load preview'
  isLoading.value = false
}

// Close modal
function close() {
  emit('close')
}

// Download file
function download() {
  emit('download', props.file)
}

// Format helpers
function formatFileSize(bytes) {
  const sizes = ['B', 'KB', 'MB', 'GB']
  if (bytes === 0) return '0 B'
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getFileIcon(mimeType) {
  if (!mimeType) return DocumentIcon
  
  if (mimeType.startsWith('image/')) return PhotoIcon
  if (mimeType.startsWith('video/')) return VideoCameraIcon
  if (mimeType.startsWith('audio/')) return MusicalNoteIcon
  if (mimeType.includes('pdf')) return DocumentTextIcon
  
  return DocumentIcon
}

function getFileIconBackground(mimeType) {
  if (!mimeType) return 'bg-gray-500'
  
  if (mimeType.startsWith('image/')) return 'bg-blue-500'
  if (mimeType.startsWith('video/')) return 'bg-purple-500'
  if (mimeType.startsWith('audio/')) return 'bg-pink-500'
  if (mimeType.includes('pdf')) return 'bg-red-500'
  
  return 'bg-gray-500'
}

// Watch for file changes
watch(() => props.file, () => {
  if (props.isOpen) {
    loadPreview()
  }
})

// Load preview when modal opens
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    loadPreview()
  }
})

onMounted(() => {
  if (props.isOpen) {
    loadPreview()
  }
})
</script>