<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Kelola Akun > Log Aktivitas (khusus Admin PMB): jejak siapa melakukan apa, kapan, dan perubahannya.
 * Hanya baca; log tidak dapat diubah atau dihapus dari aplikasi.
 */
class ActivityLogController extends Controller
{
    /**
     * Kelompok aksi untuk filter: label => awalan kode aksi (nama model/kejadian).
     */
    public const GROUPS = [
        'auth' => ['label' => 'Login, logout & Login As', 'prefixes' => ['auth']],
        'applicant' => ['label' => 'Biodata camaba', 'prefixes' => ['applicant', 'applicant_address', 'applicant_education', 'applicant_parent', 'follow_up']],
        'document' => ['label' => 'Berkas', 'prefixes' => ['applicant_document']],
        'payment' => ['label' => 'Pembayaran & invoice', 'prefixes' => ['payment', 'invoice', 'invoice_installment']],
        'selection' => ['label' => 'Seleksi', 'prefixes' => ['selection', 'selection_score']],
        're_registration' => ['label' => 'Daftar ulang', 'prefixes' => ['re_registration']],
        'master' => ['label' => 'Master data & akun', 'prefixes' => [
            'admission_wave', 'pmb_year', 'study_program', 'class_type', 'document_type', 'fee_component', 'fee_component_path_amount',
            'fee_installment_template', 'payment_method', 'registration_path', 'selection_component', 'selection_schedule', 'setting', 'brochure', 'user',
        ]],
        'report' => ['label' => 'Unduh laporan', 'prefixes' => ['report']],
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'user' => ['nullable', 'integer'],
            'actor' => ['nullable', 'in:' . implode(',', array_keys(ActivityLog::ACTOR_TYPES))],
            'group' => ['nullable', 'in:' . implode(',', array_keys(self::GROUPS))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'keyword' => ['nullable', 'string', 'max:100'],
        ]);

        $logs = ActivityLog::query()
            ->with('applicant:id,registration_number')
            ->when($filters['user'] ?? null, fn (Builder $query, $userId) => $query->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query->where('actor_type', 'user')->where('actor_id', $userId))
                // Login gagal ke akun ini (email benar, password salah) ikut ditampilkan.
                ->orWhere(fn (Builder $query) => $query->where('actor_type', 'guest')->where('actor_role', 'Panel admin')->where('actor_id', $userId))))
            ->when($filters['actor'] ?? null, fn (Builder $query, $type) => $query->where('actor_type', $type))
            ->when($filters['group'] ?? null, fn (Builder $query, $group) => $query->where(function (Builder $query) use ($group) {
                foreach (self::GROUPS[$group]['prefixes'] as $prefix) {
                    $query->orWhere('action', 'like', $prefix . '.%');
                }
            }))
            ->when($filters['from'] ?? null, fn (Builder $query, $from) => $query->where('created_at', '>=', $from . ' 00:00:00'))
            ->when($filters['to'] ?? null, fn (Builder $query, $to) => $query->where('created_at', '<=', $to . ' 23:59:59'))
            ->when($filters['keyword'] ?? null, function (Builder $query, $keyword) {
                $like = '%' . addcslashes($keyword, '\\%_') . '%';

                $query->where(fn (Builder $query) => $query
                    ->where('subject_label', 'like', $like)
                    ->orWhere('actor_name', 'like', $like)
                    ->orWhere('description', 'like', $like));
            })
            ->latest('id')
            ->paginate($this->perPage($request, 50))
            ->withQueryString();

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'filters' => array_filter($filters, fn ($value) => filled($value)),
            'users' => User::orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }
}
