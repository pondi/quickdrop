<template>
  <AppLayout>
    <Head :title="`Analytics - ${uploadRequest.title || 'QuickDrop'}`" />

    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-indigo-950 dark:to-purple-950">
      <div class="max-w-7xl mx-auto px-4 py-12">
        <!-- Header -->
        <div class="mb-8">
          <Link
            :href="route('quickdrop.show', uploadRequest.id)"
            class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-4"
          >
            <ArrowLeftIcon class="w-4 h-4 mr-2" />
            Back to QuickDrop
          </Link>
          
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                Share Analytics
              </h1>
              <p class="text-lg text-gray-600 dark:text-gray-400 mt-2">
                Track performance and engagement for your shared files
              </p>
            </div>
            
            <div class="flex items-center space-x-4">
              <!-- Period Selector -->
              <select
                v-model="selectedPeriod"
                @change="loadAnalytics"
                class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
              >
                <option value="7">Last 7 days</option>
                <option value="14">Last 14 days</option>
                <option value="30">Last 30 days</option>
              </select>
              
              <!-- Refresh Button -->
              <button
                @click="loadAnalytics"
                :disabled="isLoading"
                class="p-2 rounded-lg bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
              >
                <ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': isLoading }" />
              </button>
            </div>
          </div>
        </div>

        <!-- QuickDrop Info Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6 mb-8">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                <FolderIcon class="w-8 h-8 text-indigo-600 dark:text-indigo-400" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                  {{ uploadRequest.title || 'Untitled QuickDrop' }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  Created {{ formatDate(uploadRequest.created_at) }}
                </p>
              </div>
            </div>
            
            <div class="flex items-center space-x-3">
              <span v-if="!uploadRequest.is_expired" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                Active
              </span>
              <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                Expired
              </span>
              
              <button
                @click="shareUrl"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
              >
                <ShareIcon class="w-4 h-4 mr-2" />
                Share Link
              </button>
            </div>
          </div>
        </div>

        <!-- Stats Cards -->
        <div v-if="!isLoading && analytics" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Views</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                  {{ analytics.totals.total_views || 0 }}
                </p>
              </div>
              <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                <EyeIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Unique Visitors</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                  {{ analytics.totals.unique_views || 0 }}
                </p>
              </div>
              <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                <UsersIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Downloads</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                  {{ analytics.totals.download_count || 0 }}
                </p>
              </div>
              <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                <ArrowDownTrayIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Uploads</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                  {{ analytics.totals.upload_count || 0 }}
                </p>
              </div>
              <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                <ArrowUpTrayIcon class="w-6 h-6 text-orange-600 dark:text-orange-400" />
              </div>
            </div>
          </div>
        </div>

        <!-- Analytics Charts -->
        <ShareAnalytics
          v-if="!isLoading && analytics"
          :analytics="analytics"
          :recent-activities="analytics.recent_activity || []"
        />

        <!-- Loading State -->
        <div v-else-if="isLoading" class="flex items-center justify-center h-64">
          <div class="text-center">
            <svg class="animate-spin h-8 w-8 text-indigo-600 dark:text-indigo-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-sm text-gray-600 dark:text-gray-400">Loading analytics...</p>
          </div>
        </div>

        <!-- No Data State -->
        <div v-else-if="!analytics || analytics.totals.total_views === 0" class="text-center py-16">
          <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
            No analytics data yet
          </h3>
          <p class="text-gray-600 dark:text-gray-400 mb-6">
            Share your QuickDrop link to start tracking views and downloads
          </p>
          <button
            @click="shareUrl"
            class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
          >
            <ShareIcon class="w-5 h-5 mr-2" />
            Share QuickDrop
          </button>
        </div>

        <!-- Additional Insights -->
        <div v-if="analytics && analytics.totals.total_views > 0" class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Download Rate -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-4">Download Rate</h4>
            <div class="flex items-center justify-center">
              <div class="relative">
                <svg class="w-32 h-32 transform -rotate-90">
                  <circle
                    cx="64"
                    cy="64"
                    r="56"
                    stroke-width="12"
                    fill="none"
                    class="stroke-gray-200 dark:stroke-gray-700"
                  />
                  <circle
                    cx="64"
                    cy="64"
                    r="56"
                    stroke-width="12"
                    fill="none"
                    :stroke-dasharray="`${downloadRate * 3.52} 352`"
                    class="stroke-indigo-600 dark:stroke-indigo-400 transition-all duration-500"
                  />
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                  <span class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ downloadRate }}%
                  </span>
                </div>
              </div>
            </div>
            <p class="text-center text-sm text-gray-600 dark:text-gray-400 mt-4">
              of visitors download files
            </p>
          </div>

          <!-- Device Breakdown -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-4">Device Types</h4>
            <div class="space-y-3">
              <div v-for="(count, device) in analytics.device_breakdown" :key="device" class="flex items-center justify-between">
                <div class="flex items-center">
                  <component :is="getDeviceIcon(device)" class="w-4 h-4 mr-2 text-gray-500" />
                  <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ device }}</span>
                </div>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ getPercentage(count, totalDevices) }}%</span>
              </div>
            </div>
          </div>

          <!-- Browser Breakdown -->
          <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6">
            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-4">Browsers</h4>
            <div class="space-y-3">
              <div v-for="(count, browser) in topBrowsers" :key="browser" class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ browser }}</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ getPercentage(count, totalBrowsers) }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ShareAnalytics from '@/Components/App/ShareAnalytics.vue'
