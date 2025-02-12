<div class="container-fluid">
    <div class="row">
        @foreach($groups as $group)
            <div class="col-lg-3 col-md-6 mb-1">
                <div class="card">
                    <div class="card-header bg-primary text-white"
                         style="position: sticky; top: 0; z-index: 1; padding: 0.5rem;">
                        <h3 style="margin: 0;">{{ $group->name }}</h3>
                    </div>
                    <div class="card-body" style="padding: 0.5rem;">
                        @foreach($classes as $class)
                            <h4 class="text-secondary"
                                style="position: sticky; top: 40px; z-index: 1; background-color: white; margin: 0.5rem 0;">
                                {{ $class->name }}
                            </h4>
                            @php
                                $sortedChildren = $children->where('group_id', $group->id)->where('class_id', $class->id)->sortBy('last_name');
                            @endphp
                            <ul class="list-group" style="margin: 0;">
                                @foreach($sortedChildren as $child)
                                    <li class="list-group-item custom-list-item d-flex align-items-center child-item {{ $loop->index % 2 == 0 ? 'list-item-odd' : '' }} @if(!$child->checkedIn()) child-checkedOut @endif"
                                        data-child='@json(array_merge($child->toArray(), ['checked_in' => $child->checkedIn() ? 'true' : 'false', 'schickzeiten' => $child->getSchickzeitenForToday()]))'
                                        style="padding: 0.5rem;">
                                        <div>
                                            <p class="mb-0">
                                                {{ $child->last_name }}, {{ $child->first_name }}
                                                @if($child->getSchickzeitenForToday()->count() > 0 and $child->checkedIn())
                                                    @foreach($child->getSchickzeitenForToday()->sortBy('type') as $schickzeit)
                                                            @php
                                                                $currentTime = now();
                                                                $schickzeitTime = $schickzeit->time;
                                                                $timeDifference = $currentTime->diffInMinutes($schickzeitTime, false);
                                                                $backgroundClass = '';

                                                                if ($timeDifference <= 15 && $timeDifference >= 0 && ($schickzeit->type == 'genau' || $schickzeit->type == 'spät.')) {
                                                                    $backgroundClass = 'badge badge-warning pull-right ml-2 text-great';
                                                                } elseif ($timeDifference < 0 && ($schickzeit->type == 'genau' || $schickzeit->type == 'spät.')) {
                                                                    $backgroundClass = 'badge badge-danger pull-right ml-2 text-greater';
                                                                } elseif ($schickzeit->type == 'ab' && $timeDifference > 15) {
                                                                    $backgroundClass = 'badge badge-success ml-2';
                                                                } else {
                                                                    $backgroundClass = 'badge badge-info ml-2 pull-right text-medium';
                                                                }

                                                            @endphp
                                                            @if($schickzeit->type == 'genau' || $schickzeit->type == 'spät.')
                                                                <span class="{{ $backgroundClass }}">
                                                                    {{ $schickzeit->time->format('H:i') }}
                                                                </span>
                                                            @elseif($schickzeit->type == 'ab' && $timeDifference > 15)
                                                                <span class="{{ $backgroundClass }}">
                                                                    &#10003;
                                                                </span>
                                                            @endif
                                                        @endforeach
                                                @endif
                                            </p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
