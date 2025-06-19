<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'card', 'list', 'table', 'form', 'dashboard', 'file-grid'].includes(value)
    },
    count: {
        type: Number,
        default: 3
    },
    showText: {
        type: Boolean,
        default: true
    },
    text: {
        type: String,
        default: 'Loading...'
    }
});

const skeletonItems = computed(() => {
    return Array.from({ length: props.count }, (_, index) => index);
});
</script>

<template>
    <div class="space-y-4 animate-pulse">
        <!-- Loading text -->
        <div v-if="showText" class="text-center mb-8">
            <div class="inline-flex items-center gap-3 px-4 py-2 rounded-xl bg-white/5 border border-white/10">
                <!-- Spinner -->
                <div class="relative">
                    <div class="w-4 h-4 rounded-full border-2 border-white/20 border-t-white/80 animate-spin" />
                </div>
                <span class="text-sm text-white/70">{{ text }}</span>
            </div>
        </div>
        
        <!-- Default skeleton -->
        <template v-if="type === 'default'">
            <div v-for="i in skeletonItems" :key="i" class="glass-card p-6">
                <div class="space-y-3">
                    <div class="h-4 bg-white/10 rounded-lg w-3/4 shimmer" />
                    <div class="h-3 bg-white/5 rounded-lg w-1/2 shimmer" />
                    <div class="h-3 bg-white/5 rounded-lg w-5/6 shimmer" />
                </div>
            </div>
        </template>
        
        <!-- Card skeleton -->
        <template v-else-if="type === 'card'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="i in skeletonItems" :key="i" class="glass-card p-6">
                    <div class="space-y-4">
                        <!-- Header -->
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-white/10 rounded-xl shimmer" />
                            <div class="flex-1 space-y-2">
                                <div class="h-4 bg-white/10 rounded-lg w-3/4 shimmer" />
                                <div class="h-3 bg-white/5 rounded-lg w-1/2 shimmer" />
                            </div>
                        </div>
                        <!-- Content -->
                        <div class="space-y-2">
                            <div class="h-3 bg-white/5 rounded-lg shimmer" />
                            <div class="h-3 bg-white/5 rounded-lg w-4/5 shimmer" />
                        </div>
                        <!-- Footer -->
                        <div class="flex justify-between items-center pt-2">
                            <div class="h-6 bg-white/5 rounded-lg w-16 shimmer" />
                            <div class="h-8 bg-white/10 rounded-lg w-20 shimmer" />
                        </div>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- List skeleton -->
        <template v-else-if="type === 'list'">
            <div class="glass-card divide-y divide-white/10">
                <div v-for="i in skeletonItems" :key="i" class="p-4">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-white/10 rounded-xl shimmer" />
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-white/10 rounded-lg w-1/3 shimmer" />
                            <div class="h-3 bg-white/5 rounded-lg w-1/2 shimmer" />
                        </div>
                        <div class="flex gap-2">
                            <div class="h-8 w-8 bg-white/5 rounded-lg shimmer" />
                            <div class="h-8 w-8 bg-white/5 rounded-lg shimmer" />
                        </div>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- Table skeleton -->
        <template v-else-if="type === 'table'">
            <div class="glass-card overflow-hidden">
                <!-- Table header -->
                <div class="px-6 py-4 border-b border-white/10">
                    <div class="flex gap-8">
                        <div class="h-4 bg-white/10 rounded-lg w-24 shimmer" />
                        <div class="h-4 bg-white/10 rounded-lg w-32 shimmer" />
                        <div class="h-4 bg-white/10 rounded-lg w-20 shimmer" />
                        <div class="h-4 bg-white/10 rounded-lg w-28 shimmer" />
                    </div>
                </div>
                <!-- Table rows -->
                <div class="divide-y divide-white/5">
                    <div v-for="i in skeletonItems" :key="i" class="px-6 py-4">
                        <div class="flex gap-8 items-center">
                            <div class="h-3 bg-white/5 rounded-lg w-24 shimmer" />
                            <div class="h-3 bg-white/5 rounded-lg w-32 shimmer" />
                            <div class="h-3 bg-white/5 rounded-lg w-20 shimmer" />
                            <div class="h-3 bg-white/5 rounded-lg w-28 shimmer" />
                        </div>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- Form skeleton -->
        <template v-else-if="type === 'form'">
            <div class="glass-card p-6">
                <div class="space-y-6">
                    <div v-for="i in skeletonItems" :key="i" class="space-y-2">
                        <div class="h-4 bg-white/10 rounded-lg w-24 shimmer" />
                        <div class="h-12 bg-white/5 rounded-xl shimmer" />
                    </div>
                    <div class="flex gap-3 pt-4">
                        <div class="h-10 bg-white/10 rounded-xl w-24 shimmer" />
                        <div class="h-10 bg-white/5 rounded-xl w-20 shimmer" />
                    </div>
                </div>
            </div>
        </template>
        
        <!-- Dashboard skeleton -->
        <template v-else-if="type === 'dashboard'">
            <!-- Stats cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div v-for="i in 3" :key="i" class="glass-card p-6">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="h-4 bg-white/10 rounded-lg w-20 shimmer" />
                            <div class="w-8 h-8 bg-white/5 rounded-lg shimmer" />
                        </div>
                        <div class="h-8 bg-white/10 rounded-lg w-16 shimmer" />
                        <div class="h-3 bg-white/5 rounded-lg w-24 shimmer" />
                    </div>
                </div>
            </div>
            
            <!-- Chart area -->
            <div class="glass-card p-6 mb-6">
                <div class="space-y-4">
                    <div class="h-6 bg-white/10 rounded-lg w-32 shimmer" />
                    <div class="h-64 bg-white/5 rounded-xl shimmer" />
                </div>
            </div>
            
            <!-- Recent activity -->
            <div class="glass-card p-6">
                <div class="space-y-4">
                    <div class="h-5 bg-white/10 rounded-lg w-28 shimmer" />
                    <div class="space-y-3">
                        <div v-for="i in 4" :key="i" class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-white/5 rounded-lg shimmer" />
                            <div class="flex-1 space-y-1">
                                <div class="h-3 bg-white/5 rounded-lg w-3/4 shimmer" />
                                <div class="h-2 bg-white/5 rounded-lg w-1/2 shimmer" />
                            </div>
                            <div class="h-6 bg-white/5 rounded-lg w-12 shimmer" />
                        </div>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- File grid skeleton -->
        <template v-else-if="type === 'file-grid'">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div v-for="i in skeletonItems" :key="i" class="glass-card p-4">
                    <div class="space-y-3">
                        <!-- File icon/preview -->
                        <div class="aspect-square bg-white/5 rounded-xl shimmer" />
                        <!-- File name -->
                        <div class="h-4 bg-white/10 rounded-lg shimmer" />
                        <!-- File details -->
                        <div class="flex justify-between items-center">
                            <div class="h-3 bg-white/5 rounded-lg w-16 shimmer" />
                            <div class="h-3 bg-white/5 rounded-lg w-12 shimmer" />
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
/* Shimmer animation */
.shimmer {
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

/* Pulse animation for the entire container */
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}
</style>