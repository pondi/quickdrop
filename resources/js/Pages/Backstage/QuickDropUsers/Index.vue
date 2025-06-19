<template>
    <BackstageLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">QuickDrop Users</h1>
                <div class="flex gap-4">
                    <Link
                        :href="route('backstage.quickdrop-users.export')"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        Export CSV
                    </Link>
                    <Link
                        :href="route('backstage.quickdrop-users.create')"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700"
                    >
                        Add User
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card class="p-6">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Users</p>
                    <p class="text-3xl font-semibold text-gray-900 dark:text-white">{{ stats.total_users }}</p>
                </Card>
                <Card class="p-6">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Users</p>
                    <p class="text-3xl font-semibold text-gray-900 dark:text-white">{{ stats.active_users }}</p>
                </Card>
                <Card class="p-6">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Verified Users</p>
                    <p class="text-3xl font-semibold text-gray-900 dark:text-white">{{ stats.verified_users }}</p>
                </Card>
                <Card class="p-6">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">New This Month</p>
                    <p class="text-3xl font-semibold text-gray-900 dark:text-white">{{ stats.users_this_month }}</p>
                </Card>
            </div>

            <!-- Filters -->
            <Card class="p-4">
                <form @submit.prevent="applyFilters" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="filterForm.search"
                            type="text"
                            placeholder="Search by name or email..."
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        />
                    </div>
                    <select
                        v-model="filterForm.status"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <select
                        v-model="filterForm.verified"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">All Verification</option>
                        <option value="yes">Verified</option>
                        <option value="no">Unverified</option>
                    </select>
                    <Button type="submit">Filter</Button>
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        type="button"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
                    >
                        Clear
                    </button>
                </form>
            </Card>

            <!-- Users Table -->
            <Card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    User
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Storage
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Activity
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Joined
                                </th>
                                <th class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="user in users.data" :key="user.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ user.name }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ user.email }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span v-if="user.is_active" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Active
                                        </span>
                                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            Inactive
                                        </span>
                                        <span v-if="user.email_verified_at" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            Verified
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <div>
                                        <div class="text-sm">
                                            {{ formatBytes(user.storage_used) }} / {{ formatBytes(user.storage_limit) }}
                                        </div>
                                        <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                                            <div
                                                class="bg-primary-600 h-2 rounded-full"
                                                :style="`width: ${user.storage_used_percentage}%`"
                                            ></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div>
                                        <div>{{ user.upload_requests_count }} requests</div>
                                        <div>{{ user.upload_objects_count }} files</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        :href="route('backstage.quickdrop-users.show', user.id)"
                                        class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                                    >
                                        View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.links.length > 3" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <Pagination :links="users.links" />
                </div>
            </Card>
        </div>
    </BackstageLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import BackstageLayout from '@/Layouts/BackstageLayout.vue';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    users: Object,
    filters: Object,
    stats: Object,
});

const filterForm = useForm({
    search: props.filters.search || '',
    status: props.filters.status || '',
    verified: props.filters.verified || '',
});

const hasActiveFilters = computed(() => {
    return filterForm.search || filterForm.status || filterForm.verified;
});

const applyFilters = () => {
    router.get(route('backstage.quickdrop-users.index'), {
        search: filterForm.search || undefined,
        status: filterForm.status || undefined,
        verified: filterForm.verified || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filterForm.search = '';
    filterForm.status = '';
    filterForm.verified = '';
    applyFilters();
};

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString();
};
</script>