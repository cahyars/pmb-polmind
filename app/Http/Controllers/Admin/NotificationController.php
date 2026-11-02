<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminNotifications;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Dipanggil berkala oleh lonceng admin: badge terbaru dan data baru sejak penanda terakhir.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'applicant_after' => ['required', 'integer', 'min:0'],
            'payment_after' => ['required', 'integer', 'min:0'],
        ]);

        return response()->json([
            'counts' => AdminNotifications::counts($request->user('web')),
            'cursor' => AdminNotifications::cursor(),
            'events' => AdminNotifications::eventsAfter($validated['applicant_after'], $validated['payment_after']),
        ]);
    }
}
