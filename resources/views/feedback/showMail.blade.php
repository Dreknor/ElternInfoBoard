@extends('layouts.app')
@section('title')
    - Kontakt
@endsection

@section('content')
    <a href="{{url('feedback')}}" class="btn btn-primary btn-round">zurück</a>
    <div class="card">
        <div class="card-header">
            <h6 class="card-title">
                An: {{$mail->to}}
            </h6>
            <div class="card-title">
                Betreff: {{$mail->subject}}
                <div class="pull-right">
                    {{$mail->created_at->format('d.m.Y H:i')}}
                </div>
            </div>
        </div>
        <div class="card-body">
            {!! $mail->text !!}
        </div>
        <div class="card-footer">
            @if($mail->getMedia('files')->count() >0 )
                <b>Anhänge:</b>

                <ul class="list-group">
                    @foreach($mail->getMedia('files') as $media)
                        <li class="list-group-item">
                            {{$media->name}}
                        </li>
                    @endforeach
                </ul>

            @endif

        </div>
    </div>

@endsection

