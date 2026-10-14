@props(['title', 'desc' => null, 'badge' => null])
<section {{ $attributes->class(['ps-sec']) }}>
    <div>
        <h3 class="ps-sec-title">{{ $title }}@if($badge)<span class="ps-pill ps-pill-gold" style="min-height:24px;padding:0 9px;font-size:.7rem">{{ $badge }}</span>@endif</h3>
        @if($desc)<p class="ps-sec-desc">{{ $desc }}</p>@endif
    </div>
    <div class="ps-sec-body">{{ $slot }}</div>
</section>
