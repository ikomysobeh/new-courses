<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ $config->get('ui.title') ?? config('app.name') . ' - API Docs' }}</title>
    <style>body { margin: 0; padding: 0; }</style>
</head>
<body>
    <script id="api-reference" data-url="{{ route('scramble.docs.document') }}"></script>
    <script>
        document.getElementById('api-reference').dataset.configuration = JSON.stringify({
            theme: '{{ $config->get('ui.theme', 'light') }}',
            layout: 'sidebar',
            authentication: {
                preferredSecurityScheme: 'http',
            },
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@scalar/api-reference"></script>
</body>
</html>
