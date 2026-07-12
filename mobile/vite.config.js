import legacy from '@vitejs/plugin-legacy';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import { defineConfig } from 'vite';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    legacy()
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 8100,
    allowedHosts: ['mobile.localhost', 'localhost'],
    hmr: {
      clientPort: 80,
    },
  },
  test: {
    globals: true,
    environment: 'jsdom'
  }
});
