<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { 
    CheckCircleIcon, 
    ExclamationTriangleIcon, 
    InformationCircleIcon 
} from '@heroicons/vue/24/outline';
import { XMarkIcon } from '@heroicons/vue/20/solid';

const show = ref(false);
const message = ref('');
const detail = ref('');
const type = ref('success'); // 'success', 'error', 'info'

const icons = {
    success: CheckCircleIcon,
    error: ExclamationTriangleIcon,
    info: InformationCircleIcon
};

const colors = {
    success: 'text-green-400',
    error: 'text-red-400',
    info: 'text-blue-400'
};

const showNotification = (event) => {
    message.value = event.detail.message;
    detail.value = event.detail.detail || '';
    type.value = event.detail.type || 'success';
    show.value = true;
    setTimeout(() => {
        show.value = false;
    }, 5000);
};

onMounted(() => {
    window.addEventListener('show-notification', showNotification);
});

onUnmounted(() => {
    window.removeEventListener('show-notification', showNotification);
});
</script>

<template>
    <div aria-live="assertive" class="pointer-events-none absolute top-0 right-0 flex items-start p-4 z-50">
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="show" class="pointer-events-auto w-full sm:w-[28rem] overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black/5 dark:ring-white/10">
                    <div class="p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <component :is="icons[type]" class="h-6 w-6" :class="colors[type]" aria-hidden="true" />
                            </div>
                            <div class="ml-3 flex-1 pt-0.5">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ message }}</p>
                                <p v-if="detail" class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ detail }}</p>
                            </div>
                            <div class="ml-4 flex-shrink-0">
                                <button
                                    type="button"
                                    @click="show = false"
                                    class="inline-flex rounded-md bg-white dark:bg-gray-800 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:ring-offset-gray-800"
                                >
                                    <span class="sr-only">Close</span>
                                    <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template> 