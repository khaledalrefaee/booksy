{{-- Round venue seal: the venue's logo, or its initial. Expects: $c, $size ('sm'|'lg'). --}}
<span class="fa-seal fa-seal--{{ $size ?? 'sm' }}">
  @if($c->logo)<img src="{{ $c->logo }}" alt="">@else<b>{{ mb_substr(trim((string) ($c->company ?: $c->name)), 0, 1) }}</b>@endif
</span>
