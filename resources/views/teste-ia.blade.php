<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Tensorflow.js no Blade</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        body { background-color: #f8f9fa; }
        #log-container { height: 150px; overflow-y: auto; font-family: monospace; font-size: 0.85rem; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">IA de Recomendação de Filmes BLADE</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Clique em treinar primeiro. A IA vai aprender com base no banco de dados.</p>

                    <button id="btn-treinar" class="btn btn-success w-100 mb-3">
                        1. Treinar Modelo
                    </button>
                    <hr>
                    <p class="text-muted small">Depois de treinado, insira sua idade e gênero para receber uma recomendação de filme.</p>
                    <div class="input-group mb-3">
                        <input type="number" id="input-valor" class="form-control" placeholder="Digite sua idade">
                    </div>

                    <div class="input-group mb-3">
                        <select id="select-genre" class="form-control" multiple="multiple">
                            <option disabled>Selecione seu gênero</option>
                            @foreach($genres as $genre_name)
                                <option value="{{ $genre_name }}">{{ $genre_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button id="btn-prever" class="btn btn-primary w-100 mb-3" disabled>
                        2. Fazer Predição
                    </button>

                    <div class="alert alert-info d-none" id="resultado-box">
                        <strong>Predição:</strong> <span id="resultado-texto"></span>
                    </div>

                    <label class="form-label small">Log de Treinamento:</label>
                    <div id="log-container" class="bg-dark text-success p-2 rounded border">
                        Aguardando início...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tensorflow.js -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest"></script>
<!-- Core do TensorFlow.js -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest"></script>
<!-- Biblioteca de Visualização (Opcional, mas resolve o erro de tfvis) -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-vis@latest"></script>

<!-- jQuery (necessário para Select2 e SweetAlert2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script>

    $(document).ready(function() {
        //carrega o select2
        $('#select-genre').select2({
            placeholder: "Selecione seu gênero",
            allowClear: true
        });

        //se idade e genero estiverem preenchidos, habilita o botão de prever
        $('#input-valor, #select-genre').on('change', function() {
            const idadePreenchida = $('#input-valor').val().trim() !== '' && !isNaN($('#input-valor').val()) && parseInt($('#input-valor').val()) > 0;
            const generoSelecionado = $('#select-genre').val() && $('#select-genre').val().length > 0;
            $('#btn-prever').prop('disabled', !(idadePreenchida && generoSelecionado));
        });

        $(document).on('click', '#btn-treinar', async function() {
            // Simula o processo de treinamento
            console.log("Iniciou...");

            await treinarModelo(); // O código PARA aqui e espera o resolve()

            console.log("Só vou aparecer DEPOIS das 100 épocas!");
        });


        $('#btn-prever').on('click', function() {
            const idade = $('#input-valor').val();
            const generos = $('#select-genre').val();

            Swal.fire({
                title: 'Predição de Filme',
                html: `<p>Idade: <strong>${idade}</strong></p><p>Gêneros: <strong>${generos.join(', ')}</strong></p><p><em>A IA recomendaria um filme baseado nesses dados.</em></p>`,
                icon: 'info'
            });
        });

    });
</script>


{{-- pegar o timestamp apra sempre pegar versao atualizada do js --}}
<script src="{{ asset('assets/js/treinamento.js') }}?v={{ time() }}"></script>

{{--
<script>
    let model;

    const logContainer = document.getElementById('log-container');
    const btnTreinar = document.getElementById('btn-treinar');
    const btnPrever = document.getElementById('btn-prever');
    const inputValor = document.getElementById('input-valor');
    const resBox = document.getElementById('resultado-box');
    const resTexto = document.getElementById('resultado-texto');

    function log(msg) {
        logContainer.innerHTML += `> ${msg}<br>`;
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    async function treinarModelo() {
        btnTreinar.disabled = true;
        btnTreinar.innerText = "Treinando...";
        log("Iniciando criação do modelo...");

        // 1. Definir a arquitetura (1 camada, 1 neurônio)
        model = tf.sequential();
        model.add(tf.layers.dense({units: 1, inputShape: [1]}));

        // 2. Compilar
        model.compile({loss: 'meanSquaredError', optimizer: 'sgd'});

        // 3. Dados de exemplo (x: entrada, y: saída)
        const xs = tf.tensor2d([1, 2, 3, 4, 5, 6], [6, 1]);
        const ys = tf.tensor2d([10, 20, 30, 40, 50, 60], [6, 1]);

        log("Treinando por 100 épocas...");

        // 4. Treinar
        await model.fit(xs, ys, {
            epochs: 100,
            callbacks: {
                onEpochEnd: (epoch, logs) => {
                    if (epoch % 20 === 0) log(`Época ${epoch}: Erro = ${logs.loss.toFixed(4)}`);
                }
            }
        });

        log("Treino finalizado!");
        btnTreinar.innerText = "Treinado!";
        btnPrever.disabled = false;
        inputValor.disabled = false;
    }

    function fazerPredicao() {
        const valor = parseFloat(inputValor.value);
        if (isNaN(valor)) return alert("Digite um número");

        // tf.tidy limpa a memória automaticamente
        tf.tidy(() => {
            const inputTensor = tf.tensor2d([valor], [1, 1]);
            const output = model.predict(inputTensor);
            const resultado = output.dataSync()[0];

            resTexto.innerText = resultado.toFixed(2);
            resBox.classList.remove('d-none');
        });
    }

    btnTreinar.addEventListener('click', treinarModelo);
    btnPrever.addEventListener('click', fazerPredicao);
</script>
--}}

</body>
</html>
