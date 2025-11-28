<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $Liste->listenname }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="{{ asset('css/all.css') }}" rel="stylesheet">
    <script src="{{ asset('js/html2pdf.bundle.min.js') }}"></script>

    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="w-full max-w-4xl mx-auto px-4 py-6">
        <!-- Action Buttons -->
        <div class="flex gap-2 mb-6 no-print">
            <button onclick="generatePDF()"
                    class="inline-flex items-center gap-2 px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200">
                <i class="fas fa-file-pdf"></i>
                Als PDF herunterladen
            </button>
            <a href="{{ route('listen.export-excel.termine', ['id' => $Liste->id]) }}"
               class="inline-flex items-center gap-2 px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                <i class="fas fa-file-excel"></i>
                Als Excel herunterladen
            </a>
        </div>

        <!-- Export Content -->
        <div id="export" class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="mb-8 pb-6 border-b-2 border-gray-300">
                <h1 class="text-4xl font-bold text-gray-800 mb-2">{{ $Liste->listenname }}</h1>
                @if($Liste->comment)
                    <p class="text-gray-600">{{ strip_tags($Liste->comment) }}</p>
                @endif
                <p class="text-sm text-gray-500 mt-2">Erstellungsdatum: {{ now()->format('d.m.Y H:i') }}</p>
            </div>

            <!-- Table -->
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gradient-to-r from-blue-600 to-indigo-600">
                        <th class="text-left px-4 py-3 font-semibold text-white border border-gray-300">Datum</th>
                        <th class="text-left px-4 py-3 font-semibold text-white border border-gray-300">Uhrzeit</th>
                        <th class="text-left px-4 py-3 font-semibold text-white border border-gray-300">Familie</th>
                        <th class="text-left px-4 py-3 font-semibold text-white border border-gray-300">Bemerkungen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($listentermine as $eintrag)
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-4 py-3 border border-gray-300 text-gray-700">
                                {{ $eintrag->termin->format('d.m.Y') }}
                            </td>
                            <td class="px-4 py-3 border border-gray-300 text-gray-700">
                                {{ $eintrag->termin->format('H:i') }} -
                                {{ $eintrag->termin->copy()->addMinutes($Liste->duration)->format('H:i') }} Uhr
                            </td>
                            <td class="px-4 py-3 border border-gray-300 text-gray-700">
                                {{ $eintrag->eingetragenePerson?->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 border border-gray-300 text-gray-700">
                                {{ $eintrag->comment ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500 border border-gray-300">
                                Es wurden noch keine Termine eingetragen.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t-2 border-gray-300 text-xs text-gray-500">
                <p>Gesamtzahl Einträge: <strong>{{ $listentermine->count() }}</strong></p>
                <p>Ausgegeben: {{ now()->format('d.m.Y H:i:s') }}</p>
            </div>
        </div>
    </div>

    <script>
        function generatePDF() {
            const element = document.getElementById('export');
            html2pdf().from(element).save('{{ $Liste->listenname }}.pdf');
        }
    </script>
</body>
</html>

