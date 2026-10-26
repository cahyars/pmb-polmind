{{-- Modal pratinjau berkas. Buka dengan: $store.preview.show(url, namaFile) --}}
<div x-data
     x-show="$store.preview.open"
     x-cloak
     x-transition.opacity
     @keydown.escape.window="$store.preview.close()"
     @click.self="$store.preview.close()"
     class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/70 p-4"
     role="dialog"
     aria-modal="true">
    <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4">
            <p class="truncate text-sm font-black text-polmind-blue" x-text="$store.preview.name || 'Pratinjau Berkas'"></p>

            <div class="flex shrink-0 items-center gap-2">
                <a :href="$store.preview.url"
                   download
                   class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                    Unduh
                </a>
                <button type="button"
                        @click="$store.preview.close()"
                        class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-black text-slate-700 transition hover:bg-slate-200"
                        aria-label="Tutup pratinjau">
                    ✕ Tutup
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-auto bg-slate-100 p-3">
            <template x-if="$store.preview.open && $store.preview.type === 'image'">
                <img :src="$store.preview.url" :alt="$store.preview.name" class="mx-auto max-h-[78vh] rounded-xl object-contain">
            </template>

            <template x-if="$store.preview.open && $store.preview.type === 'pdf'">
                <iframe :src="$store.preview.url" class="h-[78vh] w-full rounded-xl bg-white" title="Pratinjau PDF"></iframe>
            </template>

            <template x-if="$store.preview.open && $store.preview.type === 'other'">
                <div class="p-10 text-center text-sm font-semibold text-slate-600">
                    Pratinjau tidak tersedia untuk jenis file ini. Gunakan tombol Unduh.
                </div>
            </template>
        </div>
    </div>
</div>
