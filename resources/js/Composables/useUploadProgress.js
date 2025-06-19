import { ref, computed } from 'vue'

export function useUploadProgress() {
  const uploadingFiles = ref([])
  let fileIdCounter = 0

  // Add a file to the upload queue
  function addFile(file) {
    const uploadFile = {
      id: ++fileIdCounter,
      name: file.name,
      size: file.size,
      type: file.type,
      progress: 0,
      status: 'pending', // pending, uploading, completed, error
      speed: 0,
      timeRemaining: null,
      startTime: null,
      loaded: 0,
      error: null,
      file: file // Keep reference to original file
    }
    
    uploadingFiles.value.push(uploadFile)
    return uploadFile.id
  }

  // Update file progress
  function updateProgress(fileId, loaded, total) {
    const file = uploadingFiles.value.find(f => f.id === fileId)
    if (!file) return

    const now = Date.now()
    
    if (file.status !== 'uploading') {
      file.status = 'uploading'
      file.startTime = now
    }

    // Calculate progress percentage
    file.progress = Math.round((loaded / total) * 100)
    file.loaded = loaded

    // Calculate upload speed
    if (file.startTime) {
      const elapsed = (now - file.startTime) / 1000 // seconds
      if (elapsed > 0) {
        file.speed = loaded / elapsed // bytes per second
        
        // Calculate time remaining
        const remaining = total - loaded
        if (file.speed > 0) {
          file.timeRemaining = remaining / file.speed // seconds
        }
      }
    }

    // Mark as completed when done
    if (file.progress >= 100) {
      file.status = 'completed'
      file.speed = 0
      file.timeRemaining = null
    }
  }

  // Mark file as completed
  function markCompleted(fileId) {
    const file = uploadingFiles.value.find(f => f.id === fileId)
    if (file) {
      file.status = 'completed'
      file.progress = 100
      file.speed = 0
      file.timeRemaining = null
    }
  }

  // Mark file as error
  function markError(fileId, error) {
    const file = uploadingFiles.value.find(f => f.id === fileId)
    if (file) {
      file.status = 'error'
      file.error = error
      file.speed = 0
      file.timeRemaining = null
    }
  }

  // Remove a file from the queue
  function removeFile(fileId) {
    const index = uploadingFiles.value.findIndex(f => f.id === fileId)
    if (index !== -1) {
      uploadingFiles.value.splice(index, 1)
    }
  }

  // Reset file for retry
  function resetFile(fileId) {
    const file = uploadingFiles.value.find(f => f.id === fileId)
    if (file) {
      file.status = 'pending'
      file.progress = 0
      file.speed = 0
      file.timeRemaining = null
      file.startTime = null
      file.loaded = 0
      file.error = null
    }
  }

  // Clear all completed files
  function clearCompleted() {
    uploadingFiles.value = uploadingFiles.value.filter(f => f.status !== 'completed')
  }

  // Clear all files
  function clearAll() {
    uploadingFiles.value = []
  }

  // Computed properties
  const hasActiveUploads = computed(() => {
    return uploadingFiles.value.some(f => f.status === 'uploading')
  })

  const completedCount = computed(() => {
    return uploadingFiles.value.filter(f => f.status === 'completed').length
  })

  const errorCount = computed(() => {
    return uploadingFiles.value.filter(f => f.status === 'error').length
  })

  const totalProgress = computed(() => {
    if (uploadingFiles.value.length === 0) return 0
    const sum = uploadingFiles.value.reduce((total, file) => total + file.progress, 0)
    return Math.round(sum / uploadingFiles.value.length)
  })

  return {
    uploadingFiles,
    addFile,
    updateProgress,
    markCompleted,
    markError,
    removeFile,
    resetFile,
    clearCompleted,
    clearAll,
    hasActiveUploads,
    completedCount,
    errorCount,
    totalProgress
  }
}