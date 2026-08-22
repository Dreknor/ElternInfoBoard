@extends('layouts.app')

@section('css')
    <link href="{{asset('css/switch.css')}}" rel="stylesheet" />
    <style>
        /* Modern UI Tweaks */
        .card-modern {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: none;
            border-radius: 0.5rem;
        }
        .card-modern .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #f3f4f6;
            border-top-left-radius: 0.5rem !important;
            border-top-right-radius: 0.5rem !important;
            padding: 1.25rem 1.5rem;
        }
        .table-modern th {
            border-top: none;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #6b7280;
            background-color: #f9fafb;
            padding: 0.75rem 1rem;
        }
        .table-modern td {
            vertical-align: middle;
            border-color: #f3f4f6;
            padding: 1rem;
        }
        .table-modern tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Typography */
        .module-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.95rem;
        }
        .module-desc {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        /* Soft Badges */
        .badge-soft-info {
            background-color: #e0f2fe;
            color: #0369a1;
            font-weight: 500;
            padding: 0.35em 0.75em;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        .badge-soft-secondary {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 500;
            padding: 0.35em 0.75em;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        /* Drag Handle */
        .drag-handle {
            cursor: grab;
            color: #cbd5e1;
            padding: 0 12px;
            transition: color 0.2s ease;
        }
        .drag-handle:hover {
            color: #64748b;
        }
        .drag-handle:active {
            cursor: grabbing;
        }
        .sortable-ghost {
            opacity: 0.5;
            background: #e0e7ff !important;
        }

        #sort-status {
            display: none;
            margin: 15px 15px 0 15px;
            border-radius: 6px;
        }

        /* Flex Gaps für ältere Browser als margin umsetzen */
        .badge-container span {
            margin-right: 4px;
            margin-bottom: 4px;
            display: inline-block;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-3">
        <div class="row">
            <div class="col-12">
                <div class="card card-modern mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-1 font-weight-bold">
                                Module & Navigation
                            </h5>
                            <small class="text-muted">
                                <i class="fas fa-grip-vertical mr-1"></i>
                                Zeilen verschieben, um die Reihenfolge im Menü anzupassen
                            </small>
                        </div>
                    </div>

                    <div id="sort-status" class="alert alert-success alert-sm py-2 px-3 shadow-sm border-0">
                        <i class="fas fa-check-circle mr-2"></i> Reihenfolge erfolgreich gespeichert
                    </div>

                    <div class="table-responsive">
                        <form class="form-horizontal" method="post" action="{{url('roles')}}">
                            @csrf
                            @method ('put')
                            <table class="table table-modern mb-0">
                                <thead>
                                <tr>
                                    <th style="width: 30px;"></th>
                                    <th style="width: 25%;">Modul</th>
                                    <th style="width: 35%;">Berechtigungen (Navigation)</th>
                                    <th class="text-center">Mobile Nav.</th>
                                    <th class="text-center">Status</th>
                                </tr>
                                </thead>
                                <tbody id="sortable-modules">
                                @foreach($module as $modul)
                                    <tr data-id="{{$modul->id}}">
                                        <td class="drag-handle text-center">
                                            <i class="fas fa-grip-vertical"></i>
                                        </td>
                                        <td>
                                            <div class="module-title">{{$modul->setting}}</div>
                                            <div class="module-desc">{{$modul->description}}</div>
                                        </td>
                                        <td>
                                            @php
                                                $options = is_array($modul->options) ? $modul->options : [];
                                                $userRights = $options['rights'] ?? [];
                                                $adminRights = $options['adm-nav']['adm-rights'] ?? [];
                                            @endphp

                                            @if(!empty($userRights))
                                                <div class="mb-2 badge-container">
                                                    <small class="text-muted d-block mb-1" style="font-size: 0.75rem;">
                                                        <i class="fas fa-users mr-1"></i>Sorgeberechtigte (App/Web):
                                                    </small>
                                                    @foreach((array)$userRights as $right)
                                                        <span class="badge-soft-info">{{ $right }}</span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if(!empty($adminRights))
                                                <div class="badge-container {{ !empty($userRights) ? 'mt-2 pt-2 border-top border-light' : '' }}">
                                                    <small class="text-muted d-block mb-1" style="font-size: 0.75rem;">
                                                        <i class="fas fa-cog mr-1"></i>Verwaltung:
                                                    </small>
                                                    @foreach((array)$adminRights as $right)
                                                        <span class="badge-soft-secondary">{{ $right }}</span>
                                                    @endforeach
                                                </div>
                                            @endif

                                            @if(empty($userRights) && empty($adminRights))
                                                <span class="text-muted small">
                                                    <i class="fas fa-info-circle mr-1"></i> Standard-Modul (keine spez. Rechte)
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(is_array($modul->options) && array_key_exists('nav', $modul->options))
                                                <label class="switch mb-0">
                                                    <input type="checkbox" class="bottomMenuButton"
                                                           id="{{$modul->setting}}"
                                                           @if(is_array($modul->options['nav']) && ($modul->options['nav']['bottom-nav'] ?? null) == "true") checked @endif>
                                                    <span class="slider round"></span>
                                                </label>
                                            @else
                                                <span class="text-muted small">---</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $isProtected = in_array($modul->setting, ['Settings']);
                                                $isActive = is_array($modul->options) && ($modul->options['active'] ?? 0) == 1;
                                            @endphp
                                            @if($isProtected)
                                                <span title="Dieses Modul ist systemrelevant und kann nicht deaktiviert werden." class="badge badge-light border text-muted">
                                                    <i class="fas fa-lock mr-1"></i> System
                                                </span>
                                            @else
                                                <label class="switch mb-0">
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
                                    <td colspan="5" class="bg-light border-0">
                                        <button type="submit" class="btn btn-success collapse" id="btn-save">
                                            <i class="fas fa-save mr-1"></i> Änderungen speichern
                                        </button>
                                    </td>
                                </tr>
                                </tfoot>
                            </table>
                        </form>
                    </div>
                </div>

                {{-- Scan Card Modernized --}}
                <div class="card card-modern border-0">
                    <div class="card-body d-flex align-items-center justify-content-between p-4">
                        <div>
                            <h6 class="mb-1 font-weight-bold"><i class="fas fa-broom text-primary mr-2"></i>System-Bereinigung</h6>
                            <p class="text-muted small mb-0">Scan nach alten oder verwaisten Dateien sowie gelöschten Nachrichten, um Speicherplatz freizugeben.</p>
                        </div>
                        <a href="{{url('settings/scan')}}" class="btn btn-outline-primary shadow-sm">
                            Scan starten <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

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
