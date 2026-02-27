/** @type {import('tailwindcss').Config} */
module.exports = {
    purge: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './public/js/**/*.js',
    ],
    darkMode: false,
    theme: {
        extend: {
            colors: {
                dark: {
                    DEFAULT : '#0a0a0f',
                    2       : '#12121a',
                    card    : '#16161f',
                    hover   : '#1c1c28',
                },
                accent: {
                    DEFAULT : '#e94560',
                    dark    : '#c73652',
                    glow    : 'rgba(233,69,96,0.25)',
                },
                muted: '#5a5a7a',
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui'],
            },
        },
    },
    variants: {
        extend: {},
    },
    plugins: [],
};
