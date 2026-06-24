<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\FollowUp;
use App\Models\StudyProgram;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function index(Request $request)
    {
        $totalApplicants = Applicant::count();

        $notContactedApplicants = Applicant::doesntHave('followUps')->count();

        $contactedApplicants = Applicant::whereHas('followUps')->count();

        $highPriorityFollowUps = FollowUp::where('priority', 'tinggi')->count();

        $todayFollowUps = FollowUp::whereDate('next_follow_up_date', now()->toDateString())->count();

        $studyPrograms = StudyProgram::where('is_active', true)
            ->orderBy('code')
            ->get();

        $applicants = Applicant::query()
            ->with([
                'studyProgram',
                'classType',
                'education',
                'admissionWave',
                'latestFollowUp',
                'followUps' => function ($query) {
                    $query->latest()->limit(3);
                },
            ])
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->keyword;

                $query->where(function ($q) use ($keyword) {
                    $q->where('registration_number', 'like', "%{$keyword}%")
                        ->orWhere('full_name', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhere('nik', 'like', "%{$keyword}%")
                        ->orWhereHas('education', function ($educationQuery) use ($keyword) {
                            $educationQuery->where('school_name', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($request->filled('study_program'), function ($query) use ($request) {
                $query->where('study_program_id', $request->study_program);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->status === 'belum_dihubungi') {
                    $query->doesntHave('followUps');
                } else {
                    $query->whereHas('followUps', function ($followUpQuery) use ($request) {
                        $followUpQuery->where('status', $request->status);
                    });
                }
            })
            ->when($request->filled('priority'), function ($query) use ($request) {
                $query->whereHas('followUps', function ($followUpQuery) use ($request) {
                    $followUpQuery->where('priority', $request->priority);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.follow-ups.index', compact(
            'totalApplicants',
            'notContactedApplicants',
            'contactedApplicants',
            'highPriorityFollowUps',
            'todayFollowUps',
            'studyPrograms',
            'applicants'
        ));
    }

    public function store(Request $request, Applicant $applicant)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'max:100'],
            'priority' => ['required', 'string', 'max:50'],
            'contact_method' => ['required', 'string', 'max:50'],
            'next_follow_up_date' => ['nullable', 'date'],
            'note' => ['required', 'string', 'max:2000'],
        ]);

        FollowUp::create([
            'applicant_id' => $applicant->id,
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'contact_method' => $validated['contact_method'],
            'contacted_at' => now(),
            'next_follow_up_date' => $validated['next_follow_up_date'] ?? null,
            'note' => $validated['note'],
            'officer_name' => 'Admin PMB',
        ]);

        return back()->with('success', 'Catatan follow up berhasil disimpan.');
    }
}