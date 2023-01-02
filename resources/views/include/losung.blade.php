    <div class="card blur">
        <div class="card-body">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-12 col-md-6">
                        <p>
                            <b>
                                Tageslosung zum {{$losung?->date}}:
                            </b>
                        </p>
                        <p>
                            {{$losung?->Losungstext}} <BR>
                            <i>
                                {{$losung?->Losungsvers}}
                            </i>
                        </p>
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <p>
                            <b>
                                Lehrtext:
                            </b>
                        </p>
                        <p>
                            {{$losung?->Lehrtext}} <BR>
                            <i>
                                {{$losung?->Lehrtextvers}}
                            </i>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

