@extends('layouts.app')

@section('title', '| Elternabfragen · Änderungsmeldungen')

@section('content')
<div class="container-fluid px-4 py-6">
    @include('elternabfragen.admin._nav', [
        'title' => 'Änderungsmeldungen',
        'subtitle' => 'Von Eltern gemeldete Änderungen zu bereits abgegebenen Angaben – prüfen und übernehmen',
        'icon' => 'fas fa-exchange-alt',
    ])

    <form method="GET" class="ea-card p-4 mb-5 flex flex-wrap items-end gap-3">
        <div>
            <label for="status" class="ea-label">Stand</label>
            <select id="status" name="status" class="ea-input">
                <option value="" @selected($status === null)>alle</option>
                @foreach(\App\Enums\InquiryChangeRequestStatus::options() as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[200px]">
            <label for="abfrage" class="ea-label">Abfrage</label>
            <select id="abfrage" name="abfrage" class="ea-input">
                <option value="">alle</option>
                @foreach($inquiries as $inquiry)
                    <option value="{{ $inquiry->id }}" @selected((int) request('abfrage') === $inquiry->id)>{{ $inquiry->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="kind" class="ea-label">Schüler/in</label>
            <input id="kind" name="kind" value="{{ request('kind') }}" class="ea-input" placeholder="Vor- oder Nachname">
        </div>
        <button class="ea-btn ea-btn-secondary"><i class="fas fa-filter" aria-hidden="true"></i> Filtern</button>
    </form>

    <div class="ea-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="ea-table">
                <thead>
                    <tr><th>Schüler/in</th><th>Abfrage</th><th>Gemeldet</th><th>Nachricht</th><th>Stand</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($requests as $change)
                        <tr>
                            <td>
                                <span class="font-medium">{{ $change->child?->first_name }} {{ $change->child?->last_name }}</span>
                                <span class="block text-xs ea-muted">{{ $change->assignment?->group?->name ?? 'ohne Klasse' }}</span>
                            </td>
                            <td>{{ $change->inquiry?->title }}</td>
                            <td class="whitespace-nowrap">{{ $change->created_at->format('d.m.Y H:i') }}<span class="block text-xs ea-muted">{{ $change->user?->name ?? 'unbekannt' }}</span></td>
                            <td class="max-w-xs"><span class="line-clamp-2">{{ $change->message }}</span></td>
                            <td>
                                <x-elternabfragen.badge :color="$change->status->color()" dot>{{ $change->status->label() }}</x-elternabfragen.badge>
                                @if($change->handled_at && ! $change->isPending())<span class="block text-xs ea-muted">{{ $change->handled_at->format('d.m.Y') }}{{ $change->handler ? ' · '.$change->handler->name : '' }}</span>@endif
                            </td>
                            <td class="text-right"><a href="{{ route('elternabfragen.admin.change-requests.show', $change) }}" class="ea-btn ea-btn-ghost ea-btn-sm">{{ $change->isPending() ? 'Bearbeiten' : 'Ansehen' }} <i class="fas fa-chevron-right" aria-hidden="true"></i></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="ea-empty"><div class="ea-empty-icon"><i class="fas fa-check" aria-hidden="true"></i></div>{{ $status === 'pending' ? 'Keine offenen Änderungsmeldungen.' : 'Keine Änderungsmeldungen gefunden.' }}</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-5">{{ $requests->links() }}</div>
</div>
@endsection
