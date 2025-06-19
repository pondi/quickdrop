<template>
    <AppLayout title="Storage Analytics">
        <div class="px-4 sm:px-6 lg:px-8 py-8">
            <div class="max-w-7xl mx-auto">
                <div class="md:flex md:items-center md:justify-between mb-8">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
                            Storage Analytics
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Detailed breakdown of storage usage and trends
                        </p>
                    </div>
                    <div class="mt-4 flex md:mt-0 md:ml-4">
                        <select v-model="selectedPeriod" @change="fetchHistoricalData"
                            class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="90">Last 90 days</option>
                        </select>
                    </div>
                </div>

                <!-- Current Stats Cards -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10a2 2 0 012 2v9a2 2 0 01-2 2H7a2 2 0 01-2-2V9a2 2 0 012-2zM7 7V5a2 2 0 012-2h6a2 2 0 012 2v2" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                            Total Storage
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ formatBytes(overview.current.total_storage_used) }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
                            <div class="text-sm">
                                <span class="font-medium" :class="overview.growth_rate.storage >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ overview.growth_rate.storage >= 0 ? '+' : '' }}{{ overview.growth_rate.storage }}%
                                </span>
                                <span class="text-gray-500 dark:text-gray-400"> from last period</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                            Total Files
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ overview.current.total_files.toLocaleString() }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
                            <div class="text-sm">
                                <span class="font-medium" :class="overview.growth_rate.files >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ overview.growth_rate.files >= 0 ? '+' : '' }}{{ overview.growth_rate.files }}%
                                </span>
                                <span class="text-gray-500 dark:text-gray-400"> from last period</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                            Active QuickDrops
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ overview.current.active_quickdrops.toLocaleString() }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
                            <div class="text-sm">
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ overview.current.expired_quickdrops.toLocaleString() }} expired
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                            Active Storage
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ formatBytes(overview.current.active_storage_used) }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
                            <div class="text-sm">
                                <span class="text-gray-500 dark:text-gray-400">
                                    {{ Math.round((overview.current.active_storage_used / overview.current.total_storage_used) * 100) }}% of total
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Storage Trend Chart -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                            Storage Trend
                        </h3>
                        <div class="h-64">
                            <canvas ref="storageTrendChart"></canvas>
                        </div>
                    </div>

                    <!-- File Type Breakdown -->
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                            Storage by File Type
                        </h3>
                        <div class="h-64">
                            <canvas ref="fileTypeChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Users Table -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                            Top Storage Users
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Storage Used
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        QuickDrops
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Files
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="user in overview.top_users" :key="user.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ user.name }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ user.email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ formatBytes(user.storage_used) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ user.quickdrop_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ user.file_count }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    overview: Object,
});

const selectedPeriod = ref(30);
const storageTrendChart = ref(null);
const fileTypeChart = ref(null);
let storageTrendChartInstance = null;
let fileTypeChartInstance = null;

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const createStorageTrendChart = () => {
    if (storageTrendChartInstance) {
        storageTrendChartInstance.destroy();
    }

    const ctx = storageTrendChart.value.getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');
    
    const labels = props.overview.historical.map(item => 
        new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    );
    
    storageTrendChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Storage',
                data: props.overview.historical.map(item => item.total_storage_used),
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.3,
            }, {
                label: 'Active Storage',
                data: props.overview.historical.map(item => item.active_storage_used),
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: isDark ? '#e5e7eb' : '#374151',
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: function(value) {
                            return formatBytes(value);
                        },
                        color: isDark ? '#9ca3af' : '#6b7280',
                    },
                    grid: {
                        color: isDark ? '#374151' : '#e5e7eb',
                    }
                },
                x: {
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280',
                    },
                    grid: {
                        display: false,
                    }
                }
            }
        }
    });
};

const createFileTypeChart = () => {
    if (fileTypeChartInstance) {
        fileTypeChartInstance.destroy();
    }

    const ctx = fileTypeChart.value.getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');
    
    const fileTypes = Object.keys(props.overview.file_types);
    const sizes = fileTypes.map(type => props.overview.file_types[type].size);
    
    fileTypeChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: fileTypes.map(type => type.toUpperCase()),
            datasets: [{
                data: sizes,
                backgroundColor: [
                    'rgb(99, 102, 241)',
                    'rgb(34, 197, 94)',
                    'rgb(251, 146, 60)',
                    'rgb(163, 230, 53)',
                    'rgb(249, 115, 22)',
                    'rgb(139, 92, 246)',
                    'rgb(236, 72, 153)',
                    'rgb(14, 165, 233)',
                ],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: isDark ? '#e5e7eb' : '#374151',
                        padding: 10,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const type = context.label;
                            const percentage = props.overview.file_types[type.toLowerCase()].percentage;
                            return `${type}: ${formatBytes(context.parsed)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
};

const fetchHistoricalData = async () => {
    try {
        const response = await fetch(`/api/storage-analytics/historical?days=${selectedPeriod.value}`);
        const data = await response.json();
        props.overview.historical = data.data;
        await nextTick();
        createStorageTrendChart();
    } catch (error) {
        console.error('Failed to fetch historical data:', error);
    }
};

onMounted(() => {
    createStorageTrendChart();
    createFileTypeChart();
});

watch(() => document.documentElement.classList.contains('dark'), () => {
    createStorageTrendChart();
    createFileTypeChart();
});
</script>