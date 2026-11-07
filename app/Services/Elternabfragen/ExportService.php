<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryReviewStatus;
use App\Exports\InquiryResponsesExport;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryEvent;
use App\Model\InquiryExport;
use App\Model\User;
use App\Settings\ElternabfragenSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Prozess E / Kap. 9: Export auf Abfrage- oder Klassenebene mit Vorschau
 * (Datensätze, Felder, Antwortversion, fehlende/auffällige Angaben, Ziel,
 * verantwortliche Person), Protokoll und Ablaufdatum.
 */
class ExportService
{
    public const DISK = 'local';

    /** Metaspalten, die zusätzlich zu den Formularfeldern exportiert werden können. */
    public const META_COLUMNS = [
        '_child' => 'Kind',
        '_external_id' => 'Schüler-ID',
        '_class' => 'Klasse',
        '_status' => 'Status',
        '_submitted_by' => 'Abgegeben von',
        '_submitted_at' => 'Zeitpunkt',
        '_version' => 'Antwortversion',
        '_review' => 'Prüfstatus',
        '_changes' => 'Änderungen zur Vorabfrage',
    ];

    public function __construct(
        private readonly FieldSchema $schema,
        private readonly ElternabfragenSetting $settings,
    ) {}

    /** @return array<string, string> Schlüssel => Beschriftung */
    public function availableColumns(Inquiry $inquiry): array
    {
        $columns = self::META_COLUMNS;
        if (! $inquiry->previous_inquiry_id) {
            unset($columns['_changes']);
        }
        foreach ($inquiry->templateVersion->inputFields() as $field) {
            $columns[$field['key']] = $field['label'];
        }

        return $columns;
    }

    /**
     * @param  list<string>  $columns
     * @return array{records: int, open: int, missing: int, clarification: int, unreviewed: int, columns: array<string, string>, version: string}
     */
    public function preview(Inquiry $inquiry, ?int $groupId, array $columns, bool $includeOpen): array
    {
        $assignments = $this->assignments($inquiry, $groupId, $includeOpen);
        $fields = $inquiry->templateVersion->inputFields();
        $available = $this->availableColumns($inquiry);

        $answered = $assignments->filter(fn (InquiryAssignment $a) => $a->currentResponse !== null);

        return [
            'records' => $assignments->count(),
            'open' => $assignments->count() - $answered->count(),
            'missing' => $answered->filter(fn ($a) => $this->schema->missing($fields, $a->currentResponse->answers) !== [])->count(),
            'clarification' => $answered->filter(fn ($a) => $a->currentResponse->review_status === InquiryReviewStatus::Clarification)->count(),
            'unreviewed' => $answered->filter(fn ($a) => $a->currentResponse->review_status === InquiryReviewStatus::Pending)->count(),
            'columns' => array_intersect_key($available, array_flip($columns)),
            'version' => 'jeweils aktuell gültige Antwortversion',
        ];
    }

    /**
     * @param  list<string>  $columns
     */
    public function create(Inquiry $inquiry, User $user, ?int $groupId, array $columns, bool $includeOpen, string $format, string $target): InquiryExport
    {
        $available = $this->availableColumns($inquiry);
        $columns = array_values(array_filter($columns, fn ($c) => isset($available[$c])));
        $assignments = $this->assignments($inquiry, $groupId, $includeOpen);
        $fields = collect($inquiry->templateVersion->inputFields())->keyBy('key');

        $rows = $assignments->map(fn (InquiryAssignment $a) => array_map(
            fn (string $column) => $this->cell($a, $column, $fields),
            $columns
        ))->values()->all();

        $format = $format === 'csv' ? 'csv' : 'xlsx';
        $path = 'elternabfragen/exports/'.$inquiry->id.'/'.now()->format('Ymd_His').'_'.Str::random(8).'.'.$format;

        Excel::store(
            new InquiryResponsesExport(array_map(fn ($c) => $available[$c], $columns), $rows),
            $path,
            self::DISK,
            $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX,
        );

        $missing = $assignments->filter(fn ($a) => $a->currentResponse
            && $this->schema->missing($fields->all(), $a->currentResponse->answers) !== [])->count();

        $export = InquiryExport::create([
            'inquiry_id' => $inquiry->id,
            'created_by' => $user->id,
            'group_id' => $groupId,
            'fields' => $columns,
            'format' => $format,
            'target' => $target,
            'record_count' => count($rows),
            'missing_count' => $missing,
            'path' => $path,
            'expires_at' => now()->addDays(max(1, $this->settings->export_expiry_days)),
        ]);

        InquiryEvent::log('export.created', ['inquiry_id' => $inquiry->id], $target, [
            'export_id' => $export->id,
            'records' => $export->record_count,
            'format' => $format,
            'group_id' => $groupId,
            'columns' => $columns,
        ]);

        return $export;
    }

    public function registerDownload(InquiryExport $export): void
    {
        $export->increment('download_count', 1, ['last_downloaded_at' => now()]);
        InquiryEvent::log('export.downloaded', ['inquiry_id' => $export->inquiry_id], null, ['export_id' => $export->id]);
    }

    /** Abgelaufene Exportdateien löschen (Protokolleintrag bleibt). */
    public function purgeExpired(): int
    {
        $count = 0;
        InquiryExport::query()->whereNull('file_deleted_at')->where('expires_at', '<=', now())->get()
            ->each(function (InquiryExport $export) use (&$count) {
                $this->deleteFile($export);
                $count++;
            });

        return $count;
    }

    public function deleteFile(InquiryExport $export): void
    {
        if ($export->path) {
            Storage::disk(self::DISK)->delete($export->path);
        }
        $export->update(['file_deleted_at' => now()]);
    }

    /** @return Collection<int, InquiryAssignment> */
    private function assignments(Inquiry $inquiry, ?int $groupId, bool $includeOpen): Collection
    {
        return $inquiry->assignments()
            ->with(['child', 'group', 'currentResponse.user'])
            ->when($groupId, fn ($q) => $q->where('group_id', $groupId))
            ->when(! $includeOpen, fn ($q) => $q->whereNotNull('current_response_id'))
            ->get()
            ->sortBy(fn (InquiryAssignment $a) => [$a->group?->name ?? '', $a->child?->last_name ?? '', $a->child?->first_name ?? ''])
            ->values();
    }

    private function cell(InquiryAssignment $assignment, string $column, Collection $fields): string|int|null
    {
        $response = $assignment->currentResponse;

        return match ($column) {
            '_child' => $assignment->childName(),
            '_external_id' => $assignment->child?->external_id,
            '_class' => $assignment->group?->name,
            '_status' => $assignment->status->label(),
            '_submitted_by' => $response?->user?->name,
            '_submitted_at' => $response?->submitted_at?->format('d.m.Y H:i'),
            '_version' => $response?->version,
            '_review' => $response?->review_status->label(),
            '_changes' => $response?->hasBaseline()
                ? (collect(array_keys($response->baselineChanges()))->map(fn ($key) => $fields->get($key)['label'] ?? $key)->implode(', ') ?: 'unverändert')
                : null,
            default => $response && $fields->has($column)
                ? $this->neutralizeFormula($this->schema->display($fields->get($column), $response->answers[$column] ?? null))
                : null,
        };
    }

    /** Freitext von Eltern darf in Excel nicht als Formel ausgeführt werden (CSV-Injection). */
    private function neutralizeFormula(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
