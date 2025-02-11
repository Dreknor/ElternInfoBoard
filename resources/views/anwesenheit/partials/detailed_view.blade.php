@foreach($groups as $group)
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white" style="position: sticky; top: 0; z-index: 1;">
                <h3>{{ $group }}</h3>
            </div>
            <div class="card-body">
                @foreach($classes as $class)
                    <h4 class="text-secondary" style="position: sticky; top: 60px; z-index: 1; background-color: white;">
                        {{ $class }}
                    </h4>
                    @php
                        $sortedChildren = $children->where('group', $group)->where('class', $class)->sortBy('lastname');
                    @endphp
                    <div class="row">
                        @foreach($sortedChildren as $child)
                            <div class="col-12 mb-2">
                                <div class="card p-2" style="background-color: {{ $loop->index % 2 == 0 ? '#D0D0D0'  : '#ffffff' }};">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ isset($child['image']) ? $child['image'] : asset('img/avatar.png') }}" class="avatar-img mr-3" alt="{{ $child['first_name'] }} {{ $child['last_name'] }}">
                                        <div>
                                            <p class="card-text mb-0">{{ $child['last_name'] }}</p>
                                            <p class="card-text mb-0">{{ $child['first_name'] }}</p>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <small class="text-muted">Additional information here</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
