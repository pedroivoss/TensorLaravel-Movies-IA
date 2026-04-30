<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Movie Recommendation AI') }}</title>

    {{--
        Vite injeta automaticamente o CSS do Ant Design e o bundle React.
        Durante o desenvolvimento (npm run dev), usa HMR.
        Em produção (npm run build), usa os arquivos compilados em /public/build/.
    --}}
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body>

    {{--
        Ponto de montagem do React.
        O app.jsx encontra este elemento e renderiza toda a aplicação aqui.
        Não há lógica de template Blade nesta página — tudo é React.
    --}}
    <div id="app"></div>

</body>
</html>
