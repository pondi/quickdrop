<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/App/Card.vue';
import Button from '@/Components/App/Button.vue';
import Icon from '@/Components/App/Icon.vue';
import Modal from '@/Components/App/Modal.vue';
import { useTransitionClasses } from '@/Composables/useAnimations';

const props = defineProps({
    uploadRequests: {
        type: Array,
        required: true
    }
});

const { fadeSlide } = useTransitionClasses();
const searchQuery = ref('');
const filterStatus = ref('all');
const showShareModal = ref(false);
const selectedBox = ref(null);
const copySuccess = ref(false);

const filteredRequests = computed(() => {
    let filtered = props.uploadRequests;
    
    if (searchQuery.value) {
        filtered = filtered.filter(box => 
            box.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            box.reference_number?.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }
    
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
        console.error('Failed to copy to clipboard');
    }
};

const shareBox = (box) => {
    selectedBox.value = box;
    showShareModal.value = true;
};

const deleteBox = (box) => {
    if (confirm(`Are you sure you want to delete "${box.title}"?`)) {
        router.delete(route('quick-drops.destroy', box.id));
    }
};
</script>

<template>
    <Head title="My QuickDrops" />

    <AppLayout>
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
                    :href="route('quick-drops.create')"
                    class="animate-pulse-glow"
                >
                    Create New Drop
                </Button>
            </div>

            <!-- Filters -->
            <Card>
                <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4">
                    <div class="flex-1 relative">
                        <Icon 
                            name="search" 
                            :size="20" 
                            class="absolute left-3 top-3 text-text-muted"
                        />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search drops..."
                            class="w-full pl-10 pr-4 py-3 rounded-xl bg-surface border border-white/10 text-text-primary placeholder-text-muted focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                        />
                    </div>
                    
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
                    {{ searchQuery || filterStatus !== 'all' ? 'No drops found' : 'No drops yet' }}
                </h3>
                <p class="text-text-secondary mb-8 max-w-md mx-auto">
                    {{ searchQuery || filterStatus !== 'all' 
                        ? 'Try adjusting your search or filters' 
                        : 'Create your first QuickDrop to start sharing files securely' }}
                </p>
                <Button
                    v-if="!searchQuery && filterStatus === 'all'"
                    variant="primary"
                    size="lg"
                    icon="plus"
                    as="Link"
                    :href="route('quick-drops.create')"
                >
                    Create Your First Drop
                </Button>
            </div>

            <!-- Drops Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <TransitionGroup v-bind="fadeSlide">
                    <div
                        v-for="(box, index) in filteredRequests"
                        :key="box.id"
                        :data-index="index"
                        class="group"
                    >
                        <Card 
                            :hoverable="true"
                            class="h-full flex flex-col transform transition-all duration-300 hover:scale-105"
                        >
                            <!-- Status Badge -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <span 
                                        class="px-3 py-1 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-green-500/20 text-green-400': box.is_active,
                                            'bg-red-500/20 text-red-400': !box.is_active,
                                        }"
                                    >
                                        {{ box.is_active ? 'Active' : 'Expired' }}
                                    </span>
                                    <span v-if="box.is_encrypted" class="px-3 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400">
                                        <Icon name="lock" :size="12" class="inline mr-1" />
                                        Encrypted
                                    </span>
                                </div>
                                
                                <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button
                                        @click.stop="deleteBox(box)"
                                        class="p-2 rounded-lg hover:bg-surface-hover transition-colors"
                                    >
                                        <Icon name="trash2" :size="16" class="text-red-400" />
                                    </button>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-text-primary mb-2 group-hover:gradient-text transition-all">
                                    {{ box.title }}
                                </h3>
                                
                                <div class="space-y-2 text-sm text-text-secondary">
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center">
                                            <Icon name="clock" :size="14" class="mr-1" />
                                            Created
                                        </span>
                                        <span>{{ formatDate(box.created_at) }}</span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center">
                                            <Icon name="timer" :size="14" class="mr-1" />
                                            Expires
                                        </span>
                                        <span :class="{ 'text-red-400': !box.is_active }">
                                            {{ timeUntilExpiry(box.expires_at) }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center">
                                            <Icon name="files" :size="14" class="mr-1" />
                                            Files
                                        </span>
                                        <span>{{ box.files_count }} ({{ formatBytes(box.total_size) }})</span>
                                    </div>
                                    
                                    <div v-if="box.reference_number" class="flex items-center justify-between">
                                        <span class="flex items-center">
                                            <Icon name="hash" :size="14" class="mr-1" />
                                            Reference
                                        </span>
                                        <span class="font-mono text-xs">{{ box.reference_number }}</span>
                                    </div>
                                </div>
                                
                                <div v-if="box.comment" class="mt-4 p-3 rounded-lg bg-surface">
                                    <p class="text-xs text-text-secondary line-clamp-2">
                                        {{ box.comment }}
                                    </p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-6 flex space-x-2">
                                <Button
                                    variant="primary"
                                    size="sm"
                                    icon="eye"
                                    as="Link"
                                    :href="box.upload_url"
                                    class="flex-1"
                                >
                                    View
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    icon="share2"
                                    @click="shareBox(box)"
                                    class="flex-1"
                                >
                                    Share
                                </Button>
                            </div>
                        </Card>
                    </div>
                </TransitionGroup>
            </div>
        </div>

        <!-- Share Modal -->
        <Modal
            :show="showShareModal"
            @close="showShareModal = false"
            title="Share QuickDrop"
        >
            <div v-if="selectedBox" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-text-primary mb-2">
                        Share Link
                    </label>
                    <div class="flex space-x-2">
                        <input
                            :value="selectedBox.upload_url"
                            readonly
                            class="flex-1 px-4 py-2 rounded-lg bg-surface border border-white/10 text-text-primary text-sm"
                        />
                        <Button
                            variant="primary"
                            size="sm"
                            icon="copy"
                            @click="copyToClipboard(selectedBox.upload_url)"
                        >
                            {{ copySuccess ? 'Copied!' : 'Copy' }}
                        </Button>
                    </div>
                </div>
                
                <div v-if="selectedBox.reference_number">
                    <label class="block text-sm font-medium text-text-primary mb-2">
                        Reference Number
                    </label>
                    <p class="font-mono text-lg text-primary">
                        {{ selectedBox.reference_number }}
                    </p>
                    <p class="text-xs text-text-secondary mt-1">
                        Share this reference number with recipients to access the drop
                    </p>
                </div>
                
                <div class="p-4 rounded-lg bg-amber-500/10 border border-amber-500/20">
                    <p class="text-sm text-amber-400">
                        <Icon name="alertTriangle" :size="16" class="inline mr-1" />
                        This link expires {{ timeUntilExpiry(selectedBox.expires_at) }}
                    </p>
                </div>
            </div>
            
            <template #footer>
                <Button variant="ghost" @click="showShareModal = false">
                    Close
                </Button>
            </template>
        </Modal>
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