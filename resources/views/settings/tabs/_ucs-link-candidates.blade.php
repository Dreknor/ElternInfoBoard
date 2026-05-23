{{--
    Partial: Offene UCS-Link-Kandidaten (Admin-UI, TODO-08)
    Wird im UCS-Settings-Tab unterhalb des Formulars eingebunden.

    Zeigt alle Kandidaten, bei denen confirmed_at IS NULL (d.h. weder bestätigt noch verworfen).
    Bestätigte Einträge verknüpfen das lokale Kind mit dem UCS-Account.
    Verworfene Einträge setzen payload.status='rejected', damit der Sync nicht
    erneut detektiert.

    Berechtigung: 'edit settings' (bereits im Parent-Template geprüft, aber
    zusätzlich via @can abgesichert).
--}}
@can('edit settings')
@php
    $openCandidates = \App\Model\UcsLinkCandidate::with([
                            'child',
                            'child.group',
                        ])
                        ->open()
                        ->orderByDesc('detected_at')
                        ->get();
@endphp

<div class="card mb-3 mt-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="m-0">
            <i class="fas fa-link mr-1 text-warning"></i>
            Verknüpfungsvorschläge (Duplikat-Vermeidung)
            @if($openCandidates->isNotEmpty())
                <span class="badge badge-warning ml-1">{{ $openCandidates->count() }}</span>
            @endif
        </h6>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Beim Sync wurden lokale Kinder (<code>ucs_source='local'</code>) gefunden, deren
            Vor-/Nachname und Klasse zu einem UCS-Kind passen.
            Um Duplikate zu vermeiden, wurden <strong>keine</strong> neuen Datensätze angelegt –
            stattdessen erscheinen sie hier als Vorschläge.
            <br>
            <strong>Verknüpfen</strong>: setzt <code>ucs_username</code> (und ggf. <code>ucs_uuid</code>)
            am lokalen Kind.<br>
            <strong>Verwerfen</strong>: markiert den Vorschlag als abgelehnt – der nächste Sync
            erstellt keinen neuen Vorschlag für dieses Paar.
        </p>

        @if($openCandidates->isEmpty())
            <div class="alert alert-success py-2 mb-0">
                <i class="fas fa-check-circle mr-1"></i>
                Keine offenen Verknüpfungsvorschläge vorhanden.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="min-width:160px">Lokales Kind</th>
                            <th style="min-width:160px">UCS-Vorschlag</th>
                            <th>Grund</th>
                            <th style="min-width:110px">Erkannt am</th>
                            <th style="min-width:200px">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($openCandidates as $candidate)
                            @php
                                $child   = $candidate->child;
                                $payload = $candidate->payload ?? [];
                            @endphp
                            <tr>
                                {{-- Lokales Kind --}}
                                <td>
                                    <strong>{{ $child?->first_name }} {{ $child?->last_name }}</strong>
                                    @if($child?->group)
                                        <br><small class="text-muted">
                                            <i class="fas fa-users mr-1"></i>{{ $child->group->name }}
                                        </small>
                                    @endif
                                    <br><small class="text-muted">ID&nbsp;{{ $candidate->child_id }}</small>
                                </td>

                                {{-- UCS-Vorschlag --}}
                                <td>
                                    <code>{{ $candidate->ucs_username }}</code>
                                    @if(! empty($payload['firstname']) || ! empty($payload['lastname']))
                                        <br>
                                        <small class="text-muted">
                                            {{ $payload['firstname'] ?? '' }}
                                            {{ $payload['lastname']  ?? '' }}
                                        </small>
                                    @endif
                                    @if($candidate->ucs_uuid)
                                        <br>
                                        <small class="text-muted" title="{{ $candidate->ucs_uuid }}">
                                            {{ Str::limit($candidate->ucs_uuid, 20) }}
                                        </small>
                                    @endif
                                </td>

                                {{-- Grund --}}
                                <td>
                                    @if($candidate->reason === 'name_match')
                                        <span class="badge badge-info">Namen + Klasse</span>
                                    @elseif($candidate->reason === 'manual')
                                        <span class="badge badge-secondary">Manuell</span>
                                    @else
                                        <span class="badge badge-light">{{ $candidate->reason }}</span>
                                    @endif
                                </td>

                                {{-- Erkannt am --}}
                                <td>
                                    <small>
                                        {{ $candidate->detected_at?->format('d.m.Y H:i') ?? '–' }}
                                    </small>
                                </td>

                                {{-- Aktionen --}}
                                <td class="text-nowrap">
                                    {{-- Verknüpfen --}}
                                    <form
                                        action="{{ route('settings.ucs.link.confirm', $candidate) }}"
                                        method="POST"
                                        class="d-inline"
                                    >
                                        @csrf
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Kind &quot;{{ addslashes(($child?->first_name ?? '').' '.($child?->last_name ?? '')) }}&quot; mit UCS-Account &quot;{{ addslashes($candidate->ucs_username) }}&quot; verknüpfen?')"
                                        >
                                            <i class="fas fa-check mr-1"></i>Verknüpfen
                                        </button>
                                    </form>

                                    {{-- Verwerfen --}}
                                    <form
                                        action="{{ route('settings.ucs.link.reject', $candidate) }}"
                                        method="POST"
                                        class="d-inline ml-1"
                                    >
                                        @csrf
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Vorschlag verwerfen? Der nächste Sync wird dieses Paar nicht erneut vorschlagen.')"
                                        >
                                            <i class="fas fa-times mr-1"></i>Verwerfen
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-muted small mt-2 mb-0">
                <i class="fas fa-info-circle mr-1"></i>
                Alternativ: <code>php artisan ucs:link-child &lt;child_id&gt; &lt;ucs_username&gt;</code>
            </p>
        @endif
    </div>
</div>
@endcan

