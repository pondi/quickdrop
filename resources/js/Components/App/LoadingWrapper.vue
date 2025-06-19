<template>
  <div class="loading-wrapper">
    <!-- Skeleton Loading -->
    <div v-if="showSkeleton" class="skeleton-wrapper">
      <slot name="skeleton">
        <component 
          :is="skeletonComponent"
          v-bind="skeletonProps"
        />
      </slot>
    </div>
    
    <!-- Actual Content -->
    <Transition
      :name="transition"
      mode="out-in"
      @before-enter="handleBeforeEnter"
      @after-enter="handleAfterEnter"
    >
      <div
        v-show="!loading || !showSkeleton"
        :key="contentKey"
        class="content-wrapper"
      >
        <slot />
      </div>
    </Transition>
    
    <!-- Loading Overlay -->
    <Transition name="fade">
      <div
        v-if="loading && overlay"
        class="loading-overlay"
      >
        <div class="loading-content">
          <LoadingSpinner
            :size="spinnerSize"
            :text="loadingText"
            :show-text="showLoadingText"
            :show-progress="showProgress"
          />
        </div>
      </div>
    </Transition>
    
    <!-- Progress Bar -->
    <div
      v-if="loading && showProgress && !overlay"
      class="loading-progress"
    >
      <div
        class="progress-bar"
        :style="{ width: `${progress}%` }"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, toRef } from 'vue'
import LoadingSpinner from './LoadingSpinner.vue'
import SkeletonLoader from './SkeletonLoader.vue'

const props = defineProps({
  loading: {
    type: Boolean,
    default: false
  },
  skeleton: {
    type: [Boolean, String],
    default: true
  },
  skeletonComponent: {
    type: [String, Object],
    default: () => SkeletonLoader
  },
  skeletonProps: {
    type: Object,
    default: () => ({})
  },
  overlay: {
    type: Boolean,
    default: false
  },
  loadingText: {
    type: String,
    default: 'Loading...'
  },
  showLoadingText: {
    type: Boolean,
    default: true
  },
  progress: {
    type: Number,
    default: 0
  },
  showProgress: {
    type: Boolean,
    default: false
  },
  spinnerSize: {
    type: String,
    default: 'md'
  },
  transition: {
    type: String,
    default: 'fade'
  },
  delay: {
    type: Number,
    default: 200
  },
  minDuration: {
    type: Number,
    default: 500
  }
})

// State
const showSkeleton = ref(false)
const isFirstLoad = ref(true)
const contentKey = ref(0)

// Timers
let showTimer = null
let hideTimer = null
let loadStartTime = null

// Loading ref
const loadingRef = toRef(props, 'loading')

// Handle loading state changes
watch(loadingRef, (newLoading) => {
  if (newLoading) {
    handleLoadingStart()
  } else {
    handleLoadingEnd()
  }
}, { immediate: true })

// Handle loading start
function handleLoadingStart() {
  // Clear any existing timers
  clearTimers()
  
  loadStartTime = Date.now()
  
  // Show skeleton after delay
  if (props.skeleton && isFirstLoad.value) {
    showTimer = setTimeout(() => {
      showSkeleton.value = true
    }, props.delay)
  }
}

// Handle loading end
function handleLoadingEnd() {
  const elapsed = loadStartTime ? Date.now() - loadStartTime : 0
  const remainingTime = Math.max(0, props.minDuration - elapsed)
  
  // Hide with minimum duration
  hideTimer = setTimeout(() => {
    showSkeleton.value = false
    isFirstLoad.value = false
    contentKey.value++ // Force re-render for transitions
    clearTimers()
  }, remainingTime)
}

// Clear timers
function clearTimers() {
  if (showTimer) {
    clearTimeout(showTimer)
    showTimer = null
  }
  if (hideTimer) {
    clearTimeout(hideTimer)
    hideTimer = null
  }
}

// Transition hooks
function handleBeforeEnter(el) {
  el.style.willChange = 'opacity, transform'
}

function handleAfterEnter(el) {
  el.style.willChange = ''
}

// Cleanup
onUnmounted(() => {
  clearTimers()
})
</script>

<style scoped>
.loading-wrapper {
  position: relative;
  min-height: inherit;
}

.skeleton-wrapper,
.content-wrapper {
  min-height: inherit;
}

/* Loading overlay */
.loading-overlay {
  position: absolute;
  inset: 0;
  background-color: rgba(255, 255, 255, 0.9);
  dark:background-color: rgba(0, 0, 0, 0.9);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.loading-content {
  text-align: center;
}

/* Progress bar */
.loading-progress {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background-color: rgba(0, 0, 0, 0.05);
  dark:background-color: rgba(255, 255, 255, 0.05);
  overflow: hidden;
  z-index: 20;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
  transition: width 0.3s ease;
  box-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
}

/* Transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Scale transition */
.scale-enter-active,
.scale-leave-active {
  transition: all 0.3s ease;
}

.scale-enter-from {
  opacity: 0;
  transform: scale(0.95);
}

.scale-leave-to {
  opacity: 0;
  transform: scale(1.05);
}

/* Slide transition */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
}

.slide-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>