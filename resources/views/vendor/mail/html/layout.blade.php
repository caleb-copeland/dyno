<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ config('app.name') }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
{{-- Dyno: declare both schemes so clients use our dark styles below instead of force-inverting. --}}
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<style>
@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}

.footer {
width: 100% !important;
}
}

@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
}
}

/* Dyno dark mode — Apple Mail, iOS Mail, Outlook (mac/iOS). Overrides inlined light styles. */
:root { color-scheme: light dark; supported-color-schemes: light dark; }
@media (prefers-color-scheme: dark) {
body, .wrapper, .body { background-color: #0A0A0B !important; }
.inner-body { background-color: #141416 !important; border-color: #26262A !important; }
h1, h2, h3, .header a { color: #F2F2F3 !important; }
p, ul, ol, td, .table td, .subcopy p { color: #B4B4BA !important; }
a:not(.button) { color: #F2F2F3 !important; }
.subcopy { border-top-color: #26262A !important; }
.footer p, .footer a { color: #8A8A90 !important; }
.panel-content { background-color: #1E1E21 !important; }
}
</style>
{!! $head ?? '' !!}
</head>
<body>

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
