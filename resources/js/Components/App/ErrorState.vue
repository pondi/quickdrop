<template>
  <div class="flex items-center justify-center min-h-[400px] w-full p-8">
    <div class="text-center max-w-md">
      <!-- Error icon -->
      <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-red-100 dark:bg-red-900/20 flex items-center justify-center">
        <ExclamationTriangleIcon class="w-10 h-10 text-red-500" />
      </div>
      
      <!-- Error message -->
      <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
        {{ title }}
      </h3>
      <p class="text-gray-600 dark:text-gray-400 mb-6">
        {{ message }}
      </p>
      
      <!-- Actions -->
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <Button
          v-if="showRetry"
          variant="primary"
          size="sm"
          @click="$emit('retry')"
        >
          <ArrowPathIcon class="w-4 h-4 mr-2" />
          Try Again
        </Button>
        <Button
          v-if="showHome"
          variant="outline"
          size="sm"
          as="Link"
          :href="route('dashboard')"
        >
          <HomeIcon class="w-4 h-4 mr-2" />
          Go Home
        </Button>
      </div>
      
      <!-- Error details (development only) -->
      <details v-if="isDevelopment && error" class="mt-6 text-left">
        <summary class="text-sm text-gray-500 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300">
          Show error details
        </summary>
        <pre class="mt-2 p-4 bg-gray-100 dark:bg-gray-800 rounded-lg text-xs text-gray-700 dark:text-gray-300 overflow-auto">{{ errorDetails }}</pre>
      </details>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Button from './Button.vue'
import { 
  ExclamationTriangleIcon,
  ArrowPathIcon,
  HomeIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  error: {
    type: Error,
    default: null
  },
  title: {
    type: String,
    default: 'Something went wrong'
  },
  message: {
    type: String,
    default: 'We encountered an error loading this content. Please try again.'
  },
  showRetry: {
    type: Boolean,
    default: true
  },
  showHome: {
    type: Boolean,
    default: true
  }
})

defineEmits(['retry'])

// Check if in development mode
const isDevelopment = computed(() => 
  import.meta.env.DEV || process.env.NODE_ENV === 'development'
)

// Format error details
const errorDetails = computed(() => {
  if (!props.error) return 'No error details available'
  
  return {
    message: props.error.message,
    stack: props.error.stack,
    name: props.error.name,
    ...(props.error.response && {
      response: {
        status: props.error.response.status,
        data: props.error.response.data
      }
    })
  }
})
</script>