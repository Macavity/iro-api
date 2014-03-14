@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Kundenliste</h3>
                </div>
                <div class="panel-body">
                    <input type="text" class="form-control" id="dev-table-filter" data-action="filter" data-filters="#dev-table" placeholder="Kunden filtern" />
                </div>
                <table class="table table-hover" id="dev-table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Host</th>
                        <th>Datenbankname</th>
                        <th>Seriennummer</th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($clients as $client)
                    <tr>
                        <td>{{ $client->id }}</td>
                        <td>{{ $client->name }}</td>
                        <td>{{ $client->host }}</td>
                        <td>{{ $client->db_name }}</td>
                        <td>{{ $client->serial }}</td>
                        <td>
                            <a class="btn btn-small btn-success" href="{{ action('ClientsController@show', $client->id) }}">Details</a>
                            <a class="btn btn-small btn-info" href="{{ action('ClientsController@edit', $client->id) }}">Bearbeiten</a>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script type="text/javascript">
    /**
     *   I don't recommend using this plugin on large tables, I just wrote it to make the demo useable. It will work fine for smaller tables
     *   but will likely encounter performance issues on larger tables.
     *
     *		<input type="text" class="form-control" id="dev-table-filter" data-action="filter" data-filters="#dev-table" placeholder="Filter Developers" />
     *		$(input-element).filterTable()
     *
     *	The important attributes are 'data-action="filter"' and 'data-filters="#table-selector"'
     */
    (function(){
        'use strict';
        $.fn.extend({
            filterTable: function(){
                return this.each(function(){
                    $(this).on('keyup', function(e){
                        $('.filterTable_no_results').remove();
                        var $this = $(this), search = $this.val().toLowerCase(), target = $this.attr('data-filters'), $target = $(target), $rows = $target.find('tbody tr');
                        if(search == '') {
                            $rows.show();
                        }
                        else {
                            $rows.each(function(){
                                var $this = $(this);
                                $this.text().toLowerCase().indexOf(search) === -1 ? $this.hide() : $this.show();
                            })
                            if($target.find('tbody tr:visible').size() === 0) {
                                var col_count = $target.find('tr').first().find('td').size();
                                var no_results = $('<tr class="filterTable_no_results"><td colspan="'+col_count+'">Keine Suchergebnisse</td></tr>')
                                $target.find('tbody').append(no_results);
                            }
                        }
                    });
                });
            }
        });
        $('[data-action="filter"]').filterTable();
    })(jQuery);

    $(function(){
        // attach table filter plugin to inputs
        $('[data-action="filter"]').filterTable();

        $('.container').on('click', '.panel-heading span.filter', function(e){
            var $this = $(this),
                $panel = $this.parents('.panel');

            $panel.find('.panel-body').slideToggle();
            if($this.css('display') != 'none') {
                $panel.find('.panel-body input').focus();
            }
        });
        $('[data-toggle="tooltip"]').tooltip();
    })
</script>
@stop