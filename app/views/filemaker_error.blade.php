@extends('layouts.master')

@section('content')
    <div class="front-page">
        <div class="row">
            <div class="col-md-6">
                <div class="alert alert-info">API Fehler {{ $code }} {{ $error }}</div>
                @if (count($log))
                <ul>
                    @foreach( $log as $item)
                        <li class="{{ $item['visible'] ? '' : $debugClass  }}">{{{ $item['text'] }}}</li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>
@stop