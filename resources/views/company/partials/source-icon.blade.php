{{--
    Customer-source icon — unified iconography.
    Social sources render their official brand marks; every other source uses a
    monotone Feather glyph (same stroke weight as the rest of the dashboard).

    Params:
      $source : source key (instagram|facebook|google|friend_referral|walk_in|website|other|null)
      $size   : pixel size (default 16)
--}}
@php
    $source = $source ?? 'other';
    $size   = $size ?? 16;
    // Unique id so multiple Instagram gradients on one page never collide.
    $ig = 'ig'.substr(md5(uniqid('', true)), 0, 8);
    $glyphs = [
        'friend_referral' => 'users',
        'walk_in'         => 'log-in',
        'website'         => 'globe',
        'reception'       => 'phone',
        'other'           => 'more-horizontal',
    ];
@endphp
@switch($source)
    @case('whatsapp')
        <svg viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" role="img" aria-label="WhatsApp" style="display:block;flex-shrink:0;">
            <path fill="#25D366" d="M.06 24l1.69-6.16A11.87 11.87 0 0 1 .16 11.9C.16 5.34 5.5 0 12.06 0a11.82 11.82 0 0 1 8.41 3.49 11.82 11.82 0 0 1 3.48 8.42c0 6.56-5.34 11.9-11.9 11.9a11.9 11.9 0 0 1-5.68-1.45L.06 24zm6.6-3.8c1.68.99 3.28 1.59 5.4 1.59 5.45 0 9.89-4.43 9.89-9.88a9.82 9.82 0 0 0-2.9-6.99 9.82 9.82 0 0 0-6.98-2.9c-5.46 0-9.9 4.44-9.9 9.89 0 2.23.65 3.9 1.75 5.65l-1 3.65 3.74-1.01z"/>
            <path fill="#fff" d="M9.13 6.92c-.22-.5-.46-.51-.68-.52l-.58-.01c-.2 0-.53.08-.81.38s-1.07 1.04-1.07 2.54 1.1 2.95 1.25 3.15c.15.2 2.12 3.4 5.24 4.63 2.6 1.02 3.13.82 3.69.77.56-.05 1.81-.74 2.07-1.46.25-.71.25-1.32.18-1.45-.07-.12-.27-.2-.56-.35-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.44-.53.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.65-1.66-.9-2.26z"/>
        </svg>
        @break
    @case('instagram')
        <svg viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" role="img" aria-label="Instagram" style="display:block;flex-shrink:0;">
            <defs>
                <linearGradient id="{{ $ig }}" x1="0" y1="1" x2="1" y2="0">
                    <stop offset="0" stop-color="#FEDA75"/><stop offset=".25" stop-color="#FA7E1E"/>
                    <stop offset=".5" stop-color="#D62976"/><stop offset=".75" stop-color="#962FBF"/>
                    <stop offset="1" stop-color="#4F5BD5"/>
                </linearGradient>
            </defs>
            <path fill="url(#{{ $ig }})" d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23a3.7 3.7 0 0 1-.9 1.38 3.7 3.7 0 0 1-1.38.9c-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.7 3.7 0 0 1-1.38-.9 3.7 3.7 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41 1.27-.06 1.65-.07 4.85-.07M12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63a5.9 5.9 0 0 0-2.13 1.38A5.9 5.9 0 0 0 .63 4.14C.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.31.79.72 1.46 1.38 2.13.67.66 1.34 1.07 2.13 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56.79-.31 1.46-.72 2.13-1.38.66-.67 1.07-1.34 1.38-2.13.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91a5.9 5.9 0 0 0-1.38-2.13A5.9 5.9 0 0 0 19.86.63c-.76-.3-1.64-.5-2.91-.56C15.67.01 15.26 0 12 0z"/>
            <path fill="url(#{{ $ig }})" d="M12 5.84A6.16 6.16 0 1 0 18.16 12 6.16 6.16 0 0 0 12 5.84zm0 10.16A4 4 0 1 1 16 12a4 4 0 0 1-4 4z"/>
            <circle fill="url(#{{ $ig }})" cx="18.41" cy="5.59" r="1.44"/>
        </svg>
        @break
    @case('facebook')
        <svg viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" role="img" aria-label="Facebook" style="display:block;flex-shrink:0;">
            <path fill="#1877F2" d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.49 0-1.96.93-1.96 1.89v2.25h3.32l-.53 3.49h-2.79V24C19.61 23.1 24 18.1 24 12.07z"/>
        </svg>
        @break
    @case('google')
        <svg viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" role="img" aria-label="Google" style="display:block;flex-shrink:0;">
            <path fill="#4285F4" d="M23.06 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h6.2a5.3 5.3 0 0 1-2.3 3.48v2.89h3.72c2.18-2.01 3.44-4.97 3.44-8.38z"/>
            <path fill="#34A853" d="M12 24c3.1 0 5.7-1.03 7.6-2.79l-3.72-2.89c-1.03.69-2.35 1.1-3.88 1.1-2.98 0-5.5-2.01-6.4-4.72H1.75v2.98A11.99 11.99 0 0 0 12 24z"/>
            <path fill="#FBBC05" d="M5.6 14.7A7.2 7.2 0 0 1 5.22 12c0-.94.16-1.85.38-2.7V6.32H1.75A11.99 11.99 0 0 0 .48 12c0 1.94.46 3.77 1.27 5.68l3.85-2.98z"/>
            <path fill="#EA4335" d="M12 4.75c1.68 0 3.19.58 4.38 1.71l3.28-3.28C17.7 1.19 15.1 0 12 0 7.31 0 3.26 2.69 1.75 6.32L5.6 9.3c.9-2.71 3.42-4.55 6.4-4.55z"/>
        </svg>
        @break
    @default
        <i data-feather="{{ $glyphs[$source] ?? 'more-horizontal' }}"
           style="width:{{ $size }}px;height:{{ $size }}px;flex-shrink:0;"></i>
@endswitch
