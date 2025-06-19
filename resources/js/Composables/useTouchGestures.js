import { ref, onMounted, onUnmounted } from 'vue'

export function useTouchGestures(element = null) {
  const isSwiping = ref(false)
  const swipeDirection = ref(null)
  const swipeDistance = ref(0)
  const isPulling = ref(false)
  const pullDistance = ref(0)

  let startX = 0
  let startY = 0
  let currentX = 0
  let currentY = 0
  let targetElement = null

  // Swipe configuration
  const SWIPE_THRESHOLD = 100
  const SWIPE_VELOCITY_THRESHOLD = 0.5
  const PULL_THRESHOLD = 80
  const PULL_MAX_DISTANCE = 120

  // Touch event handlers
  function handleTouchStart(event) {
    if (event.touches.length !== 1) return

    const touch = event.touches[0]
    startX = touch.clientX
    startY = touch.clientY
    currentX = startX
    currentY = startY

    // Check if we're at the top for pull to refresh
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop
    if (scrollTop === 0) {
      isPulling.value = true
    }
  }

  function handleTouchMove(event) {
    if (event.touches.length !== 1) return

    const touch = event.touches[0]
    currentX = touch.clientX
    currentY = touch.clientY

    const deltaX = currentX - startX
    const deltaY = currentY - startY

    // Handle pull to refresh
    if (isPulling.value && deltaY > 0 && Math.abs(deltaX) < Math.abs(deltaY)) {
      event.preventDefault()
      pullDistance.value = Math.min(deltaY, PULL_MAX_DISTANCE)
      return
    }

    // Handle horizontal swipe
    if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
      isSwiping.value = true
      swipeDistance.value = deltaX
      swipeDirection.value = deltaX > 0 ? 'right' : 'left'
      
      // Prevent scrolling while swiping
      if (Math.abs(deltaX) > 30) {
        event.preventDefault()
      }
    }
  }

  function handleTouchEnd(event) {
    const deltaX = currentX - startX
    const deltaY = currentY - startY
    const velocity = Math.abs(deltaX) / (Date.now() - startTime)

    // Handle pull to refresh
    if (isPulling.value) {
      if (pullDistance.value >= PULL_THRESHOLD) {
        handlePullRefresh()
      }
      
      // Animate back
      isPulling.value = false
      pullDistance.value = 0
    }

    // Handle swipe
    if (isSwiping.value) {
      if (Math.abs(deltaX) >= SWIPE_THRESHOLD || velocity >= SWIPE_VELOCITY_THRESHOLD) {
        handleSwipe(swipeDirection.value, Math.abs(deltaX))
      }
      
      // Reset
      isSwiping.value = false
      swipeDirection.value = null
      swipeDistance.value = 0
    }

    startX = 0
    startY = 0
    currentX = 0
    currentY = 0
  }

  function handleTouchCancel() {
    // Reset all states
    isSwiping.value = false
    swipeDirection.value = null
    swipeDistance.value = 0
    isPulling.value = false
    pullDistance.value = 0
    startX = 0
    startY = 0
  }

  // Gesture handlers
  let startTime = 0
  const swipeHandlers = {
    left: [],
    right: [],
    up: [],
    down: []
  }
  const pullRefreshHandlers = []

  function onSwipe(direction, handler) {
    if (swipeHandlers[direction]) {
      swipeHandlers[direction].push(handler)
    }
  }

  function onPullRefresh(handler) {
    pullRefreshHandlers.push(handler)
  }

  function handleSwipe(direction, distance) {
    const handlers = swipeHandlers[direction] || []
    handlers.forEach(handler => handler({ direction, distance }))
  }

  function handlePullRefresh() {
    pullRefreshHandlers.forEach(handler => handler())
  }

  // Long press detection
  const isLongPressing = ref(false)
  const longPressHandlers = []
  let longPressTimer = null

  function onLongPress(handler) {
    longPressHandlers.push(handler)
  }

  function handleLongPressStart(event) {
    longPressTimer = setTimeout(() => {
      isLongPressing.value = true
      longPressHandlers.forEach(handler => handler(event))
      
      // Haptic feedback if available
      if (window.navigator.vibrate) {
        window.navigator.vibrate(50)
      }
    }, 500)
  }

  function handleLongPressEnd() {
    if (longPressTimer) {
      clearTimeout(longPressTimer)
      longPressTimer = null
    }
    isLongPressing.value = false
  }

  // Pinch to zoom
  const isPinching = ref(false)
  const pinchScale = ref(1)
  let initialPinchDistance = 0

  function handlePinchStart(event) {
    if (event.touches.length === 2) {
      isPinching.value = true
      const touch1 = event.touches[0]
      const touch2 = event.touches[1]
      initialPinchDistance = Math.hypot(
        touch2.clientX - touch1.clientX,
        touch2.clientY - touch1.clientY
      )
    }
  }

  function handlePinchMove(event) {
    if (isPinching.value && event.touches.length === 2) {
      const touch1 = event.touches[0]
      const touch2 = event.touches[1]
      const currentDistance = Math.hypot(
        touch2.clientX - touch1.clientX,
        touch2.clientY - touch1.clientY
      )
      pinchScale.value = currentDistance / initialPinchDistance
    }
  }

  function handlePinchEnd() {
    isPinching.value = false
    pinchScale.value = 1
    initialPinchDistance = 0
  }

  // Setup and cleanup
  function setup(el) {
    targetElement = el || element?.value || document.body
    
    // Touch events
    targetElement.addEventListener('touchstart', (e) => {
      startTime = Date.now()
      handleTouchStart(e)
      handleLongPressStart(e)
      handlePinchStart(e)
    }, { passive: false })
    
    targetElement.addEventListener('touchmove', (e) => {
      handleTouchMove(e)
      handlePinchMove(e)
      if (longPressTimer) {
        clearTimeout(longPressTimer)
        longPressTimer = null
      }
    }, { passive: false })
    
    targetElement.addEventListener('touchend', (e) => {
      handleTouchEnd(e)
      handleLongPressEnd()
      handlePinchEnd()
    })
    
    targetElement.addEventListener('touchcancel', () => {
      handleTouchCancel()
      handleLongPressEnd()
      handlePinchEnd()
    })
  }

  function cleanup() {
    if (targetElement) {
      targetElement.removeEventListener('touchstart', handleTouchStart)
      targetElement.removeEventListener('touchmove', handleTouchMove)
      targetElement.removeEventListener('touchend', handleTouchEnd)
      targetElement.removeEventListener('touchcancel', handleTouchCancel)
    }
  }

  // Auto setup/cleanup
  onMounted(() => {
    setup()
  })

  onUnmounted(() => {
    cleanup()
  })

  return {
    // States
    isSwiping,
    swipeDirection,
    swipeDistance,
    isPulling,
    pullDistance,
    isLongPressing,
    isPinching,
    pinchScale,
    
    // Event handlers
    onSwipe,
    onPullRefresh,
    onLongPress,
    
    // Manual setup/cleanup
    setup,
    cleanup
  }
}