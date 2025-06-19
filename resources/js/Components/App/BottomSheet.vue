<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 overflow-hidden"
        @click="handleBackdropClick"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" />
        
        <!-- Sheet -->
        <Transition
          enter-active-class="transition ease-out duration-300"
          enter-from-class="translate-y-full"
          enter-to-class="translate-y-0"
          leave-active-class="transition ease-in duration-200"
          leave-from-class="translate-y-0"
          leave-to-class="translate-y-full"
        >
          <div
            v-if="isOpen"
            ref="sheetElement"
            class="absolute bottom-0 left-0 right-0 bg-white dark:bg-gray-900 rounded-t-3xl shadow-2xl transform"
            :style="{
              transform: `translateY(${currentOffset}px)`,
              transition: isDragging ? 'none' : 'transform 0.3s ease-out',
              maxHeight: maxHeight,
              height: sheetHeight
            }"
            @touchstart="handleTouchStart"
            @touchmove="handleTouchMove"
            @touchend="handleTouchEnd"
          >
            <!-- Drag Handle -->
            <div class="pt-4 pb-2">
              <div class="w-12 h-1 bg-gray-300 dark:bg-gray-700 rounded-full mx-auto" />
            </div>
            
            <!-- Header -->
            <div v-if="title || $slots.header" class="px-6 pb-4">
              <slot name="header">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                  {{ title }}
                </h3>
              </slot>
            </div>
            
            <!-- Content -->
            <div 
              ref="contentElement"
              class="px-6 pb-6 overflow-y-auto"
              :style="{
                maxHeight: `calc(${maxHeight} - 120px)`
              }"
            >
              <slot />
            </div>
            
            <!-- Footer -->
            <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
              <slot name="footer" />
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  maxHeight: {
    type: String,
    default: '90vh'
  },
  height: {
    type: String,
    default: 'auto'
  },
  snapPoints: {
    type: Array,
    default: () => [0.5, 0.9] // 50% and 90% of viewport height
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true
  },
  swipeToClose: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['close', 'snap'])

// Refs
const sheetElement = ref(null)
const contentElement = ref(null)
const isDragging = ref(false)
const currentOffset = ref(0)

// Touch tracking
let startY = 0
let currentY = 0
let currentSnapIndex = props.snapPoints.length - 1

// Computed
const sheetHeight = computed(() => {
  if (props.height !== 'auto') return props.height
  const snapPoint = props.snapPoints[currentSnapIndex]
  return `${snapPoint * 100}vh`
})

// Methods
function handleBackdropClick(event) {
  if (props.closeOnBackdrop && event.target === event.currentTarget) {
    close()
  }
}

// State
let sheetHeightPx = 0

function handleTouchStart(event) {
  if (!props.swipeToClose) return
  
  const touch = event.touches[0]
  startY = touch.clientY
  currentY = startY
  sheetHeightPx = sheetElement.value?.offsetHeight || 0
  isDragging.value = true
}

function handleTouchMove(event) {
  if (!isDragging.value) return
  
  const touch = event.touches[0]
  currentY = touch.clientY
  const deltaY = currentY - startY
  
  // Only allow dragging down
  if (deltaY > 0) {
    currentOffset.value = deltaY
    
    // Add resistance when dragging
    if (deltaY > 100) {
      currentOffset.value = 100 + (deltaY - 100) * 0.2
    }
  }
}

function handleTouchEnd() {
  if (!isDragging.value) return
  
  isDragging.value = false
  const velocity = (currentY - startY) / sheetHeightPx
  
  // Close if dragged more than 30% or with sufficient velocity
  if (currentOffset.value > sheetHeightPx * 0.3 || velocity > 0.3) {
    close()
  } else {
    // Snap to nearest snap point
    snapToPoint()
  }
  
  currentOffset.value = 0
  startY = 0
  currentY = 0
}

function snapToPoint() {
  // Find nearest snap point
  const currentHeight = sheetElement.value?.offsetHeight || 0
  const viewportHeight = window.innerHeight
  const currentRatio = currentHeight / viewportHeight
  
  let nearestIndex = 0
  let minDistance = Math.abs(props.snapPoints[0] - currentRatio)
  
  for (let i = 1; i < props.snapPoints.length; i++) {
    const distance = Math.abs(props.snapPoints[i] - currentRatio)
    if (distance < minDistance) {
      minDistance = distance
      nearestIndex = i
    }
  }
  
  if (nearestIndex !== currentSnapIndex) {
    currentSnapIndex = nearestIndex
    emit('snap', props.snapPoints[nearestIndex])
  }
}

function close() {
  emit('close')
}

// Prevent body scroll when sheet is open
function preventBodyScroll(prevent) {
  if (prevent) {
    document.body.style.overflow = 'hidden'
    document.body.style.position = 'fixed'
    document.body.style.width = '100%'
    document.body.style.top = `-${window.scrollY}px`
  } else {
    const scrollY = document.body.style.top
    document.body.style.overflow = ''
    document.body.style.position = ''
    document.body.style.width = ''
    document.body.style.top = ''
    window.scrollTo(0, parseInt(scrollY || '0') * -1)
  }
}

// Watch open state
watch(() => props.isOpen, async (isOpen) => {
  if (isOpen) {
    await nextTick()
    preventBodyScroll(true)
    
    // Reset to default snap point
    currentSnapIndex = props.snapPoints.length - 1
  } else {
    preventBodyScroll(false)
  }
})

// Cleanup
onUnmounted(() => {
  if (props.isOpen) {
    preventBodyScroll(false)
  }
})
</script>

<style scoped>
/* Smooth iOS-style scrolling */
.overflow-y-auto {
  -webkit-overflow-scrolling: touch;
  overscroll-behavior: contain;
}

/* Prevent text selection while dragging */
.transform {
  user-select: none;
  -webkit-user-select: none;
}
</style>