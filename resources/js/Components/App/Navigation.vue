<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { 
    HomeIcon, 
    CloudArrowUpIcon, 
    FolderIcon, 
    BellIcon,
    Bars3Icon,
    XMarkIcon,
    UserIcon,
    ArrowRightOnRectangleIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
});

const showMobileMenu = ref(false);

const navigationItems = [
    {
        name: 'Dashboard',
        href: 'dashboard',
        icon: HomeIcon,
        current: 'dashboard'
    },
    {
        name: 'My Drops',
        href: 'quickdrop.index',
        icon: FolderIcon,
        current: 'quickdrop.*'
    },
    {
        name: 'New Drop',
        href: 'quickdrop.create',
        icon: CloudArrowUpIcon,
        current: 'quickdrop.create'
    }
];

const userInitials = computed(() => {
    return props.user.name
        .split(' ')
        .map(name => name.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
});
</script>

<template>
    <!-- Desktop Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 px-4 py-4">
        <div class="max-w-7xl mx-auto">
            <!-- Glass container with backdrop blur -->
            <div class="relative backdrop-blur-xl bg-white/10 dark:bg-white/5 rounded-2xl border border-white/20 dark:border-white/10 shadow-2xl px-6 py-3">
                <!-- Subtle glow effect -->
                <div class="absolute inset-0 bg-gradient-to-r from-purple-500/10 to-pink-500/10 rounded-2xl opacity-50 blur-xl -z-10" />
                
                <div class="flex items-center justify-between">
                    <!-- Logo and Brand -->
                    <div class="flex items-center space-x-8">
                        <Link :href="route('dashboard')" class="flex items-center space-x-3 group">
                            <div class="relative">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center shadow-lg group-hover:shadow-purple-500/25 transition-all duration-300 group-hover:scale-105">
                                    <CloudArrowUpIcon class="w-6 h-6 text-white" />
                                </div>
                                <!-- Animated glow on hover -->
                                <div class="absolute inset-0 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 opacity-0 group-hover:opacity-30 blur-md transition-opacity duration-300" />
                            </div>
                            <span class="text-xl font-display font-semibold bg-gradient-to-r from-white to-white/80 bg-clip-text text-transparent">
                                QuickDrop
                            </span>
                        </Link>
                        
                        <!-- Desktop Navigation Items -->
                        <div class="hidden lg:flex items-center space-x-2">
                            <div 
                                v-for="item in navigationItems" 
                                :key="item.name"
                                class="relative"
                            >
                                <Link
                                    :href="route(item.href)"
                                    class="group relative flex items-center space-x-2 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-300"
                                    :class="route().current(item.current) 
                                        ? 'text-white bg-white/10 shadow-lg' 
                                        : 'text-white/70 hover:text-white hover:bg-white/5'"
                                >
                                    <component 
                                        :is="item.icon" 
                                        class="h-4 w-4 transition-transform duration-300 group-hover:scale-110" 
                                    />
                                    <span>{{ item.name }}</span>
                                    
                                    <!-- Active indicator -->
                                    <div 
                                        v-if="route().current(item.current)"
                                        class="absolute inset-0 rounded-xl bg-gradient-to-r from-purple-500/20 to-pink-500/20 border border-purple-500/30"
                                    />
                                </Link>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right side actions -->
                    <div class="flex items-center space-x-3">
                        <!-- Notifications -->
                        <button class="relative p-2.5 rounded-xl text-white/70 hover:text-white hover:bg-white/5 transition-all duration-300 group">
                            <BellIcon class="w-5 h-5 transition-transform duration-300 group-hover:scale-110" />
                            <!-- Notification dot -->
                            <div class="absolute top-1 right-1 w-2 h-2 bg-gradient-to-r from-red-400 to-pink-400 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                        </button>
                        
                        <!-- User Menu -->
                        <div class="relative">
                            <Dropdown align="right" width="64">
                                <template #trigger>
                                    <button class="flex items-center space-x-3 p-2 rounded-xl hover:bg-white/5 transition-all duration-300 group">
                                        <div class="relative">
                                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-medium text-sm shadow-lg group-hover:shadow-purple-500/25 transition-all duration-300 group-hover:scale-105">
                                                {{ userInitials }}
                                            </div>
                                            <!-- Glow effect -->
                                            <div class="absolute inset-0 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 opacity-0 group-hover:opacity-30 blur-md transition-opacity duration-300" />
                                        </div>
                                        <ChevronDownIcon class="w-4 h-4 text-white/70 transition-transform duration-300 group-hover:rotate-180" />
                                    </button>
                                </template>
                                
                                <template #content>
                                    <div class="py-2 backdrop-blur-xl bg-white/10 dark:bg-white/5 border border-white/20 rounded-xl shadow-2xl">
                                        <!-- User info -->
                                        <div class="px-4 py-3 border-b border-white/10">
                                            <p class="text-sm font-medium text-white">{{ user.name }}</p>
                                            <p class="text-xs text-white/60 mt-0.5">{{ user.email }}</p>
                                        </div>
                                        
                                        <!-- Menu items -->
                                        <div class="py-1">
                                            <DropdownLink 
                                                :href="route('profile.edit')" 
                                                class="flex items-center space-x-3 px-4 py-3 text-sm text-white/80 hover:text-white hover:bg-white/5 transition-all duration-200"
                                            >
                                                <UserIcon class="w-4 h-4" />
                                                <span>Profile Settings</span>
                                            </DropdownLink>
                                            
                                            <DropdownLink 
                                                :href="route('logout')" 
                                                method="post" 
                                                as="button" 
                                                class="flex items-center space-x-3 px-4 py-3 text-sm text-white/80 hover:text-white hover:bg-white/5 transition-all duration-200 w-full"
                                            >
                                                <ArrowRightOnRectangleIcon class="w-4 h-4" />
                                                <span>Sign Out</span>
                                            </DropdownLink>
                                        </div>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                        
                        <!-- Mobile menu toggle -->
                        <button 
                            @click="showMobileMenu = !showMobileMenu" 
                            class="lg:hidden p-2.5 rounded-xl text-white/70 hover:text-white hover:bg-white/5 transition-all duration-300"
                        >
                            <Bars3Icon v-if="!showMobileMenu" class="w-6 h-6" />
                            <XMarkIcon v-else class="w-6 h-6" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Navigation Overlay -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showMobileMenu" class="fixed inset-0 z-40 lg:hidden">
                <!-- Backdrop -->
                <div 
                    class="fixed inset-0 bg-black/50 backdrop-blur-sm" 
                    @click="showMobileMenu = false"
                />
                
                <!-- Mobile menu panel -->
                <Transition
                    enter-active-class="transition-transform duration-300 ease-out"
                    enter-from-class="translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition-transform duration-200 ease-in"
                    leave-from-class="translate-x-0"
                    leave-to-class="translate-x-full"
                >
                    <div v-if="showMobileMenu" class="fixed right-0 top-0 bottom-0 w-80 backdrop-blur-xl bg-white/10 dark:bg-white/5 border-l border-white/20 p-6">
                        <!-- Mobile menu header -->
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                                    <CloudArrowUpIcon class="w-5 h-5 text-white" />
                                </div>
                                <span class="text-lg font-semibold text-white">Menu</span>
                            </div>
                            <button 
                                @click="showMobileMenu = false"
                                class="p-2 rounded-lg text-white/70 hover:text-white hover:bg-white/5 transition-colors duration-200"
                            >
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>
                        
                        <!-- Mobile navigation items -->
                        <div class="space-y-2">
                            <Link
                                v-for="item in navigationItems"
                                :key="item.name"
                                :href="route(item.href)"
                                @click="showMobileMenu = false"
                                class="flex items-center space-x-3 px-4 py-4 rounded-xl transition-all duration-200"
                                :class="route().current(item.current) 
                                    ? 'text-white bg-white/10 border border-white/20' 
                                    : 'text-white/70 hover:text-white hover:bg-white/5'"
                            >
                                <component :is="item.icon" class="w-5 h-5" />
                                <span class="font-medium">{{ item.name }}</span>
                            </Link>
                        </div>
                        
                        <!-- Mobile user section -->
                        <div class="absolute bottom-6 left-6 right-6">
                            <div class="p-4 rounded-xl bg-white/5 border border-white/10">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-medium">
                                        {{ userInitials }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-white">{{ user.name }}</p>
                                        <p class="text-xs text-white/60">{{ user.email }}</p>
                                    </div>
                                </div>
                                
                                <div class="space-y-1">
                                    <Link 
                                        :href="route('profile.edit')"
                                        @click="showMobileMenu = false"
                                        class="flex items-center space-x-2 px-3 py-2 rounded-lg text-sm text-white/80 hover:text-white hover:bg-white/5 transition-colors duration-200"
                                    >
                                        <UserIcon class="w-4 h-4" />
                                        <span>Profile Settings</span>
                                    </Link>
                                    <Link 
                                        :href="route('logout')" 
                                        method="post"
                                        as="button"
                                        class="flex items-center space-x-2 px-3 py-2 rounded-lg text-sm text-white/80 hover:text-white hover:bg-white/5 transition-colors duration-200 w-full"
                                    >
                                        <ArrowRightOnRectangleIcon class="w-4 h-4" />
                                        <span>Sign Out</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>