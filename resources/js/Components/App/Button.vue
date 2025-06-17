<template>
    <component
        :is="as"
        :type="type"
        :disabled="disabled || loading"
        class="relative inline-flex items-center justify-center font-medium transition-all duration-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-transparent disabled:opacity-50 disabled:cursor-not-allowed"
        :class="[variantClasses, sizeClasses, className]"
        v-bind="$attrs"
    >
        <span v-if="loading" class="absolute inset-0 flex items-center justify-center">
            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </span>
        
        <span class="flex items-center space-x-2" :class="{ 'opacity-0': loading }">
            <Icon v-if="icon" :name="icon" :size="iconSize" />
            <span v-if="$slots.default"><slot /></span>
        </span>
    </component>
</template>

<script setup>
import { computed } from 'vue'
import Icon from './Icon.vue'

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'ghost', 'outline', 'danger'].includes(value)
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
    },
    as: {
        type: String,
        default: 'button'
    },
    type: {
        type: String,
        default: 'button'
    },
    disabled: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    },
    icon: {
        type: String,
        default: null
    },
    className: {
        type: String,
        default: ''
    }
})

const variantClasses = computed(() => {
    const variants = {
        primary: 'gradient-button text-white focus:ring-primary',
        secondary: 'bg-gradient-secondary text-white hover:opacity-90 focus:ring-secondary rounded-xl',
        ghost: 'bg-transparent text-text-primary hover:bg-surface-hover focus:ring-white/20 rounded-xl',
        outline: 'bg-transparent border border-glass text-text-primary hover:bg-surface-hover focus:ring-white/20 rounded-xl',
        danger: 'bg-red-500 text-white hover:bg-red-600 focus:ring-red-500 rounded-xl'
    }
    return variants[props.variant]
})

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'px-3 py-1.5 text-sm',
        md: 'px-4 py-2 text-base',
        lg: 'px-6 py-3 text-lg',
        xl: 'px-8 py-4 text-xl'
    }
    return sizes[props.size]
})

const iconSize = computed(() => {
    const sizes = {
        sm: 16,
        md: 20,
        lg: 24,
        xl: 28
    }
    return sizes[props.size]
})
</script>