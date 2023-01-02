@if(isset($termine) and !is_null($termine) and !isset($archiv))
    <div class="card border blur">
        <div class="card-header border-bottom">
            <div class="row">
                <div class="col-md-10">
                    <h6 class="card-title">
                        aktuelle Termine
                    </h6>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="container-fluid  ">
                @foreach($termine as $termin)
                    @include('termine.termin')
                @endforeach
            </div>
        </div>
    </div>
@endif
