<?php

namespace App\Models\Concerns;

use App\Models\Applicant;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Catat otomatis tambah/ubah/hapus model ke Log Aktivitas, beserta nilai sebelum dan sesudahnya.
 * Model boleh mendefinisikan:
 * - $activityLabel: nama data di log (contoh "Pembayaran");
 * - $activityIgnore: kolom yang tidak perlu dicatat;
 * - activitySubjectLabel(): penanda data, contoh nomor invoice.
 */
trait LogsActivity
{
    private const ALWAYS_IGNORED = ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token'];

    public static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => $model->writeActivity('created'));
        static::updated(fn (Model $model) => $model->writeActivity('updated'));
        static::deleted(fn (Model $model) => $model->writeActivity('deleted'));
    }

    protected function writeActivity(string $event): void
    {
        $changes = $this->activityChanges($event);

        // Perubahan yang hanya menyentuh kolom yang diabaikan tidak perlu dicatat.
        if ($event === 'updated' && $changes === []) {
            return;
        }

        $applicantId = $this->activityApplicantId();
        $label = $this->activityLabel ?? class_basename($this);
        $verb = ['created' => 'Menambah', 'updated' => 'Mengubah', 'deleted' => 'Menghapus'][$event];

        $subjectLabel = $this instanceof Applicant
            ? $this->registration_number . ' · ' . $this->full_name
            : collect([ActivityLogger::applicantLabel($applicantId), $this->activitySubjectLabel()])->filter()->implode(' · ');

        ActivityLogger::record(str(class_basename($this))->snake() . '.' . $event, $verb . ' ' . mb_strtolower($label), [
            'subject_type' => class_basename($this),
            'subject_id' => $this->getKey(),
            'subject_label' => $subjectLabel ?: null,
            'applicant_id' => $applicantId,
            'changes' => $changes,
        ]);
    }

    /**
     * @return array<string, array{label: string, old: ?string, new: ?string}>
     */
    protected function activityChanges(string $event): array
    {
        $ignored = array_merge(self::ALWAYS_IGNORED, $this->activityIgnore ?? []);
        $fields = match ($event) {
            'updated' => array_keys($this->getChanges()),
            'created' => array_keys(array_filter($this->getAttributes(), fn ($value) => $value !== null && $value !== '')),
            'deleted' => array_keys($this->getOriginal()),
        };

        $changes = [];
        foreach (array_diff($fields, $ignored) as $field) {
            $secret = $this->activityIsSecret($field);
            $old = $event === 'created' ? null : ActivityLogger::format($this->getOriginal($field));
            $new = $event === 'deleted' ? null : ActivityLogger::format($this->getAttribute($field));

            if ($event === 'updated' && $old === $new) {
                continue;
            }

            $changes[$field] = [
                'label' => ActivityLogger::label($field),
                'old' => $secret && $old !== null ? '(disembunyikan)' : $old,
                'new' => $secret && $new !== null ? '(diubah)' : $new,
            ];
        }

        return $changes;
    }

    protected function activityApplicantId(): ?int
    {
        if ($this instanceof Applicant) {
            return $this->getKey();
        }

        return $this->getAttribute('applicant_id') ?? $this->activityParentApplicantId();
    }

    /**
     * Untuk data anak tanpa kolom applicant_id (contoh nilai seleksi, angsuran invoice).
     */
    protected function activityParentApplicantId(): ?int
    {
        return null;
    }

    /**
     * Penanda data selain identitas camaba; default nama/judul/kode untuk data master.
     */
    protected function activitySubjectLabel(): ?string
    {
        if ($this->activityApplicantId()) {
            return null;
        }

        return $this->getAttribute('name') ?? $this->getAttribute('title') ?? $this->getAttribute('code') ?? $this->getAttribute('key');
    }

    protected function activityIsSecret(string $field): bool
    {
        return in_array($field, ActivityLogger::SECRET_FIELDS, true);
    }
}
