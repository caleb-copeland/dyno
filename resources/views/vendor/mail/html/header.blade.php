@props(['url'])
{{-- Dyno wordmark as a PNG — Gmail/Outlook strip inline SVG. White on
     transparent, rendered from components/app-wordmark at 3x. --}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ asset('images/email-wordmark.png') }}" class="logo" width="145" height="40" alt="{{ trim($slot) }}">
</a>
</td>
</tr>
