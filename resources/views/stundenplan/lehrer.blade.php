@extends('layouts.app')

@section('title', '- ' . __('stundenplan.title') . ' - ' . $teacher)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header with back button -->
    <div class="mb-8">
        <a href="/stundenplan" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
            <i class="fas fa-arrow-left mr-2"></i>
            {{ __('stundenplan.back_to_overview') }}
        </a>

        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>
                    {{ __('stundenplan.title') }} - {{ __('stundenplan.teacher') }} {{ $teacher }}
                    @if($currentWeek)
                        <span class="ml-2 px-3 py-1 text-lg font-semibold rounded-full {{ $currentWeek->type === 'A' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                            {{ $currentWeek->type }}-{{ __('stundenplan.week') }}
                        </span>
                    @endif
                </h1>
                <p class="text-gray-600">
                    {{ __('stundenplan.valid_from') }}: {{ $basisdaten['DatumVon'] }} - {{ $basisdaten['DatumBis'] }} |
                    {{ __('stundenplan.school_weeks') }}: {{ $basisdaten['SwVon'] }} - {{ $basisdaten['SwBis'] }}
                </p>
            </div>

            <div class="flex space-x-2">
                <button onclick="window.print()"
                        class="px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-print mr-2"></i>
                    {{ __('stundenplan.print') }}
                </button>
            </div>
        </div>
    </div>

    <!-- News Section -->
    @if($news && $news->count() > 0)
        <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-md shadow">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">{{ __('stundenplan.important_notes') }}</h3>
                    <div class="mt-2 text-sm text-yellow-700 space-y-2">
                        @foreach($news as $newsItem)
                            <div class="flex items-start">
                                <i class="fas fa-bullhorn mr-2 mt-0.5"></i>
                                <div>
                                    <span class="font-semibold">{{ $newsItem->news }}</span>
                                    <span class="text-xs text-yellow-600 ml-2">
                                        ({{ $newsItem->start->format('d.m.') }} - {{ $newsItem->end ? $newsItem->end->format('d.m.Y') : __('stundenplan.indefinite') }})
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Absences Section -->
    @if($absences && $absences->count() > 0)
        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded-md shadow">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-times text-red-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">{{ __('stundenplan.absences') }}</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <div class="flex flex-wrap gap-2">
                            @foreach($absences as $absence)
                                <div class="bg-white px-3 py-1 rounded-full border border-red-200">
                                    <i class="fas fa-user mr-1"></i>
                                    <span class="font-semibold">{{ $absence->name }}</span>
                                    @if($absence->reason)
                                        <span class="text-xs ml-1">({{ $absence->reason }})</span>
                                    @endif
                                    <span class="text-xs text-red-600 ml-1">
                                        {{ $absence->start_date->format('d.m.') }} - {{ $absence->end_date ? $absence->end_date->format('d.m.') : '?' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Timetable -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gradient-to-r from-blue-600 to-indigo-600">
                    <tr>
                        <th scope="col" class="px-3 py-3 text-center text-xs font-medium text-white uppercase tracking-wider w-20">
                            Zeit
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            {{ __('stundenplan.monday') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            {{ __('stundenplan.tuesday') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            {{ __('stundenplan.wednesday') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            {{ __('stundenplan.thursday') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            {{ __('stundenplan.friday') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($zeitslots as $slot)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-3 py-4 whitespace-nowrap text-center bg-gray-50">
                                <div class="text-sm font-bold text-gray-900">{{ $slot['Stunde'] }}.</div>
                                <div class="text-xs text-gray-600">{{ $slot['ZeitVon'] }}</div>
                                <div class="text-xs text-gray-500">{{ $slot['ZeitBis'] }}</div>
                            </td>

                            @for($tag = 1; $tag <= 5; $tag++)
                                <td class="px-4 py-4 align-top relative">
                                    @php
                                        // Check if there's a substitution for this day and lesson
                                        $hasVertretung = false;
                                        $vertretungForSlot = null;
                                        if (isset($vertretungen[$tag])) {
                                            foreach ($vertretungen[$tag] as $v) {
                                                if ($v->stunde == $slot['Stunde']) {
                                                    $hasVertretung = true;
                                                    $vertretungForSlot = $v;
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp

                                    @if($hasVertretung && $vertretungForSlot)
                                        <!-- Substitution Badge -->
                                        <div class="absolute top-1 right-1 z-10">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800 border border-red-300">
                                                <i class="fas fa-exchange-alt mr-1"></i>
                                                {{ __('stundenplan.substitution') }}
                                            </span>
                                        </div>

                                        <!-- Substitution Details -->
                                        <div class="rounded-md border-l-4 border-red-500 bg-red-50 p-3 h-full">
                                            <div class="space-y-1">
                                                @if($vertretungForSlot->klasse)
                                                    <div class="text-xs text-red-700 font-semibold">
                                                        <i class="fas fa-users mr-1"></i>
                                                        {{ $vertretungForSlot->klasse }}
                                                    </div>
                                                @endif
                                                @if($vertretungForSlot->altFach)
                                                    <div class="text-xs text-red-700 line-through">
                                                        <i class="fas fa-book mr-1"></i>
                                                        {{ $vertretungForSlot->altFach }}
                                                    </div>
                                                @endif
                                                @if($vertretungForSlot->neuFach)
                                                    <div class="font-semibold text-red-900">
                                                        <i class="fas fa-arrow-right mr-1"></i>
                                                        {{ $vertretungForSlot->neuFach }}
                                                    </div>
                                                @endif
                                                @if($vertretungForSlot->comment)
                                                    <div class="text-xs text-red-700 mt-2 italic border-t border-red-200 pt-1">
                                                        <i class="fas fa-comment-dots mr-1"></i>
                                                        {{ $vertretungForSlot->comment }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @elseif(isset($timetable[$tag][$slot['Stunde']]) && !empty($timetable[$tag][$slot['Stunde']]))
                                        <div class="space-y-2">
                                            @foreach($timetable[$tag][$slot['Stunde']] as $entry)
                                                @php
                                                    $fach = $entry['PlFa'] ?? '';
                                                    $lehrer = implode(', ', $entry['PlLe'] ?? []);
                                                    $raum = implode(', ', $entry['PlRa'] ?? []);
                                                    $klassen = implode(', ', $entry['PlKl'] ?? []);

                                                    // Color coding based on subject
                                                    $colorClass = match(true) {
                                                        str_contains($fach, 'MA') => 'bg-blue-100 border-blue-300',
                                                        str_contains($fach, 'DE') => 'bg-yellow-100 border-yellow-300',
                                                        str_contains($fach, 'FAwp') => 'bg-purple-100 border-purple-300',
                                                        str_contains($fach, 'SP') => 'bg-green-100 border-green-300',
                                                        str_contains($fach, 'MU') => 'bg-pink-100 border-pink-300',
                                                        str_contains($fach, 'KU') => 'bg-orange-100 border-orange-300',
                                                        str_contains($fach, 'RE') => 'bg-indigo-100 border-indigo-300',
                                                        default => 'bg-gray-100 border-gray-300'
                                                    };
                                                @endphp

                                                <div class="rounded-md border-l-4 p-3 {{ $colorClass }}">
                                                    <div class="font-semibold text-gray-900 mb-1">
                                                        {{ $fach }}
                                                    </div>
                                                    @if($klassen)
                                                        <div class="text-xs text-gray-700 flex items-center mb-1">
                                                            <i class="fas fa-users text-gray-500 mr-1" style="width: 12px;"></i>
                                                            {{ $klassen }}
                                                        </div>
                                                    @endif
                                                    @if($raum)
                                                        <div class="text-xs text-gray-700 flex items-center">
                                                            <i class="fas fa-door-open text-gray-500 mr-1" style="width: 12px;"></i>
                                                            {{ $raum }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center text-gray-400 text-sm py-3">
                                            <i class="fas fa-minus"></i>
                                        </div>
                                    @endif
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Legend -->
    <div class="mt-6 bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-palette mr-2"></i>
            {{ __('stundenplan.color_legend') }}
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
            <div class="flex items-center">
                <div class="w-4 h-4 rounded bg-blue-100 border-l-4 border-blue-300 mr-2"></div>
                <span class="text-sm text-gray-700">{{ __('stundenplan.mathematics') }}</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 rounded bg-yellow-100 border-l-4 border-yellow-300 mr-2"></div>
                <span class="text-sm text-gray-700">{{ __('stundenplan.german') }}</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 rounded bg-purple-100 border-l-4 border-purple-300 mr-2"></div>
                <span class="text-sm text-gray-700">{{ __('stundenplan.free_work') }}</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 rounded bg-green-100 border-l-4 border-green-300 mr-2"></div>
                <span class="text-sm text-gray-700">{{ __('stundenplan.sports') }}</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 rounded bg-pink-100 border-l-4 border-pink-300 mr-2"></div>
                <span class="text-sm text-gray-700">{{ __('stundenplan.music') }}</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 rounded bg-orange-100 border-l-4 border-orange-300 mr-2"></div>
                <span class="text-sm text-gray-700">{{ __('stundenplan.art') }}</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 rounded bg-indigo-100 border-l-4 border-indigo-300 mr-2"></div>
                <span class="text-sm text-gray-700">{{ __('stundenplan.religion') }}</span>
            </div>
            <div class="flex items-center">
                <div class="w-4 h-4 rounded bg-gray-100 border-l-4 border-gray-300 mr-2"></div>
                <span class="text-sm text-gray-700">{{ __('stundenplan.other') }}</span>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none !important;
    }

    body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
}
</style>
@endsection

