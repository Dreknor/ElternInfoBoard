<div class="form-group">
    <label>Für welche Gruppen?</label>
    <br>
    <input type="checkbox" name="gruppen[]" value="all" id="checkboxAll"/>
    <label for="checkboxAll" id="labelCheckAll"><b>Alle Gruppen (außer geschützte)</b></label>

    @foreach($gruppen->unique('bereich')->pluck('bereich') as $bereich )
        @if($bereich != "")
            <div>
                <input type="checkbox" name="gruppen[]" value="{{$bereich}}" id="checkbox{{$bereich}}"/>
                <label for="checkbox{{$bereich}}" id="labelCheck{{$bereich}}"><b>{{$bereich}}</b></label>
            </div>
        @endif
    @endforeach

    @foreach($gruppen as $gruppe)
        <div>
            <input type="checkbox" id="{{$gruppe->name}}" name="gruppen[]" value="{{$gruppe->id}}"
                   @if(isset($post) and $post->groups->contains($gruppe->id) or (isset($user) and $user->groups->contains($gruppe)) or (isset($liste) and $liste->groups->contains($gruppe))) checked @endif>
            <label for="{{$gruppe->name}}">{{$gruppe->name}} @if($gruppe->protected)
                    <i class="fas fa-lock"></i>
                @endif</label>
        </div>
    @endforeach
</div>
