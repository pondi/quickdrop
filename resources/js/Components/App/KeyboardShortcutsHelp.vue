<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" @close="close" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-900 shadow-2xl transition-all">
              <!-- Header -->
              <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                <div class="flex items-center justify-between">
                  <DialogTitle class="text-lg font-semibold text-white flex items-center">
                    <CommandLineIcon class="w-5 h-5 mr-2" />
                    Keyboard Shortcuts
                  </DialogTitle>
                  <button
                    @click="close"
                    class="p-1 rounded-lg bg-white/20 hover:bg-white/30 transition-colors"
                  >
                    <XMarkIcon class="w-5 h-5 text-white" />
                  </button>
                </div>
              </div>

              <!-- Content -->
              <div class="p-6 max-h-[60vh] overflow-y-auto">
                <!-- Global Shortcuts -->
                <div class="mb-8">
                  <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <GlobeAltIcon class="w-4 h-4 mr-2" />
                    Global Shortcuts
                  </h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div
                      v-for="(shortcut, key) in globalShortcuts"
                      :key="key"
                      class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800"
                    >
                      <span class="text-sm text-gray-700 dark:text-gray-300">
                        {{ shortcut.description }}
                      </span>
                      <kbd class="ml-2 flex items-center space-x-1">
                        <span
                          v-for="(part, index) in formatShortcut(key)"
                          :key="index"
                          class="px-2 py-1 text-xs font-semibold text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded shadow-sm"
                        >
                          {{ part }}
                        </span>
                      </kbd>
                    </div>
                  </div>
                </div>

                <!-- Context-specific shortcuts -->
                <div v-if="currentContext" class="mb-8">
                  <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <CursorArrowRaysIcon class="w-4 h-4 mr-2" />
                    {{ getContextTitle(currentContext) }}
                  </h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div
                      v-for="(shortcut, key) in contextShortcuts"
                      :key="key"
                      class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800"
                    >
                      <span class="text-sm text-gray-700 dark:text-gray-300">
                        {{ shortcut.description }}
                      </span>
                      <kbd class="ml-2 flex items-center space-x-1">
                        <span
                          v-for="(part, index) in formatShortcut(key)"
                          :key="index"
                          class="px-2 py-1 text-xs font-semibold text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded shadow-sm"
                        >
                          {{ part }}
                        </span>
                      </kbd>
                    </div>
                  </div>
                </div>

                <!-- Tips -->
                <div class="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                  <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-300 mb-2">
                    Pro Tips
                  </h4>
                  <ul class="space-y-1 text-sm text-blue-800 dark:text-blue-200">
                    <li class="flex items-start">
                      <span class="mr-2">•</span>
                      Press <kbd class="px-1.5 py-0.5 text-xs bg-blue-100 dark:bg-blue-800 rounded">?</kbd> anytime to show this help
                    </li>
                    <li class="flex items-start">
                      <span class="mr-2">•</span>
                      Multi-key shortcuts like <kbd class="px-1.5 py-0.5 text-xs bg-blue-100 dark:bg-blue-800 rounded">g h</kbd> should be pressed in sequence
                    </li>
                    <li class="flex items-start">
                      <span class="mr-2">•</span>
                      Shortcuts are disabled when typing in input fields
                    </li>
                  </ul>
                </div>
              </div>

              <!-- Footer -->
              <div class="bg-gray-50 dark:bg-gray-800 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <InformationCircleIcon class="w-4 h-4 mr-1" />
                  Keyboard shortcuts can be customized in settings
                </div>
                <button
                  @click="close"
                  class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                >
                  Got it
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { computed } from 'vue'
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue'
import {
  XMarkIcon,
  CommandLineIcon,
  GlobeAltIcon,
  CursorArrowRaysIcon,
  InformationCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  currentContext: {
    type: String,
    default: null
  },
  shortcuts: {
    type: Map,
    default: () => new Map()
  }
})

const emit = defineEmits(['close'])

// Computed
const globalShortcuts = computed(() => {
  const shortcuts = {}
  props.shortcuts.forEach((shortcut, key) => {
    if (!shortcut.context) {
      shortcuts[key] = shortcut
    }
  })
  return shortcuts
})

const contextShortcuts = computed(() => {
  if (!props.currentContext) return {}
  
  const shortcuts = {}
  props.shortcuts.forEach((shortcut, key) => {
    if (shortcut.context === props.currentContext) {
      shortcuts[key] = shortcut
    }
  })
  return shortcuts
})

// Methods
function close() {
  emit('close')
}

function formatShortcut(key) {
  // Handle special formatting
  const formatted = key
    .replace(/cmd/g, '⌘')
    .replace(/alt/g, '⌥')
    .replace(/shift/g, '⇧')
    .replace(/space/g, 'Space')
    .replace(/ArrowLeft/g, '←')
    .replace(/ArrowRight/g, '→')
    .replace(/ArrowUp/g, '↑')
    .replace(/ArrowDown/g, '↓')
    .replace(/Enter/g, '⏎')
    .replace(/Delete/g, '⌫')
    .replace(/Escape/g, 'Esc')
  
  // Split multi-key shortcuts
  if (formatted.includes(' ')) {
    return formatted.split(' ')
  } else if (formatted.includes('+')) {
    return formatted.split('+')
  }
  
  return [formatted]
}

function getContextTitle(context) {
  const titles = {
    'quickdrop': 'QuickDrop Actions',
    'file-list': 'File List',
    'file-preview': 'File Preview',
    'upload': 'Upload',
    'modal': 'Modal/Dialog'
  }
  return titles[context] || context
}
</script>