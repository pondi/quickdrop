<template>
    <div class="min-h-screen bg-gradient-background">
        <nav class="fixed top-0 left-0 right-0 z-50 px-4 py-4">
            <div class="max-w-7xl mx-auto">
                <div class="glass-card px-6 py-3 flex items-center justify-between">
                    <div class="flex items-center space-x-8">
                        <Link :href="route('dashboard')" class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-primary flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <span class="text-xl font-display font-semibold text-text-primary">QuickDrop</span>
                        </Link>
                        
                        <div class="hidden md:flex items-center space-x-6">
                            <NavItem :href="route('dashboard')" :active="route().current('dashboard')">
                                Dashboard
                            </NavItem>
                            <NavItem :href="route('quick-drops.index')" :active="route().current('quick-drops.*')">
                                My Drops
                            </NavItem>
                            <NavItem :href="route('quick-drops.create')" :active="route().current('quick-drops.create')">
                                New Drop
                            </NavItem>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <button class="p-2 rounded-lg hover:bg-surface-hover transition-colors duration-fast">
                            <svg class="w-5 h-5 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </button>
                        
                        <div class="relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center space-x-3 p-2 rounded-lg hover:bg-surface-hover transition-colors duration-fast">
                                        <div class="w-8 h-8 rounded-lg bg-gradient-primary flex items-center justify-center">
                                            <span class="text-sm font-medium text-white">
                                                {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                        <svg class="w-4 h-4 text-text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </template>
                                
                                <template #content>
                                    <div class="py-2">
                                        <div class="px-4 py-2 border-b border-white/10">
                                            <p class="text-sm font-medium text-text-primary">{{ $page.props.auth.user.name }}</p>
                                            <p class="text-xs text-text-secondary">{{ $page.props.auth.user.email }}</p>
                                        </div>
                                        
                                        <DropdownLink :href="route('profile.edit')" class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span>Profile</span>
                                        </DropdownLink>
                                        
                                        <DropdownLink :href="route('logout')" method="post" as="button" class="flex items-center space-x-2 w-full">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            <span>Logout</span>
                                        </DropdownLink>
                                    </div>
                                </template>
                            </Dropdown>
                        </div>
                        
                        <button @click="showMobileMenu = !showMobileMenu" class="md:hidden p-2 rounded-lg hover:bg-surface-hover transition-colors duration-fast">
                            <svg class="w-6 h-6 text-text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!showMobileMenu" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>
        
        <div v-if="showMobileMenu" class="fixed inset-0 z-40 md:hidden">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showMobileMenu = false"></div>
            <div class="fixed right-0 top-0 bottom-0 w-80 glass-card rounded-l-2xl p-6 transform transition-transform duration-300"
                 :class="showMobileMenu ? 'translate-x-0' : 'translate-x-full'">
                <div class="flex flex-col space-y-4">
                    <Link :href="route('dashboard')" 
                          class="px-4 py-3 rounded-lg hover:bg-surface-hover transition-colors duration-fast"
                          :class="{ 'bg-surface-hover': route().current('dashboard') }">
                        Dashboard
                    </Link>
                    <Link :href="route('quick-drops.index')" 
                          class="px-4 py-3 rounded-lg hover:bg-surface-hover transition-colors duration-fast"
                          :class="{ 'bg-surface-hover': route().current('quick-drops.*') }">
                        My Drops
                    </Link>
                    <Link :href="route('quick-drops.create')" 
                          class="px-4 py-3 rounded-lg hover:bg-surface-hover transition-colors duration-fast"
                          :class="{ 'bg-surface-hover': route().current('quick-drops.create') }">
                        New Drop
                    </Link>
                </div>
            </div>
        </div>
        
        <main class="pt-24 px-4 pb-8">
            <div class="max-w-7xl mx-auto">
                <slot />
            </div>
        </main>
        
        <Teleport to="body">
            <TransitionGroup name="toast" tag="div" class="fixed bottom-4 right-4 z-50 space-y-2">
                <Notification v-for="notification in $page.props.notifications || []" 
                             :key="notification.id"
                             :type="notification.type"
                             :message="notification.message" />
            </TransitionGroup>
        </Teleport>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import Notification from '@/Components/Notification.vue'

const showMobileMenu = ref(false)

const NavItem = {
    props: ['href', 'active'],
    template: `
        <Link :href="href" 
              class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-fast"
              :class="active ? 'text-text-primary bg-surface-hover' : 'text-text-secondary hover:text-text-primary hover:bg-surface-hover'">
            <slot />
        </Link>
    `
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from {
    transform: translateX(100%);
    opacity: 0;
}

.toast-leave-to {
    transform: translateX(100%);
    opacity: 0;
}

.toast-move {
    transition: transform 0.3s ease;
}
</style>