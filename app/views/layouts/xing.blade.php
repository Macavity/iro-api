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
<script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-24950655-2', 'auto');
    ga('send', 'pageview');

</script>
</body>
</html>
