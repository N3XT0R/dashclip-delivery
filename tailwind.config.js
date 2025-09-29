import lineClamp from '@tailwindcss/line-clamp'

export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
    ],
    theme: {
        extend: {},
    },
    plugins: [
        lineClamp,
    ],
}
