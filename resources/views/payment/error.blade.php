<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
</head>

<body>
    <h1>Payment Failed</h1>
    <p>{{ $errorMessage }}</p>
    <a href="{{ url('/') }}">Try Again</a>
</body>

</html>