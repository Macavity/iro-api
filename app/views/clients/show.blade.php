<?php
    /** @var $client Client */
?>
@section('content')
    <h1>Kundendetails {{ $client->name }}</h1>

    <div class="panel panel-default">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th colspan="2">Stammdaten</th>
                </tr>
            </thead>
            @foreach($client->getFieldLabels() as $field => $label)
                <tr>
                    <td><strong>{{$label}}</strong></td>
                    <td>{{$client->$field}}</td>
                </tr>
            @endforeach
            <tr>
                <td>Cache für Jobliste ist</td>
                <td>
                    @if($cacheJoblistActive)
                        <span class="label label-success">Aktiv</span>
                    @else
                        <span class="label label-warning">Leer</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="panel panel-default">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>API Endpoints</th>
            </tr>
            </thead>
            <tr>
                <td>Data: Joblist (Normal)</td>
                <td>
                    <a href="{{ action('DataController@jobListAll', ['serial' => $client->serial]) }}" target="_blank">Normal</a>
                </td>
                <td>
                    <a href="{{ action('DataController@jobListAll', ['serial' => $client->serial]) }}?debug=true" target="_blank">Debug</a>
                </td>
            </tr>
            <tr>
                <td>Data: Joblist (Normal, No-Cache)</td>
                <td>
                    <a href="{{ action('DataController@jobListAll', ['serial' => $client->serial]) }}?forceRefresh=1" target="_blank">Normal</a>
                </td>
                <td>
                    <a href="{{ action('DataController@jobListAll', ['serial' => $client->serial]) }}?forceRefresh=1&debug=true" target="_blank">Debug</a>
                </td>
            </tr>
            <tr>
                <td>Search: Check Cache (Änderungen)</td>
                <td>
                    <a href="{{ action('AlgoliaController@checkCache', ['serial' => $client->serial]) }}" target="_blank">Normal</a>
                </td>
                <td>
                    <a href="{{ action('AlgoliaController@checkCache', ['serial' => $client->serial]) }}?debug=true" target="_blank">Debug</a>
                </td>
            </tr>
            <tr>
                <td>Search: Check Cache (Alle offenen Jobs)</td>
                <td>
                    <a href="{{ action('AlgoliaController@checkCache', ['serial' => $client->serial, 'type' => 'open']) }}" target="_blank">Normal</a>
                </td>
                <td>
                    <a href="{{ action('AlgoliaController@checkCache', ['serial' => $client->serial, 'type' => 'open']) }}?debug=true" target="_blank">Debug</a>
                </td>
            </tr>
        </table>
    </div>
@stop