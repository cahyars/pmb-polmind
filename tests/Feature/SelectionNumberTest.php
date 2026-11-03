<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\RefOption;
use App\Models\Setting;
use App\Models\User;
use App\Services\SelectionNumber;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class SelectionNumberTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    private AdmissionWave $wave;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();
        Setting::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class]);

        $this->wave = AdmissionWave::where('code', 'GEL1')->firstOrFail();
        $this->wave->update(['is_active' => true, 'status' => 'open']);
    }

    /**
     * Tiga camaba mendaftar berurutan: A dan C sudah bayar, B belum.
     *
     * @return array{0: Applicant, 1: Applicant, 2: Applicant}
     */
    private function registerThree(): array
    {
        $registered = [];
        foreach (['a', 'b', 'c'] as $index => $name) {
            $this->travelTo(Carbon::parse('2026-11-0' . ($index + 2) . ' 09:00'));
            $registered[] = $this->registerApplicant(['email' => $name . '@example.com', 'full_name' => 'Camaba ' . $name]);
        }
        $this->travelBack();

        $registered[0]->update(['payment_status' => 'valid']);
        $registered[2]->update(['payment_status' => 'valid']);

        return $registered;
    }

    public function test_numbers_follow_registration_order_and_stay_stable(): void
    {
        [$a, $b, $c] = $this->registerThree();

        $this->assertSame(2, SelectionNumber::assignMissing($this->wave));
        $this->assertSame(['1.001', null, '1.002'], [$a->fresh()->selection_number, $b->fresh()->selection_number, $c->fresh()->selection_number]);

        // B baru bayar: dapat nomor lanjutan, nomor yang sudah ada tidak berubah.
        $b->update(['payment_status' => 'valid']);
        $this->assertSame(1, SelectionNumber::assignMissing($this->wave));
        $this->assertSame(['1.001', '1.003', '1.002'], [$a->fresh()->selection_number, $b->fresh()->selection_number, $c->fresh()->selection_number]);
        $this->assertSame(0, SelectionNumber::assignMissing($this->wave));

        // Susun ulang mengikuti urutan mendaftar; hanya nomor yang berubah yang tercatat di log.
        $logsBefore = ActivityLog::where('action', 'applicant.updated')->count();
        $this->assertSame(3, SelectionNumber::renumber($this->wave));
        $this->assertSame(['1.001', '1.002', '1.003'], [$a->fresh()->selection_number, $b->fresh()->selection_number, $c->fresh()->selection_number]);

        $renumbered = ActivityLog::where('action', 'applicant.updated')->skip($logsBefore)->take(10)->get();
        $this->assertSame([$b->id, $c->id], $renumbered->pluck('applicant_id')->sort()->values()->all());
        $this->assertSame(['label' => 'Nomor urut peserta', 'old' => '3', 'new' => '2'], $renumbered->firstWhere('applicant_id', $b->id)->changes['selection_sequence']);

        // Pembayaran dibatalkan dan camaba dihapus: nomornya dilepas saat disusun ulang.
        $b->update(['payment_status' => 'ditolak']);
        $c->delete();
        $this->assertSame(1, SelectionNumber::renumber($this->wave));
        $this->assertSame('1.001', $a->fresh()->selection_number);
        $this->assertNull($b->fresh()->selection_sequence);
        $this->assertNull(Applicant::withTrashed()->find($c->id)->selection_sequence);
    }

    public function test_partner_applicants_get_a_separate_range_numbered_last(): void
    {
        [$a, $b, $c] = $this->registerThree();
        $b->update(['payment_status' => 'valid']);
        $b->education->update(['school_name' => 'SMKS MITRA INDUSTRI MM2100']);

        $this->assertSame(2, SelectionNumber::assignMissing($this->wave));
        $this->assertSame(['1.001', null, '1.002'], [$a->fresh()->selection_number, $b->fresh()->selection_number, $c->fresh()->selection_number]);
        $this->assertSame(101, SelectionNumber::partnerStart($this->wave));

        $this->assertSame(1, SelectionNumber::assignMissing($this->wave, partner: true));
        $this->assertSame('1.101', $b->fresh()->selection_number);

        // Pendaftar umum baru tetap melanjutkan nomor umum.
        $this->travelTo(Carbon::parse('2026-11-05 09:00'));
        $d = $this->registerApplicant(['email' => 'd@example.com', 'full_name' => 'Camaba d']);
        $this->travelBack();
        $d->update(['payment_status' => 'valid']);
        SelectionNumber::assignMissing($this->wave);
        $this->assertSame('1.003', $d->fresh()->selection_number);

        // Nomor mulai mitra yang menimpa nomor umum ditolak.
        $admin = User::factory()->create();
        $this->actingAs($admin, 'web')
            ->post(route('admin.spmb.numbers.renumber', $this->wave), ['jenis' => 'mitra', 'mulai' => 2])
            ->assertSessionHasErrors('mulai');
        $this->actingAs($admin, 'web')
            ->post(route('admin.spmb.numbers.renumber', $this->wave), ['jenis' => 'mitra', 'mulai' => 201])
            ->assertSessionHas('success', 'Nomor peserta mitra Gelombang 1 disusun ulang: 1 peserta, mulai 1.201.');
        $this->assertSame('1.201', $b->fresh()->selection_number);
        $this->assertSame('1.003', $d->fresh()->selection_number);
    }

    public function test_numbers_are_managed_in_selection_menu(): void
    {
        [$a, , $c] = $this->registerThree();
        $admin = User::factory()->create();

        $this->actingAs($admin, 'web')
            ->get(route('admin.spmb.numbers.index', ['gelombang' => $this->wave->id]))
            ->assertOk()
            ->assertSee('Nomor Peserta Seleksi')
            ->assertSee('1.001')
            ->assertSee('Beri Nomor (2)');

        $this->actingAs($admin, 'web')
            ->post(route('admin.spmb.numbers.assign', $this->wave))
            ->assertRedirect(route('admin.spmb.numbers.index', ['gelombang' => $this->wave->id]))
            ->assertSessionHas('success', '2 pendaftar umum Gelombang 1 mendapat nomor peserta baru.');

        $this->actingAs($admin, 'web')
            ->post(route('admin.spmb.numbers.renumber', $this->wave))
            ->assertSessionHas('success');
        $this->assertSame(['1.001', '1.002'], [$a->fresh()->selection_number, $c->fresh()->selection_number]);

        // Ketua SPMB yang ditugaskan juga mengelola nomor peserta.
        $chair = User::factory()->selectionChair()->create();
        $this->actingAs($chair, 'web')->post(route('admin.spmb.numbers.assign', $this->wave))->assertSessionHas('success');
    }

    public function test_selection_cards_print_numbered_applicants(): void
    {
        [$a, $b, $c] = $this->registerThree();
        SelectionNumber::assignMissing($this->wave);
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator, 'web')
            ->get(route('admin.selections.index'))
            ->assertSee(route('admin.spmb.prints.cards'))
            ->assertSee('No. Peserta');

        $this->actingAs($operator, 'web')
            ->get(route('admin.spmb.prints.cards', ['gelombang' => $this->wave->id]))
            ->assertOk()
            ->assertSee('KARTU PESERTA SELEKSI')
            ->assertSee('TAHUN AKADEMIK 2027/2028')
            ->assertSeeInOrder(['CAMABA A', '1.001', 'D4 - Teknologi Rekayasa Perangkat Lunak', 'TPA dan TMB', 'Tes Fisik', 'Interview', 'MCU', 'CAMABA C', '1.002'])
            ->assertDontSee('CAMABA B')
            ->assertSee('2 kartu peserta · 1 lembar A4');

        $this->actingAs($operator, 'web')
            ->get(route('admin.spmb.prints.cards', ['gelombang' => $this->wave->id, 'from' => 2]))
            ->assertDontSee('CAMABA A')
            ->assertSee('CAMABA C');

        // Belum bernomor: diberi tahu, tanpa kartu.
        $b->update(['payment_status' => 'valid']);
        $this->actingAs($operator, 'web')
            ->get(route('admin.spmb.prints.cards', ['gelombang' => $this->wave->id]))
            ->assertSee('1 camaba sudah bayar tetapi belum bernomor');
    }
}
