<template>
    <Link :href="href" 
          class="admin-sidebar-item flex items-center space-x-3"
          :class="{ 'active': active }">
        <slot name="icon" />
        <span class="text-sm font-medium"><slot /></span>
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    href: String,
    active: Boolean
})
</script>