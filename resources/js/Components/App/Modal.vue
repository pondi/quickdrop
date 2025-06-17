<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div 
                        class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                        @click="closeable && close()"
                    />
                    
                    <Transition
                        enter-active-class="transition ease-out duration-300"
                        enter-from-class="opacity-0 transform scale-95"
                        enter-to-class="opacity-100 transform scale-100"
                        leave-active-class="transition ease-in duration-200"
                        leave-from-class="opacity-100 transform scale-100"
                        leave-to-class="opacity-0 transform scale-95"
                    >
                        <div 
                            v-if="show"
                            class="glass-card relative z-10 w-full transform transition-all"
                            :class="sizeClass"
                        >
                            <div v-if="showHeader" class="flex items-center justify-between mb-6">
                                <h3 class="text-xl font-semibold text-text-primary">
                                    <slot name="title">{{ title }}</slot>
                                </h3>
                                <button
                                    v-if="closeable"
                                    @click="close"
                                    class="p-2 rounded-lg hover:bg-surface-hover transition-colors"
                                >
                                    <Icon name="x" :size="20" class="text-text-secondary" />
                                </button>
                            </div>
                            
                            <div class="text-text-secondary">
                                <slot />
                            </div>
                            
                            <div v-if="$slots.footer" class="mt-6 flex justify-end space-x-3">
                                <slot name="footer" />
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, watch, onMounted, onUnmounted } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    title: {
        type: String,
        default: null
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg', 'xl', 'full'].includes(value)
    },
    closeable: {
        type: Boolean,
        default: true
    },
    showHeader: {
        type: Boolean,
        default: true
    }
})

const emit = defineEmits(['close'])

const sizeClass = computed(() => {
    const sizes = {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        full: 'max-w-full mx-4'
    }
    return sizes[props.size]
})

const close = () => {
    if (props.closeable) {
        emit('close')
    }
}

const handleEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        close()
    }
}

watch(() => props.show, (newValue) => {
    if (newValue) {
        document.body.style.overflow = 'hidden'
        document.addEventListener('keydown', handleEscape)
    } else {
        document.body.style.overflow = ''
        document.removeEventListener('keydown', handleEscape)
    }
})

onUnmounted(() => {
    document.body.style.overflow = ''
    document.removeEventListener('keydown', handleEscape)
})
</script>