@extends('layouts.anwesenheit')

@section('content')
    <div class="container mt-5">
        <div class="row">
            @if(!$careSettings->view_detailed_care)
                @include('anwesenheit.partials.simple_list')
            @else
                @include('anwesenheit.partials.detailed_view')
            @endif
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="childModal" tabindex="-1" role="dialog" aria-labelledby="childModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="childModalLabel">Übersicht</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <h4 id="childName"></h4>
                        <p>Schickzeiten für heute:</p>
                        <div id="schickzeitenContainer" class="mt-3">
                            <!-- Schickzeiten will be displayed here -->
                        </div>
                    </div>
                    <hr>
                    <h5>Schickzeit für heute erfassen</h5>
                    <form id="schickzeitForm">
                        @csrf
                        <div class="form-group">
                            <label for="type">Typ</label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="ab">von ... bis ... Uhr</option>
                                <option value="genau">genau</option>
                            </select>
                        </div>
                        <div class="form-group collapse" id="genau_row">
                            <label for="schickzeitTime">Zeit</label>
                            <input type="time" class="form-control" id="schickzeitTime" name="time">
                        </div>

                        <div class="form-group" id="spaet_row">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="ab">ab ... Uhr</label>
                                    <input type="time" class="form-control" id="ab" name="ab">
                                </div>
                                <div class="col-md-6">
                                    <label for="spät.">bis ... Uhr</label>
                                    <input type="time" class="form-control" id="spät." name="spät.">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Schickzeit erfassen</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="logoutButton" style="display: none;">Abmelden</button>
                    <div id="spinner" class="spinner-border text-danger" role="status" style="display: none;">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script>
        $(document).ready(function () {
            $("#type").change(function () {
                $('#spaet_row').toggle();
                $('#genau_row').toggle();
            });
        });


        document.addEventListener('DOMContentLoaded', function () {
            const childModal = $('#childModal');
            const childName = document.getElementById('childName');
            const logoutButton = document.getElementById('logoutButton');
            const spinner = document.getElementById('spinner');
            const schickzeitForm = document.getElementById('schickzeitForm');

            document.querySelectorAll('.child-item').forEach(item => {
                item.addEventListener('click', function () {

                    const childData = JSON.parse(this.dataset.child);
                    childName.textContent = `${childData.first_name} ${childData.last_name}`;
                    logoutButton.dataset.childId = childData.id;

                    if (childData.checked_in === 'false') {
                        logoutButton.style.display = 'none';
                    } else {
                        logoutButton.style.display = 'inline-block';
                    }

                    const schickzeitenContainer = document.getElementById('schickzeitenContainer');
                    schickzeitenContainer.innerHTML = '';
                    if (childData.schickzeiten.length > 0) {
                        childData.schickzeiten.forEach(schickzeit => {
                            const schickzeitElement = document.createElement('p');
                            schickzeitElement.textContent = `${schickzeit.type}: ${new Date(schickzeit.time).toLocaleTimeString()}`;
                            schickzeitenContainer.appendChild(schickzeitElement);
                        });
                    }

                    childModal.modal('show');
                });
            });

            logoutButton.addEventListener('click', function () {
                const childId = this.dataset.childId;

                logoutButton.style.display = 'none';
                spinner.style.display = 'inline-block';

                $.ajax({
                    url: `anwesenheit/${childId}/abmelden`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    }
                }).done(function () {
                    childModal.modal('hide');
                    window.location.reload();
                }).always(function () {
                    spinner.style.display = 'none';
                    logoutButton.style.display = 'inline-block';
                });
            });

            schickzeitForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const childId = logoutButton.dataset.childId;
                const formData = $(this).serialize();
                console.log(formData);

                $.ajax({
                    url: `anwesenheit/${childId}/schickzeit`,
                    method: 'POST',
                    data: formData
                }).done(function () {
                    childModal.modal('hide');
                    window.location.reload();
                });
            });
        });
    </script>
@endpush
