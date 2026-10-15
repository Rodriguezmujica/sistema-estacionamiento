import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    tailwindcss(),
    react()
  ],
  base: './', // Crucial para que Electron cargue archivos con protocolo file://
  server: {
    port: 5173,
    strictPort: true
  },
  build: {
    outDir: 'dist',
    emptyOutDir: true,
    assetsDir: 'assets'
  },
  resolve: {
    alias: {
      '@': path.resolve(import.meta.dirname, './src')
    }
  }
});
