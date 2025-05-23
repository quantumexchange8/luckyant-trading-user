<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        @if (App::environment('production') || App::environment('staging'))
            <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
        @endif

        <title inertia>{{ trans('public.luckyant-trading') }}</title>

        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico')}}">
        <link rel="apple-touch-icon" href="{{ asset('favicon.ico')}}">

        <!-- Fonts -->
        <link
            href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap"
            rel="stylesheet"
        />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-950">
        @inertia
    </body>
</html>
