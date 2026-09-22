import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [vue(), tailwindcss()],
    // public/ is served by PHP, so Vite must not copy it into the build output.
    publicDir: false,
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        // The page is served by PHP on port 8000, so asset URLs need the full Vite origin.
        origin: 'http://localhost:5173',
        hmr: { host: 'localhost', port: 5173 },
        // Bind mounts in Docker do not always emit file events.
        watch: { usePolling: true, interval: 300 },
    },
    build: {
        manifest: true,
        outDir: 'public/build',
        emptyOutDir: true,
        rollupOptions: {
            input: 'resources/FrontEnd/app.js',
        },
    },
});
