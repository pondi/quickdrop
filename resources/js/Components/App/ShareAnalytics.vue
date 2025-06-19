<template>
  <div class="space-y-6">
    <!-- Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Views</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
              {{ formatNumber(analytics.totalViews) }}
            </p>
          </div>
          <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
            <EyeIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
          </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
          <span :class="[
            'flex items-center',
            analytics.viewsChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
          ]">
            <component 
              :is="analytics.viewsChange >= 0 ? ArrowUpIcon : ArrowDownIcon" 
              class="w-4 h-4 mr-1" 
            />
            {{ Math.abs(analytics.viewsChange) }}%
          </span>
          <span class="text-gray-500 dark:text-gray-400 ml-2">vs last period</span>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Downloads</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
              {{ formatNumber(analytics.totalDownloads) }}
            </p>
          </div>
          <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
            <ArrowDownTrayIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
          </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
          <span :class="[
            'flex items-center',
            analytics.downloadsChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
          ]">
            <component 
              :is="analytics.downloadsChange >= 0 ? ArrowUpIcon : ArrowDownIcon" 
              class="w-4 h-4 mr-1" 
            />
            {{ Math.abs(analytics.downloadsChange) }}%
          </span>
          <span class="text-gray-500 dark:text-gray-400 ml-2">vs last period</span>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Unique Visitors</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
              {{ formatNumber(analytics.uniqueVisitors) }}
            </p>
          </div>
          <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
            <UsersIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
          </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
          <span :class="[
            'flex items-center',
            analytics.visitorsChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
          ]">
            <component 
              :is="analytics.visitorsChange >= 0 ? ArrowUpIcon : ArrowDownIcon" 
              class="w-4 h-4 mr-1" 
            />
            {{ Math.abs(analytics.visitorsChange) }}%
          </span>
          <span class="text-gray-500 dark:text-gray-400 ml-2">vs last period</span>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Avg. Time on Page</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
              {{ formatDuration(analytics.avgTimeOnPage) }}
            </p>
          </div>
          <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
            <ClockIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
          </div>
        </div>
        <div class="mt-4 flex items-center text-sm">
          <span :class="[
            'flex items-center',
            analytics.timeChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
          ]">
            <component 
              :is="analytics.timeChange >= 0 ? ArrowUpIcon : ArrowDownIcon" 
              class="w-4 h-4 mr-1" 
            />
            {{ Math.abs(analytics.timeChange) }}%
          </span>
          <span class="text-gray-500 dark:text-gray-400 ml-2">vs last period</span>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Activity Timeline -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Activity Timeline</h3>
        <div class="h-64">
          <canvas ref="activityChart"></canvas>
        </div>
      </div>

      <!-- Geographic Distribution -->
      <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Geographic Distribution</h3>
        <div class="space-y-3">
          <div v-for="country in topCountries" :key="country.code" class="flex items-center">
            <div class="flex items-center flex-1">
              <img 
                :src="`https://flagcdn.com/w40/${country.code.toLowerCase()}.png`" 
                :alt="country.name"
                class="w-6 h-4 rounded mr-3"
              />
              <span class="text-sm font-medium text-gray-900 dark:text-white">{{ country.name }}</span>
            </div>
            <div class="flex items-center space-x-3">
              <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div 
                  class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full transition-all duration-500"
                  :style="{ width: `${country.percentage}%` }"
                ></div>
              </div>
              <span class="text-sm text-gray-600 dark:text-gray-400 w-12 text-right">
                {{ country.percentage }}%
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Analytics Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
      <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Detailed Activity Log</h3>
          <button
            @click="exportData"
            class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
            Export CSV
          </button>
        </div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
          <thead>
            <tr>
              <th class="px-6 py-3 bg-gray-50 dark:bg-gray-900 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Time
              </th>
              <th class="px-6 py-3 bg-gray-50 dark:bg-gray-900 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Action
              </th>
              <th class="px-6 py-3 bg-gray-50 dark:bg-gray-900 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                File
              </th>
              <th class="px-6 py-3 bg-gray-50 dark:bg-gray-900 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Location
              </th>
              <th class="px-6 py-3 bg-gray-50 dark:bg-gray-900 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Device
              </th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr v-for="activity in recentActivities" :key="activity.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ formatDateTime(activity.timestamp) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  activity.action === 'view' 
                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
                    : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                ]">
                  {{ activity.action }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                {{ activity.fileName }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                {{ activity.location }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                <div class="flex items-center">
                  <component :is="getDeviceIcon(activity.device)" class="w-4 h-4 mr-2" />
                  {{ activity.device }}
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import {
  EyeIcon,
  ArrowDownTrayIcon,
  UsersIcon,
  ClockIcon,
  ArrowUpIcon,
  ArrowDownIcon,
  ComputerDesktopIcon,
  DevicePhoneMobileIcon,
  DeviceTabletIcon,
} from '@heroicons/vue/24/outline'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps({
  analytics: {
    type: Object,
    default: () => ({
      totalViews: 0,
      viewsChange: 0,
      totalDownloads: 0,
      downloadsChange: 0,
      uniqueVisitors: 0,
      visitorsChange: 0,
      avgTimeOnPage: 0,
      timeChange: 0,
      timeline: [],
      countries: []
    })
  },
  recentActivities: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['export'])

// Refs
const activityChart = ref(null)
let chartInstance = null

// Computed
const topCountries = computed(() => {
  return props.analytics.countries
    .sort((a, b) => b.count - a.count)
    .slice(0, 5)
    .map(country => ({
      ...country,
      percentage: Math.round((country.count / props.analytics.uniqueVisitors) * 100)
    }))
})

// Methods
function formatNumber(num) {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}

function formatDuration(seconds) {
  if (seconds < 60) {
    return `${seconds}s`
  } else if (seconds < 3600) {
    const minutes = Math.floor(seconds / 60)
    const remainingSeconds = seconds % 60
    return `${minutes}m ${remainingSeconds}s`
  } else {
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    return `${hours}h ${minutes}m`
  }
}

function formatDateTime(timestamp) {
  const date = new Date(timestamp)
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getDeviceIcon(device) {
  if (device.toLowerCase().includes('mobile')) return DevicePhoneMobileIcon
  if (device.toLowerCase().includes('tablet')) return DeviceTabletIcon
  return ComputerDesktopIcon
}

function initChart() {
  if (!activityChart.value) return

  const ctx = activityChart.value.getContext('2d')
  
  // Prepare data
  const labels = props.analytics.timeline.map(item => 
    new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  )
  
  const viewsData = props.analytics.timeline.map(item => item.views)
  const downloadsData = props.analytics.timeline.map(item => item.downloads)

  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Views',
          data: viewsData,
          borderColor: 'rgb(99, 102, 241)',
          backgroundColor: 'rgba(99, 102, 241, 0.1)',
          tension: 0.4
        },
        {
          label: 'Downloads',
          data: downloadsData,
          borderColor: 'rgb(34, 197, 94)',
          backgroundColor: 'rgba(34, 197, 94, 0.1)',
          tension: 0.4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            display: true,
            drawBorder: false
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  })
}

function exportData() {
  emit('export')
}

// Lifecycle
onMounted(() => {
  initChart()
})

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
})
</script>