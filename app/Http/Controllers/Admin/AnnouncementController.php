<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SelectAdminPmbYear;
use App\Models\AdmissionWave;
use App\Models\Announcement;
use App\Models\StudyProgram;
use App\Services\AnnouncementAudience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $announcements = Announcement::query()
            ->with(['admissionWave', 'studyProgram', 'creator'])
            ->withCount(['recipients', 'recipients as read_count' => fn ($query) => $query->whereNotNull('read_at')])
            ->where('pmb_year_id', $request->session()->get(SelectAdminPmbYear::SESSION_KEY))
            ->latest()
            ->paginate(20);

        return view('admin.announcements.index', [
            'announcements' => $announcements,
            'segments' => AnnouncementAudience::SEGMENTS,
            'waves' => AdmissionWave::orderBy('id')->get(),
            'studyPrograms' => StudyProgram::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Jumlah camaba yang akan menerima pengumuman, untuk pratinjau sebelum dikirim.
     */
    public function recipientCount(Request $request)
    {
        $validated = $request->validate($this->targetRules(required: false));

        $count = empty($validated['audiences'])
            ? 0
            : AnnouncementAudience::query($validated['audiences'], $validated['admission_wave_id'] ?? null, $validated['study_program_id'] ?? null)->count();

        return response()->json(['count' => $count]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'link_url' => ['nullable', 'url:http,https', 'max:500'],
            'link_label' => ['nullable', 'string', 'max:60'],
        ] + $this->targetRules(required: true), [], [
            'title' => 'judul',
            'body' => 'isi pengumuman',
            'link_url' => 'link',
            'link_label' => 'teks tombol link',
            'audiences' => 'target penerima',
            'admission_wave_id' => 'gelombang',
            'study_program_id' => 'program studi',
        ]);

        $applicantIds = AnnouncementAudience::query($validated['audiences'], $validated['admission_wave_id'] ?? null, $validated['study_program_id'] ?? null)
            ->pluck('id');

        if ($applicantIds->isEmpty()) {
            return back()->withInput()->withErrors(['audiences' => 'Tidak ada camaba pada target yang dipilih.']);
        }

        $announcement = DB::transaction(function () use ($request, $validated, $applicantIds) {
            $announcement = Announcement::create([
                'pmb_year_id' => $request->session()->get(SelectAdminPmbYear::SESSION_KEY),
                'title' => $validated['title'],
                'body' => $validated['body'],
                'link_url' => $validated['link_url'] ?? null,
                'link_label' => filled($validated['link_url'] ?? null) ? ($validated['link_label'] ?? null) : null,
                'audiences' => in_array('semua', $validated['audiences'], true) ? ['semua'] : array_values($validated['audiences']),
                'admission_wave_id' => $validated['admission_wave_id'] ?? null,
                'study_program_id' => $validated['study_program_id'] ?? null,
                'recipient_count' => $applicantIds->count(),
                'created_by' => $request->user()->id,
            ]);

            $now = now();

            foreach ($applicantIds->chunk(500) as $chunk) {
                DB::table('announcement_recipients')->insert($chunk->map(fn ($applicantId) => [
                    'announcement_id' => $announcement->id,
                    'applicant_id' => $applicantId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->values()->all());
            }

            return $announcement;
        });

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Pengumuman "' . $announcement->title . '" terkirim ke ' . number_format($announcement->recipient_count, 0, ',', '.') . ' camaba.');
    }

    public function show(Request $request, Announcement $announcement)
    {
        $readFilter = $request->query('baca');

        $recipients = $announcement->applicants()
            ->with(['admissionWave', 'studyProgram'])
            ->when($readFilter === 'sudah', fn ($query) => $query->wherePivotNotNull('read_at'))
            ->when($readFilter === 'belum', fn ($query) => $query->wherePivotNull('read_at'))
            ->orderBy('full_name')
            ->paginate(50)
            ->withQueryString();

        $recipientCount = $announcement->recipients()->count();
        $readCount = $announcement->recipients()->whereNotNull('read_at')->count();

        return view('admin.announcements.show', compact('announcement', 'recipients', 'recipientCount', 'readCount', 'readFilter'));
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Pengumuman "' . $announcement->title . '" ditarik dari portal camaba.');
    }

    private function targetRules(bool $required): array
    {
        return [
            'audiences' => [$required ? 'required' : 'nullable', 'array', 'min:1'],
            'audiences.*' => ['string', Rule::in(array_keys(AnnouncementAudience::SEGMENTS))],
            'admission_wave_id' => ['nullable', 'integer', Rule::exists('admission_waves', 'id')],
            'study_program_id' => ['nullable', 'integer', Rule::exists('study_programs', 'id')],
        ];
    }
}
