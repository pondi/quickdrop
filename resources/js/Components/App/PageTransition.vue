<template>
  <div class="relative">
    <!-- Loading bar -->
    <div
      v-if="isTransitioning"
      class="fixed top-0 left-0 right-0 z-[60] h-1 bg-gradient-to-r from-indigo-500 to-purple-600"
      :style="{
        transform: `scaleX(${progress})`,
        transformOrigin: 'left',
        transition: 'transform 0.3s ease-out'
      }"
    />
    
    <!-- Page transition wrapper -->
    <Transition
      :name="transitionName"
      mode="out-in"
      @before-enter="handleBeforeEnter"
      @after-enter="handleAfterEnter"
      @before-leave="handleBeforeLeave"
      @after-leave="handleAfterLeave"
    >
      <div :key="$page.url" class="transition-wrapper">
        <slot />
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { usePageTransitions } from '@/Composables/usePageTransitions'

const page = usePage()
const { transitionName, isTransitioning, transitions } = usePageTransitions()

// Progress bar
const progress = ref(0)
let progressInterval = null

// Watch for transitions
watch(isTransitioning, (transitioning) => {
  if (transitioning) {
    progress.value = 0
    progressInterval = setInterval(() => {
      progress.value = Math.min(progress.value + 0.1, 0.9)
    }, 50)
  } else {
    progress.value = 1
    if (progressInterval) {
      clearInterval(progressInterval)
    }
    setTimeout(() => {
      progress.value = 0
    }, 300)
  }
})

// Transition handlers
function handleBeforeEnter(el) {
  // Add will-change for performance
  el.style.willChange = 'transform, opacity'
}

function handleAfterEnter(el) {
  // Remove will-change
  el.style.willChange = ''
}

function handleBeforeLeave(el) {
  // Fix position during transition
  const rect = el.getBoundingClientRect()
  el.style.position = 'fixed'
  el.style.top = `${rect.top}px`
  el.style.left = `${rect.left}px`
  el.style.width = `${rect.width}px`
  el.style.willChange = 'transform, opacity'
}

function handleAfterLeave(el) {
  el.style.position = ''
  el.style.top = ''
  el.style.left = ''
  el.style.width = ''
  el.style.willChange = ''
}
</script>

<style scoped>
/* Base transition wrapper */
.transition-wrapper {
  position: relative;
  width: 100%;
  min-height: 100vh;
}

/* Slide Right */
.slide-right-enter-active,
.slide-right-leave-active {
  transition: all 0.3s ease;
}

.slide-right-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.slide-right-leave-to {
  transform: translateX(-30%);
  opacity: 0;
}

/* Slide Left */
.slide-left-enter-active,
.slide-left-leave-active {
  transition: all 0.3s ease;
}

.slide-left-enter-from {
  transform: translateX(-100%);
  opacity: 0;
}

.slide-left-leave-to {
  transform: translateX(30%);
  opacity: 0;
}

/* Slide Up */
.slide-up-enter-active {
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.slide-up-leave-active {
  transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.slide-up-enter-from {
  transform: translateY(100%);
  opacity: 0;
}

.slide-up-leave-to {
  transform: translateY(-10%);
  opacity: 0;
}

/* Fade */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Scale */
.scale-enter-active,
.scale-leave-active {
  transition: all 0.3s ease;
}

.scale-enter-from {
  transform: scale(0.95);
  opacity: 0;
}

.scale-leave-to {
  transform: scale(1.05);
  opacity: 0;
}

/* iOS Push */
.ios-push-enter-active,
.ios-push-leave-active {
  transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.ios-push-enter-from {
  transform: translateX(100%);
  box-shadow: -10px 0 20px rgba(0, 0, 0, 0.1);
}

.ios-push-leave-to {
  transform: translateX(-33%);
  opacity: 0.7;
}

/* iOS Pop */
.ios-pop-enter-active,
.ios-pop-leave-active {
  transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.ios-pop-enter-from {
  transform: translateX(-33%);
  opacity: 0.7;
}

.ios-pop-leave-to {
  transform: translateX(100%);
  box-shadow: -10px 0 20px rgba(0, 0, 0, 0.1);
}

/* Disable transitions for reduced motion */
@media (prefers-reduced-motion: reduce) {
  * {
    transition-duration: 0.01s !important;
  }
}

/* Mobile optimizations */
@media (max-width: 768px) {
  .transition-wrapper {
    /* Hardware acceleration */
    transform: translateZ(0);
    backface-visibility: hidden;
    perspective: 1000px;
  }
}
</style>