<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { 
    CheckCircleIcon, 
    ExclamationTriangleIcon, 
    InformationCircleIcon,
    XCircleIcon
} from '@heroicons/vue/24/outline';
import { XMarkIcon } from '@heroicons/vue/20/solid';

const toasts = ref([]);
let toastId = 0;

const showToast = (event) => {
    const toast = {
        id: ++toastId,
        message: event.detail.message,
        detail: event.detail.detail || '',
        type: event.detail.type || 'success',
        duration: event.detail.duration || 5000,
        progress: 100
    };
    
    toasts.value.push(toast);
    
    // Start progress animation
    const interval = setInterval(() => {
        toast.progress -= (100 / (toast.duration / 50));
        if (toast.progress <= 0) {
            clearInterval(interval);
            removeToast(toast.id);
        }
    }, 50);
};

const removeToast = (id) => {
    const index = toasts.value.findIndex(toast => toast.id === id);
    if (index > -1) {
        toasts.value.splice(index, 1);
    }
};

const getIcon = (type) => {
    const icons = {
        success: CheckCircleIcon,
        error: XCircleIcon,
        warning: ExclamationTriangleIcon,
        info: InformationCircleIcon
    };
    return icons[type] || icons.info;
};

const getStyles = (type) => {
    const styles = {
        success: {
            icon: 'text-emerald-400',
            border: 'border-emerald-500/20',
            progress: 'bg-gradient-to-r from-emerald-400 to-emerald-500'
        },
        error: {
            icon: 'text-red-400',
            border: 'border-red-500/20',
            progress: 'bg-gradient-to-r from-red-400 to-red-500'
        },
        warning: {
            icon: 'text-amber-400',
            border: 'border-amber-500/20',
            progress: 'bg-gradient-to-r from-amber-400 to-amber-500'
        },
        info: {
            icon: 'text-blue-400',
            border: 'border-blue-500/20',
            progress: 'bg-gradient-to-r from-blue-400 to-blue-500'
        }
    };
    return styles[type] || styles.info;
};

onMounted(() => {
    window.addEventListener('show-toast', showToast);
});

onUnmounted(() => {
    window.removeEventListener('show-toast', showToast);
});
</script>

<template>
    <div 
        aria-live="polite" 
        class="fixed top-4 right-4 z-50 flex flex-col gap-3 pointer-events-none max-w-sm w-full"
    >
        <TransitionGroup
            enter-active-class="transform transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-x-full scale-95"
            enter-to-class="opacity-100 translate-x-0 scale-100"
            leave-active-class="transform transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-x-0 scale-100"
            leave-to-class="opacity-0 -translate-x-full scale-95"
            move-class="transition-transform duration-200"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto relative overflow-hidden"
            >
                <!-- Glass effect container -->
                <div 
                    class="relative backdrop-blur-xl bg-white/10 dark:bg-white/5 rounded-2xl border border-white/20 dark:border-white/10 shadow-2xl"
                    :class="getStyles(toast.type).border"
                >
                    <!-- Content -->
                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            <!-- Icon -->
                            <div class="flex-shrink-0 mt-0.5">
                                <component 
                                    :is="getIcon(toast.type)" 
                                    class="h-5 w-5" 
                                    :class="getStyles(toast.type).icon"
                                    aria-hidden="true" 
                                />
                            </div>
                            
                            <!-- Message content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-white">
                                    {{ toast.message }}
                                </p>
                                <p 
                                    v-if="toast.detail" 
                                    class="mt-1 text-xs text-white/70 leading-relaxed"
                                >
                                    {{ toast.detail }}
                                </p>
                            </div>
                            
                            <!-- Close button -->
                            <button
                                type="button"
                                @click="removeToast(toast.id)"
                                class="flex-shrink-0 p-1 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-white/30"
                            >
                                <span class="sr-only">Close</span>
                                <XMarkIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    
                    <!-- Progress bar -->
                    <div class="absolute bottom-0 left-0 right-0 h-1 bg-white/10">
                        <div 
                            class="h-full transition-all duration-75 ease-linear rounded-full"
                            :class="getStyles(toast.type).progress"
                            :style="{ width: `${toast.progress}%` }"
                        />
                    </div>
                    
                    <!-- Glow effect -->
                    <div 
                        class="absolute inset-0 rounded-2xl opacity-50 blur-xl -z-10"
                        :class="getStyles(toast.type).progress"
                    />
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>