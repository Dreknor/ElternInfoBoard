/** @type {import('tailwindcss').Config} */
export default {
  // Dark Mode: Wird per CSS-Klasse "dark" auf dem <html>-Element gesteuert.
  // Ermöglicht theme-basiertes Dark Mode (dark: Prefix in Tailwind).
  darkMode: 'class',

  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./app/View/Components/**/*.php",
    "./app/Livewire/**/*.php",
  ],

  theme: {
    extend: {
      fontFamily: {
        sans: ['Newsreader', 'ui-serif', 'Georgia', 'Cambria', 'Times New Roman', 'Times', 'serif'],
      },

      // Theme-Farben über CSS Custom Properties – ermöglicht dynamisches Theming
      // ohne Tailwind-Rebuild. Werte werden per <x-theme-vars /> gesetzt.
      colors: {
        theme: {
          primary:       'var(--color-primary)',
          'primary-dark':'var(--color-primary-dark)',
          'primary-light':'var(--color-primary-light)',
          secondary:     'var(--color-secondary)',
          'sidebar-bg':  'var(--color-sidebar-bg)',
          'sidebar-mid': 'var(--color-sidebar-bg-mid)',
          'sidebar-text':'var(--color-sidebar-text)',
          'navbar-bg':   'var(--color-navbar-bg)',
          'navbar-text': 'var(--color-navbar-text)',
          'body-bg':     'var(--color-body-bg)',
          'card-bg':     'var(--color-card-bg)',
          'card-border': 'var(--color-card-border)',
          'text':        'var(--color-text-primary)',
          'text-muted':  'var(--color-text-secondary)',
        },
      },

      // Benutzerdefinierte Übergänge für Sidebar / Modal
      transitionProperty: {
        'sidebar': 'transform, width, margin',
      },

      // Schatten
      boxShadow: {
        'sidebar': '4px 0 24px rgba(0,0,0,0.15)',
      },
    },
  },

  plugins: [
    require('@tailwindcss/typography'),
  ],
}


