<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Login As (khusus Admin PMB): membuka portal camaba persis seperti yang dilihat camaba,
 * untuk membantu camaba yang bertanya atau mengecek kendala. Sesi admin tetap berjalan;
 * camaba ditandai di session agar logout dari portal camaba tidak ikut mengeluarkan admin.
 */
class LoginAsController extends Controller
{
    public const SESSION_KEY = 'login_as';

    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('applicant'));
        $applicant = null;
        $matches = collect();

        if ($keyword !== '') {
            // Pilihan dari saran berbentuk "ID - NAMA"; ketikan bebas dicari seperti saran.
            $applicant = Applicant::where('registration_number', mb_strtoupper(trim(Str::before($keyword, ' - '))))->first();

            if (! $applicant) {
                $matches = $this->search($keyword)->limit(10)->get();
                $applicant = $matches->count() === 1 ? $matches->first() : null;
            }

            $applicant?->load(['pmbYear', 'admissionWave', 'classType', 'studyProgram', 'secondStudyProgram', 'photoDocument']);
        }

        return view('admin.login-as.index', [
            'keyword' => $keyword,
            'applicant' => $applicant,
            'matches' => $applicant ? collect() : $matches,
        ]);
    }

    /**
     * Saran nama saat mengetik: cocok di awal, tengah, atau akhir nama, atau awal ID Pendaftar.
     */
    public function suggestions(Request $request)
    {
        $keyword = trim((string) $request->query('q'));

        if (mb_strlen($keyword) < 2) {
            return response()->json([]);
        }

        return response()->json(
            $this->search($keyword)->limit(10)->get(['registration_number', 'full_name'])
                ->map(fn (Applicant $applicant) => [
                    'id' => $applicant->registration_number,
                    'name' => $applicant->full_name,
                ])
        );
    }

    public function store(Request $request, string $registration_number)
    {
        $applicant = Applicant::where('registration_number', $registration_number)->firstOrFail();
        $admin = $request->user('web');

        Auth::guard('applicant')->login($applicant);

        $request->session()->put(self::SESSION_KEY, [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'applicant_id' => $applicant->id,
        ]);

        Log::info('Login As camaba', [
            'admin' => $admin->email,
            'registration_number' => $applicant->registration_number,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('camaba.dashboard');
    }

    /**
     * Data admin yang sedang Login As sebagai camaba yang login sekarang, null jika camaba login sendiri.
     */
    public static function impersonator(): ?array
    {
        $session = session(self::SESSION_KEY);
        $applicant = Auth::guard('applicant')->user();

        return $session && $applicant && ($session['applicant_id'] ?? null) === $applicant->id ? $session : null;
    }

    private function search(string $keyword): Builder
    {
        $like = '%' . addcslashes($keyword, '\\%_') . '%';

        return Applicant::query()
            ->where(fn (Builder $query) => $query
                ->where('full_name', 'like', $like)
                ->orWhere('registration_number', 'like', addcslashes(mb_strtoupper($keyword), '\\%_') . '%'))
            ->orderBy('full_name');
    }
}
