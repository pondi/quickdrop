<template>
  <BackstageLayout>
    <Head title="Backstage Dashboard" />
    
    <div class="space-y-6">
      <!-- Page Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
          <p class="text-gray-600 dark:text-gray-400 mt-1">System overview for the last 30 days</p>
        </div>
        <div class="flex items-center space-x-2">
          <div class="flex-none rounded-full bg-green-400/10 p-1 text-green-400 dark:bg-green-500/10 dark:text-green-400">
            <div class="size-2 rounded-full bg-current" />
          </div>
          <Badge variant="secondary">
            {{ stats.total_users }} Total Users
          </Badge>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <UsersIcon class="h-8 w-8 text-purple-400" />
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_users }}</p>
                <div class="mt-1 flex items-baseline">
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ stats.active_users }} active this month</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <FolderIcon class="h-8 w-8 text-blue-400" />
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Active QuickDrops</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.active_requests }}</p>
                <div class="mt-1 flex items-baseline">
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ stats.expired_requests }} expired</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ArrowsRightLeftIcon class="h-8 w-8 text-green-400" />
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Files</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_files }}</p>
                <div class="mt-1 flex items-baseline">
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ stats.monthly_uploads }} this month</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <CircleStackIcon class="h-8 w-8 text-yellow-400" />
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Storage Used</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatBytes(stats.total_storage) }}</p>
                <div class="mt-1 flex items-baseline">
                  <p class="text-xs text-gray-500 dark:text-gray-400">across all files</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Downloads Statistics Cards -->
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ArrowDownTrayIcon class="h-8 w-8 text-cyan-400" />
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Downloads</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_downloads || 0 }}</p>
                <div class="mt-1 flex items-baseline">
                  <span :class="stats.downloads_growth >= 0 ? 'text-green-600' : 'text-red-600'" class="text-xs font-medium">
                    {{ stats.downloads_growth >= 0 ? '+' : '' }}{{ stats.downloads_growth || 0 }}%
                  </span>
                  <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">from last month</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ChartBarIcon class="h-8 w-8 text-indigo-400" />
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Monthly Downloads</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.monthly_downloads || 0 }}</p>
                <div class="mt-1 flex items-baseline">
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ stats.download_stats?.completed_downloads || 0 }} completed</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <DocumentIcon class="h-8 w-8 text-emerald-400" />
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Single Downloads</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.download_stats?.single_downloads || 0 }}</p>
                <div class="mt-1 flex items-baseline">
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ stats.download_stats?.bulk_downloads || 0 }} bulk downloads</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ArrowsRightLeftIcon class="h-8 w-8 text-orange-400" />
              </div>
              <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Bandwidth</h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatBytes(stats.download_stats?.total_bytes || 0) }}</p>
                <div class="mt-1 flex items-baseline">
                  <p class="text-xs text-gray-500 dark:text-gray-400">transferred this month</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Activity Trends Chart -->
        <div class="lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle>Activity Trends</CardTitle>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Daily uploads and downloads over the past 14 days</p>
            </CardHeader>
            <CardContent class="pt-2">
              <div class="h-64 flex items-center justify-center">
                <div v-if="uploadTrends && uploadTrends.length > 0" class="w-full">
                  <!-- Simple bar chart representation -->
                  <div class="flex items-end justify-between h-48 space-x-1">
                    <div 
                      v-for="(item, index) in uploadTrends" 
                      :key="index"
                      class="flex flex-col items-center flex-1"
                    >
                      <div class="flex flex-col justify-end h-full w-full space-y-1">
                        <div 
                          class="w-full bg-cyan-500 dark:bg-cyan-400 rounded-t"
                          :style="{ height: `${item.downloadPercentage}%` }"
                          :title="`${item.date}: ${item.downloads} downloads`"
                        ></div>
                        <div 
                          class="w-full bg-indigo-500 dark:bg-indigo-400 rounded-t"
                          :style="{ height: `${item.uploadPercentage}%` }"
                          :title="`${item.date}: ${item.uploads} uploads`"
                        ></div>
                      </div>
                      <span class="text-xs text-gray-500 dark:text-gray-400 mt-2 transform -rotate-45 origin-left">
                        {{ item.date }}
                      </span>
                    </div>
                  </div>
                  <div class="flex items-center justify-center space-x-6 mt-4">
                    <div class="flex items-center space-x-2">
                      <div class="w-3 h-3 bg-indigo-500 dark:bg-indigo-400 rounded"></div>
                      <span class="text-xs text-gray-600 dark:text-gray-400">Uploads</span>
                    </div>
                    <div class="flex items-center space-x-2">
                      <div class="w-3 h-3 bg-cyan-500 dark:bg-cyan-400 rounded"></div>
                      <span class="text-xs text-gray-600 dark:text-gray-400">Downloads</span>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center">
                  <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
                  <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No upload data</h3>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Upload trends will appear here when data is available.</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- System Status -->
        <div>
          <Card>
            <CardHeader>
              <CardTitle>System Status</CardTitle>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Current system health and capacity</p>
            </CardHeader>
            <CardContent class="pt-2">
              <div class="space-y-6">
                <!-- Storage Progress -->
                <div>
                  <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Storage Usage</span>
                    <span class="text-gray-500 dark:text-gray-400">
                      {{ formatBytes(stats.total_storage) }}
                    </span>
                  </div>
                  <div class="mt-2">
                    <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                      <div 
                        class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full transition-all duration-300" 
                        :style="{ width: '45%' }"
                      ></div>
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      Storage tracking
                    </div>
                  </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-1 gap-4">
                  <div class="rounded-lg p-3 ring-1 ring-gray-200 dark:ring-gray-700">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Sessions</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900 dark:text-gray-100">
                      {{ stats.active_users }}
                    </dd>
                  </div>
                  <div class="rounded-lg p-3 ring-1 ring-gray-200 dark:ring-gray-700">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg. File Size</dt>
                    <dd class="mt-1 text-lg font-semibold text-green-600 dark:text-green-400">
                      {{ formatBytes(stats.total_files > 0 ? stats.total_storage / stats.total_files : 0) }}
                    </dd>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Recent Activity -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div>
              <CardTitle>Recent Activity</CardTitle>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Latest activity in the past 24 hours</p>
            </div>
            <Link 
              :href="route('backstage.audit-log')" 
              class="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-2.5 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100 ring-1 ring-gray-300 dark:ring-gray-600 ring-inset hover:bg-gray-50 dark:hover:bg-gray-600"
            >
              View all
              <span class="sr-only">, activity</span>
            </Link>
          </div>
        </CardHeader>
        <CardContent class="p-0">
          <div v-if="recentActivity && recentActivity.length > 0">
            <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
              <li v-for="activity in recentActivity" :key="activity.id" class="px-6 py-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                      <div class="size-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                        <component :is="getActivityIcon(activity.type)" class="size-4 text-indigo-600 dark:text-indigo-400" />
                      </div>
                    </div>
                    <div class="min-w-0 flex-1">
                      <div class="flex items-center space-x-2">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                          {{ activity.user?.name || 'Anonymous' }}
                        </p>
                        <span :class="getActivityBadgeClass(activity.type)">
                          {{ activity.files_count }} files
                        </span>
                      </div>
                      <div class="flex items-center space-x-2 mt-1">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                          {{ activity.title || 'Untitled' }} - {{ formatBytes(activity.total_size) }}
                        </p>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center space-x-2">
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ formatTimeAgo(activity.created_at) }}</span>
                  </div>
                </div>
              </li>
            </ul>
          </div>
          <div v-else class="p-12 text-center">
            <ClockIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No recent activity</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No activity in the past 24 hours.</p>
          </div>
        </CardContent>
      </Card>

      <!-- Top Users -->
      <Card v-if="topUsers && topUsers.length > 0">
        <CardHeader>
          <CardTitle>Top Users by Storage</CardTitle>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Users with highest storage consumption</p>
        </CardHeader>
        <CardContent>
          <ul role="list" class="space-y-4">
            <li v-for="(user, index) in topUsers" :key="user.id" class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="size-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                      {{ (index + 1) }}
                    </span>
                  </div>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ user.name }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ user.uploads_count }} QuickDrops</p>
                </div>
              </div>
              <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ formatBytes(user.total_size) }}
              </div>
            </li>
          </ul>
        </CardContent>
      </Card>
      
      <!-- Top Downloads -->
      <Card v-if="topDownloads && topDownloads.quickdrops && topDownloads.quickdrops.length > 0" class="lg:col-span-2">
        <CardHeader>
          <CardTitle>Top Downloaded QuickDrops</CardTitle>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Most frequently downloaded QuickDrops</p>
        </CardHeader>
        <CardContent>
          <ul role="list" class="space-y-4">
            <li v-for="(item, index) in topDownloads.quickdrops" :key="item.upload_request_id" class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <div class="size-8 rounded-full bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center">
                    <ArrowDownTrayIcon class="size-4 text-cyan-600 dark:text-cyan-400" />
                  </div>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ item.title }}</p>
                  <p class="text-sm text-gray-500 dark:text-gray-400">{{ item.download_count }} downloads</p>
                </div>
              </div>
              <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ formatBytes(item.total_bytes) }}
              </div>
            </li>
          </ul>
        </CardContent>
      </Card>
    </div>
  </BackstageLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import BackstageLayout from '@/Layouts/BackstageLayout.vue'
