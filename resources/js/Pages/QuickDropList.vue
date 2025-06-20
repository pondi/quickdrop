<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import Icon from '@/Components/App/Icon.vue';
import Modal from '@/Components/App/Modal.vue';
import PullToRefresh from '@/Components/App/PullToRefresh.vue';
import SwipeableQuickDropCard from '@/Components/App/SwipeableQuickDropCard.vue';
import ShareLinkModal from '@/Components/ShareLinkModal.vue';
import { useTransitionClasses } from '@/Composables/useAnimations';
import axios from 'axios';

const props = defineProps({
    uploadRequests: {
        type: Array,
        required: true
    }
});

const { fadeSlide } = useTransitionClasses();
// const searchQuery = ref(''); // REMOVED: FEAT-034
const filterStatus = ref('all');
const showShareModal = ref(false);
const selectedBox = ref(null);
const copySuccess = ref(false);
const shareUrl = ref('');
const shareData = ref(null);
const loadingShare = ref(false);

const filteredRequests = computed(() => {
    let filtered = props.uploadRequests;
    
    // REMOVED: FEAT-034 - Search filtering
    
    if (filterStatus.value !== 'all') {
        filtered = filtered.filter(box => {
            if (filterStatus.value === 'active') return box.is_active;
            if (filterStatus.value === 'expired') return !box.is_active;
            if (filterStatus.value === 'encrypted') return box.is_encrypted;
        });
    }
    
    return filtered;
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
    
    return d.toLocaleDateString();
};

const timeUntilExpiry = (expiryDate) => {
    const now = new Date();
    const expiry = new Date(expiryDate);
    const diff = expiry - now;
    
    if (diff <= 0) return 'Expired';
    if (diff < 3600000) return `${Math.floor(diff / 60000)}m left`;
    if (diff < 86400000) return `${Math.floor(diff / 3600000)}h left`;
    return `${Math.floor(diff / 86400000)}d left`;
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        copySuccess.value = true;
        setTimeout(() => copySuccess.value = false, 2000);
    } catch (err) {
    }
};

const shareBox = async (box) => {
    selectedBox.value = box;
    loadingShare.value = true;
    
    try {
        const response = await axios.get(route('quickdrop.share-link', box.id));
        shareUrl.value = response.data.share_url;
        shareData.value = response.data;
        showShareModal.value = true;
    } catch (error) {
        // Fallback to using the existing upload_url
        shareUrl.value = box.upload_url;
        shareData.value = {
            title: box.title,
            expires_at: box.expires_at,
            is_encrypted: box.is_encrypted
        };
        showShareModal.value = true;
    } finally {
        loadingShare.value = false;
    }
};

const deleteBox = (box) => {
    // Delete functionality not yet implemented
    // Delete functionality not yet implemented
};

const handleRefresh = async () => {
    // Reload the page data
    await router.reload({ preserveScroll: true });
};

const handleSwipeAction = ({ action, file }) => {
    if (action === 'delete') {
        deleteBox(file);
    } else if (action === 'share') {
        shareBox(file);
    } else if (action === 'download') {
        // Navigate to view the quickdrop
        router.visit(file.upload_url);
    }
};
</script>

<template>
    <Head title="My QuickDrops" />

    <AppLayout>
        <PullToRefresh
            :on-refresh="handleRefresh"
            :threshold="80"
            class="min-h-screen"
        >
            <div class="space-y-8">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                <div>
                    <h1 class="text-3xl font-display font-bold text-text-primary">
                        My QuickDrops
                    </h1>
                    <p class="text-text-secondary mt-1">
                        Manage and share your file collections
                    </p>
                </div>
                
                <Button
                    variant="primary"
                    size="lg"
                    icon="plus"
                    as="Link"
                    :href="route('quickdrop.create')"
                    class="animate-pulse-glow"
                >
                    Create New Drop
                </Button>
            </div>

            <!-- Filters -->
            <Card>
                <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4">
                    <!-- REMOVED: FEAT-034 - Search input -->
                    
                    <div class="flex space-x-2">
                        <button
                            v-for="status in ['all', 'active', 'expired', 'encrypted']"
                            :key="status"
                            @click="filterStatus = status"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                            :class="filterStatus === status 
                                ? 'bg-gradient-primary text-white' 
                                : 'bg-surface text-text-secondary hover:bg-surface-hover'"
                        >
                            {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                        </button>
                    </div>
                </div>
            </Card>

            <!-- Empty State -->
            <div v-if="filteredRequests.length === 0" class="text-center py-16">
                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-surface flex items-center justify-center">
                    <Icon name="inbox" :size="48" class="text-text-muted" />
                </div>
                <h3 class="text-xl font-semibold text-text-primary mb-2">
                    {{ filterStatus !== 'all' ? 'No drops found' : 'No drops yet' }}
                </h3>
                <p class="text-text-secondary mb-8 max-w-md mx-auto">
                    {{ filterStatus !== 'all' 
                        ? 'Try adjusting your filters' 
                        : 'Create your first QuickDrop to start sharing files securely' }}
                </p>
                <Button
                    v-if="filterStatus === 'all'"
                    variant="primary"
                    size="lg"
                    icon="plus"
                    as="Link"
                    :href="route('quickdrop.create')"
                >
                    Create Your First Drop
                </Button>
            </div>

            <!-- Drops Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <TransitionGroup v-bind="fadeSlide">
                    <SwipeableQuickDropCard
                        v-for="(box, index) in filteredRequests"
                        :key="box.id"
                        :box="box"
                        :data-index="index"
                        @click="router.visit(box.upload_url)"
                        @delete="deleteBox"
                        @share="shareBox"
                        class="touch-manipulation"
                    />
                </TransitionGroup>
            </div>
        </div>

        <!-- Share Modal -->
        <ShareLinkModal
            :show="showShareModal"
            :share-url="shareUrl"
            :share-data="shareData"
            @close="showShareModal = false"
        />
        </PullToRefresh>
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

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>