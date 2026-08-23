<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 overflow-x-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>JRV CRM - Multi-Sector SaaS Engine</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- JRV CRM Official Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='25' fill='%23dc2626'/><text x='50%' y='68%' font-size='60' text-anchor='middle' fill='white' font-family='sans-serif' font-weight='900'>⚡</text></svg>">

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        html, body, #app {
            max-width: 100vw;
            overflow-x: hidden;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        *, *::before, *::after {
            box-sizing: border-box;
        }
    </style>
    @vite(['resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full bg-slate-50 text-slate-900 font-sans antialiased overflow-x-hidden w-full max-w-full">
    @inertia
</body>
</html>
