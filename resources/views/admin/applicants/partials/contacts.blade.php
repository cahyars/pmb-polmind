<div class="space-y-1.5 text-sm">
    <a href="mailto:{{ $applicant->email }}" class="flex items-start gap-1.5 text-polmind-blue hover:underline">
        <x-icon name="mail" class="mt-0.5 h-4 w-4 shrink-0" stroke-width="1.5" />
        {{-- Email boleh patah sebelum "@" agar tetap terbaca di kolom sempit. --}}
        <span class="min-w-0 [overflow-wrap:anywhere]">{{ \Illuminate\Support\Str::before($applicant->email, '@') }}<wbr>{{ '@' . \Illuminate\Support\Str::after($applicant->email, '@') }}</span>
    </a>
    @if($applicant->phone)
        <a href="{{ $row['whatsapp'] }}" target="_blank" rel="noopener" title="Chat WhatsApp" class="flex items-start gap-1.5 text-polmind-blue hover:underline">
            <x-icon name="phone" class="mt-0.5 h-4 w-4 shrink-0" stroke-width="1.5" />
            <span>{{ $applicant->phone }}</span>
        </a>
    @endif
</div>
