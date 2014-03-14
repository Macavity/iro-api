<?

/**
 * @var Illuminate\Support\MessageBag       $errors
 * @var Client $client
 */

?>

@section('content')
<h1>Kunden bearbeiten</h1>

<?= HTML::ul($errors->all()) ?>

<?= Form::model($client, array('method' => 'PUT', 'route' => array('admin.clients.update',$client->id))) ?>

@foreach($client->getFieldLabels() as $field => $label)
<div class="form-group">
    <?= Form::label($field, $label) ?>
    <?= Form::text($field, null, array('class' => 'form-control')) ?>
</div>
@endforeach

<?= Form::submit('Speichern', array('class' => 'btn btn-primary')) ?>

<?= Form::close() ?>


@stop