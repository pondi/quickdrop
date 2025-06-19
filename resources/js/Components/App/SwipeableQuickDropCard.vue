<template>
  <div class="relative overflow-hidden rounded-2xl">
    <!-- Swipe action backgrounds -->
    <div class="absolute inset-0 flex">
      <!-- Delete action (left swipe) -->
      <div 
        class="flex-1 bg-gradient-to-r from-red-600 to-red-500 flex items-center justify-start px-6"
        :class="{ 'opacity-0': swipeDirection !== 'left' }"
      >
        <TrashIcon class="w-6 h-6 text-white" />
      </div>
      
      <!-- Share action (right swipe) -->
      <div 
        class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 flex items-center justify-end px-6"
        :class="{ 'opacity-0': swipeDirection !== 'right' }"
      >
        <ShareIcon class="w-6 h-6 text-white" />
      </div>
    </div>
    
    <!-- QuickDrop card -->
    <div
      ref="cardElement"
      class="relative transform transition-transform"
      :style="{
        transform: `translateX(${currentOffset}px)`,
        transition: isDragging ? 'none' : 'transform 0.3s ease-out'
      }"
    >
      <Card 
        :hoverable="true"
        class="h-full flex flex-col"
        @click="handleClick"
      >
        <!-- Status Badge -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-2">
            <span 
              class="px-3 py-1 rounded-full text-xs font-medium"
              :class="{
                'bg-green-500/20 text-green-400': box.is_active,
                'bg-red-500/20 text-red-400': !box.is_active,
              }"
            >
              {{ box.is_active ? 'Active' : 'Expired' }}
            </span>
            <span v-if="box.is_encrypted" class="px-3 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400">
              <Icon name="lock" :size="12" class="inline mr-1" />
              Encrypted
            </span>
          </div>
        </div>

        <!-- Content -->
        <div class="flex-1">
          <h3 class="text-lg font-semibold text-text-primary mb-2">
            {{ box.title }}
          </h3>
          
          <div class="space-y-2 text-sm text-text-secondary">
            <div class="flex items-center justify-between">
              <span class="flex items-center">
                <Icon name="clock" :size="14" class="mr-1" />
                Created
              </span>
              <span>{{ formatDate(box.created_at) }}</span>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="flex items-center">
                <Icon name="timer" :size="14" class="mr-1" />
                Expires
              </span>
              <span :class="{ 'text-red-400': !box.is_active }">
                {{ timeUntilExpiry(box.expires_at) }}
              </span>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="flex items-center">
                <Icon name="files" :size="14" class="mr-1" />
                Files
              </span>
              <span>{{ box.files_count }} ({{ formatBytes(box.total_size) }})</span>
            </div>
            
            <div v-if="box.reference_number" class="flex items-center justify-between">
              <span class="flex items-center">
                <Icon name="hash" :size="14" class="mr-1" />
                Reference
              </span>
              <span class="font-mono text-xs">{{ box.reference_number }}</span>
            </div>
          </div>
          
          <div v-if="box.comment" class="mt-4 p-3 rounded-lg bg-surface">
            <p class="text-xs text-text-secondary line-clamp-2">
              {{ box.comment }}
            </p>
          </div>
        </div>

        <!-- Actions (hidden when swiping) -->
        <div v-if="!isSwiping" class="mt-6 flex space-x-2">
          <Button
            variant="primary"
            size="sm"
            icon="eye"
            as="Link"
            :href="box.upload_url"
            class="flex-1"
          >
            View
          </Button>
          <Button
            variant="outline"
            size="sm"
            icon="share2"
            @click.stop="() => emit('share', box)"
            class="flex-1"
          >
            Share
          </Button>
        </div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useTouchGestures } from '@/Composables/useTouchGestures'
import Card from './Card.vue'
import Button from './Button.vue'
import Icon from './Icon.vue'
import { TrashIcon, ShareIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  box: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['click', 'delete', 'share'])

// Refs
const cardElement = ref(null)
const isDragging = ref(false)
const currentOffset = ref(0)

// Touch gestures
const { 
  isSwiping, 
  swipeDirection, 
  swipeDistance,
  onSwipe,
  setup
} = useTouchGestures(cardElement)

// Constants
const SWIPE_CONFIRM_THRESHOLD = 100
const MAX_SWIPE_DISTANCE = 150

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
      emit('delete', props.box)
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
      emit('share', props.box)
      currentOffset.value = 0
    }, 300)
  } else {
    // Snap back
    currentOffset.value = 0
  }
})

// Methods
function handleClick() {
  if (!isSwiping.value) {
    emit('click', props.box)
  }
}

// Formatting helpers (these would normally come from props or composables)
const formatBytes = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (date) => {
  const d = new Date(date);
  const now = new Date();
  const diff = now - d;
  
  if (diff < 60000) return 'Just now';
  if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;
  if (diff < 604800000) return `${Math.floor(diff / 86400000)}d ago`;
  
  return d.toLocaleDateString();
};

const timeUntilExpiry = (expiryDate) => {
  const now = new Date();
  const expiry = new Date(expiryDate);
  const diff = expiry - now;
  
  if (diff <= 0) return 'Expired';
  if (diff < 3600000) return `${Math.floor(diff / 60000)}m left`;
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}h left`;
  return `${Math.floor(diff / 86400000)}d left`;
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>