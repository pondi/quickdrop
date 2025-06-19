/**
 * Global error handling utilities
 */

// Error types
export const ErrorTypes = {
  NETWORK: 'NETWORK_ERROR',
  VALIDATION: 'VALIDATION_ERROR',
  AUTHENTICATION: 'AUTHENTICATION_ERROR',
  AUTHORIZATION: 'AUTHORIZATION_ERROR',
  NOT_FOUND: 'NOT_FOUND_ERROR',
  SERVER: 'SERVER_ERROR',
  CLIENT: 'CLIENT_ERROR',
  UNKNOWN: 'UNKNOWN_ERROR'
}

// Error messages
const errorMessages = {
  [ErrorTypes.NETWORK]: 'Unable to connect. Please check your internet connection.',
  [ErrorTypes.VALIDATION]: 'Please check your input and try again.',
  [ErrorTypes.AUTHENTICATION]: 'Please log in to continue.',
  [ErrorTypes.AUTHORIZATION]: 'You don\'t have permission to perform this action.',
  [ErrorTypes.NOT_FOUND]: 'The requested resource was not found.',
  [ErrorTypes.SERVER]: 'Something went wrong on our end. Please try again later.',
  [ErrorTypes.CLIENT]: 'An unexpected error occurred. Please refresh and try again.',
  [ErrorTypes.UNKNOWN]: 'An unknown error occurred. Please try again.'
}

/**
 * Custom error class
 */
export class AppError extends Error {
  constructor(message, type = ErrorTypes.UNKNOWN, details = {}) {
    super(message)
    this.name = 'AppError'
    this.type = type
    this.details = details
    this.timestamp = new Date().toISOString()
  }
}

/**
 * Determine error type from error object
 */
export function getErrorType(error) {
  // Network errors
  if (error.code === 'ECONNABORTED' || 
      error.message === 'Network Error' ||
      !navigator.onLine) {
    return ErrorTypes.NETWORK
  }

  // HTTP status based errors
  if (error.response) {
    const status = error.response.status
    
    switch (status) {
      case 400:
      case 422:
        return ErrorTypes.VALIDATION
      case 401:
        return ErrorTypes.AUTHENTICATION
      case 403:
        return ErrorTypes.AUTHORIZATION
      case 404:
        return ErrorTypes.NOT_FOUND
      case 500:
      case 502:
      case 503:
      case 504:
        return ErrorTypes.SERVER
      default:
        if (status >= 400 && status < 500) {
          return ErrorTypes.CLIENT
        }
        if (status >= 500) {
          return ErrorTypes.SERVER
        }
    }
  }

  return ErrorTypes.UNKNOWN
}

/**
 * Format error for display
 */
export function formatError(error) {
  const type = getErrorType(error)
  const defaultMessage = errorMessages[type]

  // Extract message
  let message = defaultMessage
  
  if (error.response?.data?.message) {
    message = error.response.data.message
  } else if (error.message) {
    message = error.message
  }

  // Extract validation errors
  let validationErrors = null
  if (error.response?.data?.errors) {
    validationErrors = error.response.data.errors
  }

  return {
    type,
    message,
    validationErrors,
    statusCode: error.response?.status,
    details: error.response?.data
  }
}

/**
 * Global error handler
 */
export class ErrorHandler {
  constructor() {
    this.handlers = new Map()
    this.errorQueue = []
    this.isOnline = navigator.onLine
    
    this.setupGlobalHandlers()
    this.setupNetworkMonitoring()
  }

  /**
   * Register error handler for specific error type
   */
  register(errorType, handler) {
    if (!this.handlers.has(errorType)) {
      this.handlers.set(errorType, [])
    }
    this.handlers.get(errorType).push(handler)
  }

  /**
   * Handle error
   */
  handle(error, context = {}) {
    const formattedError = formatError(error)
    const errorType = formattedError.type

    // Log error
    this.logError(error, context)

    // Call registered handlers
    if (this.handlers.has(errorType)) {
      this.handlers.get(errorType).forEach(handler => {
        handler(formattedError, context)
      })
    }

    // Call default handler if no specific handler found
    if (!this.handlers.has(errorType) && this.handlers.has('default')) {
      this.handlers.get('default').forEach(handler => {
        handler(formattedError, context)
      })
    }

    // Queue error if offline
    if (!this.isOnline && errorType === ErrorTypes.NETWORK) {
      this.queueError(error, context)
    }

    return formattedError
  }

  /**
   * Setup global error handlers
   */
  setupGlobalHandlers() {
    // Unhandled promise rejections
    window.addEventListener('unhandledrejection', (event) => {
      console.error('Unhandled promise rejection:', event.reason)
      this.handle(event.reason, { source: 'unhandledRejection' })
    })

    // Global errors
    window.addEventListener('error', (event) => {
      console.error('Global error:', event.error)
      this.handle(event.error, { 
        source: 'globalError',
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno
      })
    })
  }

  /**
   * Setup network monitoring
   */
  setupNetworkMonitoring() {
    window.addEventListener('online', () => {
      this.isOnline = true
      this.processErrorQueue()
    })

    window.addEventListener('offline', () => {
      this.isOnline = false
    })
  }

  /**
   * Queue error for later processing
   */
  queueError(error, context) {
    this.errorQueue.push({
      error,
      context,
      timestamp: new Date().toISOString()
    })

    // Limit queue size
    if (this.errorQueue.length > 100) {
      this.errorQueue.shift()
    }
  }

  /**
   * Process queued errors
   */
  async processErrorQueue() {
    while (this.errorQueue.length > 0) {
      const { error, context } = this.errorQueue.shift()
      
      try {
        await this.sendErrorToServer(error, context)
      } catch (err) {
        // Re-queue if still failing
        this.queueError(error, context)
        break
      }
    }
  }

  /**
   * Log error
   */
  logError(error, context) {
    const errorLog = {
      message: error.message,
      stack: error.stack,
      type: getErrorType(error),
      context,
      timestamp: new Date().toISOString(),
      userAgent: navigator.userAgent,
      url: window.location.href
    }

    // Console log in development
    if (import.meta.env.DEV) {
      console.group('🚨 Error Log')
      console.error('Error:', error)
      console.table(errorLog)
      console.groupEnd()
    }

    // Send to logging service
    this.sendErrorToServer(errorLog)
  }

  /**
   * Send error to server
   */
  async sendErrorToServer(errorLog) {
    try {
      await fetch('/api/client-errors', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify(errorLog)
      })
    } catch (err) {
      console.error('Failed to send error to server:', err)
    }
  }
}

// Create singleton instance
export const errorHandler = new ErrorHandler()

// Register default handlers
errorHandler.register(ErrorTypes.AUTHENTICATION, (error) => {
  // Redirect to login
  window.location.href = '/login'
})

errorHandler.register(ErrorTypes.AUTHORIZATION, (error) => {
  // Show permission denied message
  alert('You don\'t have permission to perform this action.')
})

errorHandler.register(ErrorTypes.NETWORK, (error) => {
  // Show offline message
  if (!navigator.onLine) {
    console.log('You are currently offline. Your action will be processed when connection is restored.')
  }
})