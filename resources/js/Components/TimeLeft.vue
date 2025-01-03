<script setup>
import { computed } from 'vue';
import { formatDistanceToNow } from 'date-fns';

const props = defineProps({
    expiresAt: {
        type: String,
        required: true,
    },
    isExpired: {
        type: Boolean,
        required: true,
    },
});

const timeLeft = computed(() => {
    if (props.isExpired) {
        return 'Expired';
    }
    return formatDistanceToNow(new Date(props.expiresAt), { addSuffix: true });
});
</script>

<template>
    <p class="text-sm text-gray-500">Expires {{ timeLeft }}</p>
</template> 