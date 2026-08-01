import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [tailwindcss()],
  build: {
    emptyOutDir: true,
    manifest: true,
    outDir: 'dist',
    rollupOptions: {
      input: {
        'new-debug-bar': 'resources/js/new-debug-bar.js',
      },
    },
  },
});
