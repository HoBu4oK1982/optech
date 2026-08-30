import type { Config } from 'tailwindcss'

const config: Config = {
  content: [
    './src/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      colors: {
        primary: '#4eb0fb',
        dark: '#121123',
      },
    },
  },
  plugins: [],
}
export default config
