import { router } from '@inertiajs/vue3'

/**
 * Prefetch strategy for Inertia.js routes
 */
export class RoutePrefetcher {
  constructor() {
    this.prefetchedRoutes = new Set()
    this.prefetchQueue = []
    this.isPrefetching = false
    this.observer = null
  }

  /**
   * Initialize route prefetching
   */
  init() {
    // Prefetch on link hover
    this.setupHoverPrefetch()
    
    // Prefetch visible links using Intersection Observer
    this.setupIntersectionObserver()
    
    // Prefetch based on user patterns
    this.setupPredictivePrefetch()
  }

  /**
   * Setup hover-based prefetching
   */
  setupHoverPrefetch() {
    let hoverTimer = null
    
    document.addEventListener('mouseover', (e) => {
      const link = e.target.closest('a[href]')
      if (!link || !this.shouldPrefetch(link.href)) return
      
      // Delay prefetch to avoid unnecessary requests
      hoverTimer = setTimeout(() => {
        this.prefetchRoute(link.href)
      }, 100)
    })
    
    document.addEventListener('mouseout', (e) => {
      if (hoverTimer) {
        clearTimeout(hoverTimer)
        hoverTimer = null
      }
    })
  }

  /**
   * Setup intersection observer for visible links
   */
  setupIntersectionObserver() {
    if (!('IntersectionObserver' in window)) return
    
    this.observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const link = entry.target
          if (this.shouldPrefetch(link.href)) {
            // Add to queue with low priority
            this.addToQueue(link.href, 'low')
          }
        }
      })
    }, {
      rootMargin: '50px'
    })
    
    // Observe all internal links
    this.observeLinks()
  }

  /**
   * Observe all internal links
   */
  observeLinks() {
    const links = document.querySelectorAll('a[href^="/"]')
    links.forEach(link => {
      if (this.observer) {
        this.observer.observe(link)
      }
    })
  }

  /**
   * Setup predictive prefetching based on user patterns
   */
  setupPredictivePrefetch() {
    // Common navigation patterns
    const patterns = {
      '/dashboard': ['/quickdrop/create', '/quickdrop'],
      '/quickdrop': ['/quickdrop/create'],
      '/quickdrop/create': ['/quickdrop']
    }
    
    // Prefetch likely next pages based on current route
    router.on('navigate', (event) => {
      const currentPath = event.detail.page.url
      const likelyNext = patterns[currentPath] || []
      
      likelyNext.forEach(path => {
        this.addToQueue(path, 'medium')
      })
    })
  }

  /**
   * Check if route should be prefetched
   */
  shouldPrefetch(url) {
    // Skip if already prefetched
    if (this.prefetchedRoutes.has(url)) return false
    
    // Skip external links
    if (!url.startsWith('/') && !url.startsWith(window.location.origin)) return false
    
    // Skip downloads and special links
    if (url.includes('/download') || url.includes('#')) return false
    
    // Check connection type
    if ('connection' in navigator) {
      const connection = navigator.connection
      if (connection.saveData || connection.effectiveType === 'slow-2g') {
        return false
      }
    }
    
    return true
  }

  /**
   * Add route to prefetch queue
   */
  addToQueue(url, priority = 'medium') {
    if (!this.shouldPrefetch(url)) return
    
    const priorityWeight = {
      high: 0,
      medium: 1,
      low: 2
    }
    
    this.prefetchQueue.push({ url, priority: priorityWeight[priority] })
    this.prefetchQueue.sort((a, b) => a.priority - b.priority)
    
    this.processPrefetchQueue()
  }

  /**
   * Process prefetch queue
   */
  async processPrefetchQueue() {
    if (this.isPrefetching || this.prefetchQueue.length === 0) return
    
    this.isPrefetching = true
    
    while (this.prefetchQueue.length > 0) {
      const { url } = this.prefetchQueue.shift()
      
      try {
        await this.prefetchRoute(url)
        // Small delay between prefetches
        await new Promise(resolve => setTimeout(resolve, 100))
      } catch (error) {
        console.warn('Prefetch failed:', url, error)
      }
    }
    
    this.isPrefetching = false
  }

  /**
   * Prefetch a route
   */
  async prefetchRoute(url) {
    if (this.prefetchedRoutes.has(url)) return
    
    // Mark as prefetched immediately to avoid duplicates
    this.prefetchedRoutes.add(url)
    
    // Use Inertia's built-in prefetch if available
    if (router.prefetch) {
      await router.prefetch(url)
    } else {
      // Fallback to manual prefetch
      await fetch(url, {
        method: 'GET',
        headers: {
          'X-Inertia': 'true',
          'X-Inertia-Version': router.version
        }
      })
    }
  }

  /**
   * Clear prefetch cache
   */
  clearCache() {
    this.prefetchedRoutes.clear()
    this.prefetchQueue = []
  }

  /**
   * Destroy prefetcher
   */
  destroy() {
    if (this.observer) {
      this.observer.disconnect()
    }
    this.clearCache()
  }
}

// Create singleton instance
export const routePrefetcher = new RoutePrefetcher()

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => routePrefetcher.init())
} else {
  routePrefetcher.init()
}