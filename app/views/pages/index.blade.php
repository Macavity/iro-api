
@section('content')
<div class="row">
    <div class="col-md-11">
        <h1>Willkommen {{$userName}}</h1>
    </div>
    <div class="col-md-1 pull-right">
        <a href="/{{$serial}}/{{$fmId}}"><i class="glyphicon glyphicon-refresh"></i></a>
    </div>
</div>
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
            <input type="text" class="form-control" id="xinglink" name="xinglink" placeholder="Link eintragen" value="{{$fmXingLink}}">
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