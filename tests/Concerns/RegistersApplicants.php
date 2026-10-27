<?php

namespace Tests\Concerns;

use App\Models\Applicant;
use App\Models\ClassType;
use App\Models\StudyProgram;

trait RegistersApplicants
{
    private int $registeredCount = 0;

    /**
     * Isian form registrasi yang valid. NIK dibuat unik per pemanggilan.
     */
    protected function registrationData(array $overrides = []): array
    {
        $this->registeredCount++;

        return array_merge([
            'full_name' => 'Ammar',
            'nik' => '21710116030799' . str_pad((string) $this->registeredCount, 2, '0', STR_PAD_LEFT),
            'email' => 'ammar@example.com',
            'phone' => '081234567890',
            'study_program_id' => StudyProgram::where('code', 'TRPL')->value('id'),
            'class_type_id' => ClassType::where('code', 'REG_A')->value('id'),
            'registration_path' => 'umum',
            'school_region_id' => '316101',
            'is_manual_school' => 0,
            'school_npsn' => '11001234',
            'school_name' => 'SMK Negeri 1 Batam',
            'school_type' => 'SMK',
            'school_status' => 'Negeri',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $overrides);
    }

    protected function registerApplicant(array $overrides = []): Applicant
    {
        $data = $this->registrationData($overrides);

        $this->post('/register', $data)->assertSessionHasNoErrors();

        auth('applicant')->logout();

        return Applicant::where('email', $data['email'])->firstOrFail();
    }
}
