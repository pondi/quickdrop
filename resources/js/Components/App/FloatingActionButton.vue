<template>
  <Teleport to="body">
    <!-- FAB Container -->
    <div
      class="fixed bottom-6 right-6 z-40"
      :class="{ 'pointer-events-none': !isVisible }"
    >
      <!-- Speed Dial Actions -->
      <Transition name="speed-dial">
        <div
          v-if="showSpeedDial && actions.length > 0"
          class="absolute bottom-16 right-0 space-y-3"
        >
          <TransitionGroup name="speed-dial-item">
            <div
              v-for="(action, index) in actions"
              :key="action.id"
              :style="{ transitionDelay: `${index * 50}ms` }"
              class="flex items-center justify-end space-x-3"
            >
              <!-- Label -->
              <span
                v-if="action.label"
                class="bg-gray-900 text-white text-sm px-3 py-1.5 rounded-lg shadow-lg whitespace-nowrap"
              >
                {{ action.label }}
              </span>
              
              <!-- Mini FAB -->
              <button
                @click="handleAction(action)"
                class="w-12 h-12 rounded-full shadow-lg flex items-center justify-center transform transition-all duration-200 hover:scale-110 active:scale-95"
                :class="action.class || 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
              >
                <component
                  :is="action.icon"
                  class="w-5 h-5"
                />
              </button>
            </div>
          </TransitionGroup>
        </div>
      </Transition>
      
      <!-- Main FAB -->
      <button
        ref="fabButton"
        @click="handleMainClick"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
        class="relative w-14 h-14 rounded-full shadow-lg flex items-center justify-center transform transition-all duration-300 hover:scale-110 active:scale-95"
        :class="[
          mainButtonClass,
          {
            'animate-pulse-glow': pulse,
            'scale-0': !isVisible,
            'scale-100': isVisible
          }
        ]"
        :style="{
          transform: `translate(${dragOffset.x}px, ${dragOffset.y}px) scale(${isVisible ? 1 : 0})`
        }"
      >
        <!-- Icon transition -->
        <Transition name="rotate" mode="out-in">
          <component
            :is="currentIcon"
            :key="showSpeedDial ? 'close' : 'main'"
            class="w-6 h-6 text-white"
          />
        </Transition>
        
        <!-- Ripple effect -->
        <span
          v-if="showRipple"
          class="absolute inset-0 rounded-full animate-ping bg-white/30"
        />
      </button>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { 
  PlusIcon,
  XMarkIcon,
  CameraIcon,
  DocumentIcon,
  FolderIcon,
  ShareIcon,
  ArrowUpTrayIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  icon: {
    type: [Object, String],
    default: () => PlusIcon
  },
  actions: {
    type: Array,
    default: () => []
  },
  position: {
    type: String,
    default: 'bottom-right',
    validator: (value) => ['bottom-right', 'bottom-left', 'top-right', 'top-left'].includes(value)
  },
  hideOnScroll: {
    type: Boolean,
    default: true
  },
  pulse: {
    type: Boolean,
    default: false
  },
  mainButtonClass: {
    type: String,
    default: 'bg-gradient-to-r from-indigo-500 to-purple-600'
  }
})

const emit = defineEmits(['click', 'action'])

// State
const isVisible = ref(true)
const showSpeedDial = ref(false)
const showRipple = ref(false)
const fabButton = ref(null)

// Drag state
const isDragging = ref(false)
const dragOffset = ref({ x: 0, y: 0 })
let startPos = { x: 0, y: 0 }
let fabPos = { x: 0, y: 0 }

// Computed
const currentIcon = computed(() => 
  showSpeedDial.value ? XMarkIcon : props.icon
)

// Methods
function handleMainClick() {
  if (props.actions.length > 0) {
    showSpeedDial.value = !showSpeedDial.value
  } else {
    emit('click')
  }
  
  // Ripple effect
  showRipple.value = true
  setTimeout(() => {
    showRipple.value = false
  }, 600)
}

