<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@auth
    <meta name="auth-user-id" content="{{ auth()->id() }}">
    <meta name="active-warehouse-id" content="{{ auth()->user()->activeWarehouse()?->id }}">
@endauth

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
