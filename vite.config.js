import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

export default defineConfig({
  base: '/__newdebugbar/assets/',
  plugins: [tailwindcss()],
  build: {
    emptyOutDir: true,
    outDir: 'dist',
    lib: {
      entry: 'resources/js/newdebugbar.js',
      name: 'NewDebugBarAssets',
      formats: ['iife'],
      fileName: () => 'newdebugbar.js',
      cssFileName: 'newdebugbar',
    },
  },
});
