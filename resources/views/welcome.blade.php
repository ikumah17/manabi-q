<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <main class="container py-5 text-center">
        <h1 class="h3 fw-bold">まなびQ</h1>
        <p class="text-secondary mb-1">準備中です（現在時刻：{{ now()->format('Y/m/d H:i') }}）</p>
        <button type="button" class="btn btn-primary mt-3">Bootstrap 動作確認</button>
    </main>
</body>
</html>
