<div class="flex shrink-0 justify-center gap-1.5">
    <a href="{{ $row['url'] }}"
       title="Lihat detail"
       class="flex h-8 w-8 items-center justify-center rounded-md bg-sky-500 text-white transition hover:bg-sky-600">
        <span class="sr-only">Lihat detail {{ $applicant->full_name }}</span>
        <x-icon name="eye" class="h-4 w-4" stroke-width="1.8" />
    </a>

    @if($row['deletable'])
        <form action="{{ route('admin.applicants.destroy', $applicant->registration_number) }}"
              method="POST"
              x-data
              @submit.prevent="window.Swal.fire({
                  icon: 'warning',
                  title: 'Hapus data camaba?',
                  text: @js($applicant->full_name . ' (' . $applicant->registration_number . ') akan dihapus dari daftar camaba.'),
                  showCancelButton: true,
                  confirmButtonText: 'Ya, hapus',
                  cancelButtonText: 'Batal',
                  confirmButtonColor: '#dc2626',
              }).then((result) => result.isConfirmed && $el.submit())">
            @csrf
            @method('DELETE')
            <button type="submit"
                    title="Hapus"
                    class="flex h-8 w-8 items-center justify-center rounded-md bg-red-500 text-white transition hover:bg-red-600">
                <span class="sr-only">Hapus {{ $applicant->full_name }}</span>
                <x-icon name="trash" class="h-4 w-4" stroke-width="1.8" />
            </button>
        </form>
    @else
        <span title="Tidak dapat dihapus: pembayaran formulir sudah diproses"
              class="flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-md bg-slate-200 text-slate-400">
            <span class="sr-only">Tidak dapat dihapus</span>
            <x-icon name="trash" class="h-4 w-4" stroke-width="1.8" />
        </span>
    @endif
</div>
