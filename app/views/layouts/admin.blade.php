<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{$title or "iRO WebImport Administration"}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{ HTML::style("assets/css/main.css") }}
    {{ HTML::script("assets/js/jquery.min.js") }}
    {{ HTML::script("assets/js/bootstrap.min.js") }}
</head>
<body>
<div class="navbar navbar-inverse">
  <div class="container">
		<div class="navbar-header">
		  <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
			<a class="navbar-brand" href="/admin" name="top">Heads 2 Hunt</a>
		</div>


    <div class="collapse navbar-collapse">
				<ul class="nav navbar-nav">
					<li>
					  <a href="{{ action('ClientsController@index') }}">
					    <i class="glyphicon glyphicon-th-list"></i> Liste
            </a>
          </li>
					<li class="divider-vertical"></li>
					<li>
            <a href="{{ action('ClientsController@create') }}">
              <i class="glyphicon glyphicon-plus"></i> Neuer Eintrag
            </a>
          </li>
        </ul>
				<ul class="nav navbar-nav navbar-right">
				  <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">Username <b class="caret"></b></a>
            <ul class="dropdown-menu">
              <li><a href="{{ action('PageController@doLogout')}}">Logout</a></li>
            </ul>
          </li>
				</ul>

			</div>
			<!--/.nav-collapse -->
		</div>
		<!--/.container-fluid -->
</div>
<div class="container">
    @yield('content')
</div>
</body>
</html>
