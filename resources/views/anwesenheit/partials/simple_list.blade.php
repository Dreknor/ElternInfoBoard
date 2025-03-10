<div class="container-fluid">
    <div class="row">
        @foreach($groups as $group)
            @if($careSettings->hide_groups_when_empty and $children->where('group_id', $group->id)->count() == 0)
                @continue
            @endif
            <div class="col-lg-3 col-md-6 mb-1">
                <div class="card">
                    <div class="card-header bg-primary text-white"
                         style="position: sticky; top: 0; z-index: 1; padding: 0.5rem;">
                        <span class="badge badge-warning pull-right">{{ $children->where('group_id', $group->id)->count() }}</span>

                        <h3 style="margin: 0;">{{ $group->name }}</h3>
                    </div>
                    <div class="card-body" style="padding: 0.5rem;">
                        @foreach($classes as $class)
                            @if($careSettings->hide_groups_when_empty and $children->where('group_id', $group->id)->where('class_id', $class->id)->count() == 0)
                                @continue
                            @endif
                            <h4 class="bg-gradient-directional-grey-blue text-white p-2" style="position: sticky; top: 60px; z-index: 1; margin: 0.5rem 0;">
                                {{ $class->name }}  <span class="badge badge-primary pull-right">{{ $children->where('group_id', $group->id)->where('class_id', $class->id)->count() }}</span>
                            </h4>
                            @php
                                $sortedChildren = $children->where('group_id', $group->id)->where('class_id', $class->id)?->sortBy('last_name');
                            @endphp
                            <ul class="list-group" style="margin: 0;">
                                @forelse($sortedChildren as $child)
                                    <li class="list-group-item custom-list-item d-flex align-items-center child-item {{ $loop->index % 2 == 0 ? 'list-item-odd' : '' }} @if(!$child->checkedIn()) child-checkedOut @endif"
                                        data-child='@json(array_merge($child->toArray(), ['checked_in' => $child->checkedIn() ? 'true' : 'false','schickzeiten' => $child->getSchickzeitenForToday()?->toArray()]))'
                                        data-notices='@json($child->hasNotice())'
                                        style="padding: 0.5rem;">
                                        <div class="container-fluid">
                                            <div class="row">
                                                <div class="col-2 d-flex justify-content-center align-items-center">
                                                    @if($child->should_be_today() and !$child->checkedIn())
                                                        <div class="bg-info text-white rounded-circle" style="width: 25px; height: 25px; display: flex; justify-content: center; align-items: center;">
                                                            <i class="fas fa-question"></i>
                                                        </div>
                                                    @else

                                                    @endif


                                                    @if($child->hasNotice())
                                                        <div class="bg-info text-white rounded-circle" style="width: 25px; height: 25px; display: flex; justify-content: center; align-items: center;">
                                                            <i class="fas fa-envelope"></i>
                                                        </div>
                                                    @endif


                                                    @if($child->krankmeldungToday())
                                                        <div class="badge badge-danger">
                                                            <i class="fas fa-ban"></i> Krank
                                                        </div>
                                                    @endif

                                                </div>
                                                <div class="col-auto d-flex justify-content-center align-items-center name">
                                                        {{ $child->last_name }}, {{ $child->first_name }}
                                                </div>
                                                <div class="col">
                                                    @if($child->getSchickzeitenForToday()?->count() > 0 and $child->checkedIn())
                                                        @foreach($child->getSchickzeitenForToday()?->sortBy('type') as $schickzeit)
                                                            @php
                                                                $currentTime = now();
                                                                $backgroundClass = 'badge badge-';
                                                                $text_size = 'text-smaller';

                                                                if($schickzeit->type == 'ab' and (isset($schickzeit->time_ab) && $currentTime->isBefore($schickzeit?->time_ab))) {
                                                                    $backgroundClass .= 'success';
                                                                    $text_size = 'text-smaller';
                                                                } elseif($schickzeit->type == 'ab' and ($schickzeit->time_ab && $currentTime->isAfter($schickzeit->time_ab)) and ($schickzeit->time_spaet && $currentTime->isBefore($schickzeit->time_spaet))) {
                                                                    $backgroundClass .= 'warning';
                                                                    $text_size = 'text-great';
                                                                } elseif($schickzeit->type == 'ab' and $schickzeit->time_spaet and $currentTime->isAfter($schickzeit->time_spaet)) {
                                                                    $backgroundClass .= 'danger';
                                                                    $text_size = 'text-medium';
                                                                } elseif($schickzeit->type == 'genau' and $schickzeit->time and $currentTime->isBefore($schickzeit->time)) {
                                                                    $backgroundClass .= 'success';
                                                                    $text_size = 'text-smaller';
                                                                } elseif($schickzeit->type == 'genau' and $schickzeit->time and $currentTime->isAfter($schickzeit->time)) {
                                                                    $backgroundClass .= 'danger';
                                                                    $text_size = 'text-great';
                                                                } else {
                                                                    $backgroundClass .= 'primary';
                                                                    $text_size = 'text-medium';
                                                                }

                                                            @endphp
                                                            @if($schickzeit->type == 'ab')
                                                                @if($schickzeit->time_ab != '')
                                                                    <span class="{{ $backgroundClass }} {{$text_size}}">
                                                                         ab {{ $schickzeit->time_ab?->format('H:i') }}
                                                                    </span>
                                                                @endif
                                                                @if($schickzeit->time_spaet)
                                                                    <span class="{{ $backgroundClass }} {{$text_size}}">
                                                                             {{ $schickzeit->time_spaet?->format('H:i') }} (spät.)
                                                                        </span>
                                                                @endif
                                                            @else
                                                                <span class="{{ $backgroundClass }} {{$text_size}}">
                                                                        {{ $schickzeit->time->format('H:i') }}
                                                                    </span>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </div>

                                            </div>

                                        </div>

                                    </li>
                                    @empty
                                        @if($careSettings->show_message_on_empty_group)
                                            <li class="list-group-item bg-gradient-directional-light-yellow" style="padding: 0.5rem;">
                                                Keine Kinder in dieser Klassenstufe
                                            </li>
                                        @endif
                                @endforelse
                            </ul>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
