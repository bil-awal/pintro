import defaultTheme from 'tailwindcss/defaultTheme';

export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './storage/framework/views/*.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Instrument Sans', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [],
};
