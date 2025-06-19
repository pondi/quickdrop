<template>
  <div>
    <div v-if="hasError" class="error-boundary">
      <!-- Error State -->
      <div class="min-h-screen flex items-center justify-center p-8">
        <div class="max-w-2xl w-full">
          <Card class="text-center p-8 md:p-12">
            <!-- Error Icon -->
            <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center">
              <ExclamationTriangleIcon class="w-12 h-12 text-red-500" />
            </div>
            
            <!-- Error Message -->
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4">
              {{ title }}
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 mb-8">
              {{ message }}
            </p>
            
            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
              <Button
                variant="primary"
                @click="handleReload"
              >
                <ArrowPathIcon class="w-5 h-5 mr-2" />
                Reload Page
              </Button>
              <Button
                variant="outline"
                as="a"
                :href="route('dashboard')"
              >
                <HomeIcon class="w-5 h-5 mr-2" />
                Go to Dashboard
              </Button>
            </div>
            
            <!-- Error Details (Development) -->
            <div v-if="isDevelopment && error" class="mt-8">
              <details class="text-left">
                <summary class="cursor-pointer text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                  Show error details
                </summary>
                <div class="mt-4 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-auto">
                  <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ errorDetails }}</pre>
                </div>
              </details>
            </div>
            
            <!-- Report Button -->
            <div class="mt-8">
              <button
                @click="reportError"
                class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 underline"
              >
                Report this error
              </button>
            </div>
          </Card>
        </div>
      </div>
    </div>
    
    <!-- Normal Content -->
    <div v-else>
      <slot />
    </div>
  </div>
</template>

<script>
import { defineComponent, ref, computed, onErrorCaptured } from 'vue'
import { router } from '@inertiajs/vue3'
import Card from './Card.vue'
import Button from './Button.vue'
import { 
  ExclamationTriangleIcon,
  ArrowPathIcon,
  HomeIcon
} from '@heroicons/vue/24/outline'

export default defineComponent({
  name: 'ErrorBoundary',
  
  components: {
    Card,
    Button,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    HomeIcon
  },
  
  props: {
    fallback: {
      type: Object,
      default: null
    },
    onError: {
      type: Function,
      default: null
    },
    title: {
      type: String,
      default: 'Oops! Something went wrong'
    },
    message: {
      type: String,
      default: 'We encountered an unexpected error. Please try again or contact support if the problem persists.'
    },
    resetOnRouteChange: {
      type: Boolean,
      default: true
    }
  },
  
  setup(props, { emit }) {
    const hasError = ref(false)
    const error = ref(null)
    const errorInfo = ref(null)
    
    // Check if development
    const isDevelopment = computed(() => 
      import.meta.env.DEV || process.env.NODE_ENV === 'development'
    )
    
    // Error details
    const errorDetails = computed(() => {
      if (!error.value) return 'No error details available'
      
      return {
        message: error.value.message,
        stack: error.value.stack,
        component: errorInfo.value?.component,
        props: errorInfo.value?.props,
        timestamp: new Date().toISOString()
      }
    })
    
    // Error handler
    const handleError = (err, component, info) => {
      console.error('Error caught by ErrorBoundary:', err)
      
      hasError.value = true
      error.value = err
      errorInfo.value = {
        component: component?.$options?.name || 'Unknown',
        ...info
      }
      
      // Call custom error handler
      if (props.onError) {
        props.onError(err, component, info)
      }
      
      // Send error to logging service
      logError(err, errorInfo.value)
      
      // Emit error event
      emit('error', { error: err, info: errorInfo.value })
      
      return false // Prevent error propagation
    }
    
    // Capture errors
    onErrorCaptured(handleError)
    
    // Reset error state
    const resetError = () => {
      hasError.value = false
      error.value = null
      errorInfo.value = null
    }
    
    // Handle reload
    const handleReload = () => {
      resetError()
      window.location.reload()
    }
    
    // Report error
    const reportError = () => {
      const errorReport = {
        ...errorDetails.value,
        userAgent: navigator.userAgent,
        url: window.location.href
      }
      
      // Open email client with error report
      const subject = encodeURIComponent('Error Report: ' + error.value?.message)
      const body = encodeURIComponent(JSON.stringify(errorReport, null, 2))
      window.open(`mailto:support@quickdrop.app?subject=${subject}&body=${body}`)
    }
    
    // Log error to service
    const logError = async (err, info) => {
      try {
        // Send to error tracking service (e.g., Sentry, LogRocket)
        if (window.Sentry) {
          window.Sentry.captureException(err, {
            extra: info
          })
        }
        
        // Or send to your backend
        await fetch('/api/errors', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
          },
          body: JSON.stringify({
            message: err.message,
            stack: err.stack,
            info,
            url: window.location.href,
            timestamp: new Date().toISOString()
          })
        })
      } catch (logErr) {
        console.error('Failed to log error:', logErr)
      }
    }
    
    // Reset on route change
    if (props.resetOnRouteChange) {
      router.on('navigate', resetError)
    }
    
    // Expose reset method
    const reset = () => {
      resetError()
    }
    
    return {
      hasError,
      error,
      errorInfo,
      isDevelopment,
      errorDetails,
      handleReload,
      reportError,
      reset
    }
  }
})
</script>

<style scoped>
.error-boundary {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
</style>