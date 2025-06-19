import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

export function useKeyboardShortcuts() {
  const isEnabled = ref(true)
  const activeShortcuts = ref(new Map())
  const showHelp = ref(false)

  // Define global shortcuts
  const globalShortcuts = {
    // Navigation
    'g h': { action: () => router.visit(route('dashboard')), description: 'Go to Dashboard' },
    'g q': { action: () => router.visit(route('quickdrop.index')), description: 'Go to QuickDrops' },
    'g p': { action: () => router.visit(route('profile.edit')), description: 'Go to Profile' },
    'g a': { action: () => router.visit(route('analytics')), description: 'Go to Analytics' },
    
    // Actions
    'c': { action: () => router.visit(route('quickdrop.create')), description: 'Create new QuickDrop' },
    '/': { action: () => focusSearch(), description: 'Focus search' },
    '?': { action: () => toggleHelp(), description: 'Show keyboard shortcuts' },
    'Escape': { action: () => closeModals(), description: 'Close modals/dialogs' },
    
    // File operations (when on QuickDrop page)
    'u': { action: () => triggerUpload(), description: 'Upload files', context: 'quickdrop' },
    'd': { action: () => downloadAll(), description: 'Download all files', context: 'quickdrop' },
    's': { action: () => shareQuickDrop(), description: 'Share QuickDrop', context: 'quickdrop' },
    
    // View modes
    '1': { action: () => setViewMode('grid'), description: 'Grid view', context: 'file-list' },
    '2': { action: () => setViewMode('list'), description: 'List view', context: 'file-list' },
    
    // Theme
    'alt+t': { action: () => toggleTheme(), description: 'Toggle dark mode' },
  }

  // Context-specific shortcuts
  const contextShortcuts = {
    'file-preview': {
      'ArrowLeft': { action: () => navigateFile('prev'), description: 'Previous file' },
      'ArrowRight': { action: () => navigateFile('next'), description: 'Next file' },
      'f': { action: () => toggleFullscreen(), description: 'Toggle fullscreen' },
      'Escape': { action: () => closePreview(), description: 'Close preview' },
    },
    'upload': {
      'Enter': { action: () => confirmUpload(), description: 'Start upload' },
      'Delete': { action: () => clearPendingFiles(), description: 'Clear pending files' },
    },
    'modal': {
      'Enter': { action: () => confirmModal(), description: 'Confirm action' },
      'Escape': { action: () => closeModal(), description: 'Cancel/Close' },
    }
  }

  // Current key sequence
  let keySequence = ''
  let sequenceTimer = null

  // Register a shortcut
  function registerShortcut(key, action, description, options = {}) {
    const shortcut = {
      action,
      description,
      ...options
    }
    activeShortcuts.value.set(key.toLowerCase(), shortcut)
  }

  // Unregister a shortcut
  function unregisterShortcut(key) {
    activeShortcuts.value.delete(key.toLowerCase())
  }

  // Handle keydown event
  function handleKeyDown(event) {
    if (!isEnabled.value) return

    // Ignore if user is typing in an input
    const target = event.target
    if (target.tagName === 'INPUT' || 
        target.tagName === 'TEXTAREA' || 
        target.contentEditable === 'true') {
      return
    }

    // Build key combination
    const key = buildKeyCombination(event)
    
    // Handle multi-key sequences (like 'g h')
    if (sequenceTimer) {
      clearTimeout(sequenceTimer)
      keySequence += ' ' + key
    } else {
      keySequence = key
    }

    // Check for matching shortcut
    let shortcut = activeShortcuts.value.get(keySequence.toLowerCase())
    
    if (shortcut) {
      event.preventDefault()
      shortcut.action()
      keySequence = ''
      if (sequenceTimer) clearTimeout(sequenceTimer)
    } else {
      // Check if this could be the start of a sequence
      const possibleSequence = Array.from(activeShortcuts.value.keys()).some(k => 
        k.startsWith(keySequence.toLowerCase() + ' ')
      )
      
      if (possibleSequence) {
        event.preventDefault()
        sequenceTimer = setTimeout(() => {
          keySequence = ''
          sequenceTimer = null
        }, 1000)
      } else {
        keySequence = ''
        if (sequenceTimer) {
          clearTimeout(sequenceTimer)
          sequenceTimer = null
        }
      }
    }
  }

  // Build key combination string
  function buildKeyCombination(event) {
    const parts = []
    
    if (event.ctrlKey || event.metaKey) parts.push('cmd')
    if (event.altKey) parts.push('alt')
    if (event.shiftKey) parts.push('shift')
    
    // Special keys
    const key = event.key.toLowerCase()
    if (key === ' ') {
      parts.push('space')
    } else if (key === 'arrowleft') {
      parts.push('ArrowLeft')
    } else if (key === 'arrowright') {
      parts.push('ArrowRight')
    } else if (key === 'arrowup') {
      parts.push('ArrowUp')
    } else if (key === 'arrowdown') {
      parts.push('ArrowDown')
    } else if (key === 'escape') {
      parts.push('Escape')
    } else if (key === 'enter') {
      parts.push('Enter')
    } else if (key === 'delete' || key === 'backspace') {
      parts.push('Delete')
    } else {
      parts.push(key)
    }
    
    return parts.join('+')
  }

  // Helper functions
  function focusSearch() {
    const searchInput = document.querySelector('input[type="search"], input[placeholder*="Search"]')
    if (searchInput) {
      searchInput.focus()
      searchInput.select()
    }
  }

  function toggleHelp() {
    showHelp.value = !showHelp.value
  }

  function closeModals() {
    // Emit a global event to close all modals
    window.dispatchEvent(new CustomEvent('close-all-modals'))
    showHelp.value = false
  }

  function triggerUpload() {
    const uploadButton = document.querySelector('[data-shortcut="upload"]')
    if (uploadButton) uploadButton.click()
  }

  function downloadAll() {
    const downloadButton = document.querySelector('[data-shortcut="download-all"]')
    if (downloadButton) downloadButton.click()
  }

  function shareQuickDrop() {
    const shareButton = document.querySelector('[data-shortcut="share"]')
    if (shareButton) shareButton.click()
  }

  function setViewMode(mode) {
    window.dispatchEvent(new CustomEvent('set-view-mode', { detail: { mode } }))
  }

  function toggleTheme() {
    const isDark = document.documentElement.classList.contains('dark')
    document.documentElement.classList.toggle('dark', !isDark)
    localStorage.setItem('theme', isDark ? 'light' : 'dark')
  }

  function navigateFile(direction) {
    window.dispatchEvent(new CustomEvent('navigate-file', { detail: { direction } }))
  }

  function toggleFullscreen() {
    window.dispatchEvent(new CustomEvent('toggle-fullscreen'))
  }

  function closePreview() {
    window.dispatchEvent(new CustomEvent('close-preview'))
  }

  function confirmUpload() {
    window.dispatchEvent(new CustomEvent('confirm-upload'))
  }

  function clearPendingFiles() {
    window.dispatchEvent(new CustomEvent('clear-pending-files'))
  }

  function confirmModal() {
    window.dispatchEvent(new CustomEvent('confirm-modal'))
  }

  function closeModal() {
    window.dispatchEvent(new CustomEvent('close-modal'))
  }

  // Get shortcuts for current context
  function getContextShortcuts(context) {
    const shortcuts = new Map()
    
    // Add global shortcuts
    Object.entries(globalShortcuts).forEach(([key, shortcut]) => {
      if (!shortcut.context || shortcut.context === context) {
        shortcuts.set(key, shortcut)
      }
    })
    
    // Add context-specific shortcuts
    if (context && contextShortcuts[context]) {
      Object.entries(contextShortcuts[context]).forEach(([key, shortcut]) => {
        shortcuts.set(key, shortcut)
      })
    }
    
    return shortcuts
  }

  // Enable/disable shortcuts
  function enable() {
    isEnabled.value = true
  }

  function disable() {
    isEnabled.value = false
  }

  // Initialize
  function init() {
    // Register all global shortcuts
    Object.entries(globalShortcuts).forEach(([key, shortcut]) => {
      registerShortcut(key, shortcut.action, shortcut.description, { context: shortcut.context })
    })
    
    // Add event listener
    document.addEventListener('keydown', handleKeyDown)
  }

  // Cleanup
  function cleanup() {
    document.removeEventListener('keydown', handleKeyDown)
    if (sequenceTimer) clearTimeout(sequenceTimer)
  }

  // Auto-initialize on mount
  onMounted(() => {
    init()
  })

  onUnmounted(() => {
    cleanup()
  })

  return {
    isEnabled,
    showHelp,
    activeShortcuts,
    registerShortcut,
    unregisterShortcut,
    getContextShortcuts,
    enable,
    disable,
    init,
    cleanup
  }
}