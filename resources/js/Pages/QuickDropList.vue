<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { formatDistanceToNow, format } from 'date-fns';

const props = defineProps({
    uploadRequests: {
        type: Array,
        required: true
    }
});

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        alert('Copied to clipboard!');
    } catch (err) {
        alert('Failed to copy to clipboard');
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="My QuickDrop Boxes" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-semibold">My QuickDrop Boxes</h2>
                            <Link
                                :href="route('quickdrop.create')"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Create New Box
                            </Link>
                        </div>

                        <div v-if="uploadRequests.length === 0" class="text-center py-12">
                            <p class="text-gray-500 dark:text-gray-400">You haven't created any QuickDrop boxes yet.</p>
                            <Link
                                :href="route('quickdrop.create')"
                                class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Create Your First Box
                            </Link>
                        </div>

                        <div v-else class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-3">
                            <div
                                v-for="box in uploadRequests"
                                :key="box.id"
                                class="flex flex-col p-4 bg-white dark:bg-gray-700 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600"
                            >
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ box.title }}</h3>
                                    <div class="flex items-center">
                                        <span
                                            :class="{
                                                'bg-green-100 text-green-800': box.is_active,
                                                'bg-red-100 text-red-800': !box.is_active,
                                            }"
                                            class="px-2 py-1 text-xs font-medium rounded-full"
                                        >
                                            {{ box.is_active ? 'Active' : 'Expired' }}
                                        </span>
                                        <span v-if="box.is_encrypted" class="ml-2 px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                            Encrypted
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-300 mb-1">
                                        <span>Created:</span>
                                        <span>{{ format(new Date(box.created_at), 'MMM d, yyyy') }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-300 mb-1">
                                        <span>Expires:</span>
                                        <span>{{ formatDistanceToNow(new Date(box.expires_at), { addSuffix: true }) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-300 mb-1">
                                        <span>Files:</span>
                                        <span>{{ box.files_count }} files ({{ formatBytes(box.total_size) }})</span>
                                    </div>
                                    <div v-if="box.reference_number" class="flex justify-between text-sm text-gray-600 dark:text-gray-300 mb-1">
                                        <span>Reference:</span>
                                        <span>{{ box.reference_number }}</span>
                                    </div>
                                    <div v-if="box.comment" class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                        <p class="whitespace-pre-wrap">{{ box.comment }}</p>
                                    </div>
                                </div>

                                <div class="mt-auto">
                                    <div class="flex flex-col space-y-2">
                                        <Link
                                            :href="box.upload_url"
                                            class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            View Box
                                        </Link>
                                        <button
                                            @click="copyToClipboard(box.upload_url)"
                                            class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-200 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            Copy Link
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 