<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import { 
    ChartBarIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    ShareIcon,
    ClockIcon,
    GlobeAltIcon,
    DevicePhoneMobileIcon,
    ComputerDesktopIcon
} from '@heroicons/vue/24/outline';

// Mock analytics data - would come from backend
const analyticsData = ref({
    overview: {
        totalShares: 247,
        totalViews: 1834,
        totalDownloads: 456,
        avgResponseTime: '1.2s',
        changes: {
            shares: +12.5,
            views: +8.3,
            downloads: -2.1,
            responseTime: -5.2
        }
    },
    chartData: {
        downloads: [
            { date: '2024-01-08', value: 45 },
            { date: '2024-01-09', value: 52 },
            { date: '2024-01-10', value: 38 },
            { date: '2024-01-11', value: 67 },
            { date: '2024-01-12', value: 59 },
            { date: '2024-01-13', value: 73 },
            { date: '2024-01-14', value: 81 },
            { date: '2024-01-15', value: 94 }
        ],
        views: [
            { date: '2024-01-08', value: 156 },
            { date: '2024-01-09', value: 189 },
            { date: '2024-01-10', value: 145 },
            { date: '2024-01-11', value: 234 },
            { date: '2024-01-12', value: 198 },
            { date: '2024-01-13', value: 267 },
            { date: '2024-01-14', value: 298 },
            { date: '2024-01-15', value: 347 }
        ]
    },
    topFiles: [
        { name: 'Q4 Report.pdf', downloads: 89, views: 234, type: 'document' },
        { name: 'Product Demo.mp4', downloads: 67, views: 456, type: 'video' },
        { name: 'Brand Assets.zip', downloads: 45, views: 123, type: 'archive' },
        { name: 'Meeting Notes.docx', downloads: 34, views: 98, type: 'document' },
        { name: 'Screenshots.zip', downloads: 23, views: 67, type: 'images' }
    ],
    devices: {
        desktop: 65,
        mobile: 28,
        tablet: 7
    },
    locations: [
        { country: 'United States', visits: 45, percentage: 32 },
        { country: 'United Kingdom', visits: 28, percentage: 20 },
        { country: 'Germany', visits: 19, percentage: 13.5 },
        { country: 'Canada', visits: 15, percentage: 10.7 },
        { country: 'France', visits: 12, percentage: 8.5 },
        { country: 'Others', visits: 22, percentage: 15.3 }
    ]
});

const selectedTimeRange = ref('7d');
const timeRanges = [
    { value: '7d', label: '7 Days' },
    { value: '30d', label: '30 Days' },
    { value: '90d', label: '3 Months' },
    { value: '1y', label: '1 Year' }
];

const maxDownloads = computed(() => {
    return Math.max(...analyticsData.value.chartData.downloads.map(d => d.value));
});

const maxViews = computed(() => {
    return Math.max(...analyticsData.value.chartData.views.map(d => d.value));
});

const getChangeColor = (change) => {
    return change >= 0 ? 'text-emerald-400' : 'text-red-400';
};

const getChangeIcon = (change) => {
    return change >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon;
};

const formatChange = (change) => {
    const sign = change >= 0 ? '+' : '';
    return `${sign}${change.toFixed(1)}%`;
};

const getDeviceIcon = (device) => {
    const icons = {
        desktop: ComputerDesktopIcon,
        mobile: DevicePhoneMobileIcon,
        tablet: DevicePhoneMobileIcon
    };
    return icons[device] || ComputerDesktopIcon;
};
</script>

