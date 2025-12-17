import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        styles: resolve(__dirname, 'src/scss/main.scss'),
        main: resolve(__dirname, 'src/js/main.js'),
      },
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: (assetInfo) => {
          // Rename CSS to main.css for Drupal compatibility
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'main.css';
          }
          return '[name].[ext]';
        },
      },
    },
    cssCodeSplit: false,
    sourcemap: true,
  },
  css: {
    preprocessorOptions: {
      scss: {
        // Use the modern API for Sass
        api: 'modern-compiler',
      },
    },
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    host: true,
    port: 5173,
    strictPort: true,
    origin: 'http://localhost:5173',
  },
});

