@extends('layouts.app')

@section('content')
    <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h5>
                        verwaiste Dateien
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        @foreach($media as $items)
                            @foreach($items as $item)
                                <li class="list-group-item">
                                    {{$item}}
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
                <div class="card-footer">
                    <form action="{{url('settings/removeFiles')}}" method="post" class="form-horizontal">
                        @csrf
                        @method('delete')
                        <label>
                            ältere Dateien vor diesem Datum löschen
                            <input type="date" name="deleteBeforeDate" class="form-control"
                                   value="{{\Carbon\Carbon::now()->subYear()->format('Y-m-d')}}">
                        </label>
                        <button type="submit" class="btn btn-danger btn-block">
                            löschen
                        </button>
                    </form>
                </div>
            </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    gelöschte Nachrichten ({{@count($deletedPosts)}})
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                          <tr>
                              <th>
                                  erstellt
                              </th>
                              <th>
                                  gelöscht
                              </th>
                              <th>
                                  Autor
                              </th>
                              <th>
                                  Titel
                              </th>
                              <th></th>
                          </tr>
                        </thead>
                        @foreach($deletedPosts as $item)
                           <tr>
                               <td>
                                   {{$item->created_at}}
                               </td>
                               <td>
                                   {{$item->deleted_at}} (nach {{$item->created_at->diffInHours($item->deleted_at)}} h)
                               </td>
                               <td>
                                   {{$item->autor->name}}
                               </td>
                               <td>
                                   {{$item->header}}
                               </td>
                               <td>
                                   <a href="{{url('settings/post/'.$item->id.'/destroy')}}" class="link-danger">
                                       löschen
                                   </a>
                               </td>
                           </tr>
                        @endforeach
                    </table>
                </div>
            </div>
    </div>
    <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h5>
                        alte Dateien ({{@count($oldMedia)}})
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                          <tr>
                              <th>
                                  erstellt
                              </th>
                              <th>
                                  Name
                              </th>
                              <th>
                                  gehört zu
                              </th>
                              <th></th>
                          </tr>
                        </thead>
                        @foreach($oldMedia as $item)
                           <tr @if(!$item->model) class="bg-danger" @endif>
                               <td>
                                   {{$item->created_at}}
                               </td>
                               <td>
                                   {{$item->name}}
                               </td>
                               <td>
                                   @if($item->model_type == "App\Model\Group")
                                       <b>Dateidownload</b> für {{$item->model->name}}
                                   @elseif($item->model_type == "App\Model\Post")
                                       <b>Post:</b>
                                       {{optional($item->model)->header}}
                                   @endif
                               </td>
                               <td>
                                   <a href="{{url('settings/file/'.$item->id.'/destroy')}}" class="link-danger">
                                       löschen
                                   </a>
                               </td>
                           </tr>
                        @endforeach
                    </table>
                </div>
            </div>
    </div>

@endsection
