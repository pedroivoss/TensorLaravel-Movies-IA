import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

/**
 * Vite Config — TensorLaravel Movies IA
 *
 * Dois entry points:
 *   - resources/css/app.css  → estilos globais mínimos
 *   - resources/js/app.jsx   → React root (monta o SPA de recomendação)
 *
 * Por que hmr.host = 'localhost'?
 * O Laravel roda em :8000 e o Vite em :5173. O @vitejs/plugin-react injeta
 * o React Fast Refresh (HMR) carregando o preamble de http://[host]:5173/@react-refresh.
 * Sem hmr.host explícito, o Vite tenta usar o IP de bind (0.0.0.0), o que causa
 * o erro "RefreshRuntime.register is not a function" no browser.
 * Fixar para 'localhost' garante que o preamble é carregado corretamente.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.jsx',
            ],
            refresh: true,
        }),
        react(),
    ],

    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },

    server: {
        port: 5173,
        strictPort: true,
        /*
         * hmr.host garante que o React Fast Refresh preamble seja carregado
         * de localhost:5173 — necessário quando Laravel (:8000) e Vite (:5173)
         * rodam em portas diferentes no mesmo ambiente local.
         */
        hmr: {
            host: 'localhost',
        },
    },
});
