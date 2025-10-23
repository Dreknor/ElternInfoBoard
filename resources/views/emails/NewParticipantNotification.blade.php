@component('mail::message')
# Neuer Teilnehmer in Ihrer AG

Sehr geehrte(r) {{ $arbeitsgemeinschaft->manager->name }},

ein neuer Teilnehmer wurde zu Ihrer Arbeitsgemeinschaft "{{ $arbeitsgemeinschaft->name }}" hinzugefügt:

**Name:** {{ $child->last_name }}, {{ $child->first_name }}
**Gruppe:** {{ $child->group->name }}
**Klasse:** {{ $child->class->name }}

Die aktuelle Teilnehmerzahl beträgt: {{ $arbeitsgemeinschaft->participants->count() }}/{{ $arbeitsgemeinschaft->max_participants }}

Mit freundlichen Grüßen
{{ config('app.name') }}
@endcomponent
