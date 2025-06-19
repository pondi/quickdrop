<template>
  <div class="relative">
    <!-- Pull indicator -->
    <div
      class="absolute top-0 left-0 right-0 flex justify-center overflow-hidden transition-all duration-300"
      :style="{
        height: `${indicatorHeight}px`,
        opacity: pullProgress
      }"
    >
      <div class="flex items-center justify-center py-4">
        <div
          class="relative w-10 h-10"
          :style="{
            transform: `rotate(${pullProgress * 360}deg)`
          }"
        >
          <!-- Circular progress -->
          <svg
            class="w-full h-full"
            viewBox="0 0 40 40"
            :class="[
              isRefreshing ? 'animate-spin' : ''
            ]"
          >
            <circle
              cx="20"
              cy="20"
              r="18"
              fill="none"
              stroke="currentColor"
              stroke-width="3"
              class="text-gray-200 dark:text-gray-700"
            />
            <circle
              cx="20"
              cy="20"
              r="18"
              fill="none"
              stroke="currentColor"
              stroke-width="3"
              class="text-indigo-500"
              :stroke-dasharray="`${pullProgress * 113} 113`"
              stroke-dashoffset="0"
              stroke-linecap="round"
              transform="rotate(-90 20 20)"
            />
          </svg>
          
          <!-- Icons -->
          <div class="absolute inset-0 flex items-center justify-center">
            <ArrowDownIcon
              v-if="!isRefreshing && pullProgress < 1"
              class="w-5 h-5 text-gray-600 dark:text-gray-400 transition-transform"
              :style="{
                transform: `translateY(${(1 - pullProgress) * -10}px)`
              }"
            />
            <CheckIcon
              v-else-if="!isRefreshing && pullProgress >= 1"
              class="w-5 h-5 text-green-500"
            />
            <ArrowPathIcon
              v-else
              class="w-5 h-5 text-indigo-500 animate-spin"
            />
          </div>
        </div>
      </div>
    </div>
    
    <!-- Content wrapper -->
    <div
      ref="contentWrapper"
      class="relative transition-transform duration-300"
      :style="{
        transform: `translateY(${contentOffset}px)`
      }"
    >
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { ArrowDownIcon, CheckIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  onRefresh: {
    type: Function,
    required: true
  },
  threshold: {
    type: Number,
    default: 80
  },
  maxPullDistance: {
    type: Number,
    default: 120
  },
  disabled: {
    type: Boolean,
    default: false
  }
})

// State
const isPulling = ref(false)
const isRefreshing = ref(false)
const pullDistance = ref(0)
const contentWrapper = ref(null)

// Touch tracking
let startY = 0
let currentY = 0

// Computed
const pullProgress = computed(() => 
  Math.min(1, pullDistance.value / props.threshold)
)

const indicatorHeight = computed(() => 
  Math.min(60, pullDistance.value * 0.5)
)

const contentOffset = computed(() => {
  if (isRefreshing.value) {
    return 60
  }
  return pullDistance.value * 0.5
})

// Touch handlers
function handleTouchStart(event) {
  if (props.disabled || isRefreshing.value) return
  
  // Only start if we're at the top
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop
  if (scrollTop > 0) return
  
  const touch = event.touches[0]
  startY = touch.clientY
  currentY = startY
}

function handleTouchMove(event) {
  if (!startY || props.disabled || isRefreshing.value) return
  
  const touch = event.touches[0]
  currentY = touch.clientY
  const deltaY = currentY - startY
  
  // Only track downward pulls
  if (deltaY > 0) {
    isPulling.value = true
    pullDistance.value = Math.min(props.maxPullDistance, deltaY)
    
    // Prevent default scrolling
    if (pullDistance.value > 10) {
      event.preventDefault()
    }
  }
}

async function handleTouchEnd() {
  if (!isPulling.value) return
  
  // Check if we've pulled far enough
  if (pullDistance.value >= props.threshold) {
    // Trigger refresh
    isRefreshing.value = true
    pullDistance.value = props.threshold
    
    // Haptic feedback
    if (window.navigator.vibrate) {
      window.navigator.vibrate(50)
    }
    
    try {
      await props.onRefresh()
    } catch (error) {
      console.error('Refresh error:', error)
    } finally {
      // Animate out
      setTimeout(() => {
        isRefreshing.value = false
        pullDistance.value = 0
      }, 500)
    }
  } else {
    // Snap back
    pullDistance.value = 0
  }
  
  // Reset
  isPulling.value = false
  startY = 0
  currentY = 0
}

function handleTouchCancel() {
  isPulling.value = false
  pullDistance.value = 0
  startY = 0
  currentY = 0
}

// Custom scroll prevention
function preventScroll(event) {
  if (isPulling.value && pullDistance.value > 10) {
    event.preventDefault()
  }
}

// Setup
onMounted(() => {
  document.addEventListener('touchstart', handleTouchStart, { passive: true })
  document.addEventListener('touchmove', handleTouchMove, { passive: false })
  document.addEventListener('touchend', handleTouchEnd)
  document.addEventListener('touchcancel', handleTouchCancel)
  
  // Prevent overscroll on iOS
  document.body.addEventListener('touchmove', preventScroll, { passive: false })
})

onUnmounted(() => {
  document.removeEventListener('touchstart', handleTouchStart)
  document.removeEventListener('touchmove', handleTouchMove)
  document.removeEventListener('touchend', handleTouchEnd)
  document.removeEventListener('touchcancel', handleTouchCancel)
  document.body.removeEventListener('touchmove', preventScroll)
})
</script>