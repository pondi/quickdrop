<template>
    <div class="progress-ring relative inline-flex items-center justify-center">
        <svg 
            :width="size" 
            :height="size" 
            class="transform"
        >
            <defs>
                <linearGradient :id="`gradient-${uid}`" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" :style="`stop-color:${gradientStart};stop-opacity:1`" />
                    <stop offset="100%" :style="`stop-color:${gradientEnd};stop-opacity:1`" />
                </linearGradient>
            </defs>
            
            <circle
                :cx="center"
                :cy="center"
                :r="radius"
                :stroke-width="strokeWidth"
                fill="none"
                stroke="rgba(255, 255, 255, 0.1)"
            />
            
            <circle
                class="progress-ring__circle"
                :cx="center"
                :cy="center"
                :r="radius"
                :stroke-width="strokeWidth"
                fill="none"
                :stroke="`url(#gradient-${uid})`"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="dashOffset"
            />
        </svg>
        
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center">
                <div class="text-2xl font-bold text-text-primary">
                    {{ displayValue }}
                </div>
                <div v-if="label" class="text-xs text-text-secondary mt-1">
                    {{ label }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
    value: {
        type: Number,
        required: true,
        validator: (value) => value >= 0 && value <= 100
    },
    size: {
        type: Number,
        default: 120
    },
    strokeWidth: {
        type: Number,
        default: 8
    },
    label: {
        type: String,
        default: null
    },
    showPercentage: {
        type: Boolean,
        default: true
    },
    gradientStart: {
        type: String,
        default: '#667eea'
    },
    gradientEnd: {
        type: String,
        default: '#764ba2'
    }
})

const uid = ref(Math.random().toString(36).substr(2, 9))

const center = computed(() => props.size / 2)
const radius = computed(() => (props.size - props.strokeWidth) / 2)
const circumference = computed(() => 2 * Math.PI * radius.value)
const dashOffset = computed(() => {
    const offset = circumference.value - (props.value / 100) * circumference.value
    return Math.max(0, Math.min(circumference.value, offset))
})

const displayValue = computed(() => {
    if (props.showPercentage) {
        return `${Math.round(props.value)}%`
    }
    return Math.round(props.value)
})
</script>