
@section('content')
<h1>Willkommen {{$userName}}</h1>
    {{ Form::open(array(
        'url' => route(
            'form', array(
                'serial' => $serial,
                'fmId' => $fmId
            )
        )
    )) }}
    <div class="form-horizontal">
        <div class="col-sm-3 col-md-3 col-md-offset-1">
            <label for="xinglink">Link zum Xing-Profil</label>
        </div>
        <div class="col-sm-6 col-md-6">
            <input type="text" class="form-control" id="xinglink" name="xinglink" placeholder="Link eintragen">
        </div>
        <div class="col-sm-2 col-md-2">
            <button type="submit" class="btn btn-primary">
                Weiter
                <i class="glyphicon glyphicon-chevron-right"></i>
            </button>
        </div>
    </div>
    {{ Form::close() }}
@stop