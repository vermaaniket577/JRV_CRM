<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>JRV CRM - Multi-Sector SaaS Platform</title>

    <!-- Brand Favicon with Cache Busting -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">
    <link rel="alternate icon" type="image/x-icon" href="/favicon.svg?v=2">
    <link rel="shortcut icon" href="/favicon.svg?v=2">

    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full bg-slate-50 text-slate-900 font-sans antialiased">
    @inertia
</body>
</html>
