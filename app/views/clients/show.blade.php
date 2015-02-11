@section('content')
<h1>Kundendetails</h1>

<div class="jumbotron text-center">
    <h2>{{ $client->name }}</h2>
    <p>
        @foreach($client->getFieldLabels() as $field => $label)
        <strong>{{$label}}:</strong> {{$client->$field}}<br/>
        @endforeach
    </p>
    <p>Cache für Jobliste ist
        @if($cacheJoblistActive)
        <span class="label label-success">Aktiv</span>
        @else
        <span class="label label-warning">Leer</span>
        @endif
    </p>
</div>
@stop