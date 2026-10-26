<?php

namespace App\Http\Middleware;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Models\FollowUp;
use App\Models\IntegrationLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PmbYear;
use App\Models\ReRegistration;
use App\Models\Selection;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi seluruh data camaba di panel admin pada tahun akademik (Tahun PMB)
 * yang dipilih admin. Pilihan disimpan di session; default ke Tahun PMB aktif.
 */
class SelectAdminPmbYear
{
    public const SESSION_KEY = 'admin_pmb_year_id';

    /**
     * Model turunan camaba yang dibatasi lewat kolom applicant_id.
     */
    private const APPLICANT_SCOPED_MODELS = [
        ApplicantDocument::class,
        FollowUp::class,
        IntegrationLog::class,
        Invoice::class,
        Payment::class,
        ReRegistration::class,
        Selection::class,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $pmbYears = PmbYear::orderByDesc('year')->get();

        $selectedPmbYear = $pmbYears->firstWhere('id', $request->session()->get(self::SESSION_KEY))
            ?? $pmbYears->firstWhere('is_active', true)
            ?? $pmbYears->first();

        if ($selectedPmbYear) {
            $request->session()->put(self::SESSION_KEY, $selectedPmbYear->id);

            $this->applyScopes($selectedPmbYear->id);
        }

        View::share('adminPmbYears', $pmbYears);
        View::share('selectedPmbYear', $selectedPmbYear);

        return $next($request);
    }

    private function applyScopes(int $pmbYearId): void
    {
        Applicant::addGlobalScope('admin_pmb_year', function (Builder $query) use ($pmbYearId) {
            $query->where($query->qualifyColumn('pmb_year_id'), $pmbYearId);
        });

        AdmissionWave::addGlobalScope('admin_pmb_year', function (Builder $query) use ($pmbYearId) {
            $query->where($query->qualifyColumn('pmb_year_id'), $pmbYearId);
        });

        foreach (self::APPLICANT_SCOPED_MODELS as $model) {
            $model::addGlobalScope('admin_pmb_year', function (Builder $query) use ($pmbYearId) {
                $query->whereIn($query->qualifyColumn('applicant_id'), function ($subQuery) use ($pmbYearId) {
                    $subQuery->select('id')
                        ->from('applicants')
                        ->where('pmb_year_id', $pmbYearId);
                });
            });
        }
    }
}
