@extends('layouts.app')

@section('css')
    <link href="{{asset('css/switch.css')}}" rel="stylesheet" />
    <style>
        .sortable-ghost {
            opacity: 0.4;
            background: #e0e7ff !important;
        }
        .drag-handle {
            cursor: grab;
            color: #9ca3af;
            padding: 0 8px;
        }
        .drag-handle:active {
            cursor: grabbing;
        }
        #sort-status {
            display: none;
            margin-top: 8px;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            Module
                        </h5>
                        <small class="text-muted">
                            <i class="fas fa-grip-vertical mr-1"></i>
                            Zeilen verschieben, um die Reihenfolge in der Seitenleiste zu ändern
                        </small>
                    </div>
                    <div class="card-body">
                        <div id="sort-status" class="alert alert-success alert-sm py-1 px-3">
                            <i class="fas fa-check-circle mr-1"></i> Reihenfolge gespeichert
                        </div>
                        <div class="table-responsive-sm">
                            <form class="form-horizontal" method="post" action="{{url('roles')}}">
                                @csrf
                                @method ('put')
                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        <th style="width:40px;"></th>
                                        <th>Modulname</th>
                                        <th>Beschreibung</th>
                                        <th>mobile Navigation</th>
                                        <th>Aktiv</th>
                                    </tr>
                                    </thead>
                                    <tbody id="sortable-modules">
                                    @foreach($module as $modul)
                                        <tr data-id="{{$modul->id}}">
                                            <td class="drag-handle">
                                                <i class="fas fa-grip-vertical"></i>
                                            </td>
                                            <td>
                                                {{$modul->setting}}
                                            </td>
                                            <td>
                                                {{$modul->description}}
                                            </td>
                                            <td>
                                                @if(is_array($modul->options) && array_key_exists('nav', $modul->options))
                                                    <label class="switch">
                                                        <input type="checkbox" class="bottomMenuButton"
                                                               id="{{$modul->setting}}"
                                                                @if(is_array($modul->options['nav']) && ($modul->options['nav']['bottom-nav'] ?? null) == "true") checked @endif>
                                                        <span class="slider round"></span>
                                                    </label>
                                                @else
                                                    ---
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $isProtected = in_array($modul->setting, ['Settings']);
                                                    $isActive = is_array($modul->options) && ($modul->options['active'] ?? 0) == 1;
                                                @endphp
                                                @if($isProtected)
                                                    {{-- Kernmodule dürfen nicht deaktiviert werden --}}
                                                    <span title="Dieses Modul kann nicht deaktiviert werden" class="d-inline-flex align-items-center gap-1 text-muted" style="font-size:0.8rem;">
                                                        <i class="fas fa-lock text-warning"></i>
                                                        <span>gesichert</span>
                                                    </span>
                                                @else
                                                    <!-- Rounded switch -->
                                                    <label class="switch">
                                                        <input type="checkbox" class="activButton" id="{{$modul->setting}}"
                                                               @if($isActive) checked @endif>
                                                        <span class="slider round"></span>
                                                    </label>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td colspan="2">
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-success btn-block collapse" id="btn-save">speichern</button>
                                            </div>
                                        </td>
                                    </tr>
                                    </tfoot>
                                </table>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h5>
                    Scan nach alten oder verwaisten Dateien sowie gelöschten Nachrichten
                </h5>
            </div>
            <div class="card-footer">
                <a href="{{url('settings/scan')}}" class="btn btn-primary btn-block">
                    Scan starten
                </a>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
    <script>
        $('input.activButton').on('click', function (e) {
            var Id = this.id;
            location.href = '{{url("/modules/modul")}}' + '/' + Id
        });

        $('input.bottomMenuButton').on('click', function (e) {
            var Id = this.id;
            location.href = '{{url("/modules/modul/bottomnav")}}' + '/' + Id
        });

        // Drag-and-Drop Sortierung
        var sortable = Sortable.create(document.getElementById('sortable-modules'), {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function () {
                var order = [];
                document.querySelectorAll('#sortable-modules tr[data-id]').forEach(function (row) {
                    order.push(row.getAttribute('data-id'));
                });

                $.ajax({
                    type: 'POST',
                    url: '{{ route('modules.reorder') }}',
                    data: {
                        _token: '{{ csrf_token() }}',
                        order: order
                    },
                    success: function () {
                        var $status = $('#sort-status');
                        $status.fadeIn(200);
                        setTimeout(function () { $status.fadeOut(600); }, 2000);
                    },
                    error: function () {
                        alert('Fehler beim Speichern der Reihenfolge.');
                    }
                });
            }
        });
    </script>
@endpush
