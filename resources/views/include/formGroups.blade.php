<div class="form-group">
    <label>Für welche Gruppen?</label>
    <br>
    <input type="checkbox" name="gruppen[]" value="all" id="checkboxAll"/>
    <label for="checkboxAll" id="labelCheckAll"><b>Alle Gruppen (GS + OS)</b></label>
    <div>
        <input type="checkbox" name="gruppen[]" value="Grundschule" id="checkboxGrundschule"/>
        <label for="checkboxGrundschule" id="labelCheckGrundschule"><b>Grundschule</b></label>
    </div>
    <div>
        <input type="checkbox" name="gruppen[]" value="Oberschule" id="checkboxOberschule"/>
        <label for="checkboxOberschule" id="labelCheckOberschule"><b>Oberschule</b></label>
    </div>

    @foreach($gruppen as $gruppe)
        <div>
            <input type="checkbox" id="{{$gruppe->name}}" name="gruppen[]" value="{{$gruppe->id}}" @if(isset($post) and $post->groups->contains($gruppe->id) or (isset($user) and $user->groups->contains($gruppe)) or (isset($liste) and $liste->groups->contains($gruppe))) checked @endif>
            <label for="{{$gruppe->name}}">{{$gruppe->name}}</label>
        </div>
    @endforeach
</div>
