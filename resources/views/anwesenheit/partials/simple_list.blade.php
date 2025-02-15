<div class="container-fluid">
    <div class="row">
        @foreach($groups as $group)
            <div class="col-lg-3 col-md-6 mb-1">
                <div class="card">
                    <div class="card-header bg-primary text-white"
                         style="position: sticky; top: 0; z-index: 1; padding: 0.5rem;">
                        <span class="badge badge-warning pull-right">{{ $children->where('group_id', $group->id)->count() }}</span>

                        <h3 style="margin: 0;">{{ $group->name }}</h3>
                    </div>
                    <div class="card-body" style="padding: 0.5rem;">
                        @foreach($classes as $class)
                            <h4 class="bg-gradient-directional-grey-blue text-white p-2" style="position: sticky; top: 40px; z-index: 1; margin: 0.5rem 0;">
                                {{ $class->name }}  <span class="badge badge-primary pull-right">{{ $children->where('group_id', $group->id)->where('class_id', $class->id)->count() }}</span>
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
                                                                $backgroundClass = 'badge badge-';

                                                                if($schickzeit->type == 'ab' and $currentTime->isBefore($schickzeit->time_ab)) {
                                                                    $backgroundClass .= 'success';
                                                                    $text_size = 'text-smaller';
                                                                } elseif($schickzeit->type == 'ab' and $currentTime->isAfter($schickzeit->time_ab) and $currentTime->isBefore($schickzeit->time_spaet)) {
                                                                    $backgroundClass .= 'warning';
                                                                    $text_size = 'text-great';
                                                                } elseif($schickzeit->type == 'ab' and $currentTime->isAfter($schickzeit->time_spaet)) {
                                                                    $backgroundClass .= 'danger';
                                                                    $text_size = 'text-medium';
                                                                } elseif($schickzeit->type == 'genau' and $currentTime->isBefore($schickzeit->time)) {
                                                                    $backgroundClass .= 'success';
                                                                    $text_size = 'text-smaller';
                                                                } elseif($schickzeit->type == 'genau' and $currentTime->isAfter($schickzeit->time)) {
                                                                    $backgroundClass .= 'danger';
                                                                    $text_size = 'text-great';
                                                                }

                                                            @endphp
                                                            @if($schickzeit->type == 'ab')
                                                                <span class="{{ $backgroundClass }}">
                                                                    @if($schickzeit->time_ab) ab {{ $schickzeit->time_ab?->format('H:i') }}@endif
                                                                </span>
                                                            @if($schickzeit->time_spaet)
                                                                <span class="{{ $backgroundClass }} {{$text_size}}">
                                                                    {{ $schickzeit->time_spaet?->format('H:i') }} (spät.)
                                                                </span>
                                                            @endif
                                                            @else
                                                                <span class="{{ $backgroundClass }} {{$text_size}}">{{ $schickzeit->time }}</span>
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
