<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{$title or "iRO WebImport"}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{ HTML::style("assets/css/main.css") }}
    {{ HTML::script("assets/js/jquery.min.js") }}
    {{ HTML::script("assets/js/bootstrap.min.js") }}
</head>
<body class="xing">
<div class="wrapper">
    @yield('content')
</div>
</body>
</html>
