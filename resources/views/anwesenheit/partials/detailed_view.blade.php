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
                        style="position: sticky; top: 60px; z-index: 1; background-color: white; margin: 0.5rem 0;">
                        {{ $class->name }}
                    </h4>

                    @php
                        $sortedChildren = $children->where('group_id', $group->id)->where('class_id', $class->id)->sortBy('lastname');
                    @endphp
                    <div class="row">
                        @foreach($sortedChildren as $child)
                            <div class="col-12 mb-1">
                                <div class="card p-1 child-item {{ $loop->index % 2 == 0 ? 'list-item-odd' : '' }} @if(!$child->checkedIn()) child-checkedOut @endif"
                                     data-child='@json(array_merge($child->toArray(), ['checked_in' => $child->checkedIn() ? 'true' : 'false', 'schickzeiten' => $child->getSchickzeitenForToday()]))'
                                     style="padding: 0.5rem;">
                                    <div class="d-flex align-items-center">
                                        <img
                                            src="{{ isset($child['image']) ? $child['image'] : asset('img/avatar.png') }}"
                                            class="avatar-img mr-2"
                                            alt="{{ $child['first_name'] }} {{ $child['last_name'] }}">
                                        <div class="w-100">

                                                {{ $child['last_name'] }}, {{ $child['first_name'] }}
                                                @if($child->getSchickzeitenForToday()->count() > 0 and $child->checkedIn())
                                                    <div class="p-2 pull-right">

                                                        @foreach($child->getSchickzeitenForToday()->sortBy('type') as $schickzeit)
                                                            @php
                                                                $currentTime = now();
                                                                $schickzeitTime = $schickzeit->time;
                                                                $timeDifference = $currentTime->diffInMinutes($schickzeitTime, false);
                                                                $backgroundClass = '';

                                                                if (!$child->checkedIn()) {
                                                                    $backgroundClass = "schickzeit_liste";
                                                                } elseif ($timeDifference <= 10 && $timeDifference >= 0 && ($schickzeit->type == 'genau' || $schickzeit->type == 'spät.')) {
                                                                    $backgroundClass = "schickzeit_liste  schickzeit_liste--yellow";
                                                                } elseif ($timeDifference < 0 && ($schickzeit->type == 'genau' || $schickzeit->type == 'spät.')) {
                                                                    $backgroundClass = "schickzeit_liste  schickzeit_liste--red text-medium";

                                                                } else {
                                                                    $backgroundClass = "schickzeit_liste schickzeit_liste--blue";
                                                                }
                                                            @endphp

                                                        <span class="{{ $backgroundClass }}">
                                                            @if($schickzeit->type == 'genau')
                                                                {{ $schickzeit->time->format('H:i') }}
                                                            @else
                                                                {{ $schickzeit->time->format('H:i') }} @if(!$loop->last) - @endif
                                                            @endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                                @endif
                                        </div>
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
