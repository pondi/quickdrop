<template>
  <div 
    v-if="showHints && hints.length > 0"
    class="fixed bottom-4 right-4 z-40 max-w-sm"
  >
    <TransitionGroup
      name="hint"
      tag="div"
      class="space-y-2"
    >
      <div
        v-for="hint in hints"
        :key="hint.key"
        class="bg-gray-900 dark:bg-gray-800 text-white rounded-lg shadow-lg px-4 py-3 flex items-center space-x-3"
      >
        <kbd class="flex items-center space-x-1">
          <span
            v-for="(part, index) in formatShortcut(hint.key)"
            :key="index"
            class="px-2 py-1 text-xs font-semibold bg-gray-800 dark:bg-gray-700 border border-gray-700 dark:border-gray-600 rounded"
          >
            {{ part }}
          </span>
        </kbd>
        <span class="text-sm text-gray-300">{{ hint.description }}</span>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  context: {
    type: String,
    default: null
  },
  showHints: {
    type: Boolean,
    default: true
  }
})

// State
const currentElement = ref(null)
const hints = ref([])

// Context hints configuration
const contextHints = {
  'file-upload': [
    { key: 'u', description: 'Upload files' },
    { key: 'cmd+v', description: 'Paste files' }
  ],
  'file-list': [
    { key: '1', description: 'Grid view' },
    { key: '2', description: 'List view' },
    { key: 'd', description: 'Download all' }
  ],
  'quickdrop': [
    { key: 's', description: 'Share' },
    { key: 'cmd+c', description: 'Copy link' }
  ]
}

// Computed
const availableHints = computed(() => {
  const allHints = []
  
  // Add context-specific hints
  if (props.context && contextHints[props.context]) {
    allHints.push(...contextHints[props.context])
  }
  
  // Add hints based on current element
  if (currentElement.value) {
    const elementHints = getElementHints(currentElement.value)
    allHints.push(...elementHints)
  }
  
  return allHints.slice(0, 3) // Show max 3 hints
})

// Methods
function formatShortcut(key) {
  const formatted = key
    .replace(/cmd/g, '⌘')
    .replace(/alt/g, '⌥')
    .replace(/shift/g, '⇧')
    .replace(/\+/g, ' ')
  
  return formatted.split(' ')
}

function getElementHints(element) {
  const hints = []
  
  // Check for data attributes
  if (element.dataset.shortcut) {
    hints.push({
      key: element.dataset.shortcut,
      description: element.dataset.shortcutDescription || 'Activate'
    })
  }
  
  // Check for common patterns
  if (element.tagName === 'BUTTON' && element.textContent.includes('Upload')) {
    hints.push({ key: 'u', description: 'Upload files' })
  }
  
  if (element.classList.contains('searchable')) {
    hints.push({ key: '/', description: 'Search' })
  }
  
  return hints
}

function updateHints() {
  hints.value = availableHints.value
}

function handleFocus(event) {
  currentElement.value = event.target
  updateHints()
}

function handleBlur() {
  setTimeout(() => {
    currentElement.value = null
    updateHints()
  }, 200)
}

// Lifecycle
onMounted(() => {
  document.addEventListener('focusin', handleFocus)
  document.addEventListener('focusout', handleBlur)
  updateHints()
})

onUnmounted(() => {
  document.removeEventListener('focusin', handleFocus)
  document.removeEventListener('focusout', handleBlur)
})
</script>

<style scoped>
/* Hint transitions */
.hint-enter-active,
.hint-leave-active {
  transition: all 0.3s ease;
}

.hint-enter-from {
  opacity: 0;
  transform: translateX(20px);
}

.hint-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

.hint-move {
  transition: transform 0.3s ease;
}
</style>