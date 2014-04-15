@extends('layouts.master')

@section('content')
<div class="front-page">
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-info">{{ $message or "Ihre Datenbank ist für diese Funktion noch nicht freigeschaltet." }}</div>
        </div>
    </div>
</div>
@stop