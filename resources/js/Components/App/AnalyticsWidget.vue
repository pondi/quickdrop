<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Stats</h3>
      <Link
        :href="route('quickdrop.analytics', quickDropId)"
        class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300"
      >
        View detailed analytics →
      </Link>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="text-center">
        <div class="flex items-center justify-center mb-2">
          <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
            <EyeIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(stats.views) }}</p>
        <p class="text-xs text-gray-600 dark:text-gray-400">Views</p>
      </div>

      <div class="text-center">
        <div class="flex items-center justify-center mb-2">
          <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
            <ArrowDownTrayIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(stats.downloads) }}</p>
        <p class="text-xs text-gray-600 dark:text-gray-400">Downloads</p>
      </div>

      <div class="text-center">
        <div class="flex items-center justify-center mb-2">
          <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
            <UsersIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(stats.uniqueVisitors) }}</p>
        <p class="text-xs text-gray-600 dark:text-gray-400">Visitors</p>
      </div>

      <div class="text-center">
        <div class="flex items-center justify-center mb-2">
          <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
            <ChartBarIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ downloadRate }}%</p>
        <p class="text-xs text-gray-600 dark:text-gray-400">DL Rate</p>
      </div>
    </div>

    <!-- Mini Chart -->
    <div v-if="showChart" class="mt-6">
      <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Last 7 days</h4>
      <div class="h-20">
        <canvas ref="miniChart"></canvas>
      </div>
    </div>

    <!-- Recent Activity -->
    <div v-if="recentActivity.length > 0" class="mt-6">
      <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Recent Activity</h4>
      <div class="space-y-2">
        <div
          v-for="activity in recentActivity.slice(0, 3)"
          :key="activity.id"
          class="flex items-center justify-between text-sm"
        >
          <div class="flex items-center space-x-2">
            <component
              :is="activity.action === 'view' ? EyeIcon : ArrowDownTrayIcon"
              class="w-4 h-4 text-gray-400"
            />
            <span class="text-gray-600 dark:text-gray-400">
              {{ activity.location || 'Unknown' }}
            </span>
          </div>
          <span class="text-xs text-gray-500 dark:text-gray-500">
            {{ formatTimeAgo(activity.timestamp) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  EyeIcon,
  ArrowDownTrayIcon,
  UsersIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps({
  quickDropId: {
    type: String,
    required: true
  },
  stats: {
    type: Object,
    default: () => ({
      views: 0,
      downloads: 0,
      uniqueVisitors: 0
    })
  },
  timeline: {
    type: Array,
    default: () => []
  },
  recentActivity: {
    type: Array,
    default: () => []
  },
  showChart: {
    type: Boolean,
    default: true
  }
})

// Refs
const miniChart = ref(null)
let chartInstance = null

// Computed
const downloadRate = computed(() => {
  if (props.stats.views === 0) return 0
  return Math.round((props.stats.downloads / props.stats.views) * 100)
})

// Methods
function formatNumber(num) {
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'k'
  }
  return num.toString()
}

function formatTimeAgo(timestamp) {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now - date
  const diffMins = Math.floor(diffMs / 60000)
  
  if (diffMins < 1) return 'just now'
  if (diffMins < 60) return `${diffMins}m ago`
  
  const diffHours = Math.floor(diffMins / 60)
  if (diffHours < 24) return `${diffHours}h ago`
  
  const diffDays = Math.floor(diffHours / 24)
  return `${diffDays}d ago`
}

function initMiniChart() {
  if (!miniChart.value || !props.showChart || props.timeline.length === 0) return

  const ctx = miniChart.value.getContext('2d')
  
  // Prepare data - last 7 days
  const data = props.timeline.slice(-7)
  const labels = data.map(item => 
    new Date(item.date).toLocaleDateString('en-US', { weekday: 'short' })
  )
  const values = data.map(item => item.views)

  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        data: values,
        borderColor: 'rgb(99, 102, 241)',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        borderWidth: 2,
        tension: 0.4,
        pointRadius: 0,
        pointHoverRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          enabled: true,
          mode: 'index',
          intersect: false,
          displayColors: false,
          callbacks: {
            title: () => '',
            label: (context) => `${context.parsed.y} views`
          }
        }
      },
      scales: {
        x: {
          display: false
        },
        y: {
          display: false,
          beginAtZero: true
        }
      },
      interaction: {
        mode: 'index',
        intersect: false
      }
    }
  })
}

// Lifecycle
onMounted(() => {
  if (props.showChart) {
    initMiniChart()
  }
})

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy()
  }
})
</script>