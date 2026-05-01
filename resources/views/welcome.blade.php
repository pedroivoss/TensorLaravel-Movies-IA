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
            max-width: 900px;
            width: 100%;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }
        .card-body {
            padding: 30px;
        }
        .card-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        .card-text {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .badge-status {
            display: inline-block;
            background-color: #ffc107;
            color: #333;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .btn-project {
            background-color: #667eea;
            border: none;
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
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
            margin-bottom: 50px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container-projects">
        <h1>🎬 TensorLaravel Movies IA</h1>

        <div class="row g-4">
            <!-- Card Blade Movie IA -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">🔥 Blade Movie IA</h5>
                        <p class="card-text">
                            Sistema de recomendação de filmes com inteligência artificial integrado com Laravel e Blade.
                            Descubra filmes baseado em IA!
                        </p>
                        <a href="http://localhost:3000/blade-movie-ia" class="btn-project">
                            Acessar Projeto →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card React Movie IA -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <span class="badge-status">⚙️ Em Andamento!</span>
                        <h5 class="card-title">⚛️ React Movie IA</h5>
                        <p class="card-text">
                            Versão moderna em React do sistema de recomendação de filmes com IA.
                            Interface interativa e responsiva para melhor experiência do usuário.
                        </p>
                        <a href="http://localhost:3000/react-movie-ia" class="btn-project">
                            Acessar Projeto →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
