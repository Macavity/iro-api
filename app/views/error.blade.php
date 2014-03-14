@extends('layouts.master')

@section('content')
<div class="front-page">
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-info">{{ $message or "Diese Funktion kann nur in einem bestehenden Datensatz verwendet werden." }}</div>
        </div>
    </div>
</div>
@stop