function handleAction(action) {
  showSpeedDial.value = false
  emit('action', action)
  
  if (action.handler) {
    action.handler()
  }
}

// Touch drag handlers
function handleTouchStart(event) {
  const touch = event.touches[0]
  startPos = { x: touch.clientX, y: touch.clientY }
  fabPos = { x: dragOffset.value.x, y: dragOffset.value.y }
  isDragging.value = false
}

function handleTouchMove(event) {
  const touch = event.touches[0]
  const deltaX = touch.clientX - startPos.x
  const deltaY = touch.clientY - startPos.y
  
  // Start dragging after threshold
  if (Math.abs(deltaX) > 10 || Math.abs(deltaY) > 10) {
    isDragging.value = true
    event.preventDefault()
    
    // Update position with bounds
    const maxX = window.innerWidth - 100
    const maxY = window.innerHeight - 100
    
    dragOffset.value = {
      x: Math.max(-maxX, Math.min(0, fabPos.x + deltaX)),
      y: Math.max(-maxY, Math.min(0, fabPos.y + deltaY))
    }
  }
}

function handleTouchEnd(event) {
  if (isDragging.value) {
    event.preventDefault()
    // Snap to edge
    snapToEdge()
  }
  isDragging.value = false
}

function snapToEdge() {
  const button = fabButton.value
  if (!button) return
  
  const rect = button.getBoundingClientRect()
  const centerX = rect.left + rect.width / 2
  const screenWidth = window.innerWidth
  
  // Snap to nearest edge
  if (centerX < screenWidth / 2) {
    // Snap to left
    dragOffset.value.x = -(rect.left - 24)
  } else {
    // Snap to right
    dragOffset.value.x = 0
  }
}

// Scroll visibility
let lastScrollY = 0
let scrollTimeout = null

function handleScroll() {
  if (!props.hideOnScroll) return
  
  const currentScrollY = window.scrollY
  
  if (currentScrollY > lastScrollY && currentScrollY > 100) {
    // Scrolling down
    isVisible.value = false
    showSpeedDial.value = false
  } else {
    // Scrolling up
    isVisible.value = true
  }
  
  lastScrollY = currentScrollY
  
  // Clear existing timeout
  if (scrollTimeout) clearTimeout(scrollTimeout)
  
  // Show FAB when scrolling stops
  scrollTimeout = setTimeout(() => {
    isVisible.value = true
  }, 500)
}

// Click outside handler
function handleClickOutside(event) {
  if (showSpeedDial.value && !event.target.closest('.speed-dial')) {
    showSpeedDial.value = false
  }
}

// Lifecycle
onMounted(() => {
  window.addEventListener('scroll', handleScroll, { passive: true })
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  document.removeEventListener('click', handleClickOutside)
  if (scrollTimeout) clearTimeout(scrollTimeout)
})
</script>

<style scoped>
/* Speed dial transitions */
.speed-dial-enter-active,
.speed-dial-leave-active {
  transition: all 0.3s ease;
}

.speed-dial-enter-from,
.speed-dial-leave-to {
  opacity: 0;
  transform: scale(0.8) translateY(20px);
}

/* Speed dial items */
.speed-dial-item-enter-active,
.speed-dial-item-leave-active {
  transition: all 0.3s ease;
}

.speed-dial-item-enter-from {
  opacity: 0;
  transform: translateX(20px);
}

.speed-dial-item-leave-to {
  opacity: 0;
  transform: translateX(20px) scale(0.8);
}

/* Icon rotation */
.rotate-enter-active,
.rotate-leave-active {
  transition: all 0.3s ease;
}

.rotate-enter-from {
  transform: rotate(-180deg);
}

.rotate-leave-to {
  transform: rotate(180deg);
}

/* Pulse glow animation */
@keyframes pulse-glow {
  0%, 100% {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  }
  50% {
    box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4), 0 4px 6px -2px rgba(99, 102, 241, 0.2);
  }
}

.animate-pulse-glow {
  animation: pulse-glow 2s ease-in-out infinite;
}
</style>