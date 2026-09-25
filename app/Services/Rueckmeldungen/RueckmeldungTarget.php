<?php

namespace App\Services\Rueckmeldungen;

use App\Model\Child;
use App\Model\UserRueckmeldungen;
use Illuminate\Support\Collection;

/**
 * Ein Antwortziel einer Rückmeldung aus Sicht eines Users: entweder ein Kind
 * (Scope child) oder die Familie/Person (Scope family/person bzw. Fallback).
 */
final class RueckmeldungTarget
{
    /**
     * @param  Collection<int, UserRueckmeldungen>  $answers  vorhandene Antworten für dieses Ziel
     */
    public function __construct(
        public readonly string $scope,
        public readonly ?Child $child,
        public readonly Collection $answers,
        public readonly bool $canAnswer,
    ) {}

    public function isChild(): bool
    {
        return $this->child !== null;
    }

    public function isAnswered(): bool
    {
        return $this->answers->isNotEmpty();
    }

    /**
     * Eindeutiger Schlüssel für DOM-IDs (Formular ein-/ausblenden).
     */
    public function domKey(int $postId): string
    {
        return $this->child ? $postId.'_c'.$this->child->id : (string) $postId;
    }

    public function label(): ?string
    {
        if (! $this->child) {
            return null;
        }

        $class = $this->child->class?->name ?? $this->child->group?->name;

        return trim($this->child->first_name.' '.$this->child->last_name).($class ? ' ('.$class.')' : '');
    }

    /**
     * Wer hat bereits geantwortet (erste Antwort)?
     */
    public function answeredBy(): ?string
    {
        return $this->answers->sortBy('created_at')->first()?->user?->name;
    }
}
