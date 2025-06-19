<template>
    <div 
        class="glass-card"
        :class="[
            hoverable && 'cursor-pointer',
            noPadding && '!p-0',
            className
        ]"
        v-bind="$attrs"
    >
        <div v-if="$slots.header" class="border-b border-gray-200/20 px-6 py-4 -m-6 mb-6">
            <slot name="header" />
        </div>
        
        <slot />
        
        <div v-if="$slots.footer" class="border-t border-gray-200/20 px-6 py-4 -m-6 mt-6">
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    hoverable: {
        type: Boolean,
        default: false
    },
    noPadding: {
        type: Boolean,
        default: false
    },
    className: {
        type: String,
        default: ''
    }
})
</script>