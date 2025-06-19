/**
 * Service for generating file thumbnails client-side
 */

export class ThumbnailService {
  static async generateThumbnail(file, options = {}) {
    const {
      maxWidth = 400,
      maxHeight = 300,
      quality = 0.8
    } = options;

    // Only generate thumbnails for images
    if (!file.type.startsWith('image/')) {
      return null;
    }

    // Skip if file is too large (> 10MB)
    if (file.size > 10 * 1024 * 1024) {
      return null;
    }

    try {
      // Create object URL for the file
      const objectUrl = URL.createObjectURL(file);

      // Load image
      const img = await this.loadImage(objectUrl);

      // Calculate dimensions
      const { width, height } = this.calculateDimensions(
        img.width,
        img.height,
        maxWidth,
        maxHeight
      );

      // Create canvas and draw resized image
      const canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;

      const ctx = canvas.getContext('2d');
      
      // Enable image smoothing for better quality
      ctx.imageSmoothingEnabled = true;
      ctx.imageSmoothingQuality = 'high';

      // Draw the image
      ctx.drawImage(img, 0, 0, width, height);

      // Clean up object URL
      URL.revokeObjectURL(objectUrl);

      // Convert to blob
      const blob = await new Promise(resolve => {
        canvas.toBlob(resolve, 'image/jpeg', quality);
      });

      // Convert to data URL for immediate display
      const dataUrl = await this.blobToDataUrl(blob);

      return {
        blob,
        dataUrl,
        width,
        height
      };
    } catch (error) {
      console.error('Failed to generate thumbnail:', error);
      return null;
    }
  }

  static async generateVideoThumbnail(file, options = {}) {
    const {
      timeSeconds = 1,
      maxWidth = 400,
      maxHeight = 300,
      quality = 0.8
    } = options;

    // Only process video files
    if (!file.type.startsWith('video/')) {
      return null;
    }

    // Skip if file is too large (> 50MB)
    if (file.size > 50 * 1024 * 1024) {
      return null;
    }

    try {
      const objectUrl = URL.createObjectURL(file);
      
      // Create video element
      const video = document.createElement('video');
      video.src = objectUrl;
      video.crossOrigin = 'anonymous';
      
      // Wait for video to load metadata
      await new Promise((resolve, reject) => {
        video.onloadedmetadata = resolve;
        video.onerror = reject;
      });

      // Seek to specified time
      video.currentTime = Math.min(timeSeconds, video.duration);
      
      // Wait for seek to complete
      await new Promise((resolve) => {
        video.onseeked = resolve;
      });

      // Calculate dimensions
      const { width, height } = this.calculateDimensions(
        video.videoWidth,
        video.videoHeight,
        maxWidth,
        maxHeight
      );

      // Create canvas and draw frame
      const canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;

      const ctx = canvas.getContext('2d');
      ctx.drawImage(video, 0, 0, width, height);

      // Clean up
      URL.revokeObjectURL(objectUrl);

      // Convert to blob
      const blob = await new Promise(resolve => {
        canvas.toBlob(resolve, 'image/jpeg', quality);
      });

      const dataUrl = await this.blobToDataUrl(blob);

      return {
        blob,
        dataUrl,
        width,
        height,
        duration: video.duration
      };
    } catch (error) {
      console.error('Failed to generate video thumbnail:', error);
      return null;
    }
  }

  static async generatePDFThumbnail(file) {
    // This would require a PDF rendering library like PDF.js
    // For now, return null
    console.log('PDF thumbnail generation not implemented');
    return null;
  }

  static loadImage(url) {
    return new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = reject;
      img.src = url;
    });
  }

  static calculateDimensions(originalWidth, originalHeight, maxWidth, maxHeight) {
    let width = originalWidth;
    let height = originalHeight;

    // Calculate aspect ratio
    const aspectRatio = originalWidth / originalHeight;

    // Resize to fit within max dimensions
    if (width > maxWidth) {
      width = maxWidth;
      height = width / aspectRatio;
    }

    if (height > maxHeight) {
      height = maxHeight;
      width = height * aspectRatio;
    }

    return {
      width: Math.round(width),
      height: Math.round(height)
    };
  }

  static async blobToDataUrl(blob) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onloadend = () => resolve(reader.result);
      reader.onerror = reject;
      reader.readAsDataURL(blob);
    });
  }

  static getMimeTypeIcon(mimeType) {
    if (!mimeType) return '📄';
    
    if (mimeType.startsWith('image/')) return '🖼️';
    if (mimeType.startsWith('video/')) return '🎬';
    if (mimeType.startsWith('audio/')) return '🎵';
    if (mimeType.includes('pdf')) return '📑';
    if (mimeType.includes('zip') || mimeType.includes('rar')) return '📦';
    if (mimeType.includes('word') || mimeType.includes('document')) return '📝';
    if (mimeType.includes('sheet') || mimeType.includes('excel')) return '📊';
    if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) return '📈';
    if (mimeType.includes('text')) return '📄';
    
    return '📄';
  }
}

export default ThumbnailService;