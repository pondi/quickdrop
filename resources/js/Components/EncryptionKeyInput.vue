<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    isPublic: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'verify']);

const localValue = ref(props.modelValue);

watch(() => props.modelValue, (newValue) => {
    localValue.value = newValue;
});

watch(localValue, (newValue) => {
    emit('update:modelValue', newValue);
});
</script>

<template>
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Encryption Key
        </label>
        <input
            v-model="localValue"
            type="password"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600"
            :placeholder="isPublic ? 'Enter encryption key to upload files' : 'Enter encryption key to download files'"
            @blur="emit('verify', localValue)"
        />
        <p v-if="isPublic" class="mt-2 text-sm text-gray-500">
            This QuickDrop box requires end-to-end encryption. Please enter the encryption key provided by the box owner.
        </p>
    </div>
</template> 