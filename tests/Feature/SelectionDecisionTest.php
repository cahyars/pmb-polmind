<?php

namespace Tests\Feature;

use App\Models\AdmissionWave;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\RefOption;
use App\Models\SelectionComponent;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\NimGenerator;
use Database\Seeders\PmbMasterSeeder;
use Database\Seeders\ReferenceSeeder;
use Database\Seeders\SelectionScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\RegistersApplicants;
use Tests\TestCase;

class SelectionDecisionTest extends TestCase
{
    use RefreshDatabase, RegistersApplicants;

    private SelectionComponent $writtenTest;

    private SelectionComponent $interview;

    protected function setUp(): void
    {
        parent::setUp();

        RefOption::flushCache();
        Setting::flushCache();

        $this->seed([ReferenceSeeder::class, PmbMasterSeeder::class, SelectionScheduleSeeder::class]);

        AdmissionWave::where('code', 'GEL1')->update(['is_active' => true, 'status' => 'open']);

        $this->writtenTest = SelectionComponent::where('code', 'TES_TULIS')->firstOrFail();
        $this->interview = SelectionComponent::where('code', 'WAWANCARA')->firstOrFail();
    }

    private function readyApplicant(array $overrides = []): Applicant
    {
        $secondChoice = array_key_exists('second_study_program_id', $overrides)
            ? $overrides['second_study_program_id']
            : StudyProgram::where('code', 'TRM')->value('id');

        $applicant = $this->registerApplicant($overrides);

        $applicant->update([
            'second_study_program_id' => $secondChoice,
            'document_status' => 'valid',
            'payment_status' => 'valid',
        ]);

        return $applicant->refresh();
    }

    public function test_admin_scores_per_component_and_accepts_at_second_choice(): void
    {
        $admin = User::factory()->create();
        $applicant = $this->readyApplicant();

        $this->actingAs($admin, 'web')
            ->get(route('admin.selections.show', $applicant->registration_number))
            ->assertOk()
            ->assertSee('Tes Tertulis')
            ->assertSee('Wawancara')
            ->assertSee('Diterima di Pilihan 2')
            ->assertSee('name="scores[' . $this->writtenTest->id . ']"', false);

        // Bobot seeder 60/40: 80 * 0.6 + 70 * 0.4 = 76.
        $this->actingAs($admin, 'web')
            ->patch(route('admin.selections.decide', $applicant), [
                'scores' => [$this->writtenTest->id => 80, $this->interview->id => 70],
                'decision' => 'diterima_2',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Camaba diterima di pilihan 2 (TRM) dan invoice daftar ulang dibuat.');

        $applicant->refresh();
        $this->assertSame('diterima', $applicant->selection_status);
        $this->assertSame(StudyProgram::where('code', 'TRM')->value('id'), $applicant->accepted_study_program_id);
        $this->assertSame(2, $applicant->acceptedChoice());
        $this->assertSame('76.00', $applicant->selection->final_score);
        $this->assertCount(2, $applicant->selection->scores);
        $this->assertTrue(Invoice::where('applicant_id', $applicant->id)->where('type', 're_registration')->exists());

        // Camaba hanya melihat hasil dan prodi tempat diterima, tanpa nilai.
        $this->actingAs($applicant, 'applicant')
            ->get(route('camaba.status-seleksi'))
            ->assertOk()
            ->assertSee('Diterima di Program Studi')
            ->assertSee('Pilihan 2')
            ->assertDontSee('76.00')
            ->assertDontSee('Nilai Akhir');
    }

    public function test_scores_only_keep_status_and_empty_score_is_removed(): void
    {
        $admin = User::factory()->create();
        $applicant = $this->readyApplicant();

        $this->actingAs($admin, 'web')
            ->patch(route('admin.selections.scores', $applicant), [
                'scores' => [$this->writtenTest->id => 90, $this->interview->id => 60],
            ])
            ->assertSessionHas('success');

        $this->assertSame('78.00', $applicant->selection()->value('final_score'));
        $this->assertSame('belum_diseleksi', $applicant->refresh()->selection_status);

        $this->actingAs($admin, 'web')
            ->patch(route('admin.selections.scores', $applicant), [
                'scores' => [$this->writtenTest->id => 90, $this->interview->id => ''],
            ]);

        $this->assertSame('90.00', $applicant->selection()->value('final_score'));
        $this->assertSame(1, $applicant->selection->scores()->count());

        $this->actingAs($admin, 'web')
            ->patch(route('admin.selections.scores', $applicant), ['scores' => [$this->writtenTest->id => 120]])
            ->assertSessionHasErrors('scores.' . $this->writtenTest->id);
    }

    public function test_decision_guards(): void
    {
        $admin = User::factory()->create();
        $noSecondChoice = $this->readyApplicant(['email' => 'satu@example.com', 'second_study_program_id' => null]);

        $this->actingAs($admin, 'web')
            ->patch(route('admin.selections.decide', $noSecondChoice), ['decision' => 'diterima_2'])
            ->assertSessionHasErrors();

        $this->actingAs($admin, 'web')
            ->patch(route('admin.selections.decide', $noSecondChoice), ['decision' => 'ditolak'])
            ->assertSessionHasErrors('note');

        $this->assertSame('belum_diseleksi', $noSecondChoice->refresh()->selection_status);

        // Setelah ada pembayaran daftar ulang, prodi diterima tidak dapat dipindah.
        $applicant = $this->readyApplicant(['email' => 'dua@example.com']);

        $this->actingAs($admin, 'web')
            ->patch(route('admin.selections.decide', $applicant), ['decision' => 'diterima_1'])
            ->assertSessionHasNoErrors();

        $applicant->reRegistrationInvoice()->update(['status' => 'paid']);

        $this->actingAs($admin, 'web')
            ->patch(route('admin.selections.decide', $applicant), ['decision' => 'diterima_2'])
            ->assertSessionHasErrors();

        $this->assertSame($applicant->study_program_id, $applicant->refresh()->accepted_study_program_id);
    }

    public function test_nim_uses_accepted_study_program(): void
    {
        $applicant = $this->readyApplicant();

        $this->actingAs(User::factory()->create(), 'web')
            ->patch(route('admin.selections.decide', $applicant), ['decision' => 'diterima_2']);

        $invoice = $applicant->reRegistrationInvoice()->firstOrFail();

        Payment::create([
            'applicant_id' => $applicant->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY/DU/1',
            'transfer_date' => '2027-04-05',
            'amount' => $invoice->total_amount,
            'status' => 'valid',
            'verified_at' => now(),
        ]);

        NimGenerator::generate($applicant->pmbYear);

        $this->assertStringStartsWith(StudyProgram::where('code', 'TRM')->value('nim_code'), $applicant->refresh()->nim);
    }
}
