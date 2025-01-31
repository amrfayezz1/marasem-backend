<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Custom Report</title>
</head>

<body>
    <h2>Custom Report</h2>

    @foreach($reportData as $section => $data)
        <h3>{{ ucfirst($section) }} Data</h3>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                @foreach(array_keys((array) $data->first()) as $column)
                    <th>{{ ucfirst($column) }}</th>
                @endforeach
            </tr>
            @foreach($data as $row)
                <tr>
                    @foreach((array) $row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endforeach
</body>

</html>