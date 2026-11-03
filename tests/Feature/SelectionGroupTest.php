<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\RefOption;
use App\Models\SelectionComponent;
use App\Models\SelectionGroup;
use App\Models\Setting;
use App\Models\User;
use App\Services\SelectionGroups;
use App\Services\SelectionNumber;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class SelectionGroupTest extends TestCase
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

        SelectionComponent::query()->delete();
        foreach (['TPA' => 'TPA', 'FISIK' => 'Tes Fisik', 'WAWANCARA' => 'Interview', 'MCU' => 'MCU'] as $code => $name) {
            SelectionComponent::create(['code' => $code, 'name' => $name, 'weight' => 1, 'sort_order' => count(SelectionComponent::all()) + 1, 'is_active' => true]);
        }
    }

    /**
     * Tujuh peserta; nomor 2 dan 5 dari SMK Mitra Industri. Umum bernomor 1.001-1.005, mitra 1.101-1.102.
     *
     * @return list<Applicant>
     */
    private function registerParticipants(): array
    {
        $applicants = [];
        foreach (range(1, 7) as $index) {
            $this->travelTo(Carbon::parse('2026-11-01 09:00')->addMinutes($index));
            $applicant = $this->registerApplicant(['email' => "p{$index}@example.com", 'full_name' => "Peserta {$index}"]);
            $applicant->update(['payment_status' => 'valid']);

            if (in_array($index, [2, 5], true)) {
                $applicant->education->update(['school_name' => $index === 2 ? 'SMKS MITRA INDUSTRI MM2100' : 'SMK ANANDA MITRA INDUSTRI DELTAMAS']);
            }

            $applicants[] = $applicant;
        }
        $this->travelBack();

        SelectionNumber::assignMissing($this->wave);
        SelectionNumber::assignMissing($this->wave, partner: true);

        return array_map(fn (Applicant $applicant) => $applicant->fresh(), $applicants);
    }

    public function test_selection_chair_only_opens_selection_menu(): void
    {
        $chair = User::factory()->selectionChair()->create();

        $this->actingAs($chair, 'web')->get(route('admin.dashboard'))->assertRedirect(route('admin.spmb.groups.index'));
        $this->actingAs($chair, 'web')->get(route('admin.applicants.index'))->assertForbidden();
        $this->actingAs($chair, 'web')->get(route('admin.payments.index'))->assertForbidden();
        $this->actingAs($chair, 'web')->get(route('admin.master-data.show', 'gelombang'))->assertForbidden();

        $this->actingAs($chair, 'web')
            ->get(route('admin.spmb.groups.index'))
            ->assertOk()
            ->assertSeeInOrder(['Jadwal Tes', 'PIC Tes', 'Nomor Peserta', 'Kelompok Tes', 'Cetak Dokumen Tes', 'Penetapan Hasil'])
            ->assertDontSee('Data Camaba')
            ->assertDontSee('Cari camaba...');

        // Hasil seleksi bisa diinput Admin PMB maupun Ketua SPMB.
        $this->actingAs($chair, 'web')->get(route('admin.selections.index'))->assertOk();

        $this->actingAs($chair, 'web')->get(route('admin.spmb.numbers.index'))->assertOk();
        $this->actingAs($chair, 'web')->get(route('admin.spmb.prints.index'))->assertOk();

        // Admin PMB mendaftarkan akun Ketua SPMB lewat Kelola Akun.
        $admin = User::factory()->create();
        $this->actingAs($admin, 'web')
            ->post(route('admin.users.store'), [
                'name' => 'Ketua SPMB',
                'email' => 'spmb@polmind.ac.id',
                'role' => 'ketua_spmb',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertSessionHas('success');
        $this->assertTrue(User::where('email', 'spmb@polmind.ac.id')->first()->isSelectionChair());
    }

    public function test_participants_are_distributed_with_partner_group_separated(): void
    {
        $participants = $this->registerParticipants();
        $chair = User::factory()->selectionChair()->create();

        $this->actingAs($chair, 'web')->post(route('admin.spmb.groups.defaults', $this->wave))->assertSessionHas('success');

        $groups = $this->wave->selectionGroups()->with('components')->get();
        $this->assertSame(['Kelompok 1', 'Kelompok 2', 'Kelompok 3', 'Kelompok Mitra'], $groups->pluck('name')->all());
        $this->assertSame(['MCU'], $groups->last()->components->pluck('code')->all());
        $this->assertCount(4, $groups->first()->components);

        $this->actingAs($chair, 'web')->post(route('admin.spmb.groups.distribute', $this->wave), ['mode' => 'semua'])->assertSessionHas('success');

        // 5 peserta umum dalam blok 2-2-1 urut nomor, 2 peserta mitra ke Kelompok Mitra.
        $groupOf = fn (int $index) => $participants[$index]->fresh()->selectionGroup->name;
        $this->assertSame(
            ['Kelompok 1', 'Kelompok Mitra', 'Kelompok 1', 'Kelompok 2', 'Kelompok Mitra', 'Kelompok 2', 'Kelompok 3'],
            array_map($groupOf, range(0, 6))
        );

        // Peserta baru masuk ke kelompok umum yang paling sedikit.
        $this->travelTo(Carbon::parse('2026-11-01 10:00'));
        $late = $this->registerApplicant(['email' => 'late@example.com', 'full_name' => 'Peserta Susulan']);
        $this->travelBack();
        $late->update(['payment_status' => 'valid']);
        SelectionNumber::assignMissing($this->wave);

        $this->actingAs($chair, 'web')->post(route('admin.spmb.groups.distribute', $this->wave), ['mode' => 'belum']);
        $this->assertSame('Kelompok 3', $late->fresh()->selectionGroup->name);
        $this->assertSame('Kelompok 1', $groupOf(0));

        // Pindah manual dan hapus kelompok.
        $group3 = $groups->firstWhere('name', 'Kelompok 3');
        $this->actingAs($chair, 'web')
            ->patch(route('admin.spmb.groups.move', $this->wave), ['applicants' => [$participants[0]->id], 'target' => $group3->id])
            ->assertSessionHas('success', '1 peserta dipindahkan ke Kelompok 3.');
        $this->assertSame('Kelompok 3', $groupOf(0));

        $this->actingAs($chair, 'web')->delete(route('admin.spmb.groups.destroy', $group3))->assertSessionHas('success');
        $this->assertNull($participants[0]->fresh()->selection_group_id);
        $this->assertSame(3, SelectionGroups::participants($this->wave)->whereNull('selection_group_id')->count());
    }

    public function test_attendance_lists_group_sheet_and_blank_cards(): void
    {
        $this->registerParticipants();
        SelectionGroups::createDefaults($this->wave);
        SelectionGroups::distribute($this->wave, true);
        $chair = User::factory()->selectionChair()->create();

        $this->actingAs($chair, 'web')
            ->get(route('admin.spmb.prints.attendance', ['gelombang' => $this->wave->id, 'jenis' => 'umum', 'kosong' => 2]))
            ->assertOk()
            ->assertSee('DAFTAR HADIR PESERTA')
            ->assertSeeInOrder(['1.001', 'PESERTA 1', 'SMK NEGERI 1 BATAM', '1.002', 'PESERTA 3', '1.005', 'PESERTA 7', '1.006', '1.007'])
            ->assertDontSee('PESERTA 2');

        $this->actingAs($chair, 'web')
            ->get(route('admin.spmb.prints.attendance', ['gelombang' => $this->wave->id, 'jenis' => 'mitra']))
            ->assertOk()
            ->assertSeeInOrder(['1.101', 'PESERTA 2', 'SMKS MITRA INDUSTRI MM2100', '1.102', 'PESERTA 5', 'SMK ANANDA MITRA INDUSTRI DELTAMAS'])
            ->assertDontSee('PESERTA 1');

        $this->actingAs($chair, 'web')
            ->get(route('admin.spmb.prints.groups', ['gelombang' => $this->wave->id, 'kosong' => 3]))
            ->assertOk()
            ->assertSee('Pembagian Kelompok Peserta')
            ->assertSeeInOrder([
                'TPA', 'Tes Fisik', 'Interview', 'MCU', '1.001', 'PESERTA 1', 'Kelompok 1', 'PESERTA 4', 'Kelompok 2', 'PESERTA 7', 'Kelompok 3',
                '1.006', 'Cadangan', '1.008',
                // Lembar kedua: kelompok mitra.
                'Peserta SMK Mitra Industri', '1.101', 'PESERTA 2', 'Kelompok Mitra', '1.102', 'PESERTA 5',
            ]);

        $partner = SelectionGroup::where('is_partner', true)->first();
        $this->actingAs($chair, 'web')
            ->get(route('admin.spmb.prints.cards', ['gelombang' => $this->wave->id, 'kelompok' => $partner->id, 'kosong' => 3]))
            ->assertOk()
            ->assertSee('2 kartu peserta + 3 kartu kosong · 1 lembar A4')
            ->assertSee('PESERTA 5')
            ->assertDontSee('PESERTA 1');

        $this->actingAs($chair, 'web')
            ->get(route('admin.spmb.prints.cards', ['gelombang' => $this->wave->id, 'hanya_kosong' => 1, 'kosong' => 12]))
            ->assertSee('0 kartu peserta + 12 kartu kosong · 2 lembar A4')
            ->assertDontSee('PESERTA 1');
    }
}
