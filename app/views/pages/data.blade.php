
@section('content')
<div class="row">
    <div class="btn-group">
        <a href="{{ route('form', array('serial' => $serial, 'fmId' => $fmId ) ); }}" class="btn btn-primary">
            <i class="glyphicon glyphicon-chevron-left"></i> Neue Suche
        </a>
    </div>
</div>
<br>
<div class="row">
    <div class="col-md-12"><h3>Gefundenes Profil: {{ $resultName }}</h3></div>
</div>

@if($differentLastName)
<div class="alert alert-warning">
    Der Nachname des gefundenen Profils stimmt nicht mit dem Nachnamen in der Datenbank überein.<br/>
    Bitte überprüfen Sie ob Sie im richtigen Datensatz sind.
</div>
@endif

{{ Form::open(array(
    'url' => route(
        'form', array(
            'serial' => $serial,
            'fmId' => $fmId
        )
    )
)) }}
    {{ Form::hidden('formpost', 'yes') }}
    {{ Form::hidden('xinglink', $searchQuery) }}
    <div class="row">
        <div class="col-md-9">
            <div class="btn-group">
                <a class="btn btn-default jsSelectAll">Alle</a>
                <a class="btn btn-default jsSelectNone">Keine</a>
                <br>
                <br>
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">
                <i class="glyphicon glyphicon-chevron-right"></i> Importieren
            </button>
        </div>
    </div>
    <div class="form-data">
        @foreach ($data as $key => $row)
            <div class="row">
                <div class="col-1">
                    <input type="checkbox" name="{{$key}}" value="yes" class="jsCheckbox">
                </div>
                <div class="col-md-3">{{$row['label']}}</div>
                <div class="col-md-8">
                    @if($row['label'] == "Foto")
                        <img src="{{nl2br(trim($row['value']))}}" style="max-height: 100px;"/>
                    @else
                        {{nl2br(trim($row['value']))}}
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    <div class="row">
        <br>
        <div class="col-md-9">
            <div class="btn-group">
                <a class="btn btn-default jsSelectAll">Alle</a>
                <a class="btn btn-default jsSelectNone">Keine</a>
            </div>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">
                <i class="glyphicon glyphicon-chevron-right"></i> Importieren
            </button>
        </div>
    </div>
{{ Form::close() }}
<script type="text/javascript">
    $(document).ready(function(){
        $(".jsSelectAll").click(function(event){
            $(".jsCheckbox").each(function(){
                var checkbox = $(this);
                checkbox.prop("checked", true);
            });

        });

        $(".jsSelectNone").click(function(event){
            $(".jsCheckbox").each(function(){
                var checkbox = $(this);
                checkbox.prop("checked", false);
            });

        });
    });
</script>
@stop