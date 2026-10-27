<?php

namespace App\Services\Elternabfragen;

use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryEvent;
use App\Model\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ablage in der Schülerakte: Antworten als PDF (ein Blatt je Kind) für die Verwaltung.
 * Beim Erstellen wird je Kind vermerkt, welche Antwortversion gedruckt wurde. Bei
 * aktenrelevanten Vorlagen hält der RetentionService die Löschung zurück, solange
 * vollständige Antworten noch nicht gedruckt sind.
 */
class RecordFilingService
{
    public function __construct(private readonly FieldSchema $schema) {}

    /**
     * Fälle mit vollständiger Antwort für das Sammel-PDF.
     *
     * @return Collection<int, InquiryAssignment>
     */
    public function assignments(Inquiry $inquiry, ?int $groupId = null, bool $onlyUnfiled = false): Collection
    {
        return $inquiry->assignments()
            ->whereNotNull('current_response_id')
            ->when($groupId, fn ($q) => $q->where('group_id', $groupId))
            ->with(['child', 'group', 'currentResponse.user:id,name', 'currentResponse.templateVersion', 'currentResponse.confirmations.user:id,name'])
            ->get()
            ->each(fn (InquiryAssignment $a) => $a->setRelation('inquiry', $inquiry))
            ->filter(fn (InquiryAssignment $a) => $a->isComplete() && (! $onlyUnfiled || ! $a->isFiled()))
            ->sortBy(fn (InquiryAssignment $a) => [$a->group?->name ?? 'zzz', $a->child?->last_name ?? '', $a->child?->first_name ?? ''])
            ->values();
    }

    /** Vollständige Antworten, die noch nicht für die Akte gedruckt wurden. */
    public function pendingCount(Inquiry $inquiry): int
    {
        return $inquiry->assignments()
            ->whereNotNull('current_response_id')
            ->where(fn ($q) => $q->whereNull('filed_response_id')->orWhereColumn('filed_response_id', '!=', 'current_response_id'))
            ->with('inquiry.templateVersion')
            ->get()
            ->filter(fn (InquiryAssignment $a) => $a->isComplete())
            ->count();
    }

    /**
     * PDF erzeugen und die enthaltenen Fälle als gedruckt vermerken.
     *
     * @param  Collection<int, InquiryAssignment>  $assignments
     */
    public function download(Inquiry $inquiry, Collection $assignments, User $user, string $filename): Response
    {
        $inquiry->loadMissing('templateVersion');
        $assignments->each(fn (InquiryAssignment $a) => $a->loadMissing(['child', 'group', 'currentResponse.user:id,name', 'currentResponse.templateVersion', 'currentResponse.confirmations.user:id,name']));

        $pdf = Pdf::loadView('elternabfragen.pdf.record', [
            'inquiry' => $inquiry,
            'assignments' => $assignments,
            'schema' => $this->schema,
            'printedBy' => $user,
        ]);

        $this->markFiled($inquiry, $assignments, $user);

        return $pdf->download(Str::slug($filename, '_').'.pdf');
    }

    /**
     * @param  Collection<int, InquiryAssignment>  $assignments
     */
    private function markFiled(Inquiry $inquiry, Collection $assignments, User $user): void
    {
        $filed = 0;
        foreach ($assignments as $assignment) {
            // Unvollständige Antworten (Bestätigung offen) gelten noch nicht als abgelegt
            if (! $assignment->isComplete()) {
                continue;
            }
            $assignment->forceFill([
                'filed_response_id' => $assignment->current_response_id,
                'filed_at' => now(),
                'filed_by' => $user->id,
            ])->save();
            InquiryEvent::log('assignment.filed', ['inquiry_id' => $inquiry->id, 'assignment_id' => $assignment->id], null, [
                'version' => $assignment->currentResponse?->version,
            ]);
            $filed++;
        }

        if ($assignments->count() > 1) {
            InquiryEvent::log('inquiry.filed', ['inquiry_id' => $inquiry->id], null, ['count' => $filed]);
        }
    }
}
