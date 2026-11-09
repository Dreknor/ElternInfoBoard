<li class="py-3">
    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-800">{{ $v->stunde }}. Stunde</span>
        @if($showDate ?? false)<span class="text-sm student-muted">{{ \Carbon\Carbon::parse($v->date)->locale('de')->isoFormat('dd, DD.MM.') }}</span>@endif
        <span class="text-sm student-muted">{{ $v->group?->name ?? $v->klasse_kurzform }}</span>
    </div>
    <div class="mt-1 break-words text-sm">
        @if($v->altFach)<span class="line-through student-muted">{{ $v->altFach }}</span>@endif
        @if($v->neuFach)<i class="fas fa-arrow-right mx-1 text-xs"></i><span class="font-semibold">{{ $v->neuFach }}</span>@endif
        @if($v->lehrer)<span class="ml-2 student-muted"><i class="fas fa-user-tie mr-1"></i>{{ $v->lehrer }}</span>@endif
    </div>
    @if($v->comment)<div class="mt-1 break-words text-sm italic student-muted">{{ $v->comment }}</div>@endif
</li>
