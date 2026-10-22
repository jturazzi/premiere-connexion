<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @routes
        <script>window.__SENTRY__ = {{ \Illuminate\Support\Js::from(['dsn' => config('sentry.js_dsn'), 'environment' => config('sentry.environment') ?? app()->environment(), 'release' => config('sentry.release'), 'tracesSampleRate' => config('sentry.traces_sample_rate')]) }};</script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
