<template>
  <Teleport to="body">
    <!-- Offline Banner -->
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="transform -translate-y-full opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="transform translate-y-0 opacity-100"
      leave-to-class="transform -translate-y-full opacity-0"
    >
      <div
        v-if="!isOnline && showBanner"
        class="fixed top-0 left-0 right-0 z-[100] bg-gradient-to-r from-amber-500 to-orange-600 text-white px-4 py-3 shadow-lg"
      >
        <div class="max-w-7xl mx-auto flex items-center justify-between">
          <div class="flex items-center space-x-3">
            <WifiOffIcon class="w-5 h-5" />
            <span class="text-sm font-medium">
              You're offline. Some features may be limited.
            </span>
          </div>
          <button
            @click="showBanner = false"
            class="p-1 rounded-lg hover:bg-white/20 transition-colors"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </Transition>
    
    <!-- Reconnection Toast -->
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="transform translate-y-full opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="transform translate-y-0 opacity-100"
      leave-to-class="transform translate-y-full opacity-0"
    >
      <div
        v-if="showReconnected"
        class="fixed bottom-20 left-1/2 -translate-x-1/2 z-50 bg-green-500 text-white px-6 py-3 rounded-full shadow-lg flex items-center space-x-3"
      >
        <CheckCircleIcon class="w-5 h-5" />
        <span class="text-sm font-medium">Back online!</span>
      </div>
    </Transition>
    
    <!-- Offline Mode Indicator (Mobile) -->
    <div
      v-if="!isOnline"
      class="fixed bottom-24 right-6 z-40 md:hidden"
    >
      <div class="relative">
        <div class="absolute inset-0 bg-amber-500 rounded-full animate-ping opacity-75" />
        <div class="relative bg-amber-500 text-white rounded-full p-3 shadow-lg">
          <WifiOffIcon class="w-5 h-5" />
        </div>
      </div>
    </div>
    
    <!-- Status Bar (Desktop) -->
    <div
      v-if="!isOnline"
      class="hidden md:block fixed bottom-6 left-6 z-40"
    >
      <div class="bg-gray-900 dark:bg-gray-800 text-white rounded-lg shadow-lg px-4 py-3 flex items-center space-x-3">
        <div class="relative">
          <WifiOffIcon class="w-5 h-5 text-amber-400" />
          <span class="absolute -top-1 -right-1 w-2 h-2 bg-amber-400 rounded-full animate-pulse" />
        </div>
        <div>
          <p class="text-sm font-medium">Offline Mode</p>
          <p class="text-xs text-gray-400">{{ offlineMessage }}</p>
        </div>
      </div>
    </div>
    
    <!-- Sync Status -->
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="pendingSync.length > 0 && !isOnline"
        class="fixed bottom-32 right-6 z-40 bg-gray-900 dark:bg-gray-800 text-white rounded-lg shadow-lg p-4 max-w-xs"
      >
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-sm font-medium">Pending Actions</h4>
          <span class="text-xs text-gray-400">{{ pendingSync.length }} items</span>
        </div>
        <div class="space-y-2 max-h-32 overflow-y-auto">
          <div
            v-for="item in pendingSync.slice(0, 3)"
            :key="item.id"
            class="flex items-center space-x-2 text-xs"
          >
            <CloudArrowUpIcon class="w-4 h-4 text-gray-400" />
            <span class="text-gray-300 truncate">{{ item.description }}</span>
          </div>
          <div v-if="pendingSync.length > 3" class="text-xs text-gray-500">
            And {{ pendingSync.length - 3 }} more...
          </div>
        </div>
        <p class="text-xs text-gray-400 mt-3">
          Will sync when connection is restored
        </p>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { 
  WifiIcon,
  XMarkIcon,
  CheckCircleIcon,
  CloudArrowUpIcon
} from '@heroicons/vue/24/outline'
import { 
  WifiIcon as WifiOffIcon
} from '@heroicons/vue/24/solid'

// State
const isOnline = ref(navigator.onLine)
const showBanner = ref(true)
const showReconnected = ref(false)
const pendingSync = ref([])
const lastOnlineTime = ref(Date.now())

// Computed
const offlineMessage = ref('Loading available content...')

// Update offline message based on duration
function updateOfflineMessage() {
  const offlineDuration = Date.now() - lastOnlineTime.value
  const minutes = Math.floor(offlineDuration / 60000)
  
  if (minutes < 1) {
    offlineMessage.value = 'Just went offline'
  } else if (minutes < 60) {
    offlineMessage.value = `Offline for ${minutes}m`
  } else {
    const hours = Math.floor(minutes / 60)
    offlineMessage.value = `Offline for ${hours}h`
  }
}

// Connection handlers
function handleOnline() {
  if (!isOnline.value) {
    isOnline.value = true
    showReconnected.value = true
    showBanner.value = true
    
    // Hide reconnection toast after 3 seconds
    setTimeout(() => {
      showReconnected.value = false
    }, 3000)
    
    // Sync pending actions
    syncPendingActions()
  }
}

function handleOffline() {
  isOnline.value = false
  lastOnlineTime.value = Date.now()
  showBanner.value = true
  updateOfflineMessage()
}

// Check connection status
function checkConnection() {
  if (navigator.onLine !== isOnline.value) {
    if (navigator.onLine) {
      handleOnline()
    } else {
      handleOffline()
    }
  }
}

// Sync pending actions
async function syncPendingActions() {
  if (pendingSync.value.length === 0) return
  
  console.log('Syncing pending actions...', pendingSync.value)
  
  // Simulate sync
  for (const action of pendingSync.value) {
    try {
      // Perform sync action
      await performSync(action)
    } catch (error) {
      console.error('Sync failed for action:', action, error)
    }
  }
  
  // Clear synced actions
  pendingSync.value = []
}

async function performSync(action) {
  // Simulate API call
  return new Promise((resolve) => {
    setTimeout(resolve, 500)
  })
}

// Add action to pending sync
function addPendingSync(action) {
  pendingSync.value.push({
    id: Date.now(),
    description: action.description,
    data: action.data,
    timestamp: new Date()
  })
}

// Expose the function if needed by parent components
defineExpose({
  addPendingSync
})

// Service worker messaging
function setupServiceWorker() {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('message', (event) => {
      if (event.data.type === 'OFFLINE_ACTION') {
        addPendingSync(event.data.action)
      }
    })
  }
}

// Update message periodically when offline
let messageInterval = null

watch(isOnline, (online) => {
  if (!online) {
    messageInterval = setInterval(updateOfflineMessage, 60000)
  } else {
    if (messageInterval) {
      clearInterval(messageInterval)
      messageInterval = null
    }
  }
})

// Lifecycle
onMounted(() => {
  // Connection event listeners
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
  
  // Periodic connection check (for reliability)
  const connectionInterval = setInterval(checkConnection, 5000)
  
  // Service worker setup
  setupServiceWorker()
  
  // Initial check
  if (!navigator.onLine) {
    handleOffline()
  }
  
  // Cleanup function
  onUnmounted(() => {
    window.removeEventListener('online', handleOnline)
    window.removeEventListener('offline', handleOffline)
    clearInterval(connectionInterval)
    if (messageInterval) {
      clearInterval(messageInterval)
    }
  })
})
</script>

<style scoped>
/* Custom scrollbar for pending sync */
.overflow-y-auto::-webkit-scrollbar {
  width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 2px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 2px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.4);
}
</style>