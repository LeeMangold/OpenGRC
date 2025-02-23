<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>

    <meta name="application-name" content="OpenGRC"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace+SC:wght@400;700&amp;display=swap" rel="stylesheet">

    <title>OpenGRC - Error 401 (Access Denied)</title>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @filamentStyles
    @vite('resources/css/app.css')
</head>

<body>

<div class="mt-8 flex justify-center">
    <div name="content" class="sm:w-1 md:w-1/2">
        <div class="flex justify-center p-6">
            <img src="{{ asset('/img/logo.png') }}" width="30%" alt="OpenGRC Logo">
        </div>
        <h1 class="mb-4 text-4xl font-extrabold leading-none tracking-tight text-gray-900 md:text-3xl lg:text-3xl dark:text-white text-center"
            style="font-family: 'Bruno Ace SC', sans-serif;">OpenGRC</h1>

        <div class="text-center mt-12">
            <p class="bg-grcblue-400 text-white p-3 rounded ">{{ $exception->getMessage() }}</p>
        </div>

    </div>
</div>

@livewire('notifications')

@filamentScripts
@vite('resources/js/app.js')
</body>
</html>









