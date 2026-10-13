import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 5173,
    open: true,
    // Same-origin API in development, as Nginx does in production (start it with `npm run api`).
    proxy: {
      '/api': 'http://127.0.0.1:8000',
    },
  },
})
