<template>
    <div class="min-h-screen bg-gradient-background flex flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <Link :href="route('home')" class="flex items-center justify-center space-x-3 mb-8">
                <div class="w-14 h-14 rounded-2xl bg-gradient-primary flex items-center justify-center animate-pulse-glow">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-display font-bold text-text-primary">QuickDrop</h1>
                    <p class="text-sm text-text-secondary">Secure File Sharing</p>
                </div>
            </Link>
            
            <div class="glass-card p-8 animate-slide-up">
                <slot />
            </div>
            
            <div class="mt-8 text-center">
                <p class="text-sm text-text-muted">
                    <slot name="footer" />
                </p>
            </div>
        </div>
        
        <div class="absolute inset-0 -z-10 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-80 h-80 rounded-full bg-gradient-primary opacity-20 blur-3xl animate-float"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 rounded-full bg-gradient-secondary opacity-20 blur-3xl animate-float" style="animation-delay: 3s"></div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>