import axios from 'axios'
import {
  ArrowLeftIcon,
  ArrowPathIcon,
  FolderIcon,
  ShareIcon,
  ChartBarIcon,
  ComputerDesktopIcon,
  DevicePhoneMobileIcon,
  DeviceTabletIcon,
  EyeIcon,
  UsersIcon,
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  uploadRequest: {
    type: Object,
    required: true
  }
})

// State
const selectedPeriod = ref(7)
const analytics = ref(null)
const isLoading = ref(false)

// Computed
const downloadRate = computed(() => {
  if (!analytics.value || analytics.value.totals.total_views === 0) return 0
  return Math.round((analytics.value.totals.download_count / analytics.value.totals.total_views) * 100)
})

const totalDevices = computed(() => {
  if (!analytics.value?.device_breakdown) return 0
  return Object.values(analytics.value.device_breakdown).reduce((sum, count) => sum + count, 0)
})

const totalBrowsers = computed(() => {
  if (!analytics.value?.browser_breakdown) return 0
  return Object.values(analytics.value.browser_breakdown).reduce((sum, count) => sum + count, 0)
})

const topBrowsers = computed(() => {
  if (!analytics.value?.browser_breakdown) return {}
  const browsers = Object.entries(analytics.value.browser_breakdown)
    .sort(([,a], [,b]) => b - a)
    .slice(0, 3)
  return Object.fromEntries(browsers)
})

// Methods
async function loadAnalytics() {
  isLoading.value = true
  try {
    const response = await axios.get(route('quickdrop.api.analytics', props.uploadRequest.id), {
      params: { days: selectedPeriod.value }
    })
    analytics.value = response.data
  } catch (error) {
  } finally {
    isLoading.value = false
  }
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  })
}

function shareUrl() {
  const url = route('quickdrop.show', props.uploadRequest.id)
  
  if (navigator.share) {
    navigator.share({
      title: `QuickDrop: ${props.uploadRequest.title}`,
      text: 'Access shared files',
      url: url
    })
  } else {
    // Fallback to clipboard
    navigator.clipboard.writeText(url)
    // Show toast notification
    alert('Link copied to clipboard!')
  }
}

function getDeviceIcon(device) {
  switch (device.toLowerCase()) {
    case 'mobile':
      return DevicePhoneMobileIcon
    case 'tablet':
      return DeviceTabletIcon
    default:
      return ComputerDesktopIcon
  }
}

function getPercentage(count, total) {
  if (total === 0) return 0
  return Math.round((count / total) * 100)
}

// Load analytics on mount
onMounted(() => {
  loadAnalytics()
})
</script>