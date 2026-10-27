<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionWave;
use App\Models\SelectionComponent;
use App\Models\SelectionSchedule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Master Data seleksi: komponen tes, jadwal tes per gelombang, PIC, serta
 * tanggal pengumuman dan daftar ulang gelombang.
 */
class SelectionScheduleController extends Controller
{
    public function storeComponent(Request $request)
    {
        $validated = $this->validateComponent($request);

        SelectionComponent::create($validated + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Komponen tes berhasil ditambahkan.');
    }

    public function updateComponent(Request $request, SelectionComponent $selectionComponent)
    {
        $validated = $this->validateComponent($request, $selectionComponent);

        $selectionComponent->update($validated + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Komponen tes berhasil diperbarui.');
    }

    public function toggleComponent(SelectionComponent $selectionComponent)
    {
        $selectionComponent->update(['is_active' => ! $selectionComponent->is_active]);

        return back()->with('success', 'Status komponen tes berhasil diubah.');
    }

    public function storeSchedule(Request $request)
    {
        SelectionSchedule::create($this->validateSchedule($request));

        return back()->with('success', 'Jadwal tes berhasil ditambahkan.');
    }

    public function updateSchedule(Request $request, SelectionSchedule $selectionSchedule)
    {
        $selectionSchedule->update($this->validateSchedule($request));

        return back()->with('success', 'Jadwal tes berhasil diperbarui.');
    }

    public function destroySchedule(SelectionSchedule $selectionSchedule)
    {
        $selectionSchedule->delete();

        return back()->with('success', 'Jadwal tes berhasil dihapus.');
    }

    /**
     * Tanggal pengumuman hasil seleksi dan periode daftar ulang satu gelombang.
     */
    public function updateTimeline(Request $request, AdmissionWave $admissionWave)
    {
        $validated = $request->validate([
            'announcement_date' => ['nullable', 'date'],
            're_registration_start_date' => ['nullable', 'date', 'required_with:re_registration_end_date'],
            're_registration_end_date' => ['nullable', 'date', 'after_or_equal:re_registration_start_date'],
        ], [
            're_registration_start_date.required_with' => 'Isi tanggal mulai daftar ulang.',
            're_registration_end_date.after_or_equal' => 'Tanggal akhir daftar ulang tidak boleh sebelum tanggal mulai.',
        ]);

        $admissionWave->update($validated);

        return back()->with('success', 'Jadwal pengumuman dan daftar ulang ' . $admissionWave->name . ' berhasil disimpan.');
    }

    /**
     * Simpan PIC (nama dosen) untuk semua jadwal tes satu gelombang sekaligus.
     */
    public function updatePics(Request $request, AdmissionWave $admissionWave)
    {
        $validated = $request->validate([
            'pic_name' => ['array'],
            'pic_name.*' => ['nullable', 'string', 'max:255'],
        ]);

        $schedules = $admissionWave->selectionSchedules()->get()->keyBy('id');

        foreach ($validated['pic_name'] ?? [] as $scheduleId => $picName) {
            $schedules->get($scheduleId)?->update(['pic_name' => filled($picName) ? trim($picName) : null]);
        }

        return back()->with('success', 'PIC tes seleksi ' . $admissionWave->name . ' berhasil disimpan.');
    }

    private function validateComponent(Request $request, ?SelectionComponent $component = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('selection_components', 'code')->ignore($component?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        return $validated;
    }

    private function validateSchedule(Request $request): array
    {
        $validated = $request->validate([
            // Gelombang harus milik Tahun PMB yang sedang dipilih admin (scope global).
            'admission_wave_id' => ['required', Rule::in(AdmissionWave::pluck('id')->all())],
            'selection_component_id' => ['required', Rule::exists('selection_components', 'id')],
            'test_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
            'selection_component_id.required' => 'Pilih komponen tes.',
        ]);

        return collect($validated)->map(fn ($value) => filled($value) ? $value : null)->all();
    }
}
