<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import Icon from '@/Components/App/Icon.vue';
import ProgressRing from '@/Components/App/ProgressRing.vue';
import FileCard from '@/Components/App/FileCard.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    totalUploads: Number,
    storageUsed: Number,
    recentRequests: Array,
    activeRequests: Number,
    monthlyStats: Object,
});

const storageLimit = 5 * 1024 * 1024 * 1024; // 5GB
const storagePercentage = computed(() => {
    return Math.min(100, (props.storageUsed / storageLimit) * 100);
});

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (date) => {
    const d = new Date(date);
    const now = new Date();
    const diff = now - d;
    
    if (diff < 60000) return 'Just now';
    if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
    if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;
    if (diff < 604800000) return `${Math.floor(diff / 86400000)}d ago`;
    
    return d.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const statsCards = computed(() => [
    {
        title: 'Total Uploads',
        value: props.totalUploads,
        icon: 'upload',
        gradient: 'from-primary-start to-primary-end',
        change: '+12%',
        trend: 'up'
    },
    {
        title: 'Active Shares',
        value: props.activeRequests,
        icon: 'share2',
        gradient: 'from-secondary-start to-secondary-end',
        change: '+5%',
        trend: 'up'
    },
    {
        title: 'This Month',
        value: props.monthlyStats?.uploads || 0,
        icon: 'calendar',
        gradient: 'from-green-400 to-green-600',
        change: '+23%',
        trend: 'up'
    },
    {
        title: 'Total Downloads',
        value: props.monthlyStats?.downloads || 0,
        icon: 'download',
        gradient: 'from-amber-400 to-amber-600',
        change: '+18%',
        trend: 'up'
    }
]);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="space-y-8">
            <!-- Welcome Section -->
            <div class="text-center py-8 animate-fade-in">
                <h1 class="text-4xl font-display font-bold gradient-text mb-2">
                    Welcome back, {{ $page.props.auth.user.name }}!
                </h1>
                <p class="text-text-secondary">
                    Here's what's happening with your QuickDrop today
                </p>
            </div>

            <!-- Quick Actions -->
            <div class="flex justify-center space-x-4 mb-8">
                <Button
                    variant="primary"
                    size="lg"
                    icon="upload"
                    as="Link"
                    :href="route('quick-drops.create')"
                    class="animate-pulse-glow"
                >
                    Create New Drop
                </Button>
                <Button
                    variant="outline"
                    size="lg"
                    icon="folder"
                    as="Link"
                    :href="route('quick-drops.index')"
                >
                    View All Drops
                </Button>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card
                    v-for="(stat, index) in statsCards"
                    :key="stat.title"
                    class="group hover:scale-105 transition-transform duration-300"
                    :data-index="index"
                    :style="{ animationDelay: `${index * 100}ms` }"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-text-secondary mb-1">{{ stat.title }}</p>
                            <p class="text-3xl font-bold text-text-primary">{{ stat.value }}</p>
                            <div class="flex items-center mt-2">
                                <Icon 
                                    :name="stat.trend === 'up' ? 'trendingUp' : 'trendingDown'"
                                    :size="16"
                                    :class="stat.trend === 'up' ? 'text-green-400' : 'text-red-400'"
                                />
                                <span 
                                    class="text-xs ml-1"
                                    :class="stat.trend === 'up' ? 'text-green-400' : 'text-red-400'"
                                >
                                    {{ stat.change }}
                                </span>
                            </div>
                        </div>
                        <div 
                            class="w-12 h-12 rounded-xl bg-gradient-to-br flex items-center justify-center transform group-hover:rotate-12 transition-transform"
                            :class="stat.gradient"
                        >
                            <Icon :name="stat.icon" :size="24" class="text-white" />
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Storage Overview -->
            <Card>
                <template #header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-text-primary">Storage Overview</h3>
                        <Button variant="ghost" size="sm" icon="hardDrive">
                            Manage Storage
                        </Button>
                    </div>
                </template>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="flex items-center justify-center">
                        <ProgressRing 
                            :value="storagePercentage"
                            :size="160"
                            :stroke-width="12"
                            label="Used"
                        />
                    </div>
                    
                    <div class="lg:col-span-2 space-y-4">
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-sm text-text-secondary">Storage Used</span>
                                <span class="text-sm font-medium text-text-primary">
                                    {{ formatBytes(storageUsed) }} / {{ formatBytes(storageLimit) }}
                                </span>
                            </div>
                            <div class="w-full bg-white/10 rounded-full h-2 overflow-hidden">
                                <div 
                                    class="h-full bg-gradient-primary transition-all duration-500"
                                    :style="{ width: `${storagePercentage}%` }"
                                />
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <p class="text-2xl font-bold gradient-text">{{ totalUploads }}</p>
                                <p class="text-xs text-text-secondary">Total Files</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold gradient-text">{{ activeRequests }}</p>
                                <p class="text-xs text-text-secondary">Active Shares</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold gradient-text">{{ formatBytes(storageUsed) }}</p>
                                <p class="text-xs text-text-secondary">Used Space</p>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Recent Activity -->
            <Card>
                <template #header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-text-primary">Recent Activity</h3>
                        <Link 
                            :href="route('quick-drops.index')" 
                            class="text-sm text-primary hover:text-primary-end transition-colors"
                        >
                            View all →
                        </Link>
                    </div>
                </template>

                <div v-if="recentRequests.length > 0" class="space-y-3">
                    <div 
                        v-for="(request, index) in recentRequests.slice(0, 5)" 
                        :key="request.id"
                        class="p-4 rounded-xl bg-surface hover:bg-surface-hover transition-all cursor-pointer group"
                        :data-index="index"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div 
                                    class="w-10 h-10 rounded-lg bg-gradient-primary flex items-center justify-center transform group-hover:scale-110 transition-transform"
                                >
                                    <Icon name="folder" :size="20" class="text-white" />
                                </div>
                                <div>
                                    <h4 class="font-medium text-text-primary group-hover:text-white transition-colors">
                                        {{ request.title }}
                                    </h4>
                                    <div class="flex items-center space-x-3 text-xs text-text-secondary">
                                        <span>{{ request.files_count }} files</span>
                                        <span>•</span>
                                        <span>{{ formatDate(request.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-3">
                                <span 
                                    class="px-3 py-1 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-green-500/20 text-green-400': request.status === 'active',
                                        'bg-amber-500/20 text-amber-400': request.status === 'pending',
                                        'bg-red-500/20 text-red-400': request.status === 'expired'
                                    }"
                                >
                                    {{ request.status }}
                                </span>
                                <Icon 
                                    name="chevronRight" 
                                    :size="20" 
                                    class="text-text-secondary group-hover:text-text-primary transition-colors"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-surface flex items-center justify-center">
                        <Icon name="inbox" :size="32" class="text-text-muted" />
                    </div>
                    <p class="text-text-secondary mb-4">No recent activity</p>
                    <Button
                        variant="primary"
                        size="sm"
                        icon="plus"
                        as="Link"
                        :href="route('quick-drops.create')"
                    >
                        Create your first drop
                    </Button>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
[data-index] {
    animation: slideUp 0.5s ease-out forwards;
    opacity: 0;
}

@keyframes slideUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
    from {
        opacity: 0;
        transform: translateY(20px);
    }
}
</style>
