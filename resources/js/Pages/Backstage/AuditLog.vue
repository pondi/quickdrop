<template>
  <BackstageLayout>
    <Head title="Audit Log" />
    
    <div class="space-y-6 max-w-none">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Audit Log</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">System activity and security logs</p>
        </div>
        <div class="flex items-center space-x-2">
          <Badge variant="info">
            {{ stats.total }} total logs
          </Badge>
          <Button 
            variant="outline" 
            size="sm"
            @click="refreshData"
            :disabled="isRefreshing"
          >
            <ArrowPathIcon class="w-4 h-4 mr-2" :class="{ 'animate-spin': isRefreshing }" />
            Refresh
          </Button>
        </div>
      </div>
      
      <!-- Search Bar -->
      <div class="flex items-center space-x-4">
        <div class="flex-1">
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input
              v-model="searchQuery"
              @input="debouncedSearch"
              type="text"
              placeholder="Search logs... Try 'today', 'user:john', 'uploaded', 'last 3 days', '2024-01-15', '.pdf files'"
              class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            />
          </div>
        </div>
        <Button 
          v-if="searchQuery"
          variant="outline" 
          size="sm"
          @click="clearSearch"
        >
          Clear
        </Button>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ChartBarIcon class="h-8 w-8 text-gray-400" />
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                  Total Activities
                </h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                  {{ formatNumber(stats.total) }}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ArrowDownTrayIcon class="h-8 w-8 text-blue-400" />
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                  Downloads
                </h3>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                  {{ formatNumber(stats.downloads) }}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ArrowUpTrayIcon class="h-8 w-8 text-green-400" />
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                  Uploads
                </h3>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                  {{ formatNumber(stats.uploads) }}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <UserPlusIcon class="h-8 w-8 text-purple-400" />
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">
                  New Users
                </h3>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                  {{ formatNumber(stats.newUsers) }}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Audit Log Table -->
      <div class="-mx-4 mt-6 ring-1 ring-gray-300 dark:ring-gray-600 sm:mx-0 sm:rounded-lg overflow-hidden relative">
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Activity Log</h3>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ searchQuery ? `Filtered results for "${searchQuery}"` : 'Latest system activities' }}
            <span v-if="auditLogs.total > 0" class="ml-2">
              (Showing {{ auditLogs.from || 1 }}-{{ auditLogs.to || auditLogs.length }} of {{ auditLogs.total }})
            </span>
          </p>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Timestamp
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  User
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Action
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Details
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  IP Address
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-700 divide-y divide-gray-200 dark:divide-gray-600">
              <tr v-for="log in auditLogs.data || auditLogs" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  <div class="flex flex-col">
                    <span class="font-medium">{{ formatDate(log.created_at) }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatTime(log.created_at) }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <div v-if="log.user" class="flex items-center">
                    <div class="flex-shrink-0 h-8 w-8">
                      <div class="h-8 w-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                        <span class="text-xs font-medium text-white">
                          {{ getInitials(log.user.name) }}
                        </span>
                      </div>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm font-medium text-gray-900 dark:text-white">{{ log.user.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">{{ log.user.email }}</p>
                    </div>
                  </div>
                  <span v-else class="text-gray-400 dark:text-gray-500">System</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getActionBadgeClass(log.action)
                  ]">
                    <component :is="getActionIcon(log.action)" class="w-3 h-3 mr-1" />
                    {{ log.action }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                  <div class="max-w-xs truncate">
                    {{ log.description }}
                  </div>
                  <div v-if="log.metadata" class="mt-1">
                    <span v-if="log.metadata.quickdrop_id" class="inline-flex items-center text-xs text-gray-500 dark:text-gray-400 mr-3">
                      <FolderIcon class="w-3 h-3 mr-1" />
                      QuickDrop #{{ log.metadata.quickdrop_id }}
                    </span>
                    <span v-if="log.metadata.file_name" class="inline-flex items-center text-xs text-gray-500 dark:text-gray-400">
                      <DocumentIcon class="w-3 h-3 mr-1" />
                      {{ log.metadata.file_name }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 font-mono">
                  {{ log.ip_address || '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Loading Overlay -->
        <div v-if="isLoading" class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-75 flex items-center justify-center">
          <div class="flex flex-col items-center">
            <svg class="animate-spin h-8 w-8 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Loading logs...</p>
          </div>
        </div>
        
        <!-- Empty State -->
        <div v-if="(auditLogs.data || auditLogs).length === 0 && !isLoading" class="px-6 py-12 text-center">
          <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No logs found</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ searchQuery ? 'Try adjusting your search criteria.' : 'No activity logs have been recorded yet.' }}
          </p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="auditLogs.links" class="mt-6 space-y-4">
        <Pagination 
          :links="auditLogs.links"
          :from="auditLogs.from"
          :to="auditLogs.to"
          :total="auditLogs.total"
          :prev-page-url="auditLogs.prev_page_url"
          :next-page-url="auditLogs.next_page_url"
        />
        
        <!-- Per page selector -->
        <div class="flex justify-end">
          <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300">
            <span>Show</span>
            <select 
              v-model="perPage"
              @change="changePerPage"
              class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
              <option value="200">200</option>
            </select>
            <span>per page</span>
          </label>
        </div>
      </div>
    </div>
  </BackstageLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import {
  MagnifyingGlassIcon,
  ArrowPathIcon,
  ChartBarIcon,
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  TrashIcon,
  UserPlusIcon,
  ClockIcon,
  FolderIcon,
  DocumentIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

import BackstageLayout from '@/Layouts/BackstageLayout.vue'
import Card from '@/Components/ui/Card.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Badge from '@/Components/ui/Badge.vue'
import Button from '@/Components/App/Button.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  auditLogs: {
    type: [Array, Object],
    default: () => []
  },
  stats: {
    type: Object,
    default: () => ({
      total: 0,
      downloads: 0,
      uploads: 0,
      newUsers: 0
    })
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

// Reactive state
const searchQuery = ref(props.filters?.search || '')
const perPage = ref(props.filters?.per_page || '50')
const isLoading = ref(false)
const isRefreshing = ref(false)

// Debounced search function
const debouncedSearch = debounce(() => {
  isLoading.value = true
  router.get(route('backstage.audit-log.index'), { 
    search: searchQuery.value,
    per_page: perPage.value 
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      isLoading.value = false
    }
  })
}, 300)

// Clear search
function clearSearch() {
  searchQuery.value = ''
  debouncedSearch()
}

// Change per page
function changePerPage() {
  isLoading.value = true
  router.get(route('backstage.audit-log.index'), { 
    search: searchQuery.value,
    per_page: perPage.value 
  }, {
    preserveState: true,
    preserveScroll: false,
    onFinish: () => {
      isLoading.value = false
    }
  })
}

// Refresh data
function refreshData() {
  isRefreshing.value = true
  router.reload({
    onFinish: () => {
      isRefreshing.value = false
    }
  })
}

// Utility functions
function formatNumber(num) {
  return new Intl.NumberFormat().format(num)
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

function formatTime(dateString) {
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

function getInitials(name) {
  if (!name) return '?'
  const words = name.split(' ')
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
}

function getActionIcon(action) {
  switch (action) {
    case 'download':
      return ArrowDownTrayIcon
    case 'upload':
      return ArrowUpTrayIcon
    case 'delete':
      return TrashIcon
    case 'user_registered':
      return UserPlusIcon
    case 'login':
      return CheckCircleIcon
    case 'logout':
      return XCircleIcon
    default:
      return ExclamationTriangleIcon
  }
}

function getActionBadgeClass(action) {
  switch (action) {
    case 'download':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
    case 'upload':
      return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
    case 'delete':
      return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
    case 'user_registered':
      return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'
    case 'login':
      return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
    case 'logout':
      return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
    default:
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
  }
}
</script>