<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>f</title>

    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
</head>
<body>
<style>
    .main-container {
        max-height: 100vh; /* или другая высота */
        font-size: 0.80rem !important;
        overflow-y: auto;
    }
</style>

<div id="app" class="main-container">
    <appComponent></appComponent>
</div>

<script src="{{ mix('js/app.js') }}" defer></script>
</body>
</html>

