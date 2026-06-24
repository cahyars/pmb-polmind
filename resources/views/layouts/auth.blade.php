<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Masuk PMB Polmind')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-polmind-bg text-slate-900 antialiased">

    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="text-xl font-bold text-polmind-blue">
                Polmind
            </a>

            <a href="#" class="text-sm font-medium text-slate-600 hover:text-polmind-blue">
                Pusat Bantuan
            </a>
        </div>
    </header>

    <main class="flex min-h-[calc(100vh-85px)] items-center justify-center px-4 py-10">
        @yield('content')
    </main>

</body>
</html>