import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

export function usePageTransitions() {
  const transitionName = ref('slide-right')
  const isTransitioning = ref(false)
  
  // Navigation history for detecting direction
  const navigationHistory = []
  let currentPath = window.location.pathname
  
  // Transition configurations
  const transitions = {
    'slide-right': {
      enter: 'transform transition-all duration-300 ease-out',
      enterFrom: 'translate-x-full opacity-0',
      enterTo: 'translate-x-0 opacity-100',
      leave: 'transform transition-all duration-300 ease-in',
      leaveFrom: 'translate-x-0 opacity-100',
      leaveTo: '-translate-x-full opacity-0'
    },
    'slide-left': {
      enter: 'transform transition-all duration-300 ease-out',
      enterFrom: '-translate-x-full opacity-0',
      enterTo: 'translate-x-0 opacity-100',
      leave: 'transform transition-all duration-300 ease-in',
      leaveFrom: 'translate-x-0 opacity-100',
      leaveTo: 'translate-x-full opacity-0'
    },
    'slide-up': {
      enter: 'transform transition-all duration-300 ease-out',
      enterFrom: 'translate-y-full opacity-0',
      enterTo: 'translate-y-0 opacity-100',
      leave: 'transform transition-all duration-300 ease-in',
      leaveFrom: 'translate-y-0 opacity-100',
      leaveTo: '-translate-y-full opacity-0'
    },
    'fade': {
      enter: 'transition-opacity duration-200 ease-out',
      enterFrom: 'opacity-0',
      enterTo: 'opacity-100',
      leave: 'transition-opacity duration-150 ease-in',
      leaveFrom: 'opacity-100',
      leaveTo: 'opacity-0'
    },
    'scale': {
      enter: 'transform transition-all duration-300 ease-out',
      enterFrom: 'scale-95 opacity-0',
      enterTo: 'scale-100 opacity-100',
      leave: 'transform transition-all duration-200 ease-in',
      leaveFrom: 'scale-100 opacity-100',
      leaveTo: 'scale-95 opacity-0'
    },
    'ios-push': {
      enter: 'transform transition-all duration-350 ease-out',
      enterFrom: 'translate-x-full shadow-2xl',
      enterTo: 'translate-x-0 shadow-none',
      leave: 'transform transition-all duration-350 ease-in-out',
      leaveFrom: 'translate-x-0',
      leaveTo: '-translate-x-1/3 opacity-50'
    },
    'ios-pop': {
      enter: 'transform transition-all duration-350 ease-out',
      enterFrom: '-translate-x-1/3 opacity-50',
      enterTo: 'translate-x-0 opacity-100',
      leave: 'transform transition-all duration-350 ease-in-out',
      leaveFrom: 'translate-x-0',
      leaveTo: 'translate-x-full'
    }
  }
  
  // Detect navigation direction
  function detectNavigationDirection(to, from) {
    // Check if going back
    const lastIndex = navigationHistory.lastIndexOf(to)
    if (lastIndex !== -1 && lastIndex < navigationHistory.length - 1) {
      return 'back'
    }
    
    // Check route hierarchy
    const toDepth = to.split('/').filter(Boolean).length
    const fromDepth = from.split('/').filter(Boolean).length
    
    if (toDepth > fromDepth) {
      return 'forward'
    } else if (toDepth < fromDepth) {
      return 'back'
    }
    
    // Default to forward for same level
    return 'forward'
  }
  
  // Set transition based on route
  function setTransitionForRoute(to, from) {
    const direction = detectNavigationDirection(to, from)
    
    // iOS-style navigation
    if (isMobile()) {
      transitionName.value = direction === 'back' ? 'ios-pop' : 'ios-push'
    } else {
      // Desktop transitions
      transitionName.value = direction === 'back' ? 'slide-left' : 'slide-right'
    }
    
    // Special transitions for modals/overlays
    if (to.includes('create') || to.includes('new')) {
      transitionName.value = 'slide-up'
    }
    
    if (to === from) {
      transitionName.value = 'fade'
    }
  }
  
  // Check if mobile
  function isMobile() {
    return window.innerWidth < 768 || 'ontouchstart' in window
  }
  
  // Handle navigation start
  function handleNavigationStart(event) {
    const to = event.detail.visit.url.pathname
    const from = currentPath
    
    setTransitionForRoute(to, from)
    isTransitioning.value = true
    
    // Update history
    navigationHistory.push(currentPath)
    if (navigationHistory.length > 10) {
      navigationHistory.shift()
    }
    
    currentPath = to
  }
  
  // Handle navigation end
  function handleNavigationEnd() {
    setTimeout(() => {
      isTransitioning.value = false
    }, 400)
  }
  
  // Touch gesture navigation
  let touchStartX = 0
  let touchStartY = 0
  let isSwiping = false
  
  function handleTouchStart(event) {
    touchStartX = event.touches[0].clientX
    touchStartY = event.touches[0].clientY
    isSwiping = false
  }
  
  function handleTouchMove(event) {
    if (!touchStartX || !touchStartY) return
    
    const touchEndX = event.touches[0].clientX
    const touchEndY = event.touches[0].clientY
    const deltaX = touchEndX - touchStartX
    const deltaY = Math.abs(touchEndY - touchStartY)
    
    // Horizontal swipe detection
    if (deltaX > 50 && deltaY < 50 && touchStartX < 50) {
      isSwiping = true
    }
  }
  
  function handleTouchEnd(event) {
    if (!isSwiping) return
    
    const touchEndX = event.changedTouches[0].clientX
    const deltaX = touchEndX - touchStartX
    
    // Swipe from left edge to go back
    if (deltaX > 100 && touchStartX < 50) {
      if (window.history.length > 1) {
        router.visit(window.history.back())
      }
    }
    
    touchStartX = 0
    touchStartY = 0
    isSwiping = false
  }
  
  // Setup and cleanup
  onMounted(() => {
    // Inertia navigation events
    document.addEventListener('inertia:start', handleNavigationStart)
    document.addEventListener('inertia:finish', handleNavigationEnd)
    
    // Touch gestures for iOS-style back navigation
    if (isMobile()) {
      document.addEventListener('touchstart', handleTouchStart, { passive: true })
      document.addEventListener('touchmove', handleTouchMove, { passive: true })
      document.addEventListener('touchend', handleTouchEnd, { passive: true })
    }
  })
  
  onUnmounted(() => {
    document.removeEventListener('inertia:start', handleNavigationStart)
    document.removeEventListener('inertia:finish', handleNavigationEnd)
    
    if (isMobile()) {
      document.removeEventListener('touchstart', handleTouchStart)
      document.removeEventListener('touchmove', handleTouchMove)
      document.removeEventListener('touchend', handleTouchEnd)
    }
  })
  
  return {
    transitionName,
    isTransitioning,
    transitions,
    setTransitionForRoute
  }
}