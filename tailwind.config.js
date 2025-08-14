/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./*.html",
    "./js/**/*.js",
    "./css/**/*.css"
  ],
  theme: {
    extend: {
      colors: {
        'jru-blue': '#1e3a8a',
        'jru-orange': '#f59e0b',
        'jru-gold': '#fbbf24',
      }
    }
  },
  plugins: [],
}