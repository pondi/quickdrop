<template>
  <div class="relative overflow-hidden">
    <!-- Swipe action backgrounds -->
    <div class="absolute inset-0 flex">
      <!-- Delete action (left swipe) -->
      <div 
        class="flex-1 bg-red-500 flex items-center justify-start px-6"
        :class="{ 'opacity-0': swipeDirection !== 'left' }"
      >
        <TrashIcon class="w-6 h-6 text-white" />
      </div>
      
      <!-- Share action (right swipe) -->
      <div 
        class="flex-1 bg-blue-500 flex items-center justify-end px-6"
        :class="{ 'opacity-0': swipeDirection !== 'right' }"
      >
        <ShareIcon class="w-6 h-6 text-white" />
      </div>
    </div>
    
    <!-- File card -->
    <div
      ref="cardElement"
      class="relative bg-white dark:bg-gray-800 rounded-xl shadow-lg transform transition-transform"
      :style="{
        transform: `translateX(${currentOffset}px)`,
        transition: isDragging ? 'none' : 'transform 0.3s ease-out'
      }"
    >
      <FileCard
        :file="file"
        :show-actions="!isSwiping"
        @click="handleClick"
      />
      
      <!-- Long press menu -->
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div
          v-if="showLongPressMenu"
          class="absolute inset-x-0 top-0 -mt-32 mx-4"
        >
          <div class="bg-gray-900 text-white rounded-lg shadow-xl p-2">
            <button
              v-for="action in longPressActions"
              :key="action.id"
              @click="handleLongPressAction(action)"
              class="w-full text-left px-4 py-2 hover:bg-gray-800 rounded-lg flex items-center space-x-3"
            >
              <component :is="action.icon" class="w-5 h-5" />
              <span>{{ action.label }}</span>
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useTouchGestures } from '@/Composables/useTouchGestures'
import FileCard from './FileCard.vue'
import { 
  TrashIcon, 
  ShareIcon, 
  ArrowDownTrayIcon,
  LockClosedIcon,
  DuplicateIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  file: {
    type: Object,
    required: true
  },
  onDelete: {
    type: Function,
    default: null
  },
  onShare: {
    type: Function,
    default: null
  }
})

const emit = defineEmits(['click', 'delete', 'share', 'action'])

// Refs
const cardElement = ref(null)
const isDragging = ref(false)
const currentOffset = ref(0)
const showLongPressMenu = ref(false)

// Touch gestures
const { 
  isSwiping, 
  swipeDirection, 
  swipeDistance,
  isLongPressing,
  onSwipe,
  onLongPress,
  setup
} = useTouchGestures(cardElement)

// Constants
const SWIPE_CONFIRM_THRESHOLD = 100
const MAX_SWIPE_DISTANCE = 150

// Long press actions
const longPressActions = [
  { id: 'download', label: 'Download', icon: ArrowDownTrayIcon },
  { id: 'share', label: 'Share', icon: ShareIcon },
  { id: 'duplicate', label: 'Duplicate', icon: DuplicateIcon },
  { id: 'encrypt', label: 'Encrypt', icon: LockClosedIcon },
  { id: 'info', label: 'File Info', icon: InformationCircleIcon },
  { id: 'delete', label: 'Delete', icon: TrashIcon }
]

// Watch swipe
watch(isSwiping, (swiping) => {
  isDragging.value = swiping
})

watch(swipeDistance, (distance) => {
  if (isSwiping.value) {
    currentOffset.value = Math.max(
      -MAX_SWIPE_DISTANCE, 
      Math.min(MAX_SWIPE_DISTANCE, distance)
    )
  }
})

// Swipe handlers
onSwipe('left', ({ distance }) => {
  if (distance >= SWIPE_CONFIRM_THRESHOLD) {
    // Animate card flying off
    currentOffset.value = -window.innerWidth
    setTimeout(() => {
      emit('delete', props.file)
      currentOffset.value = 0
    }, 300)
  } else {
    // Snap back
    currentOffset.value = 0
  }
})

onSwipe('right', ({ distance }) => {
  if (distance >= SWIPE_CONFIRM_THRESHOLD) {
    // Animate and trigger share
    currentOffset.value = window.innerWidth
    setTimeout(() => {
      emit('share', props.file)
      currentOffset.value = 0
    }, 300)
  } else {
    // Snap back
    currentOffset.value = 0
  }
})

// Long press handler
onLongPress(() => {
  showLongPressMenu.value = true
})

// Methods
function handleClick() {
  if (!isSwiping.value && !isLongPressing.value) {
    emit('click', props.file)
  }
}

function handleLongPressAction(action) {
  showLongPressMenu.value = false
  emit('action', { action: action.id, file: props.file })
}

// Close menu on outside click
function handleOutsideClick(event) {
  if (showLongPressMenu.value && !event.target.closest('.long-press-menu')) {
    showLongPressMenu.value = false
  }
}

// Setup
onMounted(() => {
  document.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick)
})
</script>