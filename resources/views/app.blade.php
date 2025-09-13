<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Delego') }}</title>
        <meta name="description" content="Delego — assisted delegation for teams. Missions flow down, progress flows up.">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <meta name="theme-color" content="#faf8f5">

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
