<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>JRV CRM - Multi-Sector SaaS Engine</title>

    <!-- JRV CRM Official Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='25' fill='%23dc2626'/><text x='50%' y='68%' font-size='60' text-anchor='middle' fill='white' font-family='sans-serif' font-weight='900'>⚡</text></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full bg-slate-50 text-slate-900 font-sans antialiased">
    @inertia
</body>
</html>
