<template>
    <component 
        :is="icon" 
        :size="size"
        :stroke-width="strokeWidth"
        class="lucide-icon"
        :class="[sizeClass, colorClass, className]"
    />
</template>

<script setup>
import { computed } from 'vue'
import * as icons from 'lucide-vue-next'

const props = defineProps({
    name: {
        type: String,
        required: true
    },
    size: {
        type: [Number, String],
        default: 24
    },
    strokeWidth: {
        type: [Number, String],
        default: 2
    },
    variant: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'primary', 'secondary', 'success', 'warning', 'error', 'muted'].includes(value)
    },
    className: {
        type: String,
        default: ''
    }
})

const icon = computed(() => {
    const iconName = props.name.charAt(0).toUpperCase() + props.name.slice(1)
    return icons[iconName] || icons.HelpCircle
})

const sizeClass = computed(() => {
    const sizeMap = {
        16: 'w-4 h-4',
        20: 'w-5 h-5',
        24: 'w-6 h-6',
        32: 'w-8 h-8',
        48: 'w-12 h-12'
    }
    return sizeMap[props.size] || ''
})

const colorClass = computed(() => {
    const colorMap = {
        default: 'text-current',
        primary: 'text-primary',
        secondary: 'text-secondary',
        success: 'text-green-500',
        warning: 'text-amber-500',
        error: 'text-red-500',
        muted: 'text-text-muted'
    }
    return colorMap[props.variant] || 'text-current'
})
</script>