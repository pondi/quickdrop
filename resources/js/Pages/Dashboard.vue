<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    totalUploads: Number,
    storageUsed: Number,
    recentRequests: Array,
    activeRequests: Number,
    monthlyStats: Object,
});

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <!-- Total Uploads -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600">Total Uploads</div>
                        <div class="text-2xl font-semibold">{{ totalUploads }}</div>
                    </div>
                    
                    <!-- Storage Used -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600">Storage Used</div>
                        <div class="text-2xl font-semibold">{{ formatBytes(storageUsed) }}</div>
                    </div>
                    
                    <!-- Active Requests -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600">Active Requests</div>
                        <div class="text-2xl font-semibold">{{ activeRequests }}</div>
                    </div>
                    
                    <!-- Monthly Activity -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600">This Month</div>
                        <div class="text-2xl font-semibold">{{ monthlyStats?.uploads || 0 }}</div>
                    </div>
                </div>

                <!-- Recent Upload Requests -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Recent Upload Requests</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-2">Title</th>
                                        <th class="text-left py-2">Created</th>
                                        <th class="text-left py-2">Status</th>
                                        <th class="text-left py-2">Files</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="request in recentRequests" :key="request.id" class="border-b">
                                        <td class="py-2">{{ request.title }}</td>
                                        <td class="py-2">{{ formatDate(request.created_at) }}</td>
                                        <td class="py-2">
                                            <span :class="{
                                                'px-2 py-1 rounded text-sm': true,
                                                'bg-green-100 text-green-800': request.status === 'active',
                                                'bg-yellow-100 text-yellow-800': request.status === 'pending',
                                                'bg-red-100 text-red-800': request.status === 'expired'
                                            }">
                                                {{ request.status }}
                                            </span>
                                        </td>
                                        <td class="py-2">{{ request.files_count }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
