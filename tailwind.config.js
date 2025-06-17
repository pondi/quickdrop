import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './vueform.config.js',
        './node_modules/@vueform/vueform/themes/tailwind/**/*.vue',
        './node_modules/@vueform/vueform/themes/tailwind/**/*.js',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Space Grotesk', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                primary: {
                    start: '#667eea',
                    end: '#764ba2',
                    DEFAULT: '#667eea',
                },
                secondary: {
                    start: '#f093fb',
                    end: '#f5576c',
                    DEFAULT: '#f093fb',
                },
                background: {
                    start: '#0f0f23',
                    end: '#1a1a2e',
                    DEFAULT: '#0f0f23',
                },
                surface: {
                    DEFAULT: 'rgba(255, 255, 255, 0.05)',
                    hover: 'rgba(255, 255, 255, 0.08)',
                },
                text: {
                    primary: '#ffffff',
                    secondary: '#a0a0a0',
                    muted: '#6b6b6b',
                },
            },
            backgroundImage: {
                'gradient-primary': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'gradient-secondary': 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                'gradient-background': 'linear-gradient(180deg, #0f0f23 0%, #1a1a2e 100%)',
            },
            boxShadow: {
                'glow': '0 0 20px rgba(102, 126, 234, 0.4)',
                'card': '0 10px 40px rgba(0, 0, 0, 0.3)',
                'card-hover': '0 20px 60px rgba(0, 0, 0, 0.4)',
            },
            backdropBlur: {
                'glass': '12px',
            },
            borderWidth: {
                'glass': '1px',
            },
            transitionDuration: {
                'fast': '150ms',
                'medium': '300ms',
                'slow': '500ms',
            },
            animation: {
                'float': 'float 6s ease-in-out infinite',
                'pulse-glow': 'pulseGlow 2s ease-in-out infinite',
                'shimmer': 'shimmer 1.5s infinite',
                'slide-in': 'slideIn 300ms ease-out',
                'fade-in': 'fadeIn 300ms ease-in',
                'slide-up': 'slideUp 300ms ease-out',
            },
            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-20px)' },
                },
                pulseGlow: {
                    '0%, 100%': { boxShadow: '0 0 20px rgba(102, 126, 234, 0.4)' },
                    '50%': { boxShadow: '0 0 40px rgba(102, 126, 234, 0.8)' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
                slideIn: {
                    from: { transform: 'translateX(100%)', opacity: '0' },
                    to: { transform: 'translateX(0)', opacity: '1' },
                },
                fadeIn: {
                    from: { opacity: '0' },
                    to: { opacity: '1' },
                },
                slideUp: {
                    from: { transform: 'translateY(10px)', opacity: '0' },
                    to: { transform: 'translateY(0)', opacity: '1' },
                },
            },
        },
    },

    plugins: [
        forms,
        require('@vueform/vueform/tailwind'),
    ],
};
