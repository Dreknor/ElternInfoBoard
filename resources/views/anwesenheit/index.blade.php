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
        document.addEventListener('DOMContentLoaded', function () {
            const childModal = $('#childModal');
            const childName = document.getElementById('childName');
            const logoutButton = document.getElementById('logoutButton');
            const spinner = document.getElementById('spinner');

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

                    /**
                     * Clear schickzeiten container
                     */
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

                // Show spinner and hide logout button
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
                    // Hide spinner and show logout button
                    spinner.style.display = 'none';
                    logoutButton.style.display = 'inline-block';
                });
            });
        });
    </script>
@endpush
