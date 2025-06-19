<template>
  <BackstageLayout>
    <Head :title="`QuickDrop: ${quickdrop.reference_number || 'No Reference'}`" />
    
    <div class="px-4 sm:px-6 lg:px-8">
      <!-- Back Button and Title -->
      <div class="mb-6">
        <Link 
          :href="route('backstage.quickdrops.index')"
          class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center"
        >
          <ArrowLeftIcon class="h-4 w-4 mr-1" />
          Back to QuickDrops
        </Link>
        <div class="mt-2 md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white sm:truncate">
              {{ quickdrop.reference_number || 'No Reference' }}
            </h2>
            <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:space-x-6">
              <span 
                :class="[
                  'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset mt-2',
                  quickdrop.is_active
                    ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/30'
                    : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-500/30'
                ]"
              >
                {{ quickdrop.is_active ? 'Active' : 'Expired' }}
              </span>
            </div>
          </div>
          <div class="mt-4 flex flex-shrink-0 md:mt-0 md:ml-4 space-x-2">
            <a
              :href="quickdrop.public_url"
              target="_blank"
              class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-gray-300 dark:ring-gray-600 ring-inset hover:bg-gray-50 dark:hover:bg-gray-600"
            >
              <ArrowTopRightOnSquareIcon class="h-4 w-4 mr-1.5" />
              View Public Page
            </a>
            <button
              v-if="quickdrop.is_active"
              @click="showExtendDialog = true"
              class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-gray-300 dark:ring-gray-600 ring-inset hover:bg-gray-50 dark:hover:bg-gray-600"
            >
              <ClockIcon class="h-4 w-4 mr-1.5" />
              Extend Expiry
            </button>
            <button
              @click="showDeleteDialog = true"
              class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500"
            >
              <TrashIcon class="h-4 w-4 mr-1.5" />
              Delete
            </button>
          </div>
        </div>
      </div>

      <!-- Info Cards -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Total Files -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow">
          <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Total Files</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
            {{ stats.total_files }}
          </dd>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ stats.unique_files }} unique, {{ stats.version_count }} versions
          </p>
        </div>

        <!-- Total Size -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow">
          <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Total Size</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
            {{ formatBytes(stats.total_size) }}
          </dd>
        </div>

        <!-- Downloads -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow">
          <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Total Downloads</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
            {{ stats.total_downloads }}
          </dd>
        </div>

        <!-- Expires -->
        <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow">
          <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">Expires</dt>
          <dd class="mt-1 text-2xl font-semibold tracking-tight" :class="getExpiryColorClass(quickdrop.expires_at)">
            {{ formatExpiryTime(quickdrop.expires_at) }}
          </dd>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ formatDate(quickdrop.expires_at) }}
          </p>
        </div>
      </div>

      <!-- QuickDrop Details -->
      <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-8">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            QuickDrop Information
          </h3>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700">
          <dl>
            <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Title</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                {{ quickdrop.title || 'No Title' }}
              </dd>
            </div>
            <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Comment</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                {{ quickdrop.comment || 'No Comment' }}
              </dd>
            </div>
            <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created By</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                <div v-if="quickdrop.user">
                  {{ quickdrop.user.name }} ({{ quickdrop.user.email }})
                  <span class="text-gray-500 dark:text-gray-400 block text-xs mt-1">
                    Storage: {{ formatBytes(quickdrop.user.storage_used) }} / {{ formatBytes(quickdrop.user.storage_limit) }}
                  </span>
                </div>
                <span v-else class="text-gray-400 dark:text-gray-500">Anonymous</span>
              </dd>
            </div>
            <div class="bg-white dark:bg-gray-800 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                {{ formatDateTime(quickdrop.created_at) }}
              </dd>
            </div>
            <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Permissions</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">
                <div class="flex flex-wrap gap-2">
                  <span v-if="quickdrop.allow_public_upload" class="inline-flex items-center rounded-md bg-blue-50 dark:bg-blue-900/30 px-2 py-1 text-xs font-medium text-blue-700 dark:text-blue-400 ring-1 ring-inset ring-blue-700/10 dark:ring-blue-500/30">
                    Public Upload
                  </span>
                  <span v-if="quickdrop.allow_public_download" class="inline-flex items-center rounded-md bg-green-50 dark:bg-green-900/30 px-2 py-1 text-xs font-medium text-green-700 dark:text-green-400 ring-1 ring-inset ring-green-700/10 dark:ring-green-500/30">
                    Public Download
                  </span>
                  <span v-if="quickdrop.allow_public_delete" class="inline-flex items-center rounded-md bg-red-50 dark:bg-red-900/30 px-2 py-1 text-xs font-medium text-red-700 dark:text-red-400 ring-1 ring-inset ring-red-700/10 dark:ring-red-500/30">
                    Public Delete
                  </span>
                  <span v-if="quickdrop.is_encrypted" class="inline-flex items-center rounded-md bg-purple-50 dark:bg-purple-900/30 px-2 py-1 text-xs font-medium text-purple-700 dark:text-purple-400 ring-1 ring-inset ring-purple-700/10 dark:ring-purple-500/30">
                    Encrypted
                  </span>
                </div>
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Files List -->
      <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
            Files
          </h3>
        </div>
        <div class="border-t border-gray-200 dark:border-gray-700">
          <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
            <li v-for="fileGroup in fileGroups" :key="fileGroup.latest.id" class="px-4 py-4 sm:px-6">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <DocumentIcon class="h-10 w-10 text-gray-400" />
                  </div>
                  <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                      {{ fileGroup.latest.original_filename }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                      {{ formatBytes(fileGroup.latest.size) }} • Version {{ fileGroup.latest.version }}
                      • {{ fileGroup.latest.download_count }} downloads
                    </p>
                    <p v-if="fileGroup.versions.length > 1" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      {{ fileGroup.versions.length }} versions available
                    </p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <a
                    :href="route('download.file', { 
                      requestId: quickdrop.unique_request_id, 
                      fileUuid: fileGroup.latest.unique_file_id 
                    })"
                    class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-gray-300 dark:ring-gray-600 ring-inset hover:bg-gray-50 dark:hover:bg-gray-600"
                  >
                    <ArrowDownTrayIcon class="h-4 w-4" />
                  </a>
                </div>
              </div>
              
              <!-- Version History -->
              <div v-if="fileGroup.versions.length > 1" class="mt-3 ml-14">
                <button
                  @click="toggleVersionHistory(fileGroup.latest.id)"
                  class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500"
                >
                  {{ showVersionHistory[fileGroup.latest.id] ? 'Hide' : 'Show' }} version history
                </button>
                <div v-if="showVersionHistory[fileGroup.latest.id]" class="mt-2 space-y-2">
                  <div 
                    v-for="version in fileGroup.versions.slice().sort((a, b) => b.version - a.version)" 
                    :key="version.id"
                    class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 pl-4 border-l-2 border-gray-200 dark:border-gray-700"
                  >
                    <div>
                      Version {{ version.version }} • {{ formatBytes(version.size) }} • 
                      {{ formatDateTime(version.created_at) }}
                    </div>
                    <a
                      :href="route('download.file', { 
                        requestId: quickdrop.unique_request_id, 
                        fileUuid: version.unique_file_id 
                      })"
                      class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500"
                    >
                      Download
                    </a>
                  </div>
                </div>
              </div>
            </li>
          </ul>
          
          <!-- Empty State -->
          <div v-if="fileGroups.length === 0" class="text-center py-12">
            <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No files</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              No files have been uploaded to this QuickDrop yet.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirmation Dialogs -->
    <ConfirmationDialog
      v-model:open="showDeleteDialog"
      title="Delete QuickDrop"
      :message="`Are you sure you want to delete this QuickDrop? This action cannot be undone and will remove all associated files.`"
      confirm-text="Delete QuickDrop"
      type="danger"
      :loading="dialogLoading"
      @confirm="confirmDelete"
    />

    <ConfirmationDialog
      v-model:open="showExtendDialog"
      title="Extend QuickDrop"
      :message="`Are you sure you want to extend the expiry time for this QuickDrop by 7 days?`"
      confirm-text="Extend Expiry"
      type="info"
      :loading="dialogLoading"
      @confirm="confirmExtend"
    />
  </BackstageLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  ArrowLeftIcon,
  ArrowTopRightOnSquareIcon,
  ArrowDownTrayIcon,
  ClockIcon,
  TrashIcon,
  DocumentIcon,
} from '@heroicons/vue/24/outline'

