/**
 * Accessibility utilities for WCAG compliance
 */

/**
 * Announce message to screen readers
 */
export function announceToScreenReader(message, priority = 'polite') {
  const announcement = document.createElement('div')
  announcement.setAttribute('role', 'status')
  announcement.setAttribute('aria-live', priority)
  announcement.setAttribute('aria-atomic', 'true')
  announcement.className = 'sr-only'
  announcement.textContent = message

  document.body.appendChild(announcement)

  // Remove after announcement
  setTimeout(() => {
    document.body.removeChild(announcement)
  }, 1000)
}

/**
 * Trap focus within an element
 */
export function trapFocus(element) {
  const focusableElements = element.querySelectorAll(
    'a[href], button, textarea, input[type="text"], input[type="radio"], input[type="checkbox"], select, [tabindex]:not([tabindex="-1"])'
  )
  
  const firstFocusableElement = focusableElements[0]
  const lastFocusableElement = focusableElements[focusableElements.length - 1]

  function handleKeyDown(e) {
    const isTabPressed = e.key === 'Tab'

    if (!isTabPressed) return

    if (e.shiftKey) {
      // Shift + Tab
      if (document.activeElement === firstFocusableElement) {
        lastFocusableElement.focus()
        e.preventDefault()
      }
    } else {
      // Tab
      if (document.activeElement === lastFocusableElement) {
        firstFocusableElement.focus()
        e.preventDefault()
      }
    }
  }

  element.addEventListener('keydown', handleKeyDown)
  
  // Focus first element
  if (firstFocusableElement) {
    firstFocusableElement.focus()
  }

  return () => {
    element.removeEventListener('keydown', handleKeyDown)
  }
}

/**
 * Manage focus for route changes
 */
export function manageFocusOnRouteChange() {
  // Skip to main content on route change
  const main = document.querySelector('main') || document.querySelector('[role="main"]')
  
  if (main) {
    // Make main focusable
    main.tabIndex = -1
    main.focus()
    
    // Remove tabindex after focus
    main.addEventListener('blur', () => {
      main.removeAttribute('tabindex')
    }, { once: true })
  }

  // Announce page change
  const pageTitle = document.title
  announceToScreenReader(`Navigated to ${pageTitle}`)
}

/**
 * Generate unique IDs for ARIA relationships
 */
let idCounter = 0
export function generateId(prefix = 'aria') {
  return `${prefix}-${Date.now()}-${++idCounter}`
}

/**
 * Set up skip links
 */
export function setupSkipLinks() {
  const skipLink = document.createElement('a')
  skipLink.href = '#main-content'
  skipLink.className = 'skip-link'
  skipLink.textContent = 'Skip to main content'
  
  skipLink.addEventListener('click', (e) => {
    e.preventDefault()
    const target = document.getElementById('main-content')
    if (target) {
      target.tabIndex = -1
      target.focus()
      target.scrollIntoView()
    }
  })

  document.body.insertBefore(skipLink, document.body.firstChild)
}

/**
 * Keyboard navigation helper
 */
export class KeyboardNavigator {
  constructor(container, options = {}) {
    this.container = container
    this.options = {
      itemSelector: '[role="option"], [role="menuitem"], [role="tab"]',
      activeClass: 'active',
      focusClass: 'focus',
      wrap: true,
      orientation: 'vertical', // vertical | horizontal | both
      onSelect: null,
      onFocus: null,
      ...options
    }
    
    this.currentIndex = -1
    this.items = []
    
    this.init()
  }

  init() {
    this.updateItems()
    this.container.addEventListener('keydown', this.handleKeyDown.bind(this))
    
    // Handle focus
    this.container.addEventListener('focusin', this.handleFocusIn.bind(this))
  }

  updateItems() {
    this.items = Array.from(this.container.querySelectorAll(this.options.itemSelector))
      .filter(item => !item.disabled && !item.getAttribute('aria-disabled'))
  }

  handleKeyDown(e) {
    const { key } = e
    let handled = false

    switch (key) {
      case 'ArrowDown':
        if (this.options.orientation !== 'horizontal') {
          this.focusNext()
          handled = true
        }
        break
      
      case 'ArrowUp':
        if (this.options.orientation !== 'horizontal') {
          this.focusPrevious()
          handled = true
        }
        break
      
      case 'ArrowRight':
        if (this.options.orientation !== 'vertical') {
          this.focusNext()
          handled = true
        }
        break
      
      case 'ArrowLeft':
        if (this.options.orientation !== 'vertical') {
          this.focusPrevious()
          handled = true
        }
        break
      
      case 'Home':
        this.focusFirst()
        handled = true
        break
      
      case 'End':
        this.focusLast()
        handled = true
        break
      
      case 'Enter':
      case ' ':
        this.selectCurrent()
        handled = true
        break
      
      case 'Escape':
        this.container.blur()
        handled = true
        break
    }

    if (handled) {
      e.preventDefault()
      e.stopPropagation()
    }
  }

