<template>
  <div>
    <slot />
    
    <!-- Keyboard Shortcuts Help Modal -->
    <KeyboardShortcutsHelp
      :is-open="showHelp"
      :current-context="currentContext"
      :shortcuts="shortcuts.activeShortcuts.value"
      @close="showHelp = false"
    />
    
    <!-- Toast notification for shortcuts -->
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0 transform translate-y-2"
      enter-to-class="opacity-100 transform translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100 transform translate-y-0"
      leave-to-class="opacity-0 transform translate-y-2"
    >
      <div
        v-if="notification"
        class="fixed bottom-4 left-4 z-50 bg-gray-900 text-white rounded-lg shadow-lg px-4 py-3 max-w-sm"
      >
        <div class="flex items-center space-x-3">
          <CommandLineIcon class="w-5 h-5 text-gray-400" />
          <p class="text-sm">{{ notification }}</p>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, provide, watch, onMounted } from 'vue'
import { useKeyboardShortcuts } from '@/Composables/useKeyboardShortcuts'
import KeyboardShortcutsHelp from './KeyboardShortcutsHelp.vue'
import { CommandLineIcon } from '@heroicons/vue/24/outline'

// State
const showHelp = ref(false)
const currentContext = ref(null)
const notification = ref(null)
let notificationTimer = null

// Initialize keyboard shortcuts
const shortcuts = useKeyboardShortcuts()

// Watch for help toggle
watch(() => shortcuts.showHelp.value, (newValue) => {
  showHelp.value = newValue
})

// Provide shortcuts to child components
provide('keyboard-shortcuts', {
  shortcuts,
  setContext: (context) => {
    currentContext.value = context
  },
  showNotification: (message) => {
    notification.value = message
    if (notificationTimer) clearTimeout(notificationTimer)
    notificationTimer = setTimeout(() => {
      notification.value = null
    }, 3000)
  }
})

// Custom event handlers
function handleSetContext(event) {
  currentContext.value = event.detail.context
}

function handleShowShortcutNotification(event) {
  notification.value = event.detail.message
  if (notificationTimer) clearTimeout(notificationTimer)
  notificationTimer = setTimeout(() => {
    notification.value = null
  }, 3000)
}

// Mount
onMounted(() => {
  // Listen for custom events
  window.addEventListener('set-shortcut-context', handleSetContext)
  window.addEventListener('show-shortcut-notification', handleShowShortcutNotification)
  
  // Show welcome message if first time
  const hasSeenShortcuts = localStorage.getItem('has-seen-shortcuts')
  if (!hasSeenShortcuts) {
    setTimeout(() => {
      notification.value = 'Press ? to see keyboard shortcuts'
      localStorage.setItem('has-seen-shortcuts', 'true')
    }, 2000)
  }
})
</script>