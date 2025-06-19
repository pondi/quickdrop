<template>
  <div class="flex items-center justify-center min-h-[400px] w-full">
    <div class="relative">
      <!-- Outer ring -->
      <div class="w-16 h-16 rounded-full border-4 border-gray-200 dark:border-gray-700" />
      
      <!-- Spinning gradient ring -->
      <div class="absolute inset-0 w-16 h-16 rounded-full border-4 border-transparent border-t-indigo-500 border-r-purple-500 animate-spin" />
      
      <!-- Center dot -->
      <div class="absolute inset-0 flex items-center justify-center">
        <div class="w-2 h-2 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full animate-pulse" />
      </div>
    </div>
    
    <!-- Loading text -->
    <div v-if="showText" class="ml-4">
      <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ text }}
      </p>
      <div v-if="showProgress" class="mt-2 w-32">
        <div class="h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
          <div 
            class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full transition-all duration-300"
            :style="{ width: `${progress}%` }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  text: {
    type: String,
    default: 'Loading...'
  },
  showText: {
    type: Boolean,
    default: true
  },
  showProgress: {
    type: Boolean,
    default: false
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  }
})

// Progress simulation
const progress = ref(0)
let progressInterval = null

onMounted(() => {
  if (props.showProgress) {
    progressInterval = setInterval(() => {
      progress.value = Math.min(progress.value + Math.random() * 10, 90)
    }, 200)
  }
})

onUnmounted(() => {
  if (progressInterval) {
    clearInterval(progressInterval)
  }
})
</script>

<style scoped>
/* Smooth spinner animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Size variations */
.size-sm {
  width: 2rem;
  height: 2rem;
}

.size-lg {
  width: 4rem;
  height: 4rem;
}
</style>