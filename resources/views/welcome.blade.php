<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TensorLaravel Movies IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container-projects {
            max-width: 520px;
            width: 100%;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }
        .card-body {
            padding: 36px;
        }
        .card-title {
            font-size: 1.6rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        .card-text {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .btn-project {
            background-color: #667eea;
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1rem;
            transition: background-color 0.3s ease;
            display: inline-block;
        }
        .btn-project:hover {
            background-color: #764ba2;
            color: white;
            text-decoration: none;
        }
        h1 {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-projects">
        <h1>🎬 TensorLaravel Movies IA</h1>

        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">🔥 Movie Recommendation AI</h5>
                <p class="card-text">
                    Sistema de recomendação de filmes com inteligência artificial treinada
                    diretamente no browser via TensorFlow.js.
                    Avalie filmes, treine o modelo e receba sugestões personalizadas.
                </p>
                <a href="{{ route('laravel-movie-ia') }}" class="btn-project">
                    Acessar Projeto →
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
