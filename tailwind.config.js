/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './templates/**/*.html.twig',
        './assets/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                accent: '#e8ff00',
            },
        },
    },
    plugins: [],
    important: false,
};
