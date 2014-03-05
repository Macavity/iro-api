@section('content')

<h1>Neuen Kundeneintrag erstellen</h1>

{{ HTML::ul($errors->all()) }}

{{ Form::open(array('action' => 'ClientsController@index')) }}

<div class="form-group">
    {{ Form::label('name', 'Name') }}
    {{ Form::text('name', Input::old('name'), array('class' => 'form-control')) }}
</div>

<div class="form-group">
    {{ Form::label('host', 'Host') }}
    {{ Form::text('host', Input::old('host'), array('class' => 'form-control')) }}
</div>


<div class="form-group">
    {{ Form::label('db_name', 'Datenbankname') }}
    {{ Form::text('db_name', Input::old('db_name'), array('class' => 'form-control')) }}
</div>

<div class="form-group">
    {{ Form::label('serial', 'Seriennummer') }}
    {{ Form::text('serial', Input::old('serial'), array('class' => 'form-control')) }}
</div>

<div class="form-group">
    {{ Form::label('fm_user', 'FM User') }}
    {{ Form::text('fm_user', Input::old('fm_user'), array('class' => 'form-control')) }}
</div>

<div class="form-group">
    {{ Form::label('fm_password', 'FM Passwort') }}
    {{ Form::text('fm_password', Input::old('fm_password'), array('class' => 'form-control')) }}
</div>

{{ Form::submit('Erstellen', array('class' => 'btn btn-primary')) }}

{{ Form::close() }}

@stop