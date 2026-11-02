<?php

namespace App\Http\Controllers\Camaba;

use App\Http\Controllers\Admin\LoginAsController;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Auth::guard('applicant')->user()
            ->announcements()
            ->latest('announcement_recipients.created_at')
            ->paginate(15);

        return view('camaba.pengumuman.index', compact('announcements'));
    }

    public function show(int $announcement)
    {
        $applicant = Auth::guard('applicant')->user();

        /** @var Announcement $announcement */
        $announcement = $applicant->announcements()->findOrFail($announcement);

        // Admin yang Login As tidak ikut menandai dibaca agar statistik baca tetap akurat.
        if (! $announcement->pivot->read_at && ! LoginAsController::impersonator()) {
            $applicant->announcements()->updateExistingPivot($announcement->id, ['read_at' => now()]);
        }

        return view('camaba.pengumuman.show', compact('announcement'));
    }

    public function markAllRead()
    {
        if (! LoginAsController::impersonator()) {
            AnnouncementRecipient::where('applicant_id', Auth::guard('applicant')->id())
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        return back();
    }
}
