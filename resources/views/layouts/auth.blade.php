<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Masuk PMB Polmind')</title>
    @include('partials.favicon')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-polmind-bg text-slate-900 antialiased">

    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="flex items-center">
                <img src="{{ asset('images/logo-polmind.png') }}" alt="Politeknik Mitra Industri" class="h-12 w-auto">
            </a>

            <a href="#" class="text-sm font-medium text-slate-600 hover:text-polmind-blue">
                Pusat Bantuan
            </a>
        </div>
    </header>

    <main class="flex min-h-[calc(100vh-85px)] items-center justify-center px-4 py-10">
        @yield('content')
    </main>

    @include('partials.whatsapp-float')

</body>
</html>