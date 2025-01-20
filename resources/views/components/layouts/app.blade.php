<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />

        <meta name="application-name" content="{{ config('app.name') }}" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <title>{{ config('app.name') }}</title>

        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>

        @filamentStyles
        @vite('resources/css/app.css')
    </head>

    <body class="antialiased">
        {{ $slot }}

        @livewire('notifications')

        @filamentScripts
        @vite('resources/js/app.js')
    </body>
    <script>
        function riskColorGradient(value) {
            if (value < 0 || value > 25) {
                return '#000000'; // Default to black for invalid values
            }

            let red = 0;
            let green = 0;

            if (value <= 12) {
                green = 255;
                red = Math.round(255 * (value / 12));
            } else {
                red = 255;
                green = Math.round(255 * ((25 - value) / 13));
            }

            return '#' + red.toString(16).padStart(2, '0') + green.toString(16).padStart(2, '0') + '00';
        }
    </script>
</html>
