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
          orange: '#F05D24',
          'orange-hover': '#D84A15',
          'orange-dark': '#BF3E0F',
          'orange-light': '#FFF7ED',
          teal: '#F05D24',
          'teal-dark': '#D84A15',
          'teal-light': '#FFF7ED',
          gray: '#6B7280',
          'gray-light': '#F3F4F6',
          'gray-dark': '#1F2937',
        },
      },
    },
  },
  plugins: [],
}
