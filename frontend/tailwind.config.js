/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        // ── Brand ────────────────────────────────────────────────
        brand: {
          dark:    '#762127',   // primary burgundy
          DEFAULT: '#8F2D35',   // secondary burgundy
          light:   '#A8404A',   // lighter hover state
          muted:   '#F5EAEB',   // very light burgundy tint for active bg
          subtle:  '#FBF5F5',   // near-white burgundy tint
        },

        // ── Gold accent (use sparingly) ───────────────────────────
        gold: {
          DEFAULT: '#C5A15B',
          light:   '#D4B978',
          muted:   '#F9F3E8',
        },

        // ── Surfaces ──────────────────────────────────────────────
        surface: {
          DEFAULT: '#FFFFFF',
          muted:   '#F6F8FA',   // sidebar, muted cards
          base:    '#F4F5F7',   // workspace background
        },

        // ── Borders ───────────────────────────────────────────────
        border: {
          DEFAULT: '#E1E4E8',
          strong:  '#C9CDD2',
          focus:   '#8F2D35',
        },

        // ── Text ──────────────────────────────────────────────────
        text: {
          primary:   '#24292F',
          secondary: '#656D76',
          muted:     '#8C959F',
          disabled:  '#B1BAC4',
          inverse:   '#FFFFFF',
        },

        // ── Risk level status ─────────────────────────────────────
        // Intentionally distinct from brand burgundy
        risk: {
          critical: '#B91C1C',   // red-700
          high:     '#C2410C',   // orange-700
          medium:   '#B45309',   // amber-700
          low:      '#15803D',   // green-700
        },

        // ── Semantic status ───────────────────────────────────────
        status: {
          error:   '#CF222E',
          warning: '#9A6700',
          success: '#1A7F37',
          info:    '#0969DA',
        },
      },

      // ── Typography ──────────────────────────────────────────────
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont',
               'Segoe UI', 'sans-serif'],
        mono: ['JetBrains Mono', 'Fira Code', 'Consolas', 'monospace'],
      },

      fontSize: {
        '2xs': ['0.625rem', { lineHeight: '0.875rem' }],  // 10px
        xs:    ['0.75rem',  { lineHeight: '1rem'    }],   // 12px
        sm:    ['0.8125rem',{ lineHeight: '1.25rem' }],   // 13px
        base:  ['0.875rem', { lineHeight: '1.5rem'  }],   // 14px
        md:    ['0.9375rem',{ lineHeight: '1.5rem'  }],   // 15px
        lg:    ['1rem',     { lineHeight: '1.5rem'  }],   // 16px
        xl:    ['1.125rem', { lineHeight: '1.75rem' }],   // 18px
        '2xl': ['1.25rem',  { lineHeight: '1.75rem' }],   // 20px
        '3xl': ['1.5rem',   { lineHeight: '2rem'    }],   // 24px
      },

      // ── Spacing ─────────────────────────────────────────────────
      spacing: {
        '4.5': '1.125rem',
        '13':  '3.25rem',
        '15':  '3.75rem',
        '18':  '4.5rem',
      },

      // ── Border radius ────────────────────────────────────────────
      borderRadius: {
        sm:  '0.25rem',   // 4px  — tags, badges
        md:  '0.375rem',  // 6px  — inputs, buttons
        lg:  '0.5rem',    // 8px  — cards, modals
        xl:  '0.75rem',   // 12px — dialogs
        '2xl': '1rem',    // 16px — rarely used
      },

      // ── Shadows ──────────────────────────────────────────────────
      boxShadow: {
        // Intentionally subtle — no heavy card shadows
        card:     '0 1px 3px 0 rgb(31 35 40 / 0.06), 0 1px 2px -1px rgb(31 35 40 / 0.04)',
        dropdown: '0 8px 24px 0 rgb(31 35 40 / 0.12), 0 1px 3px 0 rgb(31 35 40 / 0.06)',
        focus:    '0 0 0 3px rgb(143 45 53 / 0.15)',
      },

      // ── Transitions ──────────────────────────────────────────────
      transitionDuration: {
        fast:   '100ms',
        normal: '150ms',
        slow:   '250ms',
      },
    },
  },
  plugins: [],
};
