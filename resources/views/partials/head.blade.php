@php
    use App\Enums\SettingKey;
    use App\Models\Setting;

     $siteName = Setting::get(SettingKey::CompanyName, 'Rewire Starter Kit');
@endphp

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.$siteName : $siteName }}
</title>

<link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
<meta name="apple-mobile-web-app-title" content="{{ $siteName }}" />
<link rel="manifest" href="{{ asset('site.webmanifest') }}" />

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])