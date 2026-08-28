<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $seo['title'] ?? 'Wedding Maulia & Kahfi' }}</title>

        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $seo['title'] ?? 'Wedding Maulia & Kahfi' }}">
        <meta property="og:description" content="{{ $seo['description'] ?? 'Anda diundang untuk hadir di acara pernikahan kami.' }}">
        <meta property="og:image" content="{{ $seo['image'] ?? url('/mk.png') }}">
        <meta property="og:url" content="{{ $seo['url'] ?? url()->current() }}">
        <meta name="twitter:card" content="summary_large_image">

        <meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] ?? 'Wedding Maulia & Kahfi' }}">
<meta name="twitter:description" content="{{ $seo['description'] ?? 'Anda diundang untuk hadir di acara pernikahan kami.' }}">
<meta name="twitter:image" content="{{ $seo['image'] ?? url('/mk.png') }}">

        @vite(['resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>