@extends('layouts.master')

@section('content')
<div class="front-page">
    <div class="row">
        <div class="col-md-1 pull-right">
            <a href="/{{$serial}}/{{$fmId}}/"><i class="glyphicon glyphicon-refresh"></i></a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-success">Die Daten wurden erfolgreich in die Datenbank importiert.</div>
        </div>
    </div>
</div>
@stop