<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0 transform -translate-y-full"
    enter-to-class="opacity-100 transform translate-y-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100 transform translate-y-0"
    leave-to-class="opacity-0 transform -translate-y-full"
  >
    <div
      v-if="shouldShow && !dismissed"
      :class="[
        'fixed top-0 left-0 right-0 z-40 px-4 py-3 shadow-lg',
        getBannerClass()
      ]"
    >
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center flex-1">
          <component :is="getIcon()" class="w-5 h-5 mr-3 flex-shrink-0" />
          <p class="text-sm font-medium">
            {{ getMessage() }}
          </p>
          <button
            @click="handleUpgrade"
            class="ml-4 inline-flex items-center px-3 py-1 rounded-md text-sm font-medium bg-white/20 hover:bg-white/30 transition-colors"
          >
            Upgrade Now
            <ArrowRightIcon class="w-4 h-4 ml-1" />
          </button>
        </div>
        
        <button
          @click="dismiss"
          class="ml-4 p-1 rounded-lg hover:bg-white/20 transition-colors"
        >
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  XMarkIcon,
  ExclamationTriangleIcon,
  ExclamationCircleIcon,
  ArrowRightIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  storagePercentage: {
    type: Number,
    required: true
  },
  storageUsed: {
    type: Number,
    required: true
  },
  storageTotal: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['upgrade'])

// State
const dismissed = ref(false)
const dismissKey = `storage-banner-${Math.floor(props.storagePercentage / 10) * 10}`

// Computed
const shouldShow = computed(() => {
  return props.storagePercentage >= 80
})

// Methods
function getBannerClass() {
  if (props.storagePercentage >= 95) {
    return 'bg-red-600 text-white'
  } else if (props.storagePercentage >= 90) {
    return 'bg-orange-500 text-white'
  } else {
    return 'bg-yellow-500 text-white'
  }
}

function getIcon() {
  if (props.storagePercentage >= 90) {
    return ExclamationCircleIcon
  }
  return ExclamationTriangleIcon
}

function getMessage() {
  const remaining = props.storageTotal - props.storageUsed
  const remainingGB = (remaining / (1024 * 1024 * 1024)).toFixed(1)
  
  if (props.storagePercentage >= 95) {
    return `Critical: Only ${remainingGB} GB of storage remaining! Upgrade now to avoid service interruption.`
  } else if (props.storagePercentage >= 90) {
    return `Warning: You're using ${props.storagePercentage}% of your storage. Only ${remainingGB} GB left.`
  } else {
    return `You're using ${props.storagePercentage}% of your storage. Upgrade for more space and features.`
  }
}

function handleUpgrade() {
  emit('upgrade')
}

function dismiss() {
  dismissed.value = true
  // Remember dismissal for this storage level
  sessionStorage.setItem(dismissKey, 'true')
}

// Check if already dismissed in this session
onMounted(() => {
  if (sessionStorage.getItem(dismissKey)) {
    dismissed.value = true
  }
})
</script>