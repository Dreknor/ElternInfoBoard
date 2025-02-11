<div class="row">
    @foreach($groups as $group)
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white" style="position: sticky; top: 0; z-index: 1;">
                    <h3>{{ $group }}</h3>
                </div>
                <div class="card-body">
                    @foreach($classes as $class)
                        <h4 class="text-secondary" style="position: sticky; top: 40px; z-index: 1; background-color: white;">
                            {{ $class }}
                        </h4>
                        @php
                            $sortedChildren = $children->where('group', $group)->where('class', $class)->sortBy('lastname');
                        @endphp
                        <ul class="list-group">
                            @foreach($sortedChildren as $child)
                                <li class="list-group-item" style="background-color: {{ $loop->index % 2 == 0 ? '#D0D0D0' : '#ffffff' }};">
                                    {{ $child['last_name'] }}, {{ $child['first_name'] }}
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
