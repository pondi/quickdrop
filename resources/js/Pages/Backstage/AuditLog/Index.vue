<template>
    <BackstageLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <span>Audit Log</span>
                <div class="flex items-center space-x-2">
                    <button
                        @click="exportLogs"
                        class="btn-secondary text-sm"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </button>
                </div>
            </div>
        </template>
        
        <!-- Filters -->
        <div class="admin-card p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Filters</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Search
                    </label>
                    <input
                        v-model="filters.search"
                        @input="debouncedSearch"
                        type="text"
                        placeholder="Search logs..."
                        class="form-input"
                    />
                </div>
                
                <!-- Event Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Event Type
                    </label>
                    <select v-model="filters.event_type" @change="applyFilters" class="form-select">
                        <option value="">All Types</option>
                        <option v-for="type in eventTypes" :key="type.value" :value="type.value">
                            {{ type.label }}
                        </option>
                    </select>
                </div>
                
                <!-- Event Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Category
                    </label>
                    <select v-model="filters.event_category" @change="applyFilters" class="form-select">
                        <option value="">All Categories</option>
                        <option v-for="category in eventCategories" :key="category.value" :value="category.value">
                            {{ category.label }}
                        </option>
                    </select>
                </div>
                
                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Date Range
                    </label>
                    <div class="flex space-x-2">
                        <input
                            v-model="filters.date_from"
                            @change="applyFilters"
                            type="date"
                            class="form-input flex-1"
                        />
                        <span class="text-gray-500 self-center">to</span>
                        <input
                            v-model="filters.date_to"
                            @change="applyFilters"
                            type="date"
                            class="form-input flex-1"
                        />
                    </div>
                </div>
            </div>
            
            <div class="mt-4 flex justify-end">
                <button @click="clearFilters" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                    Clear Filters
                </button>
            </div>
        </div>
        
        <!-- Logs Table -->
        <div class="admin-card">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Event
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Description
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                IP Address
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Time
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-if="logs.data.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                No audit logs found.
                            </td>
                        </tr>
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="getEventBadgeClass(log.color)"
                                    >
                                        {{ log.event_type }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ log.description }}
                                </div>
                                <div v-if="log.url" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ log.method }} {{ log.url }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ log.user_name }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ log.user_type }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ log.ip_address }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ formatDate(log.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div v-if="logs.last_page > 1" class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                <Pagination :links="logs.links" />
            </div>
        </div>
    </BackstageLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import BackstageLayout from '@/Layouts/BackstageLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { format } from 'date-fns'
import debounce from 'lodash/debounce'

const props = defineProps({
    logs: Object,
    filters: Object,
    eventTypes: Array,
    eventCategories: Array,
})

const filters = ref({
    search: props.filters.search || '',
    event_type: props.filters.event_type || '',
    event_category: props.filters.event_category || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
})

const applyFilters = () => {
    router.get(route('backstage.audit-log.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    })
}

const debouncedSearch = debounce(() => {
    applyFilters()
}, 300)

const clearFilters = () => {
    filters.value = {
        search: '',
        event_type: '',
        event_category: '',
        date_from: '',
        date_to: '',
    }
    applyFilters()
}

const exportLogs = () => {
    const params = new URLSearchParams(filters.value)
    window.location.href = route('backstage.audit-log.export') + '?' + params.toString()
}

const formatDate = (date) => {
    return format(new Date(date), 'MMM d, yyyy h:mm a')
}

const getEventBadgeClass = (color) => {
    const classes = {
        success: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        info: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        danger: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        primary: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        secondary: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    }
    return classes[color] || classes.secondary
}
</script>