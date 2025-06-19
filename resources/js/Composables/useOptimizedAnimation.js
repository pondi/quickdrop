import { ref, onMounted, onUnmounted } from 'vue'
import { 
  prefersReducedMotion,
  rafThrottle,
  animateTransform,
  prepareAnimation,
  enableGPUAcceleration
} from '@/Utils/animationPerformance'

export function useOptimizedAnimation(elementRef, options = {}) {
  const {
    autoGPU = true,
    respectReducedMotion = true,
    threshold = 0.1
  } = options

  const isAnimating = ref(false)
  const isVisible = ref(false)
  const observer = ref(null)

  // Check if animations should be disabled
  const shouldAnimate = ref(!respectReducedMotion || !prefersReducedMotion())

  // Optimized animation function
  const animate = async (properties, animationOptions = {}) => {
    if (!shouldAnimate.value || !elementRef.value) {
      // Skip animation if reduced motion is preferred
      if (elementRef.value && properties.opacity !== undefined) {
        elementRef.value.style.opacity = properties.opacity
      }
      return
    }

    isAnimating.value = true

    // Prepare element for animation
    const cleanup = prepareAnimation(elementRef.value)

    try {
      // Perform animation
      const animation = animateTransform(elementRef.value, properties, {
        ...animationOptions,
        duration: shouldAnimate.value ? animationOptions.duration : 0
      })

      await animation.finished
    } finally {
      cleanup()
      isAnimating.value = false
    }
  }

  // Fade in animation
  const fadeIn = (duration = 300, delay = 0) => {
    return animate(
      { opacity: 1 },
      { duration, delay, easing: 'ease-out' }
    )
  }

  // Fade out animation
  const fadeOut = (duration = 200) => {
    return animate(
      { opacity: 0 },
      { duration, easing: 'ease-in' }
    )
  }

  // Slide animations
  const slideIn = (direction = 'bottom', distance = 20, duration = 300) => {
    const transforms = {
      bottom: { y: distance, opacity: 0 },
      top: { y: -distance, opacity: 0 },
      left: { x: -distance, opacity: 0 },
      right: { x: distance, opacity: 0 }
    }

    const startTransform = transforms[direction]
    
    // Set initial state
    if (elementRef.value) {
      elementRef.value.style.opacity = '0'
      elementRef.value.style.transform = 
        direction === 'left' || direction === 'right' 
          ? `translateX(${startTransform.x}px)`
          : `translateY(${startTransform.y}px)`
    }

    return animate(
      { x: 0, y: 0, opacity: 1 },
      { duration, easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)' }
    )
  }

  // Scale animation
  const scale = (from = 0.95, to = 1, duration = 200) => {
    if (elementRef.value) {
      elementRef.value.style.transform = `scale(${from})`
    }

    return animate(
      { scale: to },
      { duration, easing: 'ease-out' }
    )
  }

  // Bounce animation
  const bounce = (intensity = 1.1, duration = 300) => {
    return animate(
      { scale: intensity },
      { 
        duration: duration / 2,
        easing: 'ease-out'
      }
    ).then(() => animate(
      { scale: 1 },
      { 
        duration: duration / 2,
        easing: 'ease-in'
      }
    ))
  }

  // Shake animation
  const shake = (intensity = 5, duration = 300) => {
    const steps = 4
    const stepDuration = duration / steps

    const shakeSequence = async () => {
      for (let i = 0; i < steps; i++) {
        const direction = i % 2 === 0 ? 1 : -1
        const distance = intensity * (1 - i / steps) * direction
        
        await animate(
          { x: distance },
          { duration: stepDuration, easing: 'ease-in-out' }
        )
      }
      
      // Return to center
      await animate(
        { x: 0 },
        { duration: stepDuration, easing: 'ease-out' }
      )
    }

    return shakeSequence()
  }

  // Reveal on scroll
  const setupRevealOnScroll = (animationCallback = slideIn) => {
    if (!('IntersectionObserver' in window)) {
      // Fallback: show immediately
      isVisible.value = true
      if (elementRef.value) {
        elementRef.value.style.opacity = '1'
      }
      return
    }

    observer.value = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !isVisible.value) {
          isVisible.value = true
          animationCallback()
          observer.value.disconnect()
        }
      })
    }, {
      threshold,
      rootMargin: '50px'
    })

    if (elementRef.value) {
      observer.value.observe(elementRef.value)
    }
  }

  // Parallax effect
  const setupParallax = (speed = 0.5) => {
    if (!shouldAnimate.value) return

    const handleScroll = rafThrottle(() => {
      if (!elementRef.value) return

      const rect = elementRef.value.getBoundingClientRect()
      const centerY = rect.top + rect.height / 2
      const windowCenterY = window.innerHeight / 2
      const distance = centerY - windowCenterY
      const offset = distance * speed

      elementRef.value.style.transform = `translateY(${offset}px)`
    })

    window.addEventListener('scroll', handleScroll, { passive: true })

    return () => {
      window.removeEventListener('scroll', handleScroll)
      if (handleScroll.cancel) {
        handleScroll.cancel()
      }
    }
  }

  // Lifecycle
  onMounted(() => {
    if (autoGPU && elementRef.value) {
      enableGPUAcceleration(elementRef.value)
    }

    // Listen for reduced motion preference changes
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
    const handleChange = (e) => {
      shouldAnimate.value = !respectReducedMotion || !e.matches
    }
    
    if (mediaQuery.addEventListener) {
      mediaQuery.addEventListener('change', handleChange)
    } else {
      mediaQuery.addListener(handleChange)
    }

    onUnmounted(() => {
      if (mediaQuery.removeEventListener) {
        mediaQuery.removeEventListener('change', handleChange)
      } else {
        mediaQuery.removeListener(handleChange)
      }

      if (observer.value) {
        observer.value.disconnect()
      }
    })
  })

  return {
    isAnimating,
    isVisible,
    shouldAnimate,
    animate,
    fadeIn,
    fadeOut,
    slideIn,
    scale,
    bounce,
    shake,
    setupRevealOnScroll,
    setupParallax
  }
}