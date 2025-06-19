<template>
  <div
    ref="container"
    class="relative overflow-hidden"
    :class="containerClass"
    :style="containerStyle"
  >
    <!-- Placeholder/Blur -->
    <div
      v-if="showPlaceholder"
      class="absolute inset-0"
      :class="placeholderClass"
    >
      <!-- Blur placeholder -->
      <img
        v-if="placeholder"
        :src="placeholder"
        :alt="alt"
        class="w-full h-full object-cover filter blur-xl scale-110"
      />
      <!-- Skeleton loader -->
      <div
        v-else
        class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 animate-pulse"
      />
    </div>
    
    <!-- Main image -->
    <picture v-if="isIntersecting || eager">
      <!-- WebP sources -->
      <source
        v-if="sources.webp"
        type="image/webp"
        :srcset="sources.webp.srcset"
        :sizes="sizes"
      />
      
      <!-- AVIF sources -->
      <source
        v-if="sources.avif"
        type="image/avif"
        :srcset="sources.avif.srcset"
        :sizes="sizes"
      />
      
      <!-- Original format -->
      <img
        ref="image"
        :src="currentSrc"
        :srcset="srcset"
        :sizes="sizes"
        :alt="alt"
        :loading="loading"
        :decoding="decoding"
        :width="width"
        :height="height"
        :class="imageClass"
        @load="handleLoad"
        @error="handleError"
      />
    </picture>
    
    <!-- Loading spinner -->
    <div
      v-if="isLoading && showLoader"
      class="absolute inset-0 flex items-center justify-center bg-black/10"
    >
      <div class="w-8 h-8 border-2 border-white/30 border-t-white rounded-full animate-spin" />
    </div>
    
    <!-- Error state -->
    <div
      v-if="hasError"
      class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800"
    >
      <div class="text-center p-4">
        <PhotoIcon class="w-12 h-12 text-gray-400 mx-auto mb-2" />
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ errorMessage }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { PhotoIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  src: {
    type: String,
    required: true
  },
  alt: {
    type: String,
    default: ''
  },
  width: {
    type: [Number, String],
    default: null
  },
  height: {
    type: [Number, String],
    default: null
  },
  sizes: {
    type: String,
    default: '100vw'
  },
  placeholder: {
    type: String,
    default: null
  },
  srcset: {
    type: String,
    default: null
  },
  sources: {
    type: Object,
    default: () => ({})
  },
  eager: {
    type: Boolean,
    default: false
  },
  loading: {
    type: String,
    default: 'lazy',
    validator: (value) => ['lazy', 'eager'].includes(value)
  },
  decoding: {
    type: String,
    default: 'async',
    validator: (value) => ['async', 'sync', 'auto'].includes(value)
  },
  objectFit: {
    type: String,
    default: 'cover',
    validator: (value) => ['contain', 'cover', 'fill', 'none', 'scale-down'].includes(value)
  },
  containerClass: {
    type: String,
    default: ''
  },
  imageClass: {
    type: String,
    default: ''
  },
  showLoader: {
    type: Boolean,
    default: true
  },
  retryCount: {
    type: Number,
    default: 3
  },
  retryDelay: {
    type: Number,
    default: 1000
  }
})

// Refs
const container = ref(null)
const image = ref(null)
const isIntersecting = ref(false)
const isLoading = ref(true)
const hasError = ref(false)
const showPlaceholder = ref(true)
const currentRetry = ref(0)

// Computed
const currentSrc = computed(() => {
  // Use responsive image based on viewport
  if (props.sources.responsive) {
    const width = window.innerWidth * window.devicePixelRatio
    const breakpoints = Object.keys(props.sources.responsive)
      .map(Number)
      .sort((a, b) => a - b)
    
    for (const breakpoint of breakpoints) {
      if (width <= breakpoint) {
        return props.sources.responsive[breakpoint]
      }
    }
  }
  
  return props.src
})

const containerStyle = computed(() => {
  const style = {}
  
  // Maintain aspect ratio
  if (props.width && props.height) {
    const aspectRatio = props.height / props.width
    style.paddingBottom = `${aspectRatio * 100}%`
  }
  
  return style
})

const placeholderClass = computed(() => {
  return [
    'transition-opacity duration-300',
    showPlaceholder.value ? 'opacity-100' : 'opacity-0'
  ]
})

const errorMessage = computed(() => {
  return 'Failed to load image'
})

// Methods
function handleLoad() {
  isLoading.value = false
  hasError.value = false
  
  // Fade out placeholder
  setTimeout(() => {
    showPlaceholder.value = false
  }, 100)
  
  // Reset retry count on success
  currentRetry.value = 0
}

function handleError() {
  if (currentRetry.value < props.retryCount) {
    currentRetry.value++
    
    // Retry after delay
    setTimeout(() => {
      if (image.value) {
        image.value.src = currentSrc.value + '?retry=' + currentRetry.value
      }
    }, props.retryDelay * currentRetry.value)
  } else {
    isLoading.value = false
    hasError.value = true
  }
}

// Intersection Observer
let observer = null

function setupIntersectionObserver() {
  if (!props.eager && 'IntersectionObserver' in window) {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            isIntersecting.value = true
            observer.disconnect()
          }
        })
      },
      {
        rootMargin: '50px'
      }
    )
    
    if (container.value) {
      observer.observe(container.value)
    }
  } else {
    isIntersecting.value = true
  }
}

// Generate srcset for different densities
function generateSrcset(src) {
  const extension = src.split('.').pop()
  const basePath = src.replace(`.${extension}`, '')
  
  return [
    `${basePath}.${extension} 1x`,
    `${basePath}@2x.${extension} 2x`,
    `${basePath}@3x.${extension} 3x`
  ].join(', ')
}

// Lifecycle
onMounted(() => {
  setupIntersectionObserver()
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
})

// Watch for src changes
watch(() => props.src, () => {
  isLoading.value = true
  hasError.value = false
  showPlaceholder.value = true
  currentRetry.value = 0
})
</script>

<style scoped>
/* Object fit utilities */
.object-contain { object-fit: contain; }
.object-cover { object-fit: cover; }
.object-fill { object-fit: fill; }
.object-none { object-fit: none; }
.object-scale-down { object-fit: scale-down; }
</style>