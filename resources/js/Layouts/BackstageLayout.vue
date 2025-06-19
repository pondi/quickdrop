<template>
    <div class="admin-layout min-h-screen flex">
        <aside class="admin-sidebar w-64 flex-shrink-0 fixed left-0 top-0 bottom-0 z-40">
            <div class="h-full flex flex-col">
                <div class="p-4 border-b border-white/10">
                    <Link :href="route('backstage.dashboard')" class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-white">QuickDrop</h2>
                            <p class="text-xs text-white/60">Backstage</p>
                        </div>
                    </Link>
                </div>
                
                <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                    <SidebarItem :href="route('backstage.dashboard')" :active="route().current('backstage.dashboard')">
                        <template #icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                        </template>
                        Dashboard
                    </SidebarItem>
                    
                    <SidebarItem :href="route('backstage.users.index')" :active="route().current('backstage.users.*')">
                        <template #icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </template>
                        Backstage Users
                    </SidebarItem>
                    
                    <SidebarItem :href="route('backstage.quickdrop-users.index')" :active="route().current('backstage.quickdrop-users.*')">
                        <template #icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </template>
                        QuickDrop Users
                    </SidebarItem>
                    
                    <SidebarItem :href="route('backstage.quickdrops.index')" :active="route().current('backstage.quickdrops.*')">
                        <template #icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </template>
                        QuickDrops
                    </SidebarItem>
                    
                    <SidebarItem :href="route('backstage.audit-log.index')" :active="route().current('backstage.audit-log.*')">
                        <template #icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </template>
                        Audit Log
                    </SidebarItem>
                    
                    <div class="pt-4 mt-4 border-t border-white/10">
                        <SidebarItem :href="route('backstage.file-types.index')" :active="route().current('backstage.file-types.*')">
                            <template #icon>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </template>
                            File Types
                        </SidebarItem>
                        
                        <SidebarItem :href="route('backstage.settings.index')" :active="route().current('backstage.settings.*')">
                            <template #icon>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </template>
                            Settings
                        </SidebarItem>
                    </div>
                </nav>
                
                <div class="p-4 border-t border-white/10">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center">
                            <span class="text-sm font-medium text-white">
                                {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                            </span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-white">{{ $page.props.auth.user.name }}</p>
                            <p class="text-xs text-white/60">Administrator</p>
                        </div>
                        <Link :href="route('logout')" method="post" as="button" 
                              class="p-2 rounded-lg hover:bg-white/10 transition-colors">
                            <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </aside>
        
        <div class="flex-1 ml-64">
            <header class="admin-card sticky top-0 z-30 px-6 py-4 border-b">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold">
                        <slot name="header" />
                    </h1>
                    
                    <div class="flex items-center space-x-4">
                        <button @click="toggleTheme" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg v-if="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>
                        
                        <Link :href="route('dashboard')" class="admin-button admin-button-primary">
                            Back to App
                        </Link>
                    </div>
                </div>
            </header>
            
            <main class="p-6">
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'

const isDark = ref(false)

const toggleTheme = () => {
    isDark.value = !isDark.value
    document.documentElement.setAttribute('data-theme', isDark.value ? 'dark' : 'light')
    localStorage.setItem('admin-theme', isDark.value ? 'dark' : 'light')
}

onMounted(() => {
    const savedTheme = localStorage.getItem('admin-theme')
    if (savedTheme) {
        isDark.value = savedTheme === 'dark'
        document.documentElement.setAttribute('data-theme', savedTheme)
    }
})

const SidebarItem = {
    props: ['href', 'active'],
    template: `
        <Link :href="href" 
              class="admin-sidebar-item flex items-center space-x-3"
              :class="{ 'active': active }">
            <slot name="icon" />
            <span class="text-sm font-medium"><slot /></span>
        </Link>
    `
}
</script>