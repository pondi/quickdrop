import { defineAsyncComponent } from 'vue'
import LoadingSpinner from '@/Components/App/LoadingSpinner.vue'
import ErrorState from '@/Components/App/ErrorState.vue'

/**
 * Lazy load a component with loading and error states
 * @param {Function} loader - Import function for the component
 * @param {Object} options - Loading options
 * @returns {Object} Async component
 */
export function lazyLoadComponent(loader, options = {}) {
  const {
    loadingComponent = LoadingSpinner,
    errorComponent = ErrorState,
    delay = 200,
    timeout = 10000,
    suspensible = false,
    onError
  } = options

  return defineAsyncComponent({
    loader,
    loadingComponent,
    errorComponent,
    delay,
    timeout,
    suspensible,
    onError: onError || ((error, retry, fail, attempts) => {
      console.error('Component loading error:', error)
      if (attempts <= 3) {
        // Retry up to 3 times
        retry()
      } else {
        fail()
      }
    })
  })
}

/**
 * Preload a component
 * @param {Function} loader - Import function for the component
 */
export function preloadComponent(loader) {
  loader()
}

/**
 * Lazy load multiple components
 * @param {Object} components - Object with component loaders
 * @returns {Object} Object with lazy loaded components
 */
export function lazyLoadComponents(components) {
  const result = {}
  for (const [key, loader] of Object.entries(components)) {
    result[key] = lazyLoadComponent(loader)
  }
  return result
}

/**
 * Create route-based code splitting
 * @param {String} path - Route path pattern
 * @returns {Function} Route matcher
 */
export function routeBasedSplit(path) {
  return {
    test: new RegExp(path),
    name: path.replace(/[^\w]/g, '_'),
    chunks: 'async'
  }
}