    <div class="card  bg-light">
        <div class="card-body">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-12 col-md-6">
                        <p>
                            <b>
                                Tageslosung zum {{optional($losung)->date}}:
                            </b>
                        </p>
                        <p>
                            {{optional($losung)->Losungstext}} <BR>
                            <i>
                                {{optional($losung)->Losungsvers}}
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
                            {{optional($losung)->Lehrtext}} <BR>
                            <i>
                                {{optional($losung)->Lehrtextvers}}
                            </i>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

