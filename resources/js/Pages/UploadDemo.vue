<template>
  <AppLayout>
    <Head title="Upload Progress Demo" />

    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-indigo-950 dark:to-purple-950">
      <div class="max-w-4xl mx-auto px-4 py-12">
        <!-- Header -->
        <div class="text-center mb-12">
          <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Enhanced Upload Progress
          </h1>
          <p class="text-lg text-gray-600 dark:text-gray-400">
            Experience real-time upload progress with animations
          </p>
        </div>

        <!-- Upload Zone -->
        <div class="mb-8">
          <DropZone
            @files-added="handleFilesAdded"
            :max-file-size="maxFileSize"
            :disabled="!canUpload"
          />
        </div>

        <!-- Upload Progress -->
        <div v-if="uploadingFiles.length > 0" class="mb-8">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Upload Progress
          </h3>
          <UploadProgressEnhanced
            :uploading-files="uploadingFiles"
            @cancel="cancelUpload"
            @retry="retryUpload"
          />
        </div>

        <!-- Action Buttons -->
        <div v-if="pendingFiles.length > 0" class="flex justify-center space-x-4">
          <Button
            @click="uploadPendingFiles"
            :disabled="isUploading || !canUpload"
            size="lg"
          >
            <ArrowUpTrayIcon class="w-5 h-5 mr-2" />
            Upload {{ pendingFiles.length }} File{{ pendingFiles.length !== 1 ? 's' : '' }}
          </Button>
          <Button
            @click="clearPending"
            variant="secondary"
            size="lg"
            :disabled="isUploading"
          >
            Clear
          </Button>
        </div>

        <!-- Uploaded Files -->
        <div v-if="files.length > 0" class="mt-12">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Uploaded Files
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="file in files"
              :key="file.id"
              class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700"
            >
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <CheckCircleIcon class="w-8 h-8 text-green-500" />
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
            </div>
          </div>
        </div>

        <!-- Demo Controls -->
        <div class="mt-12 p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            Demo Controls
          </h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Upload Speed Simulation
              </label>
              <select
                v-model="simulatedSpeed"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700"
              >
                <option value="fast">Fast (10 MB/s)</option>
                <option value="normal">Normal (1 MB/s)</option>
                <option value="slow">Slow (100 KB/s)</option>
              </select>
            </div>
            <div class="flex items-center space-x-4">
              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="simulateErrors"
                  class="rounded border-gray-300 dark:border-gray-600"
                />
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                  Simulate random errors
                </span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import DropZone from '@/Components/App/DropZone.vue'
import UploadProgressEnhanced from '@/Components/App/UploadProgressEnhanced.vue'
import Button from '@/Components/App/Button.vue'
import { ArrowUpTrayIcon, CheckCircleIcon } from '@heroicons/vue/24/outline'
import { useFileUploadEnhanced } from '@/Composables/useFileUploadEnhanced'

// Mock upload request for demo
const uploadRequest = ref({
  id: 'demo-123',
  is_active: true,
  is_expired: false,
  has_encryption: false
})

const maxFileSize = 100 * 1024 * 1024 // 100 MB

// Demo controls
const simulatedSpeed = ref('normal')
const simulateErrors = ref(false)

// Use enhanced file upload
const {
  files,
  pendingFiles,
  isUploading,
  canUpload,
  handleMultipleFiles,
  uploadPendingFiles,
  uploadingFiles,
  cancelUpload,
  retryUpload
} = useFileUploadEnhanced(uploadRequest, () => null)

// Handle files added
async function handleFilesAdded(newFiles) {
  await handleMultipleFiles(newFiles)
}

// Clear pending files
function clearPending() {
  pendingFiles.value = []
}

// Format file size
function formatFileSize(bytes) {
  const sizes = ['B', 'KB', 'MB', 'GB']
  if (bytes === 0) return '0 B'
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}
</script>