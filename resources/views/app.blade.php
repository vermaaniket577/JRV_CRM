<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Multi-Tenant SaaS CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full bg-slate-50 text-slate-900 font-sans antialiased">
    @inertia
</body>
</html>
