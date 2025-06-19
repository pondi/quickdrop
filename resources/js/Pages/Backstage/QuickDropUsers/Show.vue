<template>
    <BackstageLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ user.name }}</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ user.email }}</p>
                </div>
                <div class="flex gap-4">
                    <Link
                        :href="route('backstage.quickdrop-users.edit', user.id)"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        Edit
                    </Link>
                    <Link
                        :href="route('backstage.quickdrop-users.index')"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
                    >
                        ← Back to list
                    </Link>
                </div>
            </div>

            <!-- User Status and Actions -->
            <Card class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Status & Actions</h2>
                </div>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center gap-2">
                        <span v-if="user.is_active" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Active
                        </span>
                        <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            Inactive
                        </span>
                        <span v-if="user.email_verified_at" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            Email Verified
                        </span>
                        <span v-else class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            Email Unverified
                        </span>
                    </div>
                    <div class="flex gap-2 ml-auto">
                        <button
                            @click="toggleStatus"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white rounded-md"
                            :class="user.is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'"
                        >
                            {{ user.is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button
                            @click="resetStorage"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-700"
                        >
                            Reset Storage
                        </button>
                    </div>
                </div>
            </Card>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card class="p-6">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Uploads</p>
                    <p class="text-3xl font-semibold text-gray-900 dark:text-white">{{ stats.total_uploads }}</p>
                </Card>
                <Card class="p-6">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Requests</p>
                    <p class="text-3xl font-semibold text-gray-900 dark:text-white">{{ stats.total_requests }}</p>
                </Card>
                <Card class="p-6">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Requests</p>
                    <p class="text-3xl font-semibold text-gray-900 dark:text-white">{{ stats.active_requests }}</p>
                </Card>
                <Card class="p-6">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Last Login</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ stats.last_login ? formatDateTime(stats.last_login) : 'Never' }}
                    </p>
                </Card>
            </div>

            <!-- Storage Usage -->
            <Card class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Storage Usage</h2>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Used</span>
                        <span class="font-medium text-gray-900 dark:text-white">
                            {{ formatBytes(stats.storage_used) }} / {{ formatBytes(stats.storage_limit) }}
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div
                            class="bg-primary-600 h-3 rounded-full transition-all duration-300"
                            :style="`width: ${stats.storage_percentage}%`"
                        ></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ stats.storage_percentage }}% of storage limit used
                    </p>
                </div>
            </Card>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Requests -->
                <Card class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Recent Requests</h2>
                    <div v-if="recentActivity.requests.length > 0" class="space-y-3">
                        <div
                            v-for="request in recentActivity.requests"
                            :key="request.id"
                            class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-0"
                        >
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ request.title || 'Untitled' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDateTime(request.created_at) }}
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                :class="request.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'"
                            >
                                {{ request.status }}
                            </span>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400">No recent requests</p>
                </Card>

                <!-- Recent Uploads -->
                <Card class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Recent Uploads</h2>
                    <div v-if="recentActivity.uploads.length > 0" class="space-y-3">
                        <div
                            v-for="upload in recentActivity.uploads"
                            :key="upload.id"
                            class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-0"
                        >
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">
                                    {{ upload.original_name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatBytes(upload.file_size) }} • {{ formatDateTime(upload.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400">No recent uploads</p>
                </Card>
            </div>

            <!-- Delete User -->
            <Card class="p-6 border-red-200 dark:border-red-800">
                <h2 class="text-lg font-medium text-red-600 dark:text-red-400 mb-4">Danger Zone</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Deleting this user will permanently remove all their data, including uploads and requests.
                </p>
                <button
                    @click="confirmDelete"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700"
                >
                    Delete User
                </button>
            </Card>
        </div>

        <!-- Confirmation Dialog -->
        <ConfirmationDialog
            :show="showDeleteConfirm"
            title="Delete User"
            message="Are you sure you want to delete this user? This action cannot be undone."
            confirmText="Delete"
            confirmClass="bg-red-600 hover:bg-red-700"
            @confirm="deleteUser"
            @cancel="showDeleteConfirm = false"
        />
    </BackstageLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import BackstageLayout from '@/Layouts/BackstageLayout.vue';
import Card from '@/Components/App/Card.vue';
import ConfirmationDialog from '@/Components/ConfirmationDialog.vue';

const props = defineProps({
    user: Object,
    stats: Object,
    recentActivity: Object,
});

const showDeleteConfirm = ref(false);

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDateTime = (dateString) => {
    return new Date(dateString).toLocaleString();
};

const toggleStatus = () => {
    router.post(route('backstage.quickdrop-users.toggle-status', props.user.id));
};

const resetStorage = () => {
    if (confirm('Are you sure you want to reset this user\'s storage count to 0?')) {
        router.post(route('backstage.quickdrop-users.reset-storage', props.user.id));
    }
};

const confirmDelete = () => {
    showDeleteConfirm.value = true;
};

const deleteUser = () => {
    router.delete(route('backstage.quickdrop-users.destroy', props.user.id));
};
</script>