  handleFocusIn(e) {
    const index = this.items.indexOf(e.target)
    if (index !== -1) {
      this.currentIndex = index
      this.updateFocus()
    }
  }

  focusNext() {
    if (this.items.length === 0) return

    let nextIndex = this.currentIndex + 1
    
    if (nextIndex >= this.items.length) {
      nextIndex = this.options.wrap ? 0 : this.items.length - 1
    }

    this.focusItem(nextIndex)
  }

  focusPrevious() {
    if (this.items.length === 0) return

    let prevIndex = this.currentIndex - 1
    
    if (prevIndex < 0) {
      prevIndex = this.options.wrap ? this.items.length - 1 : 0
    }

    this.focusItem(prevIndex)
  }

  focusFirst() {
    this.focusItem(0)
  }

  focusLast() {
    this.focusItem(this.items.length - 1)
  }

  focusItem(index) {
    if (index < 0 || index >= this.items.length) return

    this.currentIndex = index
    const item = this.items[index]
    
    item.focus()
    this.updateFocus()
    
    if (this.options.onFocus) {
      this.options.onFocus(item, index)
    }
  }

  selectCurrent() {
    if (this.currentIndex === -1) return

    const item = this.items[this.currentIndex]
    
    if (this.options.onSelect) {
      this.options.onSelect(item, this.currentIndex)
    } else {
      item.click()
    }
  }

  updateFocus() {
    this.items.forEach((item, index) => {
      if (index === this.currentIndex) {
        item.classList.add(this.options.focusClass)
        item.setAttribute('aria-selected', 'true')
      } else {
        item.classList.remove(this.options.focusClass)
        item.setAttribute('aria-selected', 'false')
      }
    })
  }

  destroy() {
    this.container.removeEventListener('keydown', this.handleKeyDown)
    this.container.removeEventListener('focusin', this.handleFocusIn)
  }
}

/**
 * Color contrast checker
 */
export function checkColorContrast(foreground, background) {
  // Convert hex to RGB
  const getRGB = (hex) => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
    return result ? {
      r: parseInt(result[1], 16),
      g: parseInt(result[2], 16),
      b: parseInt(result[3], 16)
    } : null
  }

  // Calculate relative luminance
  const getLuminance = (rgb) => {
    const { r, g, b } = rgb
    const sRGB = [r, g, b].map(value => {
      value /= 255
      return value <= 0.03928
        ? value / 12.92
        : Math.pow((value + 0.055) / 1.055, 2.4)
    })
    return 0.2126 * sRGB[0] + 0.7152 * sRGB[1] + 0.0722 * sRGB[2]
  }

  const fgRGB = getRGB(foreground)
  const bgRGB = getRGB(background)
  
  if (!fgRGB || !bgRGB) return null

  const fgLuminance = getLuminance(fgRGB)
  const bgLuminance = getLuminance(bgRGB)

  // Calculate contrast ratio
  const lighter = Math.max(fgLuminance, bgLuminance)
  const darker = Math.min(fgLuminance, bgLuminance)
  const ratio = (lighter + 0.05) / (darker + 0.05)

  return {
    ratio: ratio.toFixed(2),
    passes: {
      normal: ratio >= 4.5,
      large: ratio >= 3,
      enhanced: ratio >= 7
    }
  }
}

/**
 * Set up high contrast mode detection
 */
export function detectHighContrast() {
  const mediaQuery = window.matchMedia('(prefers-contrast: high)')
  
  const updateContrast = (e) => {
    document.documentElement.classList.toggle('high-contrast', e.matches)
  }

  updateContrast(mediaQuery)
  
  if (mediaQuery.addEventListener) {
    mediaQuery.addEventListener('change', updateContrast)
  } else {
    mediaQuery.addListener(updateContrast)
  }
}

/**
 * Focus visible polyfill
 */
export function setupFocusVisible() {
  // Check if browser supports :focus-visible
  try {
    document.querySelector(':focus-visible')
  } catch {
    // Polyfill :focus-visible
    document.addEventListener('keydown', () => {
      document.documentElement.classList.add('keyboard-navigation')
    })

    document.addEventListener('mousedown', () => {
      document.documentElement.classList.remove('keyboard-navigation')
    })
  }
}