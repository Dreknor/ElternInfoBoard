<!DOCTYPE html>
<html>
<head>
    <title>{{$Liste->listenname}}</title>

    <!-- CSS Files -->
    <link href="{{asset('css/bootstrap.min.css')}}" rel="stylesheet" />
    <link href="{{asset('css/paper-dashboard.css?v=2.0.0')}}" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700,200" rel="stylesheet" />

    <link href="{{asset('/css/all.css')}}" rel="stylesheet"> <!--load all styles -->
    <script src="{{asset('js/html2pdf.bundle.min.js')}}"></script>
    <script>
        function generatePDF() {
            // Choose the element that our invoice is rendered in.
            const element = document.getElementById('export');
            // Choose the element and save the PDF for our user.
            html2pdf().from(element).save();
        }
    </script>
</head>
<body>
<button onclick="generatePDF()"class="btn btn-info pull-right">Download as PDF</button>

<div id="export">
    <h2>
        {{$Liste->listenname}}
    </h2>

    <table class="table table-striped table-bordered">
        <thead>
        <tr>
            <th>
                Datum
            </th>
            <th>
                Uhrzeit
            </th>
            <th>
                Familie
            </th>
            <th>
                Bemerkungen
            </th>
        </tr>
        </thead>
        <tbody>
        @foreach($listentermine as $eintrag)
            <tr>
                <td>
                    {{$eintrag->termin->format('d.m.Y')}}
                </td>
                <td>
                    {{	$eintrag->termin->format('H:i')}} - {{$eintrag->termin->copy()->addMinutes($Liste->duration)->format('H:i')}} Uhr
                </td>
                <td>
                    {{optional($eintrag->eingetragenePerson)->name }}
                </td>
                <td>
                    {{$eintrag->comment}}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>


</body>
</html>
