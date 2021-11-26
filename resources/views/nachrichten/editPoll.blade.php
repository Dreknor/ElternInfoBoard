<div class="card">
    <div class="card-header">
        <h6 class="card-title">
            Umfrage bearbeiten
        </h6>
    </div>
    <div class="card-body">
        <form action="{{url("/poll/".$post->poll->id."/update")}}" method="post" class="form form-horizontal">
            @csrf
            @method('put')
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label>Titel</label>
                        <input type="text" class="form-control border-input" name="poll_name"
                               value="{{old('poll_name')? old('poll_name') : $post->poll->poll_name}}" required>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <label>Beschreibung</label>
                        <input type="text" class="form-control border-input" name="description"
                               value="{{old('description')? old('description') : $post->poll->description}}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>Ende</label>
                                <input type="date" class="form-control border-input" name="ends"
                                       value="{{old('ends', $post->poll->ends->format('Y-m-d'))}}" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label>max. Antwortmöglichkeiten</label>
                                <input type="number" min="1" class="form-control border-input" name="max_number"
                                       value="{{old('max_number',$post->poll->max_number)}}" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label>Antworten</label>
                    <ul class="list-group">
                        @foreach($post->poll->options as $option)
                            <li class="list-group-item">
                                <input type="text" name="options[]" class="form-control" value="{{$option->option}}">
                            </li>
                        @endforeach
                        <li class="list-group-item">
                            <a href="#" class="card-link" id="addOption">
                                <i class="fa fa-plus-circle"></i> weitere Option anfügen
                            </a>
                        </li>
                    </ul>
                </div>

            </div>
            <div class="row">
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary btn-block">
                        Umfrage bearbeiten
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>

@push('js')

    <script type="text/javascript">
        $('#addOption').on('click', function (ev) {
            ev.preventDefault();

            var input = document.createElement('input', ['type=text'])
            input.classList.add('form-control')
            input.setAttribute('name', 'options[]')

            var li = document.createElement("li");
            li.classList.add("list-group-item");
            li.appendChild(input);

            let list = ev.target.closest("ul")

            list.insertBefore(li, ev.target.closest('li'));

        })

    </script>


@endpush
