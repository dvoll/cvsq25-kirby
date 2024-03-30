import defaultTheme from 'tailwindcss/defaultTheme'
import { Config } from 'tailwindcss'

export default {
  content: ['./site/**/*.php', './site/**/*.yml', './public/assets/**/*.svg', './src/**/*.ts'],
  future: {
    hoverOnlyWhenSupported: true
  },
  theme: {
    fontFamily: {
      sans: ['Inter', ...defaultTheme.fontFamily.sans]
    },
    screens: {
      '2xl': { max: '96rem' },
      xl: { max: '80rem' },
      lg: { max: '62rem' },
      md: { max: '44rem' },
      sm: { max: '29.5rem' },
      xs: { max: '22rem' }
    },
    container: {
      center: true
    },
    extend: {}
  },
  plugins: []
} satisfies Config
