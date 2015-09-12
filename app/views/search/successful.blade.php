@extends('layouts.master')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="alert alert-info">Update durchgeführt.</div>
                @if (count($log))
                <ul>
                    @foreach( $log as $item)
                        <li class="{{ $item['visible'] ? '' : 'hidden'  }}">{{{ $item['text'] }}}</li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>
@stop