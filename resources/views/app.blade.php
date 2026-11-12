<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- P2-T5: SEO values come from InvitationPresenter per invitation.
             Generic fallbacks only: no client name or logo is hardcoded. --}}
        <title>{{ $seo['title'] ?? 'Undangan Pernikahan' }}</title>
        <meta name="description" content="{{ $seo['description'] ?? 'Undangan pernikahan digital.' }}">

        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $seo['title'] ?? 'Undangan Pernikahan' }}">
        <meta property="og:description" content="{{ $seo['description'] ?? 'Undangan pernikahan digital.' }}">
        @if (! empty($seo['image']))
            <meta property="og:image" content="{{ $seo['image'] }}">
        @endif
        <meta property="og:url" content="{{ $seo['url'] ?? url()->current() }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seo['title'] ?? 'Undangan Pernikahan' }}">
        <meta name="twitter:description" content="{{ $seo['description'] ?? 'Undangan pernikahan digital.' }}">
        @if (! empty($seo['image']))
            <meta name="twitter:image" content="{{ $seo['image'] }}">
        @endif

        @vite(['resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
