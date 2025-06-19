/**
 * Vue directive for accessibility enhancements
 * Usage: v-a11y="options"
 */

import { generateId } from '@/Utils/accessibility'

export default {
  mounted(el, binding) {
    const options = binding.value || {}
    
    // Label association
    if (options.label) {
      const labelId = generateId('label')
      el.setAttribute('aria-labelledby', labelId)
      
      const label = document.createElement('label')
      label.id = labelId
      label.textContent = options.label
      label.className = 'sr-only'
      el.parentNode.insertBefore(label, el)
    }

    // Description
    if (options.description) {
      const descId = generateId('desc')
      el.setAttribute('aria-describedby', descId)
      
      const desc = document.createElement('span')
      desc.id = descId
      desc.textContent = options.description
      desc.className = 'sr-only'
      el.parentNode.insertBefore(desc, el.nextSibling)
    }

    // Live region
    if (options.live) {
      el.setAttribute('aria-live', options.live)
      el.setAttribute('aria-atomic', 'true')
    }

    // Busy state
    if (options.busy !== undefined) {
      el.setAttribute('aria-busy', options.busy)
    }

    // Invalid state
    if (options.invalid !== undefined) {
      el.setAttribute('aria-invalid', options.invalid)
    }

    // Required
    if (options.required) {
      el.setAttribute('aria-required', 'true')
      el.setAttribute('required', '')
    }

    // Expanded state
    if (options.expanded !== undefined) {
      el.setAttribute('aria-expanded', options.expanded)
    }

    // Selected state
    if (options.selected !== undefined) {
      el.setAttribute('aria-selected', options.selected)
    }

    // Hidden from screen readers
    if (options.hidden) {
      el.setAttribute('aria-hidden', 'true')
    }

    // Role
    if (options.role) {
      el.setAttribute('role', options.role)
    }

    // Keyboard shortcuts
    if (options.keyshortcuts) {
      el.setAttribute('aria-keyshortcuts', options.keyshortcuts)
    }

    // Current state
    if (options.current) {
      el.setAttribute('aria-current', options.current)
    }

    // Has popup
    if (options.haspopup) {
      el.setAttribute('aria-haspopup', options.haspopup)
    }

    // Controls
    if (options.controls) {
      el.setAttribute('aria-controls', options.controls)
    }

    // Store options for updates
    el._a11yOptions = options
  },

  updated(el, binding) {
    const options = binding.value || {}
    const oldOptions = el._a11yOptions || {}

    // Update dynamic attributes
    if (options.busy !== oldOptions.busy) {
      el.setAttribute('aria-busy', options.busy)
    }

    if (options.invalid !== oldOptions.invalid) {
      el.setAttribute('aria-invalid', options.invalid)
    }

    if (options.expanded !== oldOptions.expanded) {
      el.setAttribute('aria-expanded', options.expanded)
    }

    if (options.selected !== oldOptions.selected) {
      el.setAttribute('aria-selected', options.selected)
    }

    if (options.current !== oldOptions.current) {
      if (options.current) {
        el.setAttribute('aria-current', options.current)
      } else {
        el.removeAttribute('aria-current')
      }
    }

    // Update stored options
    el._a11yOptions = options
  },

  unmounted(el) {
    // Clean up generated elements
    const labelId = el.getAttribute('aria-labelledby')
    if (labelId) {
      const label = document.getElementById(labelId)
      if (label && label.parentNode) {
        label.parentNode.removeChild(label)
      }
    }

    const descId = el.getAttribute('aria-describedby')
    if (descId) {
      const desc = document.getElementById(descId)
      if (desc && desc.parentNode) {
        desc.parentNode.removeChild(desc)
      }
    }

    delete el._a11yOptions
  }
}

// Directive shortcuts
export const vA11yLabel = {
  mounted(el, binding) {
    vA11y.mounted(el, { value: { label: binding.value } })
  }
}

export const vA11yDescribe = {
  mounted(el, binding) {
    vA11y.mounted(el, { value: { description: binding.value } })
  }
}

export const vA11yLive = {
  mounted(el, binding) {
    vA11y.mounted(el, { value: { live: binding.value || 'polite' } })
  }
}

export const vA11yRole = {
  mounted(el, binding) {
    vA11y.mounted(el, { value: { role: binding.value } })
  }
}