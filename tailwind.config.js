import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Dyno palette — values live once in :root (partials/dyno-styles),
            // these just expose them as utilities for the Breeze/Tailwind pages.
            colors: {
                canvas: 'var(--bg)',
                surface: 'var(--surface)',
                'surface-raised': 'var(--surface-raised)',
                ink: 'var(--text)',
                muted: 'var(--text-muted)',
                line: 'var(--line)',
                accent: 'var(--accent)',
                core: 'var(--core)',
                danger: 'var(--push)',
                success: 'var(--back)',
                warn: 'var(--grip)',
            },
        },
    },

    plugins: [forms],
};
