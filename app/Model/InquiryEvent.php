<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Prozessprotokoll (Freigaben, Friständerungen, Nachfristen, Korrekturfreigaben,
 * Klärungen, Exporte, Löschungen). Einträge werden nur angelegt, nie geändert.
 */
class InquiryEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['inquiry_id', 'template_version_id', 'assignment_id', 'user_id', 'action', 'note', 'data', 'created_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false);
    }

    public static function log(string $action, array $refs = [], ?string $note = null, array $data = []): self
    {
        return self::create($refs + [
            'user_id' => auth()->id(),
            'action' => $action,
            'note' => $note,
            'data' => $data ?: null,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'template.created' => 'Vorlage angelegt',
            'template.version_created' => 'Neue Vorlagenversion angelegt',
            'template.submitted' => 'Vorlage zur Prüfung eingereicht',
            'template.approved' => 'Vorlage freigegeben',
            'template.rejected' => 'Vorlage zur Überarbeitung zurückgegeben',
            'template.archived' => 'Vorlage archiviert',
            'inquiry.created' => 'Abfrage angelegt',
            'inquiry.submitted' => 'Zur Freigabe eingereicht',
            'inquiry.approved' => 'Abfrage freigegeben',
            'inquiry.rejected' => 'Zur Überarbeitung zurückgegeben',
            'inquiry.started' => 'Abfrage gestartet',
            'inquiry.synced' => 'Zielgruppe abgeglichen',
            'inquiry.deadline_changed' => 'Frist geändert',
            'inquiry.settings_changed' => 'Einstellungen geändert',
            'inquiry.closed' => 'Abfrage abgeschlossen',
            'inquiry.purged' => 'Antworten nach Ablauf der Aufbewahrungsfrist gelöscht',
            'assignment.extended' => 'Nachfrist gesetzt',
            'assignment.problem' => 'Technisches Problem gemeldet',
            'assignment.problem_resolved' => 'Problem gelöst',
            'assignment.note' => 'Bearbeitungshinweis',
            'assignment.correction_unlocked' => 'Korrektur freigegeben',
            'assignment.restarted' => 'Für die Familie neu gestartet',
            'assignment.filed' => 'Für die Schülerakte gedruckt',
            'inquiry.filed' => 'Akten-PDF erstellt',
            'response.submitted' => 'Antwort eingereicht',
            'response.corrected' => 'Korrektur eingereicht',
            'response.reviewed' => 'Antwort geprüft',
            'export.created' => 'Export erstellt',
            'export.downloaded' => 'Export heruntergeladen',
            default => $this->action,
        };
    }
}