import { ArrowDownIcon, ArrowUpIcon } from '@heroicons/vue/20/solid'
import { 
  DocumentIcon,
  UsersIcon,
  FolderIcon,
  ArrowsRightLeftIcon,
  ClockIcon,
  CircleStackIcon,
  ChartBarIcon,
  ArrowUpTrayIcon,
  ArrowDownTrayIcon,
  TrashIcon,
  UserPlusIcon
} from '@heroicons/vue/24/outline'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Badge from '@/Components/ui/Badge.vue'

import { computed } from 'vue'

const props = defineProps({
  statistics: {
    type: Object,
    required: true
  }
})

// Computed properties for easy access
const stats = computed(() => props.statistics?.overview || {})
const dailyStats = computed(() => props.statistics?.daily_stats || [])
const fileTypes = computed(() => props.statistics?.file_types || [])
const topUsers = computed(() => props.statistics?.top_users || [])
const recentActivity = computed(() => props.statistics?.recent_activity || [])
const topDownloads = computed(() => props.statistics?.top_downloads || {})

// Transform daily stats for activity trends chart
const uploadTrends = computed(() => {
  const last14Days = dailyStats.value.slice(-14)
  const maxUploads = Math.max(...last14Days.map(d => d.uploads), 1)
  const maxDownloads = Math.max(...last14Days.map(d => d.downloads || 0), 1)
  const maxValue = Math.max(maxUploads, maxDownloads, 1)
  
  return last14Days.map(item => ({
    date: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
    uploads: item.uploads,
    downloads: item.downloads || 0,
    uploadPercentage: (item.uploads / maxValue) * 100,
    downloadPercentage: ((item.downloads || 0) / maxValue) * 100
  }))
})

// Utility functions
function formatBytes(bytes) {
  if (!bytes || bytes === 0) return '0 B'
  
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  
  return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i]
}

function getActivityIcon(type) {
  return ArrowUpTrayIcon
}

function getActivityBadgeClass(type) {
  const baseClasses = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium'
  return `${baseClasses} bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400`
}

function formatTimeAgo(dateString) {
  const date = new Date(dateString)
  const now = new Date()
  const seconds = Math.floor((now - date) / 1000)
  
  if (seconds < 60) return 'just now'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  return `${days}d ago`
}
</script>