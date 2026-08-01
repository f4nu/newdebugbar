import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

export default defineConfig({
  base: '/__new-debug-bar/assets/',
  plugins: [tailwindcss()],
  build: {
    emptyOutDir: true,
    outDir: 'dist',
    lib: {
      entry: 'resources/js/new-debug-bar.js',
      name: 'NewDebugBarAssets',
      formats: ['iife'],
      fileName: () => 'new-debug-bar.js',
      cssFileName: 'new-debug-bar',
    },
  },
});
