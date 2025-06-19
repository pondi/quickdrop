import { ref, computed, watchEffect } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Loading state management composable
 */
export function useLoadingState(options = {}) {
  const {
    delay = 200,
    minDuration = 500,
    global = false
  } = options

  // State
  const isLoading = ref(false)
  const loadingMessage = ref('')
  const progress = ref(0)
  const error = ref(null)
  
  // Timers
  let delayTimer = null
  let minDurationTimer = null
  let startTime = null

  // Loading states
  const states = {
    idle: 'idle',
    loading: 'loading',
    success: 'success',
    error: 'error'
  }
  
  const currentState = ref(states.idle)

  // Computed
  const isIdle = computed(() => currentState.value === states.idle)
  const isSuccess = computed(() => currentState.value === states.success)
  const isError = computed(() => currentState.value === states.error)
  
  // Should show loading indicator (with delay)
  const showLoading = computed(() => {
    return isLoading.value && currentState.value === states.loading
  })

  // Start loading
  const startLoading = (message = '') => {
    // Clear any existing timers
    clearTimers()
    
    loadingMessage.value = message
    error.value = null
    startTime = Date.now()
    currentState.value = states.loading
    
    // Delay showing loader to prevent flash
    delayTimer = setTimeout(() => {
      isLoading.value = true
    }, delay)
  }

  // Stop loading
  const stopLoading = (success = true) => {
    const elapsed = startTime ? Date.now() - startTime : 0
    const remainingTime = Math.max(0, minDuration - elapsed)
    
    // Ensure minimum duration for smooth UX
    minDurationTimer = setTimeout(() => {
      isLoading.value = false
      currentState.value = success ? states.success : states.error
      clearTimers()
      
      // Reset to idle after success
      if (success) {
        setTimeout(() => {
          currentState.value = states.idle
        }, 1000)
      }
    }, remainingTime)
  }

  // Update progress
  const updateProgress = (value) => {
    progress.value = Math.min(100, Math.max(0, value))
  }

  // Set error
  const setError = (err) => {
    error.value = err
    stopLoading(false)
  }

  // Clear timers
  const clearTimers = () => {
    if (delayTimer) {
      clearTimeout(delayTimer)
      delayTimer = null
    }
    if (minDurationTimer) {
      clearTimeout(minDurationTimer)
      minDurationTimer = null
    }
  }

  // Reset state
  const reset = () => {
    clearTimers()
    isLoading.value = false
    loadingMessage.value = ''
    progress.value = 0
    error.value = null
    currentState.value = states.idle
  }

  // Wrap async function with loading state
  const withLoading = async (asyncFn, options = {}) => {
    const {
      message = '',
      onError = null,
      rethrow = true
    } = options

    startLoading(message)
    
    try {
      const result = await asyncFn()
      stopLoading(true)
      return result
    } catch (err) {
      setError(err)
      
      if (onError) {
        onError(err)
      }
      
      if (rethrow) {
        throw err
      }
    }
  }

  // Global loading for Inertia
  if (global) {
    let navigationProgress = null
    
    router.on('start', () => {
      startLoading('Loading...')
      navigationProgress = 0
    })
    
    router.on('progress', (event) => {
      if (event.detail.progress?.percentage) {
        navigationProgress = event.detail.progress.percentage
        updateProgress(navigationProgress)
      }
    })
    
    router.on('finish', () => {
      updateProgress(100)
      stopLoading(true)
    })
    
    router.on('error', (event) => {
      setError(event.detail.errors?.[0] || 'Navigation failed')
    })
  }

  // Cleanup
  watchEffect((onInvalidate) => {
    onInvalidate(() => {
      clearTimers()
    })
  })

  return {
    // State
    isLoading: showLoading,
    loadingMessage,
    progress,
    error,
    currentState,
    
    // Computed states
    isIdle,
    isSuccess,
    isError,
    
    // Methods
    startLoading,
    stopLoading,
    updateProgress,
    setError,
    reset,
    withLoading
  }
}

/**
 * Global loading state singleton
 */
let globalLoadingState = null

export function useGlobalLoading() {
  if (!globalLoadingState) {
    globalLoadingState = useLoadingState({ global: true })
  }
  return globalLoadingState
}