
<div id="image">
    <div class="losung">
        <p>
            <b>
                Tageslosung zum {{$losung->date->format('d.m.Y')}}:
            </b>
        </p>
        <p>
            {{$losung->Losungstext}} <BR>
                <i>
                    {{$losung->Losungsvers}}
                </i>
        </p>
    </div>
    <div class="lehrtext">
        <p>
            <b>
                Lehrtext:
            </b>
        </p>
        <p>
            {{$losung->Lehrtext}} <BR>
                <i>
                    {{$losung->Lehrtextvers}}
                </i>
        </p>
    </div>
</div>

