<template>
    <div class="min-h-screen bg-gradient-background">
        <nav class="px-4 py-6">
            <div class="max-w-2xl mx-auto">
                <Link :href="route('welcome')" class="inline-flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-xl bg-gradient-primary flex items-center justify-center animate-float">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-display font-bold gradient-text">QuickDrop</h1>
                        <p class="text-xs text-text-secondary">Secure File Sharing</p>
                    </div>
                </Link>
            </div>
        </nav>
        
        <main class="px-4 py-8">
            <div class="max-w-2xl mx-auto">
                <slot />
            </div>
        </main>
        
        <footer class="mt-auto px-4 py-8">
            <div class="max-w-2xl mx-auto text-center">
                <p class="text-sm text-text-muted">
                    Powered by <span class="gradient-text font-medium">QuickDrop</span>
                </p>
                <div class="mt-4 flex items-center justify-center space-x-6">
                    <a href="#" class="text-text-secondary hover:text-text-primary transition-colors duration-fast">
                        Privacy
                    </a>
                    <a href="#" class="text-text-secondary hover:text-text-primary transition-colors duration-fast">
                        Terms
                    </a>
                    <a href="#" class="text-text-secondary hover:text-text-primary transition-colors duration-fast">
                        Support
                    </a>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
</script>