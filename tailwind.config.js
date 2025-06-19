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
                    start: '#457B9D',
                    end: '#1D3557',
                    DEFAULT: '#457B9D',
                },
                secondary: {
                    start: '#A8DADC',
                    end: '#457B9D',
                    DEFAULT: '#A8DADC',
                },
                accent: {
                    DEFAULT: '#E63946',
                    dark: '#D62828',
                },
                background: {
                    start: '#F1FAEE',
                    end: '#E8F4F8',
                    DEFAULT: '#F1FAEE',
                },
                surface: {
                    DEFAULT: 'rgba(255, 255, 255, 0.7)',
                    hover: 'rgba(255, 255, 255, 0.85)',
                },
                text: {
                    primary: '#1D3557',
                    secondary: '#457B9D',
                    muted: '#6B7C8D',
                },
            },
            backgroundImage: {
                'gradient-primary': 'linear-gradient(135deg, #457B9D 0%, #1D3557 100%)',
                'gradient-secondary': 'linear-gradient(135deg, #A8DADC 0%, #457B9D 100%)',
                'gradient-background': 'linear-gradient(180deg, #F1FAEE 0%, #E8F4F8 100%)',
                'gradient-accent': 'linear-gradient(135deg, #E63946 0%, #D62828 100%)',
            },
            boxShadow: {
                'glow': '0 0 30px rgba(69, 123, 157, 0.2)',
                'card': '0 8px 32px rgba(0, 0, 0, 0.08)',
                'card-hover': '0 12px 48px rgba(0, 0, 0, 0.12)',
            },
            backdropBlur: {
                'glass': '20px',
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
                    '0%, 100%': { boxShadow: '0 0 20px rgba(69, 123, 157, 0.3)' },
                    '50%': { boxShadow: '0 0 40px rgba(69, 123, 157, 0.6)' },
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
