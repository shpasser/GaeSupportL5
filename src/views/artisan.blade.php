<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Laravel Artisan Console for GAE</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">

            <br>

            <form action="{{ route('artisan') }}" method="POST" role="form">
                <legend>Artisan Console for Google App Engine</legend>

                <input type="hidden" name="_token" id="input" class="form-control" value="{{ csrf_token() }}">

                <div class="form-group">
                    <label>Command</label>
                    <input type="text" name="command" class="form-control" placeholder="Use 'help' for help" value="{{ $command }}">
                </div>

                <button type="submit" class="btn btn-primary">Execute</button>
            </form>

            <br>

            <label>Results</label>
            <textarea name="results" id="input" class="form-control" rows="20"
            style="font-family: Monaco, monospace;" readonly="readonly">{{ $results }}</textarea>

        </div>
        <!-- Latest compiled and minified JavaScript -->
        <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
        <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
    </body>
</html>