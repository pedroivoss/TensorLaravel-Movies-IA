/**
 * resources/js/app.jsx
 *
 * Entry point da aplicação React.
 * Monta o componente raiz no <div id="app"> do app.blade.php.
 *
 * Estrutura da aplicação:
 *   app.jsx
 *   └── Pages/RecommendationSystem.jsx   ← única página (SPA)
 *       ├── Components/UserCard.jsx
 *       └── Components/MovieCard.jsx
 *
 * Próxima etapa do curso:
 *   Quando o TF.js for integrado, o Web Worker será registrado aqui
 *   (ou dentro do RecommendationSystem) via:
 *     new Worker(new URL('./workers/modelTrainingWorker.js', import.meta.url))
 */
import React from 'react';
import { createRoot } from 'react-dom/client';
import { ConfigProvider, App as AntApp } from 'antd';
import ptBR from 'antd/locale/pt_BR';
import RecommendationSystem from './Pages/RecommendationSystem';

// Tema global do Ant Design — tokens de design do sistema
const theme = {
    token: {
        colorPrimary: '#1677ff',
        borderRadius: 8,
        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
    },
    components: {
        Card: { borderRadiusLG: 8 },
        Button: { borderRadius: 6 },
    },
};

const container = document.getElementById('app');
const root = createRoot(container);

root.render(
    <React.StrictMode>
        {/* ConfigProvider: aplica tema e locale pt-BR a todos os componentes Ant Design */}
        <ConfigProvider theme={theme} locale={ptBR}>
            {/* AntApp: habilita message.success() / notification.info() globalmente */}
            <AntApp>
                <RecommendationSystem />
            </AntApp>
        </ConfigProvider>
    </React.StrictMode>
);
