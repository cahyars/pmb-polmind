<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PMB Politeknik Mitra Industri')</title>

    <meta name="description" content="@yield('meta_description', 'Sistem Pendaftaran Mahasiswa Baru Politeknik Mitra Industri')">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-polmind-bg text-slate-900 antialiased">

    <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ url('/') }}" class="text-xl font-bold tracking-tight text-polmind-blue">
                Polmind
            </a>

            <nav class="hidden items-center gap-8 text-sm font-medium text-slate-600 md:flex">
                <a href="{{ url('/') }}" class="text-polmind-blue hover:text-polmind-blue-dark">Beranda</a>
                <a href="#program-studi" class="hover:text-polmind-blue">Program Studi</a>
                <a href="#biaya" class="hover:text-polmind-blue">Biaya</a>
                <a href="#faq" class="hover:text-polmind-blue">FAQ</a>
            </nav>

            <div class="hidden items-center gap-3 md:flex">
                <a href="{{ url('/login') }}" class="text-sm font-semibold text-polmind-blue hover:text-polmind-blue-dark">
                    Masuk
                </a>
                <a href="{{ url('/register') }}" 
                   class="rounded-xl bg-polmind-yellow px-5 py-2.5 text-sm font-bold text-polmind-blue-dark shadow-sm transition hover:brightness-95">
                    Daftar
                </a>
            </div>

            <button type="button"
                    class="inline-flex rounded-lg border border-slate-200 p-2 md:hidden"
                    x-data
                    @click="$dispatch('toggle-mobile-menu')">
                <span class="sr-only">Buka menu</span>
                ☰
            </button>
        </div>

        <div x-data="{ open: false }"
             x-on:toggle-mobile-menu.window="open = !open"
             x-show="open"
             x-transition
             class="border-t border-slate-200 bg-white md:hidden">
            <div class="space-y-1 px-4 py-4">
                <a href="{{ url('/') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-polmind-blue">Beranda</a>
                <a href="#program-studi" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700">Program Studi</a>
                <a href="#biaya" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700">Biaya</a>
                <a href="#faq" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700">FAQ</a>
                <div class="mt-3 flex gap-2">
                    <a href="{{ url('/login') }}" class="flex-1 rounded-xl border border-polmind-blue px-4 py-2 text-center text-sm font-bold text-polmind-blue">
                        Masuk
                    </a>
                    <a href="{{ url('/register') }}" class="flex-1 rounded-xl bg-polmind-yellow px-4 py-2 text-center text-sm font-bold text-polmind-blue-dark">
                        Daftar
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-3 lg:px-8">
            <div>
                <h2 class="text-lg font-bold text-polmind-blue">Polmind</h2>
                <p class="mt-3 max-w-sm text-sm leading-6 text-slate-600">
                    Politeknik Mitra Industri adalah institusi pendidikan vokasi yang berfokus pada pengembangan talenta industri.
                </p>
            </div>

            <div>
                <h3 class="font-semibold text-slate-900">Tautan Cepat</h3>
                <div class="mt-3 space-y-2 text-sm text-slate-600">
                    <a href="#" class="block hover:text-polmind-blue">Tentang Kami</a>
                    <a href="#" class="block hover:text-polmind-blue">Program Studi</a>
                    <a href="#" class="block hover:text-polmind-blue">Informasi Beasiswa</a>
                </div>
            </div>

            <div>
                <h3 class="font-semibold text-slate-900">Kontak Kami</h3>
                <div class="mt-3 space-y-2 text-sm text-slate-600">
                    <p>Kawasan Industri MM2100, Bekasi</p>
                    <p>+62 812-3456-7890</p>
                    <p>pmb@polmind.ac.id</p>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200 py-5">
            <div class="mx-auto flex max-w-7xl flex-col justify-between gap-3 px-4 text-xs text-slate-500 sm:px-6 md:flex-row lg:px-8">
                <p>© {{ date('Y') }} Politeknik Mitra Industri. All Rights Reserved.</p>
                <div class="flex gap-5">
                    <a href="#" class="hover:text-polmind-blue">Kebijakan Privasi</a>
                    <a href="#" class="hover:text-polmind-blue">Syarat & Ketentuan</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>