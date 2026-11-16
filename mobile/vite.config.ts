import legacy from '@vitejs/plugin-legacy';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    legacy()
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
    extensions: ['.mjs', '.js', '.ts', '.jsx', '.tsx', '.json', '.vue']
  },
  server: {
    host: '0.0.0.0',
    port: 8100,
    allowedHosts: ['mobile.localhost', 'localhost'],
    hmr: true,
    proxy: {
      '/api': {
        target: 'http://localhost:80',
        changeOrigin: true
      }
    }
  },
  test: {
    globals: true,
    environment: 'jsdom'
  }
});
