@if($user->read_receipts()->where('post_id', $post->id)->first() != null)
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check"></i> {{ __('Nachricht gelesen und bestätigt') }}
                </div>
            </div>
        </div>
    </div>
@else
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-times"></i> {{ __('Nachricht noch nicht gelesen') }}
                </div>
                <form action="{{ route('nachrichten.read_receipt') }}" method="post">
                    @csrf
                    <input type="hidden" name="post_id" value="{{$post->id}}">
                    <button type="submit" class="btn btn-primary btn-block">{{ __('Nachricht als gelesen markieren') }}</button>
                </form>
            </div>
        </div>
    </div>
@endif
@if(auth()->user()->can('manage rueckmeldungen') or auth()->id() == $post->author)
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                Es sind {{ $post->receipts->count() }} Lesebestätigungen vorhanden. <a data-toggle="collapse" href="#{{$post->id}}_receipts" role="button" aria-expanded="false" aria-controls="{{$post->id}}_receipts">Anzeigen</a>
            </div>
        </div>
        <div class="collapse" id="{{$post->id}}_receipts">
            <div class="row ">
                <div class="col-12">
                    <ul class="list-group">
                        @foreach($post->receipts as $receipt)
                            <li class="list-group-item">
                                {{ $receipt->user->name}} ({{ $receipt->created_at->format('d.m.Y H:i') }})
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif
