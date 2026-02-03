<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Zurück zu Moodle...</title>
</head>
<body>
    <p>Inhalt wird zu Moodle hinzugefügt...</p>

    <form id="return-form" action="{{ $returnUrl }}" method="POST">
        <input type="hidden" name="JWT" value="{{ $jwt }}">
    </form>

    <script>
        document.getElementById('return-form').submit();
    </script>
</body>
</html>
