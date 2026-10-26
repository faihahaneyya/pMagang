/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          orange: '#F15A24',
          'orange-hover': '#D84A15',
          'orange-dark': '#BF3E0F',
          'orange-light': '#FFF7ED',
          teal: '#176B7A',
          'teal-hover': '#135460',
          'teal-dark': '#0E3E47',
          'teal-light': '#E6F4F6',
          gray: '#6B7280',
          'gray-light': '#F3F4F6',
          'gray-dark': '#1F2937',
        },
      },
    },
  },
  plugins: [],
}
