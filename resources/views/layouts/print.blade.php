<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - PMB Polmind</title>
    @include('partials.favicon')

    @vite(['resources/css/app.css'])

    <style>
        @page { size: A4; margin: 12mm; }

        @media print {
            body { background: #fff !important; }
            .print-sheet { box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: auto !important; min-height: 0 !important; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>

<body class="bg-slate-200 text-slate-900 antialiased">
    {{-- Toolbar, tidak ikut tercetak --}}
    <div class="sticky top-0 z-10 border-b border-slate-300 bg-white/95 px-4 py-3 backdrop-blur print:hidden">
        <div class="mx-auto flex max-w-[210mm] flex-wrap items-center justify-between gap-3">
            <a href="@yield('back_url', url()->previous())" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                ← Kembali
            </a>
            <div class="flex items-center gap-3">
                <p class="hidden text-xs text-slate-500 sm:block">Pilih "Simpan sebagai PDF" pada dialog cetak untuk menyimpan file.</p>
                <button type="button" onclick="window.print()" class="rounded-lg bg-polmind-blue px-5 py-2 text-sm font-bold text-white hover:brightness-110">
                    Cetak / Simpan PDF
                </button>
            </div>
        </div>
    </div>

    <main class="print-sheet mx-auto my-6 min-h-[297mm] w-[210mm] max-w-full bg-white p-[12mm] shadow-lg">
        @yield('content')
    </main>
</body>
</html>