import BackstageLayout from '@/Layouts/BackstageLayout.vue'
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue'

const props = defineProps({
  quickdrop: {
    type: Object,
    required: true
  },
  stats: {
    type: Object,
    required: true
  },
  fileGroups: {
    type: Array,
    default: () => []
  }
})

// Dialog states
const showDeleteDialog = ref(false)
const showExtendDialog = ref(false)
const dialogLoading = ref(false)
const showVersionHistory = ref({})

// Toggle version history
function toggleVersionHistory(fileId) {
  showVersionHistory.value[fileId] = !showVersionHistory.value[fileId]
}

// Utility functions
function formatBytes(bytes) {
  if (!bytes || bytes === 0) return '0 B'
  
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

function formatDateTime(dateString) {
  if (!dateString) return 'N/A'
  
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatExpiryTime(dateString) {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = date - now
  
  if (diffTime < 0) {
    return 'Expired'
  }
  
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) {
    const diffHours = Math.floor(diffTime / (1000 * 60 * 60))
    if (diffHours === 0) {
      const diffMinutes = Math.floor(diffTime / (1000 * 60))
      return `${diffMinutes} minute${diffMinutes !== 1 ? 's' : ''}`
    }
    return `${diffHours} hour${diffHours !== 1 ? 's' : ''}`
  } else if (diffDays === 1) {
    return 'Tomorrow'
  } else {
    return `${diffDays} days`
  }
}

function getExpiryColorClass(dateString) {
  if (!dateString) return 'text-gray-400 dark:text-gray-500'
  
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = date - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffDays < 0) {
    return 'text-red-600 dark:text-red-400'
  } else if (diffDays <= 1) {
    return 'text-yellow-600 dark:text-yellow-400'
  } else {
    return 'text-green-600 dark:text-green-400'
  }
}

// Dialog confirmation handlers
function confirmDelete() {
  dialogLoading.value = true
  router.delete(route('backstage.quickdrops.destroy', props.quickdrop.id), {
    onFinish: () => {
      dialogLoading.value = false
      showDeleteDialog.value = false
    }
  })
}

function confirmExtend() {
  dialogLoading.value = true
  router.post(route('backstage.quickdrops.extend', props.quickdrop.id), {}, {
    onFinish: () => {
      dialogLoading.value = false
      showExtendDialog.value = false
    }
  })
}
</script>