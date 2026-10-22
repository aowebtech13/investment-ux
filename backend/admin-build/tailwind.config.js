/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "../resources/views/**/*.blade.php",
  ],
  theme: {
    extend: {
      colors: {
        primary: "#0f172a", // Sleek dark slate
        secondary: "#334155",
        accent: "#2563eb", // Blue for primary actions
        neutral: "#1e293b",
        "base-100": "#ffffff",
        "base-200": "#f8fafc",
        "base-300": "#f1f5f9",
      }
    }
  },
  daisyui: {
    themes: [
      {
        nexora: {
          "primary": "#0f172a",
          "primary-content": "#ffffff",
          "secondary": "#334155",
          "secondary-content": "#ffffff",
          "accent": "#2563eb",
          "accent-content": "#ffffff",
          "neutral": "#1e293b",
          "neutral-content": "#ffffff",
          "base-100": "#ffffff",
          "base-200": "#f8fafc",
          "base-300": "#f1f5f9",
          "base-content": "#0f172a",
          "info": "#3abff8",
          "success": "#36d399",
          "warning": "#fbbd23",
          "error": "#f87272",
        },
      },
    ],
    darkTheme: false,
    base: true,
    styled: true,
    utils: true,
    prefix: "",
    logs: false,
  },
  plugins: [require("daisyui")],
}
