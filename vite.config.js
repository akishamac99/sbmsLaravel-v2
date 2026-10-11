import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/style.css',
                'resources/css/auth.css',
                'resources/css/customer-services.css',
                'resources/css/customer-feedback.css',
                'resources/css/customer-profile.css',
                'resources/css/landing.css',
                'resources/js/app.js',
                'resources/js/customer-services.js',
                'resources/js/customer-checkout.js',
                'resources/js/customer-bookings.js',
                'resources/js/customer-feedback.js',
                'resources/js/customer-profile.js',
                'resources/js/portal-walkin.js',
                'resources/js/portal-forms.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});