<template>
    <Head title="Analytics" />

    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center">
                            <ChartBarIcon class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-display font-bold text-white">Analytics</h1>
                            <p class="text-white/60">Insights into your file sharing performance</p>
                        </div>
                    </div>
                </div>

                <!-- Time Range Selector -->
                <div class="flex gap-2 p-1 bg-white/5 rounded-xl border border-white/10">
                    <button
                        v-for="range in timeRanges"
                        :key="range.value"
                        @click="selectedTimeRange = range.value"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                        :class="selectedTimeRange === range.value 
                            ? 'bg-white/10 text-white shadow-lg' 
                            : 'text-white/60 hover:text-white hover:bg-white/5'"
                    >
                        {{ range.label }}
                    </button>
                </div>
            </div>

            <!-- Overview Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Shares -->
                <Card class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center">
                            <ShareIcon class="w-6 h-6 text-blue-400" />
                        </div>
                        <div class="flex items-center gap-1 text-sm" :class="getChangeColor(analyticsData.overview.changes.shares)">
                            <component :is="getChangeIcon(analyticsData.overview.changes.shares)" class="w-4 h-4" />
                            <span>{{ formatChange(analyticsData.overview.changes.shares) }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white mb-1">{{ analyticsData.overview.totalShares }}</div>
                        <div class="text-white/60 text-sm">Total Shares</div>
                    </div>
                </Card>

                <!-- Total Views -->
                <Card class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center">
                            <EyeIcon class="w-6 h-6 text-purple-400" />
                        </div>
                        <div class="flex items-center gap-1 text-sm" :class="getChangeColor(analyticsData.overview.changes.views)">
                            <component :is="getChangeIcon(analyticsData.overview.changes.views)" class="w-4 h-4" />
                            <span>{{ formatChange(analyticsData.overview.changes.views) }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white mb-1">{{ analyticsData.overview.totalViews.toLocaleString() }}</div>
                        <div class="text-white/60 text-sm">Total Views</div>
                    </div>
                </Card>

                <!-- Total Downloads -->
                <Card class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center">
                            <ArrowDownTrayIcon class="w-6 h-6 text-emerald-400" />
                        </div>
                        <div class="flex items-center gap-1 text-sm" :class="getChangeColor(analyticsData.overview.changes.downloads)">
                            <component :is="getChangeIcon(analyticsData.overview.changes.downloads)" class="w-4 h-4" />
                            <span>{{ formatChange(analyticsData.overview.changes.downloads) }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white mb-1">{{ analyticsData.overview.totalDownloads }}</div>
                        <div class="text-white/60 text-sm">Total Downloads</div>
                    </div>
                </Card>

                <!-- Avg Response Time -->
                <Card class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-orange-500/20 flex items-center justify-center">
                            <ClockIcon class="w-6 h-6 text-orange-400" />
                        </div>
                        <div class="flex items-center gap-1 text-sm" :class="getChangeColor(analyticsData.overview.changes.responseTime)">
                            <component :is="getChangeIcon(analyticsData.overview.changes.responseTime)" class="w-4 h-4" />
                            <span>{{ formatChange(Math.abs(analyticsData.overview.changes.responseTime)) }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-white mb-1">{{ analyticsData.overview.avgResponseTime }}</div>
                        <div class="text-white/60 text-sm">Avg Response Time</div>
                    </div>
                </Card>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Downloads Chart -->
                <Card class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-white">Downloads Over Time</h3>
                        <div class="text-emerald-400 text-sm font-medium">
                            Total: {{ analyticsData.chartData.downloads.reduce((sum, d) => sum + d.value, 0) }}
                        </div>
                    </div>

                    <!-- Simple Bar Chart -->
                    <div class="space-y-3">
                        <div 
                            v-for="(item, index) in analyticsData.chartData.downloads" 
                            :key="index"
                            class="flex items-center gap-4"
                        >
                            <div class="text-white/60 text-sm w-16 text-right">
                                {{ new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                            </div>
                            <div class="flex-1 relative">
                                <div class="h-8 bg-white/5 rounded-lg overflow-hidden">
                                    <div 
                                        class="h-full bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-lg transition-all duration-1000 ease-out"
                                        :style="{ width: `${(item.value / maxDownloads) * 100}%` }"
                                    />
                                </div>
                                <div class="absolute inset-y-0 right-2 flex items-center">
                                    <span class="text-white text-sm font-medium">{{ item.value }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Views Chart -->
                <Card class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-white">Views Over Time</h3>
                        <div class="text-purple-400 text-sm font-medium">
                            Total: {{ analyticsData.chartData.views.reduce((sum, d) => sum + d.value, 0) }}
                        </div>
                    </div>

                    <!-- Simple Bar Chart -->
                    <div class="space-y-3">
                        <div 
                            v-for="(item, index) in analyticsData.chartData.views" 
                            :key="index"
                            class="flex items-center gap-4"
                        >
                            <div class="text-white/60 text-sm w-16 text-right">
                                {{ new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
                            </div>
                            <div class="flex-1 relative">
                                <div class="h-8 bg-white/5 rounded-lg overflow-hidden">
                                    <div 
                                        class="h-full bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg transition-all duration-1000 ease-out"
                                        :style="{ width: `${(item.value / maxViews) * 100}%` }"
                                    />
                                </div>
                                <div class="absolute inset-y-0 right-2 flex items-center">
                                    <span class="text-white text-sm font-medium">{{ item.value }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Bottom Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Top Files -->
                <div class="lg:col-span-2">
                    <Card class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-white">Top Performing Files</h3>
                            <Button variant="ghost" size="sm">View All</Button>
                        </div>

                        <div class="space-y-4">
                            <div 
                                v-for="(file, index) in analyticsData.topFiles" 
                                :key="index"
                                class="flex items-center gap-4 p-4 rounded-xl bg-white/5 hover:bg-white/10 transition-colors duration-200"
                            >
                                <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center text-white/70">
                                    {{ index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-white truncate">{{ file.name }}</div>
                                    <div class="flex items-center gap-4 text-sm text-white/60 mt-1">
                                        <div class="flex items-center gap-1">
                                            <EyeIcon class="w-4 h-4" />
                                            <span>{{ file.views }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <ArrowDownTrayIcon class="w-4 h-4" />
                                            <span>{{ file.downloads }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-white">
                                        {{ Math.round((file.downloads / file.views) * 100) }}%
                                    </div>
                                    <div class="text-xs text-white/60">conversion</div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Device & Location Stats -->
                <div class="space-y-6">
                    <!-- Devices -->
                    <Card class="p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Device Types</h3>
                        <div class="space-y-4">
                            <div 
                                v-for="(percentage, device) in analyticsData.devices" 
                                :key="device"
                                class="flex items-center gap-3"
                            >
                                <component :is="getDeviceIcon(device)" class="w-5 h-5 text-white/60" />
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-white/80 capitalize">{{ device }}</span>
                                        <span class="text-white text-sm font-medium">{{ percentage }}%</span>
                                    </div>
                                    <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                                        <div 
                                            class="h-full bg-gradient-to-r from-blue-500 to-purple-500 rounded-full transition-all duration-1000"
                                            :style="{ width: `${percentage}%` }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>

                    <!-- Top Locations -->
                    <Card class="p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Top Locations</h3>
                        <div class="space-y-3">
                            <div 
                                v-for="location in analyticsData.locations" 
                                :key="location.country"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <GlobeAltIcon class="w-4 h-4 text-white/60" />
                                    <span class="text-white/80 text-sm">{{ location.country }}</span>
                                </div>
                                <div class="text-right">
                                    <div class="text-white text-sm font-medium">{{ location.visits }}</div>
                                    <div class="text-white/60 text-xs">{{ location.percentage }}%</div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>