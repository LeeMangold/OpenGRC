import preset from './vendor/filament/support/tailwind.config.preset'
const colors = require('tailwindcss/colors')
const plugin = require('tailwindcss/plugin');

export default {
    presets: [preset],
    content: [
        './storage/framework/views/*.php',
        './app/Filament/**/*.php',
        './app/Livewire/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    safelist: [
        // Ensure grcblue colors are never purged
        'bg-grcblue-200',
        'bg-grcblue-400',
        'bg-grcblue-500',
        'bg-grcblue-600',
        'bg-grcblue-700',
        'bg-grcblue-800',
        'text-grcblue-400',
        'text-grcblue-800',
        'hover:bg-grcblue-600',
        'hover:bg-grcblue-700',
        // Pattern matching for any grcblue variations
        {
            pattern: /(bg|text|border)-(grcblue)-(100|200|300|400|500|600|700|800|900)/,
            variants: ['hover', 'focus', 'active'],
        },
    ],
    theme: {
        extend: {
            colors: {
                grcblue: {
                    50: '#eaf3f7',
                    100: '#d4e7ef',
                    200: '#d4e7ef',  // Matches your CSS
                    300: '#7eb7d1',
                    400: '#1375a0',  // Matches your CSS
                    500: '#1375a0',  // Matches your CSS
                    600: '#0f5a7a',  // Matches your CSS
                    700: '#0c4a65',  // Matches your CSS
                    800: '#374151',  // Matches your CSS
                    900: '#212a3a',
                },
            },
        },
    },
}
