<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>

    <meta name="application-name" content="{{ config('app.name') }}"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link href="https://fonts.googleapis.com/css2?family=Bruno+Ace+SC:wght@400;700&amp;display=swap" rel="stylesheet">

    <title>{{ config('app.name') }}</title>

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
        <div class="mt-12">
            <p>OpenGRC is a cyber Governance, Risk, and Compliance web application intended for use
                by small businesses and teams. This is not intended to replace large-scale GRC
                Platforms. Rather, the intention is to provide a resource for those who can't manage
                the price tag on an enterprise GRC tool.</p>
            <p class="mt-6">
            <ul class="ps-5 mt-2 space-y-1 list-disc list-inside">
                <li>Simple interface designed to get up and running with very little training</li>
                <li>Quick imports of common security frameworks</li>
                <li>Ability to connect Standards, Controls, and your actual Implementations</li>
                <li>Ability to perform audits for internal and external assessments</li>
                <li>Report generation capability to create deliverables for auditors</li>
                <li>Intuitive dashboard to display your progress</li>
            </ul>
            </p>
            <p class="mt-6">Above all, OpenGRC is written to solve cyber compliance headaches that tend to be
                caused by complex enterprise solutions. It doesn't have to be that hard!</p>
        </div>
        <div class="text-center mt-12">
            <a href="/app" class="bg-primary-500 p-3 rounded" id="login-button">Login</a>
        </div>

    </div>
</div>

@livewire('notifications')

@filamentScripts
@vite('resources/js/app.js')
</body>
</html>









