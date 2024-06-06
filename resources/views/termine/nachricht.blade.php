@if(isset($termine) and !is_null($termine) and !isset($archiv))
    <div class="card border blur">
        <div class="card-header border-bottom">
            <div class="row">
                <div class="col-10">
                    <h5 class="">
                        aktuelle Termine
                    </h5>
                </div>
                @can('edit termin')
                    <div class="col-1 ml-auto">
                        <div class="pull-right">
                            <a href="#" class="card-link text-black-50" data-toggle="dropdown" aria-haspopup="true"
                               aria-expanded="false">
                                <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                            </a>
                            <div class="dropdown-menu">
                                <a href="{{url('termin/create')}}" class="dropdown-item">
                                    <i class="fa fa-plus"></i>
                                    neuer Termin
                                </a>
                            </div>
                        </div>
                    </div>
                @endcan
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
