@props(['url'])
{{-- Dyno wordmark as a PNG — Gmail/Outlook strip inline SVG. White on its
     own dark tile (reads in light and dark clients), rendered from
     components/app-wordmark at 4x. Loaded from
     mail.asset_url (the public site) so it works in mail sent from dev too. --}}
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ rtrim(config('mail.asset_url'), '/') }}/images/email-wordmark.png" class="logo" width="132" height="42" alt="{{ trim($slot) }}">
</a>
</td>
</tr>
