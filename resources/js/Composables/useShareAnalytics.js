import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

export function useShareAnalytics(quickDropId) {
  const analytics = ref({
    totalViews: 0,
    viewsChange: 0,
    totalDownloads: 0,
    downloadsChange: 0,
    uniqueVisitors: 0,
    visitorsChange: 0,
    avgTimeOnPage: 0,
    timeChange: 0,
    timeline: [],
    countries: [],
    referrers: [],
    devices: {
      desktop: 0,
      mobile: 0,
      tablet: 0
    },
    browsers: {},
    fileStats: []
  })

  const recentActivities = ref([])
  const isLoading = ref(false)
  const error = ref(null)

  // Track a view event
  async function trackView(quickDropId, metadata = {}) {
    try {
      const payload = {
        quickdrop_id: quickDropId,
        event_type: 'view',
        metadata: {
          ...metadata,
          timestamp: new Date().toISOString(),
          user_agent: navigator.userAgent,
          screen_resolution: `${window.screen.width}x${window.screen.height}`,
          referrer: document.referrer || 'direct',
          timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        }
      }

      // Send tracking data
      await router.post(route('analytics.track'), payload, {
        preserveState: true,
        preserveScroll: true
      })
    } catch (err) {
      console.error('Failed to track view:', err)
    }
  }

  // Track a download event
  async function trackDownload(quickDropId, fileId, metadata = {}) {
    try {
      const payload = {
        quickdrop_id: quickDropId,
        file_id: fileId,
        event_type: 'download',
        metadata: {
          ...metadata,
          timestamp: new Date().toISOString(),
          user_agent: navigator.userAgent
        }
      }

      await router.post(route('analytics.track'), payload, {
        preserveState: true,
        preserveScroll: true
      })
    } catch (err) {
      console.error('Failed to track download:', err)
    }
  }

  // Track time on page
  let startTime = Date.now()
  let isActive = true

  function trackTimeOnPage() {
    if (!isActive) return

    const timeSpent = Math.floor((Date.now() - startTime) / 1000)
    
    // Send time tracking every 30 seconds
    if (timeSpent > 0 && timeSpent % 30 === 0) {
      router.post(route('analytics.track'), {
        quickdrop_id: quickDropId,
        event_type: 'time_on_page',
        metadata: {
          duration: timeSpent
        }
      }, {
        preserveState: true,
        preserveScroll: true
      })
    }
  }

  // Track page visibility
  function handleVisibilityChange() {
    if (document.hidden) {
      isActive = false
    } else {
      isActive = true
      startTime = Date.now() // Reset start time when page becomes visible
    }
  }

  // Load analytics data
  async function loadAnalytics(period = '7d') {
    isLoading.value = true
    error.value = null

    try {
      const response = await router.get(route('quickdrop.analytics', {
        id: quickDropId,
        period: period
      }), {
        preserveState: true,
        onSuccess: (page) => {
          if (page.props.analytics) {
            analytics.value = page.props.analytics
          }
          if (page.props.recentActivities) {
            recentActivities.value = page.props.recentActivities
          }
        }
      })
    } catch (err) {
      error.value = err.message || 'Failed to load analytics'
    } finally {
      isLoading.value = false
    }
  }

  // Export analytics data
  async function exportAnalytics(format = 'csv') {
    try {
      window.location.href = route('quickdrop.analytics.export', {
        id: quickDropId,
        format: format
      })
    } catch (err) {
      console.error('Failed to export analytics:', err)
    }
  }

  // Computed properties
  const hasData = computed(() => {
    return analytics.value.totalViews > 0 || analytics.value.totalDownloads > 0
  })

  const downloadRate = computed(() => {
    if (analytics.value.totalViews === 0) return 0
    return Math.round((analytics.value.totalDownloads / analytics.value.totalViews) * 100)
  })

  const topReferrer = computed(() => {
    if (analytics.value.referrers.length === 0) return 'Direct'
    return analytics.value.referrers[0].source
  })

  const deviceBreakdown = computed(() => {
    const total = Object.values(analytics.value.devices).reduce((sum, count) => sum + count, 0)
    if (total === 0) return []

    return Object.entries(analytics.value.devices).map(([device, count]) => ({
      device,
      count,
      percentage: Math.round((count / total) * 100)
    }))
  })

  // Initialize tracking
  function initTracking() {
    // Track initial view
    trackView(quickDropId)

    // Set up time tracking
    const timeInterval = setInterval(trackTimeOnPage, 1000)

    // Set up visibility tracking
    document.addEventListener('visibilitychange', handleVisibilityChange)

    // Cleanup function
    return () => {
      clearInterval(timeInterval)
      document.removeEventListener('visibilitychange', handleVisibilityChange)
    }
  }

  return {
    analytics,
    recentActivities,
    isLoading,
    error,
    hasData,
    downloadRate,
    topReferrer,
    deviceBreakdown,
    trackView,
    trackDownload,
    loadAnalytics,
    exportAnalytics,
    initTracking
  }
}