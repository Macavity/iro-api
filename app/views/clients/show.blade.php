@section('content')
<h1>Kundendetails</h1>

<div class="jumbotron text-center">
    <h2>{{ $client->name }}</h2>
    <p>
        @foreach($client->getFieldLabels() as $field => $label)
        <strong>{{$label}}:</strong> {{$client->$field}}<br/>
        @endforeach
    </p>
</div>
@stop