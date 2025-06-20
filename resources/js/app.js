import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Preload critical pages
const criticalPages = ['Dashboard', 'QuickDropList', 'QuickDropCreate'];

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue');
        
        // Use eager loading for critical pages
        if (criticalPages.includes(name.split('/').pop().replace('.vue', ''))) {
            return resolvePageComponent(`./Pages/${name}.vue`, pages);
        }
        
        // Lazy load other pages
        const page = await resolvePageComponent(`./Pages/${name}.vue`, pages);
        
        // Add loading delay for non-critical pages to prevent layout shift
        if (!criticalPages.includes(name)) {
            await new Promise(resolve => setTimeout(resolve, 0));
        }
        
        return page;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        
        // Global error handler
        app.config.errorHandler = (err, instance, info) => {
        };
        
        // Performance monitoring
        if (typeof window !== 'undefined' && window.performance) {
            app.config.performance = true;
        }
        
        return app
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#6366f1',
        showSpinner: true,
        includeCSS: true,
    },
});
