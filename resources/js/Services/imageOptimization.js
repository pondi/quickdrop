/**
 * Image optimization service for client-side image handling
 */
export class ImageOptimizationService {
  constructor() {
    this.maxWidth = 2048
    this.maxHeight = 2048
    this.quality = 0.85
    this.formats = ['webp', 'jpeg']
  }

  /**
   * Optimize an image file
   * @param {File} file - Image file to optimize
   * @param {Object} options - Optimization options
   * @returns {Promise<Object>} Optimized image data
   */
  async optimizeImage(file, options = {}) {
    const {
      maxWidth = this.maxWidth,
      maxHeight = this.maxHeight,
      quality = this.quality,
      format = 'original',
      generateThumbnail = true,
      thumbnailSize = 400
    } = options

    try {
      // Load image
      const img = await this.loadImage(file)
      
      // Calculate dimensions
      const { width, height } = this.calculateDimensions(
        img.width,
        img.height,
        maxWidth,
        maxHeight
      )
      
      // Create canvas
      const canvas = document.createElement('canvas')
      const ctx = canvas.getContext('2d')
      canvas.width = width
      canvas.height = height
      
      // Enable image smoothing
      ctx.imageSmoothingEnabled = true
      ctx.imageSmoothingQuality = 'high'
      
      // Draw resized image
      ctx.drawImage(img, 0, 0, width, height)
      
      // Generate different formats
      const outputs = {}
      
      // Original format
      const mimeType = format === 'original' ? file.type : `image/${format}`
      outputs.optimized = await this.canvasToBlob(canvas, mimeType, quality)
      
      // Generate WebP version if supported
      if (this.supportsWebP()) {
        outputs.webp = await this.canvasToBlob(canvas, 'image/webp', quality)
      }
      
      // Generate thumbnail
      if (generateThumbnail) {
        outputs.thumbnail = await this.generateThumbnail(img, thumbnailSize)
      }
      
      // Generate placeholder
      outputs.placeholder = await this.generatePlaceholder(canvas, 20)
      
      return {
        original: file,
        outputs,
        metadata: {
          originalSize: file.size,
          optimizedSize: outputs.optimized.size,
          width,
          height,
          reduction: ((file.size - outputs.optimized.size) / file.size * 100).toFixed(2) + '%'
        }
      }
    } catch (error) {
      console.error('Image optimization failed:', error)
      throw error
    }
  }

  /**
   * Load image from file
   */
  loadImage(file) {
    return new Promise((resolve, reject) => {
      const img = new Image()
      const url = URL.createObjectURL(file)
      
      img.onload = () => {
        URL.revokeObjectURL(url)
        resolve(img)
      }
      
      img.onerror = () => {
        URL.revokeObjectURL(url)
        reject(new Error('Failed to load image'))
      }
      
      img.src = url
    })
  }

  /**
   * Calculate scaled dimensions
   */
  calculateDimensions(originalWidth, originalHeight, maxWidth, maxHeight) {
    let width = originalWidth
    let height = originalHeight
    
    // Calculate scaling factor
    const widthRatio = maxWidth / originalWidth
    const heightRatio = maxHeight / originalHeight
    const scaleFactor = Math.min(widthRatio, heightRatio, 1)
    
    if (scaleFactor < 1) {
      width = Math.round(originalWidth * scaleFactor)
      height = Math.round(originalHeight * scaleFactor)
    }
    
    return { width, height }
  }

  /**
   * Convert canvas to blob
   */
  canvasToBlob(canvas, mimeType, quality) {
    return new Promise((resolve) => {
      canvas.toBlob(resolve, mimeType, quality)
    })
  }

  /**
   * Generate thumbnail
   */
  async generateThumbnail(img, size) {
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    
    // Square crop for thumbnail
    const minDimension = Math.min(img.width, img.height)
    const sx = (img.width - minDimension) / 2
    const sy = (img.height - minDimension) / 2
    
    canvas.width = size
    canvas.height = size
    
    ctx.imageSmoothingEnabled = true
    ctx.imageSmoothingQuality = 'high'
    ctx.drawImage(img, sx, sy, minDimension, minDimension, 0, 0, size, size)
    
    return this.canvasToBlob(canvas, 'image/jpeg', 0.8)
  }

  /**
   * Generate low-quality placeholder
   */
  async generatePlaceholder(canvas, size) {
    const placeholderCanvas = document.createElement('canvas')
    const ctx = placeholderCanvas.getContext('2d')
    
    placeholderCanvas.width = size
    placeholderCanvas.height = size
    
    ctx.imageSmoothingEnabled = true
    ctx.drawImage(canvas, 0, 0, size, size)
    
    // Convert to base64 for inline usage
    return placeholderCanvas.toDataURL('image/jpeg', 0.4)
  }

  /**
   * Check WebP support
   */
  supportsWebP() {
    const canvas = document.createElement('canvas')
    canvas.width = 1
    canvas.height = 1
    const dataURL = canvas.toDataURL('image/webp')
    return dataURL.indexOf('data:image/webp') === 0
  }

  /**
   * Lazy load images with intersection observer
   */
  setupLazyLoading(selector = 'img[data-lazy]') {
    if (!('IntersectionObserver' in window)) return
    
    const images = document.querySelectorAll(selector)
    
    const imageObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target
          img.src = img.dataset.src
          
          if (img.dataset.srcset) {
            img.srcset = img.dataset.srcset
          }
          
          img.classList.add('loaded')
          imageObserver.unobserve(img)
        }
      })
    }, {
      rootMargin: '50px'
    })
    
    images.forEach(img => imageObserver.observe(img))
  }

  /**
   * Preload critical images
   */
  preloadImages(urls) {
    urls.forEach(url => {
      const link = document.createElement('link')
      link.rel = 'preload'
      link.as = 'image'
      link.href = url
      document.head.appendChild(link)
    })
  }

  /**
   * Generate responsive image srcset
   */
  generateSrcset(baseUrl, sizes = [640, 768, 1024, 1280, 1536]) {
    return sizes
      .map(size => `${baseUrl}?w=${size} ${size}w`)
      .join(', ')
  }

  /**
   * Calculate optimal image size based on viewport
   */
  getOptimalImageSize(containerWidth) {
    const dpr = window.devicePixelRatio || 1
    const breakpoints = [640, 768, 1024, 1280, 1536, 2048]
    
    const targetWidth = containerWidth * dpr
    
    for (const breakpoint of breakpoints) {
      if (targetWidth <= breakpoint) {
        return breakpoint
      }
    }
    
    return breakpoints[breakpoints.length - 1]
  }
}

// Export singleton instance
export const imageOptimization = new ImageOptimizationService()