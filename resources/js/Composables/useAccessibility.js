import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { 
  announceToScreenReader,
  trapFocus,
  KeyboardNavigator,
  generateId
} from '@/Utils/accessibility'

export function useAccessibility(options = {}) {
  const {
    announceOnMount = null,
    autoFocusTrap = false,
    keyboardNav = false,
    liveRegion = false
  } = options

  // State
  const isScreenReaderActive = ref(false)
  const preferredColorScheme = ref('light')
  const prefersReducedMotion = ref(false)
  const prefersHighContrast = ref(false)
  const keyboardUser = ref(false)

  // Refs
  let focusTrapCleanup = null
  let keyboardNavigator = null

  // IDs for ARIA
  const labelId = ref(generateId('label'))
  const descriptionId = ref(generateId('desc'))
  const errorId = ref(generateId('error'))

  // Announce to screen reader
  const announce = (message, priority = 'polite') => {
    announceToScreenReader(message, priority)
  }

  // Setup focus trap
  const setupFocusTrap = (element) => {
    if (focusTrapCleanup) {
      focusTrapCleanup()
    }
    focusTrapCleanup = trapFocus(element)
  }

  // Setup keyboard navigation
  const setupKeyboardNav = (container, navOptions = {}) => {
    if (keyboardNavigator) {
      keyboardNavigator.destroy()
    }
    keyboardNavigator = new KeyboardNavigator(container, navOptions)
    return keyboardNavigator
  }

  // ARIA helpers
  const ariaAttrs = computed(() => ({
    'aria-labelledby': labelId.value,
    'aria-describedby': descriptionId.value
  }))

  const ariaLabel = (label) => ({
    'aria-label': label
  })

  const ariaLabelledBy = (...ids) => ({
    'aria-labelledby': ids.join(' ')
  })

  const ariaDescribedBy = (...ids) => ({
    'aria-describedby': ids.join(' ')
  })

  const ariaExpanded = (expanded) => ({
    'aria-expanded': String(expanded)
  })

  const ariaSelected = (selected) => ({
    'aria-selected': String(selected)
  })

  const ariaChecked = (checked) => ({
    'aria-checked': String(checked)
  })

  const ariaCurrent = (current) => ({
    'aria-current': current
  })

  const ariaHidden = (hidden) => ({
    'aria-hidden': String(hidden)
  })

  const ariaLive = (priority = 'polite') => ({
    'aria-live': priority,
    'aria-atomic': 'true'
  })

  const ariaBusy = (busy) => ({
    'aria-busy': String(busy)
  })

  const ariaInvalid = (invalid) => ({
    'aria-invalid': String(invalid)
  })

  // Detect screen reader
  const detectScreenReader = () => {
    // Check for screen reader indicators
    const indicators = [
      // Check for NVDA
      window.navigator.userAgent.includes('NVDA'),
      // Check for JAWS
      window.navigator.userAgent.includes('JAWS'),
      // Check for VoiceOver
      window.navigator.userAgent.includes('VoiceOver'),
      // Check for reduced motion preference (often correlates)
      window.matchMedia('(prefers-reduced-motion: reduce)').matches
    ]

    isScreenReaderActive.value = indicators.some(i => i)
  }

  // Detect user preferences
  const detectUserPreferences = () => {
    // Color scheme
    const darkMode = window.matchMedia('(prefers-color-scheme: dark)')
    preferredColorScheme.value = darkMode.matches ? 'dark' : 'light'
    
    darkMode.addEventListener('change', (e) => {
      preferredColorScheme.value = e.matches ? 'dark' : 'light'
    })

    // Reduced motion
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)')
    prefersReducedMotion.value = reducedMotion.matches
    
    reducedMotion.addEventListener('change', (e) => {
      prefersReducedMotion.value = e.matches
    })

    // High contrast
    const highContrast = window.matchMedia('(prefers-contrast: high)')
    prefersHighContrast.value = highContrast.matches
    
    highContrast.addEventListener('change', (e) => {
      prefersHighContrast.value = e.matches
    })
  }

  // Detect keyboard user
  const detectKeyboardUser = () => {
    let hadKeyboardEvent = false
    
    const onPointerDown = () => {
      hadKeyboardEvent = false
    }
    
    const onKeyDown = (e) => {
      if (e.key === 'Tab') {
        hadKeyboardEvent = true
        keyboardUser.value = true
      }
    }
    
    const onFocus = () => {
      if (hadKeyboardEvent) {
        document.documentElement.classList.add('keyboard-navigation')
      } else {
        document.documentElement.classList.remove('keyboard-navigation')
      }
    }

    document.addEventListener('pointerdown', onPointerDown)
    document.addEventListener('keydown', onKeyDown)
    document.addEventListener('focus', onFocus, true)

    return () => {
      document.removeEventListener('pointerdown', onPointerDown)
      document.removeEventListener('keydown', onKeyDown)
      document.removeEventListener('focus', onFocus, true)
    }
  }

  // Create skip link
  const createSkipLink = (targetId = 'main-content', text = 'Skip to main content') => {
    const link = document.createElement('a')
    link.href = `#${targetId}`
    link.className = 'skip-link'
    link.textContent = text
    
    link.addEventListener('click', (e) => {
      e.preventDefault()
      const target = document.getElementById(targetId)
      if (target) {
        target.tabIndex = -1
        target.focus()
        window.scrollTo({
          top: target.offsetTop,
          behavior: 'smooth'
        })
      }
    })

    return link
  }

  // Lifecycle
  onMounted(() => {
    // Detect preferences
    detectScreenReader()
    detectUserPreferences()
    const cleanupKeyboard = detectKeyboardUser()

    // Announce on mount
    if (announceOnMount) {
      announce(announceOnMount)
    }

    // Cleanup
    onUnmounted(() => {
      if (focusTrapCleanup) {
        focusTrapCleanup()
      }
      if (keyboardNavigator) {
        keyboardNavigator.destroy()
      }
      cleanupKeyboard()
    })
  })

  return {
    // State
    isScreenReaderActive,
    preferredColorScheme,
    prefersReducedMotion,
    prefersHighContrast,
    keyboardUser,
    
    // IDs
    labelId,
    descriptionId,
    errorId,
    
    // Methods
    announce,
    setupFocusTrap,
    setupKeyboardNav,
    createSkipLink,
    
    // ARIA helpers
    ariaAttrs,
    ariaLabel,
    ariaLabelledBy,
    ariaDescribedBy,
    ariaExpanded,
    ariaSelected,
    ariaChecked,
    ariaCurrent,
    ariaHidden,
    ariaLive,
    ariaBusy,
    ariaInvalid
  }
}