/**
 * Animation performance optimization utilities
 */

// Check if user prefers reduced motion
export const prefersReducedMotion = () => {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

// Request animation frame with fallback
export const raf = window.requestAnimationFrame || 
  window.webkitRequestAnimationFrame ||
  window.mozRequestAnimationFrame ||
  ((callback) => window.setTimeout(callback, 1000 / 60))

// Cancel animation frame with fallback
export const cancelRaf = window.cancelAnimationFrame ||
  window.webkitCancelAnimationFrame ||
  window.mozCancelAnimationFrame ||
  ((id) => window.clearTimeout(id))

/**
 * Throttle function execution using RAF
 */
export function rafThrottle(callback) {
  let requestId = null
  let lastArgs = null

  const throttled = (...args) => {
    lastArgs = args
    
    if (requestId === null) {
      requestId = raf(() => {
        callback.apply(null, lastArgs)
        requestId = null
      })
    }
  }

  throttled.cancel = () => {
    if (requestId !== null) {
      cancelRaf(requestId)
      requestId = null
    }
  }

  return throttled
}

/**
 * Debounce with RAF
 */
export function rafDebounce(callback, delay = 0) {
  let timeoutId = null
  let requestId = null

  const debounced = (...args) => {
    if (timeoutId !== null) {
      clearTimeout(timeoutId)
    }
    
    if (requestId !== null) {
      cancelRaf(requestId)
    }

    timeoutId = setTimeout(() => {
      requestId = raf(() => {
        callback.apply(null, args)
        requestId = null
      })
      timeoutId = null
    }, delay)
  }

  debounced.cancel = () => {
    if (timeoutId !== null) {
      clearTimeout(timeoutId)
      timeoutId = null
    }
    if (requestId !== null) {
      cancelRaf(requestId)
      requestId = null
    }
  }

  return debounced
}

/**
 * Optimize scroll performance
 */
export function optimizeScroll(element, options = {}) {
  const {
    passive = true,
    capture = false,
    throttle = true
  } = options

  let isScrolling = false
  let scrollTimeout = null

  const handleScroll = throttle ? rafThrottle((e) => {
    if (!isScrolling) {
      element.classList.add('is-scrolling')
      isScrolling = true
    }

    if (scrollTimeout) {
      clearTimeout(scrollTimeout)
    }

    scrollTimeout = setTimeout(() => {
      element.classList.remove('is-scrolling')
      isScrolling = false
    }, 150)

    if (options.onScroll) {
      options.onScroll(e)
    }
  }) : options.onScroll

  element.addEventListener('scroll', handleScroll, { passive, capture })

  return () => {
    element.removeEventListener('scroll', handleScroll, { passive, capture })
    if (handleScroll.cancel) {
      handleScroll.cancel()
    }
  }
}

/**
 * Use CSS transforms for animations
 */
export function animateTransform(element, properties, options = {}) {
  const {
    duration = 300,
    easing = 'ease-out',
    delay = 0,
    fill = 'both'
  } = options

  // Use CSS transforms for better performance
  const transforms = []
  const styles = {}

  Object.entries(properties).forEach(([key, value]) => {
    switch (key) {
      case 'x':
        transforms.push(`translateX(${value}px)`)
        break
      case 'y':
        transforms.push(`translateY(${value}px)`)
        break
      case 'scale':
        transforms.push(`scale(${value})`)
        break
      case 'rotate':
        transforms.push(`rotate(${value}deg)`)
        break
      case 'opacity':
        styles.opacity = value
        break
      default:
        styles[key] = value
    }
  })

  if (transforms.length > 0) {
    styles.transform = transforms.join(' ')
  }

  // Use Web Animations API if available
  if (element.animate) {
    return element.animate([styles], {
      duration,
      easing,
      delay,
      fill
    })
  }

  // Fallback to CSS transitions
  element.style.transition = `all ${duration}ms ${easing} ${delay}ms`
  Object.assign(element.style, styles)

  return {
    finished: new Promise(resolve => {
      setTimeout(resolve, duration + delay)
    })
  }
}

/**
 * Batch DOM updates
 */
export function batchUpdates(updates) {
  raf(() => {
    updates.forEach(update => update())
  })
}

/**
 * Use will-change for performance
 */
export function prepareAnimation(element, properties = ['transform', 'opacity']) {
  element.style.willChange = properties.join(', ')

  return () => {
    element.style.willChange = 'auto'
  }
}

/**
 * GPU acceleration helper
 */
export function enableGPUAcceleration(element) {
  element.style.transform = 'translateZ(0)'
  element.style.backfaceVisibility = 'hidden'
  element.style.perspective = '1000px'
}

/**
 * Intersection Observer for animations
 */
export function animateOnScroll(selector, animationClass = 'animate', options = {}) {
  const {
    threshold = 0.1,
    rootMargin = '0px',
    once = true
  } = options

  if (!('IntersectionObserver' in window)) {
    // Fallback: add animation class immediately
    document.querySelectorAll(selector).forEach(el => {
      el.classList.add(animationClass)
    })
    return
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add(animationClass)
        
        if (once) {
          observer.unobserve(entry.target)
        }
      } else if (!once) {
        entry.target.classList.remove(animationClass)
      }
    })
  }, {
    threshold,
    rootMargin
  })

  document.querySelectorAll(selector).forEach(el => {
    observer.observe(el)
  })

  return observer
}

/**
 * Performance monitor for animations
 */
export class AnimationPerformanceMonitor {
  constructor() {
    this.metrics = {
      fps: 0,
      frameTime: 0,
      droppedFrames: 0
    }
    this.isMonitoring = false
    this.callbacks = []
  }

  start() {
    if (this.isMonitoring) return

    this.isMonitoring = true
    let lastTime = performance.now()
    let frames = 0

    const measure = () => {
      if (!this.isMonitoring) return

      const currentTime = performance.now()
      const deltaTime = currentTime - lastTime
      frames++

      if (deltaTime >= 1000) {
        this.metrics.fps = Math.round((frames * 1000) / deltaTime)
        this.metrics.frameTime = deltaTime / frames
        
        // Detect dropped frames (> 16.67ms indicates < 60fps)
        if (this.metrics.frameTime > 16.67) {
          this.metrics.droppedFrames++
        }

        this.notifyCallbacks()
        
        frames = 0
        lastTime = currentTime
      }

      raf(measure)
    }

    raf(measure)
  }

  stop() {
    this.isMonitoring = false
  }

  onUpdate(callback) {
    this.callbacks.push(callback)
  }

  notifyCallbacks() {
    this.callbacks.forEach(callback => callback(this.metrics))
  }
}

// Export singleton monitor
export const performanceMonitor = new AnimationPerformanceMonitor()