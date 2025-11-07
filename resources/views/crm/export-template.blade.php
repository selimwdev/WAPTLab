<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CRM Export — {{ strtoupper($db) }}</title>
<style>
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #000; padding: 5px; text-align: left; }
th { background-color: #f0f0f0; }
</style>
</head>
<body>
<h3>CRM Export — {{ strtoupper($db) }}</h3>
<table>
    <thead>
        <tr>
            @foreach(array_keys($rows[0]) as $col)
                <th>{{ $col }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr>
                @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
