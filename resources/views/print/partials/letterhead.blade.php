{{-- Kop dokumen PMB --}}
<header class="flex items-center gap-5 border-b-[3px] border-double border-polmind-blue pb-4">
    <img src="{{ asset('images/logo-polmind.png') }}" alt="Politeknik Mitra Industri" class="h-16 w-auto">
    <div class="flex-1">
        <p class="text-xl font-black uppercase tracking-wide text-polmind-blue">Politeknik Mitra Industri</p>
        <p class="text-sm font-semibold text-slate-700">Panitia Penerimaan Mahasiswa Baru {{ $pmbYear?->name ? '· ' . $pmbYear->name : '' }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ config('pmb.campus_address') }}</p>
        <p class="text-xs text-slate-500">info@polmind.ac.id · WA {{ \App\Services\AdminWhatsApp::display() }}</p>
    </div>
</header>
