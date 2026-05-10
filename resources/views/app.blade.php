<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Movie Recommendation AI</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body>
    <div id="app"></div>

    {{-- TF.js + IA assets — carregados APÓS o React montar --}}
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-vis/dist/tfjs-vis.umd.min.js"></script>
    <script src="{{ asset('assets/js/TFVisorView.js') }}?v={{ filemtime(public_path('assets/js/TFVisorView.js')) }}"></script>

    <script>
        /* Globals esperados pelo modelTrainingWorker.js */
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        /* Stub de SweetAlert2 para a versão React (notificações via React) */
        window.Swal = window.Swal ?? {
            fire: (title, text, type) => console.log(`[${type?.toUpperCase()}] ${title}: ${text}`)
        };
    </script>

    <script src="{{ asset('assets/js/workers/modelTrainingWorker.js') }}?v={{ filemtime(public_path('assets/js/workers/modelTrainingWorker.js')) }}"></script>

    <script>
        /* ── loadModelFromDatabase: compartilhado com a versão React ─────────
           Recebe movies e users já carregados pelo React para montar o contexto.
        ────────────────────────────────────────────────────────────────────── */
        window.loadModelFromDatabase = async function (movies, users) {
            try {
                const res = await fetch('/api/ai-models/load', {
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
                }).then(r => r.json());

                if (!res || !res.weights_base64 || !res.model_topology) {
                    console.log('ℹ️ Nenhum modelo salvo no banco.');
                    return;
                }

                const weightData = Uint8Array.from(
                    atob(res.weights_base64), c => c.charCodeAt(0)
                ).buffer;

                const modelTopology = typeof res.model_topology === 'string'
                    ? JSON.parse(res.model_topology)
                    : res.model_topology;

                const weightSpecs = modelTopology.weightsManifest[0].weights;

                const loadedModel = await tf.loadLayersModel(tf.io.fromMemory({
                    modelTopology,
                    weightSpecs,
                    weightData,
                }));

                window._model = loadedModel;
                window._globalCtx = (typeof makeContext === 'function')
                    ? makeContext(movies, users)
                    : {};

                console.log('🚀 Modelo carregado do banco e pronto!');
            } catch (err) {
                console.error('❌ Falha ao carregar modelo:', err);
            }
        };
    </script>
</body>
</html>
