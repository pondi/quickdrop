<template>
  <div 
    class="skeleton"
    :class="[typeClass, sizeClass, customClass]"
    :style="customStyle"
  >
    <div 
      v-if="animated"
      class="skeleton-shimmer"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'text',
    validator: (value) => ['text', 'title', 'avatar', 'thumbnail', 'button', 'input', 'card'].includes(value)
  },
  width: {
    type: [String, Number],
    default: null
  },
  height: {
    type: [String, Number],
    default: null
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
  },
  rounded: {
    type: [Boolean, String],
    default: false
  },
  animated: {
    type: Boolean,
    default: true
  },
  count: {
    type: Number,
    default: 1
  },
  class: {
    type: String,
    default: ''
  }
})

// Type-based classes
const typeClass = computed(() => {
  const classes = {
    text: 'skeleton-text',
    title: 'skeleton-title',
    avatar: 'skeleton-avatar',
    thumbnail: 'skeleton-thumbnail',
    button: 'skeleton-button',
    input: 'skeleton-input',
    card: 'skeleton-card'
  }
  return classes[props.type] || ''
})

// Size classes
const sizeClass = computed(() => {
  if (props.type === 'text' || props.type === 'title') {
    const sizes = {
      xs: 'h-3',
      sm: 'h-4',
      md: 'h-5',
      lg: 'h-6',
      xl: 'h-8'
    }
    return sizes[props.size]
  }
  
  if (props.type === 'avatar') {
    const sizes = {
      xs: 'w-6 h-6',
      sm: 'w-8 h-8',
      md: 'w-10 h-10',
      lg: 'w-12 h-12',
      xl: 'w-16 h-16'
    }
    return sizes[props.size]
  }
  
  return ''
})

// Custom styles
const customStyle = computed(() => {
  const style = {}
  
  if (props.width) {
    style.width = typeof props.width === 'number' ? `${props.width}px` : props.width
  }
  
  if (props.height) {
    style.height = typeof props.height === 'number' ? `${props.height}px` : props.height
  }
  
  return style
})

// Custom class
const customClass = computed(() => {
  let classes = [props.class]
  
  // Rounded
  if (props.rounded === true) {
    classes.push('rounded')
  } else if (typeof props.rounded === 'string') {
    classes.push(`rounded-${props.rounded}`)
  }
  
  return classes.join(' ')
})
</script>

<style scoped>
/* Base skeleton styles */
.skeleton {
  position: relative;
  overflow: hidden;
  background-color: #e5e7eb;
  dark:background-color: #374151;
}

/* Shimmer animation */
.skeleton-shimmer {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  transform: translateX(-100%);
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.2),
    transparent
  );
  animation: shimmer 1.5s infinite;
}

.dark .skeleton-shimmer {
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.08),
    transparent
  );
}

@keyframes shimmer {
  100% {
    transform: translateX(100%);
  }
}

/* Type-specific styles */
.skeleton-text {
  border-radius: 0.25rem;
}

.skeleton-title {
  border-radius: 0.25rem;
  width: 60%;
}

.skeleton-avatar {
  border-radius: 9999px;
}

.skeleton-thumbnail {
  border-radius: 0.5rem;
  aspect-ratio: 16 / 9;
}

.skeleton-button {
  border-radius: 0.5rem;
  height: 2.5rem;
  width: 6rem;
}

.skeleton-input {
  border-radius: 0.5rem;
  height: 2.5rem;
  width: 100%;
}

.skeleton-card {
  border-radius: 0.75rem;
  height: 10rem;
  width: 100%;
}

/* Rounded utilities */
.rounded { border-radius: 0.25rem; }
.rounded-md { border-radius: 0.375rem; }
.rounded-lg { border-radius: 0.5rem; }
.rounded-xl { border-radius: 0.75rem; }
.rounded-2xl { border-radius: 1rem; }
.rounded-full { border-radius: 9999px; }
</style>