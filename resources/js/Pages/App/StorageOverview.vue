<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import ProgressRing from '@/Components/App/ProgressRing.vue';
import { 
    CloudIcon,
    FolderIcon,
    DocumentIcon,
    PhotoIcon,
    FilmIcon,
    MusicalNoteIcon,
    ArchiveBoxIcon,
    ChartBarIcon,
    ArrowTrendingUpIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline';

// Mock data - would come from backend in real app
const storageData = ref({
    used: 2.4, // GB
    total: 10, // GB
    files: {
        documents: { size: 0.8, count: 45, color: 'from-blue-500 to-blue-600' },
        images: { size: 1.2, count: 128, color: 'from-green-500 to-green-600' },
        videos: { size: 0.3, count: 12, color: 'from-red-500 to-red-600' },
        audio: { size: 0.05, count: 8, color: 'from-yellow-500 to-yellow-600' },
        archives: { size: 0.05, count: 3, color: 'from-purple-500 to-purple-600' }
    },
    recentActivity: [
        { action: 'uploaded', file: 'presentation.pdf', size: '2.4 MB', time: '2 minutes ago' },
        { action: 'shared', file: 'photos.zip', size: '45.2 MB', time: '1 hour ago' },
        { action: 'deleted', file: 'old_backup.tar', size: '120 MB', time: '3 hours ago' }
    ]
});

const usagePercentage = computed(() => {
    return Math.round((storageData.value.used / storageData.value.total) * 100);
});

const fileTypeIcons = {
    documents: DocumentIcon,
    images: PhotoIcon,
    videos: FilmIcon,
    audio: MusicalNoteIcon,
    archives: ArchiveBoxIcon
};

const animatedValues = ref({});

// Animate storage ring on mount
onMounted(() => {
    setTimeout(() => {
        animatedValues.value.storage = usagePercentage.value;
        Object.keys(storageData.value.files).forEach(type => {
            animatedValues.value[type] = (storageData.value.files[type].size / storageData.value.total) * 100;
        });
    }, 500);
});

const formatFileSize = (gb) => {
    if (gb >= 1) return `${gb.toFixed(1)} GB`;
    return `${(gb * 1024).toFixed(0)} MB`;
};
</script>

<template>
    <Head title="Storage Overview" />

    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-8">
            <!-- Header -->
            <div class="text-center space-y-4">
                <div class="relative inline-block">
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center shadow-2xl shadow-blue-500/25">
                        <CloudIcon class="w-10 h-10 text-white" />
                    </div>
                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-500 opacity-30 blur-xl" />
                </div>
                <div>
                    <h1 class="text-3xl font-display font-bold text-white mb-2">Storage Overview</h1>
                    <p class="text-white/60">Visualize and manage your file storage usage</p>
                </div>
            </div>

            <!-- Storage Overview Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Storage Visualization -->
                <div class="lg:col-span-2">
                    <Card class="p-8">
                        <div class="text-center space-y-8">
                            <div>
                                <h2 class="text-xl font-semibold text-white mb-2">Storage Usage</h2>
                                <p class="text-white/60 text-sm">Your current storage consumption</p>
                            </div>

                            <!-- 3D Storage Ring -->
                            <div class="relative">
                                <div class="relative w-64 h-64 mx-auto">
                                    <!-- Main storage ring with 3D effect -->
                                    <div class="absolute inset-0 rounded-full">
                                        <ProgressRing 
                                            :percentage="animatedValues.storage || 0"
                                            :size="256"
                                            :stroke-width="20"
                                            class="transform rotate-[-90deg]"
                                        />
                                    </div>
                                    
                                    <!-- Inner content -->
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <div class="text-center">
                                            <div class="text-4xl font-bold text-white mb-1">
                                                {{ usagePercentage }}%
                                            </div>
                                            <div class="text-white/60 text-sm">
                                                {{ formatFileSize(storageData.used) }} of {{ formatFileSize(storageData.total) }}
                                            </div>
                                            <div class="text-white/40 text-xs mt-1">
                                                {{ formatFileSize(storageData.total - storageData.used) }} available
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Floating storage indicators -->
                                    <div class="absolute -top-4 -right-4 w-8 h-8 rounded-full bg-gradient-to-r from-green-400 to-emerald-500 animate-pulse opacity-80" />
                                    <div class="absolute -bottom-6 -left-6 w-6 h-6 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 animate-bounce opacity-60" style="animation-delay: 0.5s" />
                                    <div class="absolute top-1/4 -left-8 w-4 h-4 rounded-full bg-gradient-to-r from-pink-400 to-rose-500 animate-pulse opacity-70" style="animation-delay: 1s" />
                                </div>
                            </div>

                            <!-- Storage Status -->
                            <div class="flex items-center justify-center gap-6">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-emerald-400 mb-1">
                                        {{ Object.values(storageData.files).reduce((acc, file) => acc + file.count, 0) }}
                                    </div>
                                    <div class="text-white/60 text-sm">Total Files</div>
                                </div>
                                <div class="w-px h-8 bg-white/10" />
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-blue-400 mb-1">
                                        {{ Object.keys(storageData.files).length }}
                                    </div>
                                    <div class="text-white/60 text-sm">File Types</div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Storage Actions -->
                <div class="space-y-6">
                    <Card class="p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <Button class="w-full justify-center" variant="gradient">
                                <ArrowTrendingUpIcon class="w-4 h-4 mr-2" />
                                Upgrade Storage
                            </Button>
                            <Button class="w-full justify-center" variant="ghost">
                                <ArrowPathIcon class="w-4 h-4 mr-2" />
                                Clean Up Files
                            </Button>
                            <Button class="w-full justify-center" variant="ghost">
                                <ChartBarIcon class="w-4 h-4 mr-2" />
                                View Analytics
                            </Button>
                        </div>
                    </Card>

                    <!-- Storage Tips -->
                    <Card class="p-6">
                        <h3 class="text-lg font-semibold text-white mb-4">Storage Tips</h3>
                        <div class="space-y-3 text-sm text-white/70">
                            <div class="flex items-start gap-3">
                                <div class="w-2 h-2 rounded-full bg-green-400 mt-2 flex-shrink-0" />
                                <p>Delete files older than 30 days to free up space</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-2 h-2 rounded-full bg-blue-400 mt-2 flex-shrink-0" />
                                <p>Compress large files before uploading</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <div class="w-2 h-2 rounded-full bg-purple-400 mt-2 flex-shrink-0" />
                                <p>Use shorter expiry times for temporary shares</p>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- File Type Breakdown -->
            <Card class="p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h2 class="text-xl font-semibold text-white mb-2">File Type Breakdown</h2>
                        <p class="text-white/60 text-sm">See how your storage is distributed across file types</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <div
                        v-for="(data, type) in storageData.files"
                        :key="type"
                        class="group relative"
                    >
                        <div class="relative p-6 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all duration-300 cursor-pointer">
                            <!-- File type icon -->
                            <div class="flex items-center justify-center w-12 h-12 rounded-xl mb-4 mx-auto"
                                 :class="`bg-gradient-to-br ${data.color}`">
                                <component :is="fileTypeIcons[type]" class="w-6 h-6 text-white" />
                            </div>

                            <!-- File type info -->
                            <div class="text-center space-y-2">
                                <h3 class="font-medium text-white capitalize">{{ type }}</h3>
                                <div class="space-y-1">
                                    <div class="text-xl font-bold text-white">{{ formatFileSize(data.size) }}</div>
                                    <div class="text-white/60 text-sm">{{ data.count }} files</div>
                                </div>
                            </div>

                            <!-- Mini progress bar -->
                            <div class="mt-4 w-full h-2 bg-white/10 rounded-full overflow-hidden">
                                <div 
                                    class="h-full transition-all duration-1000 ease-out rounded-full"
                                    :class="`bg-gradient-to-r ${data.color}`"
                                    :style="{ width: `${animatedValues[type] || 0}%` }"
                                />
                            </div>
                            <div class="text-center mt-2">
                                <span class="text-xs text-white/50">
                                    {{ Math.round((data.size / storageData.total) * 100) }}% of total
                                </span>
                            </div>

                            <!-- Hover glow effect -->
                            <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-30 transition-opacity duration-300"
                                 :class="`bg-gradient-to-br ${data.color} blur-xl`" />
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Recent Activity -->
            <Card class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-semibold text-white mb-2">Recent Storage Activity</h2>
                        <p class="text-white/60 text-sm">Track your latest file operations</p>
                    </div>
                    <Button variant="ghost" size="sm">
                        View All
                    </Button>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(activity, index) in storageData.recentActivity"
                        :key="index"
                        class="flex items-center gap-4 p-4 rounded-xl bg-white/5 hover:bg-white/10 transition-colors duration-200"
                    >
                        <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                            <FolderIcon class="w-5 h-5 text-white/70" />
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-white font-medium">{{ activity.action }}</span>
                                <span class="text-white/60">{{ activity.file }}</span>
                            </div>
                            <div class="text-white/50 text-sm">{{ activity.size }} • {{ activity.time }}</div>
                        </div>
                        <div class="w-3 h-3 rounded-full" 
                             :class="{
                                 'bg-green-400': activity.action === 'uploaded',
                                 'bg-blue-400': activity.action === 'shared',
                                 'bg-red-400': activity.action === 'deleted'
                             }" />
                    </div>
                </div>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(5deg); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-float-delayed {
    animation: float 6s ease-in-out infinite;
    animation-delay: 2s;
}
</style>