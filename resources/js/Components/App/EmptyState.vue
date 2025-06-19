<script setup>
import { computed } from 'vue';
import { 
    CloudArrowUpIcon,
    FolderIcon,
    DocumentIcon,
    SparklesIcon,
    RocketLaunchIcon,
    PlusIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    type: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'uploads', 'files', 'search', 'error', 'welcome'].includes(value)
    },
    title: {
        type: String,
        default: ''
    },
    description: {
        type: String,
        default: ''
    },
    buttonText: {
        type: String,
        default: ''
    },
    buttonHref: {
        type: String,
        default: ''
    },
    showButton: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['action']);

const emptyStateData = computed(() => {
    const states = {
        default: {
            title: 'Nothing here yet',
            description: 'Start by creating something new',
            buttonText: 'Get Started',
            icon: SparklesIcon,
            illustration: 'default'
        },
        uploads: {
            title: 'No files uploaded yet',
            description: 'Drag and drop files here or click to browse and start sharing',
            buttonText: 'Upload Files',
            icon: CloudArrowUpIcon,
            illustration: 'upload'
        },
        files: {
            title: 'No drops created',
            description: 'Create your first drop to start sharing files securely',
            buttonText: 'Create Drop',
            icon: FolderIcon,
            illustration: 'folder'
        },
        search: {
            title: 'No results found',
            description: 'Try adjusting your search terms or filters',
            buttonText: 'Clear Search',
            icon: DocumentIcon,
            illustration: 'search'
        },
        error: {
            title: 'Something went wrong',
            description: 'Please try again or contact support if the problem persists',
            buttonText: 'Try Again',
            icon: SparklesIcon,
            illustration: 'error'
        },
        welcome: {
            title: 'Welcome to QuickDrop!',
            description: 'Start sharing files instantly with secure, temporary links',
            buttonText: 'Create Your First Drop',
            icon: RocketLaunchIcon,
            illustration: 'welcome'
        }
    };
    
    const defaultState = states[props.type] || states.default;
    
    return {
        title: props.title || defaultState.title,
        description: props.description || defaultState.description,
        buttonText: props.buttonText || defaultState.buttonText,
        icon: defaultState.icon,
        illustration: defaultState.illustration
    };
});

const handleAction = () => {
    if (props.buttonHref) {
        window.location.href = props.buttonHref;
    } else {
        emit('action');
    }
};
</script>

<template>
    <div class="flex flex-col items-center justify-center py-16 px-4">
        <!-- Illustration Container -->
        <div class="relative mb-8">
            <!-- Main illustration circle -->
            <div class="relative w-32 h-32 rounded-full bg-gradient-to-br from-purple-500/20 to-pink-500/20 border border-purple-500/30 flex items-center justify-center backdrop-blur-sm">
                <!-- Floating elements around the circle -->
                <div class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-gradient-to-r from-purple-400 to-pink-400 opacity-80 animate-pulse" />
                <div class="absolute -bottom-3 -left-3 w-4 h-4 rounded-full bg-gradient-to-r from-blue-400 to-purple-400 opacity-60 animate-bounce" style="animation-delay: 0.5s" />
                <div class="absolute top-1/4 -left-4 w-3 h-3 rounded-full bg-gradient-to-r from-pink-400 to-rose-400 opacity-70 animate-pulse" style="animation-delay: 1s" />
                
                <!-- Central icon -->
                <div class="relative z-10">
                    <component 
                        :is="emptyStateData.icon" 
                        class="w-12 h-12 text-white/80" 
                    />
                </div>
                
                <!-- Glow effect -->
                <div class="absolute inset-0 rounded-full bg-gradient-to-br from-purple-500/30 to-pink-500/30 blur-xl opacity-50" />
            </div>
            
            <!-- Additional illustration elements based on type -->
            <template v-if="emptyStateData.illustration === 'upload'">
                <!-- Upload-specific elements -->
                <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-8">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center opacity-80 animate-bounce" style="animation-delay: 0.2s">
                        <DocumentIcon class="w-4 h-4 text-white" />
                    </div>
                </div>
                <div class="absolute bottom-0 right-0 transform translate-x-4 translate-y-4">
                    <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-pink-400 to-pink-600 flex items-center justify-center opacity-60 animate-bounce" style="animation-delay: 0.8s">
                        <PlusIcon class="w-3 h-3 text-white" />
                    </div>
                </div>
            </template>
            
            <template v-else-if="emptyStateData.illustration === 'folder'">
                <!-- Folder-specific elements -->
                <div class="absolute -top-4 left-0 transform -translate-x-6">
                    <div class="w-6 h-6 rounded-md bg-gradient-to-br from-blue-400 to-blue-600 opacity-70 animate-pulse" />
                </div>
                <div class="absolute top-1/2 right-0 transform translate-x-8">
                    <div class="w-4 h-4 rounded-md bg-gradient-to-br from-emerald-400 to-emerald-600 opacity-60 animate-pulse" style="animation-delay: 0.7s" />
                </div>
            </template>
            
            <template v-else-if="emptyStateData.illustration === 'welcome'">
                <!-- Welcome-specific elements -->
                <div class="absolute -top-6 -left-6 w-8 h-8 rounded-full bg-gradient-to-br from-yellow-400 to-orange-400 flex items-center justify-center opacity-80 animate-spin" style="animation-duration: 8s">
                    <SparklesIcon class="w-4 h-4 text-white" />
                </div>
                <div class="absolute -bottom-6 right-0 w-6 h-6 rounded-full bg-gradient-to-br from-emerald-400 to-teal-400 opacity-70 animate-ping" />
            </template>
        </div>
        
        <!-- Content -->
        <div class="text-center max-w-md">
            <h3 class="text-xl font-semibold text-white mb-3">
                {{ emptyStateData.title }}
            </h3>
            <p class="text-white/60 text-sm leading-relaxed mb-8">
                {{ emptyStateData.description }}
            </p>
            
            <!-- Action Button -->
            <button 
                v-if="showButton"
                @click="handleAction"
                class="group relative inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 text-white font-medium text-sm transition-all duration-300 hover:scale-105 hover:shadow-2xl hover:shadow-purple-500/25 active:scale-95"
            >
                <!-- Button glow effect -->
                <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 opacity-0 group-hover:opacity-30 blur-xl transition-opacity duration-300" />
                
                <span class="relative z-10">{{ emptyStateData.buttonText }}</span>
                
                <!-- Animated arrow or plus icon -->
                <div class="relative z-10 transition-transform duration-300 group-hover:translate-x-1">
                    <PlusIcon v-if="type === 'uploads' || type === 'files'" class="w-4 h-4" />
                    <RocketLaunchIcon v-else-if="type === 'welcome'" class="w-4 h-4" />
                    <SparklesIcon v-else class="w-4 h-4" />
                </div>
            </button>
        </div>
        
        <!-- Subtle background pattern -->
        <div class="absolute inset-0 opacity-5 pointer-events-none">
            <div class="absolute top-1/4 left-1/4 w-32 h-32 rounded-full border border-white/20" />
            <div class="absolute bottom-1/4 right-1/4 w-48 h-48 rounded-full border border-white/10" />
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 rounded-full border border-white/5" />
        </div>
    </div>
</template>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

.animate-float-delayed {
    animation: float 3s ease-in-out infinite;
    animation-delay: 1.5s;
}
</style>