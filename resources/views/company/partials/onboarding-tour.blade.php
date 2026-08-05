{{-- First-run guided tour (desktop). Shows once; state in localStorage 'bk-tour-v1'. --}}
<div id="bkTour" class="bk-tour" hidden>
    <div class="bk-tour-spot" id="bkTourSpot"></div>
    <div class="bk-tour-card" id="bkTourCard" role="dialog" aria-modal="true" aria-labelledby="bkTourTitle">
        <div class="bk-tour-step" id="bkTourStep"></div>
        <div class="bk-tour-title" id="bkTourTitle"></div>
        <div class="bk-tour-text" id="bkTourText"></div>
        <div class="bk-tour-dots" id="bkTourDots" aria-hidden="true"></div>
        <div class="bk-tour-actions">
            <button type="button" class="bk-tour-btn bk-tour-skip" id="bkTourSkip">{{ __('Skip') }}</button>
            <span class="bk-tour-actions-end">
                <button type="button" class="bk-tour-btn bk-tour-back" id="bkTourBack">{{ __('Back') }}</button>
                <button type="button" class="bk-tour-btn bk-tour-next" id="bkTourNext">{{ __('Next') }}</button>
            </span>
        </div>
    </div>
</div>

<script>
(function () {
    // State is server-side now (works across devices), not localStorage.
    var TOUR_DONE = @json((bool) ($bkOnboarding['tourDone'] ?? true));
    var TOUR_URL  = @json(route('company.onboarding.tour-complete'));
    var CSRF      = @json(csrf_token());

    var STEPS = [
        {
            center: true,
            title: @json(__('Welcome to Booksy Business!')),
            text:  @json(__('A quick tour to set up your salon in a few minutes.')),
            next:  @json(__('Start tour'))
        },
        {
            el: '[data-tour="rail-branch"]', section: 'branch',
            title: @json(__('Your branch, all in one place')),
            text:  @json(__('Services, staff, working hours and gallery for the selected branch.'))
        },
        {
            el: '[data-tour="svc-cats"]', section: 'settings',
            title: @json(__('Add service categories first')),
            text:  @json(__('Categories (e.g. Hair, Skincare) group your services on the booking page.'))
        },
        {
            el: '[data-tour="branch-services"]', section: 'branch',
            title: @json(__('Then add your services')),
            text:  @json(__('Set the price and duration for each service.'))
        },
        {
            el: '[data-tour="branch-employees"]', section: 'branch',
            title: @json(__('Add your staff')),
            text:  @json(__('Link employees to services so customers can pick them.'))
        },
        {
            el: '[data-tour="new-booking"]',
            title: @json(__('Create your first booking')),
            text:  @json(__('Everything ready — create a booking from here anytime.')),
            next:  @json(__('Finish'))
        }
    ];

    var root  = document.getElementById('bkTour');
    var spot  = document.getElementById('bkTourSpot');
    var card  = document.getElementById('bkTourCard');
    var elTitle = document.getElementById('bkTourTitle');
    var elText  = document.getElementById('bkTourText');
    var elStep  = document.getElementById('bkTourStep');
    var elDots  = document.getElementById('bkTourDots');
    var btnNext = document.getElementById('bkTourNext');
    var btnBack = document.getElementById('bkTourBack');
    var btnSkip = document.getElementById('bkTourSkip');
    var isRtl = (document.documentElement.dir || '') === 'rtl';
    var i = 0;

    function done() {
        root.hidden = true;
        window.removeEventListener('resize', position);
        // Persist server-side so it never auto-replays on any device.
        try {
            fetch(TOUR_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        } catch (e) {}
    }

    function position() {
        var step = STEPS[i];
        card.style.maxWidth = '340px';

        if (step.center) {
            // Zero-size spotlight in the middle → its shadow dims the whole screen
            spot.style.display = 'block';
            spot.style.top = '50vh'; spot.style.left = '50vw';
            spot.style.width = '0px'; spot.style.height = '0px';
            card.style.top = '50%';
            card.style.left = '50%';
            card.style.transform = 'translate(-50%,-50%)';
            return;
        }
        var el = document.querySelector(step.el);
        if (!el) { next(); return; }
        var r = el.getBoundingClientRect();
        var pad = 6;

        spot.style.display = 'block';
        spot.style.top    = (r.top - pad) + 'px';
        spot.style.left   = (r.left - pad) + 'px';
        spot.style.width  = (r.width + pad * 2) + 'px';
        spot.style.height = (r.height + pad * 2) + 'px';

        card.style.transform = 'none';
        var cw = 340, ch = card.offsetHeight || 190, gap = 16;
        // Prefer beside the target (panel side), flip if no room
        var left = isRtl ? (r.left - cw - gap) : (r.right + gap);
        if (left < 8 || left + cw > window.innerWidth - 8) {
            left = isRtl ? (r.right + gap) : (r.left - cw - gap);
        }
        if (left < 8) left = Math.min(Math.max(8, r.left), window.innerWidth - cw - 8);
        var top = Math.min(Math.max(12, r.top), window.innerHeight - ch - 12);
        card.style.left = left + 'px';
        card.style.top  = top + 'px';
    }

    function render() {
        var step = STEPS[i];
        if (step.section && window.bkShowSection) window.bkShowSection(step.section);
        elTitle.textContent = step.title;
        elText.textContent  = step.text;
        elStep.textContent  = (i + 1) + ' / ' + STEPS.length;
        btnNext.textContent = step.next || @json(__('Next'));
        btnBack.style.visibility = i === 0 ? 'hidden' : 'visible';
        var dots = '';
        for (var d = 0; d < STEPS.length; d++) {
            dots += '<span class="' + (d === i ? 'on' : '') + '"></span>';
        }
        elDots.innerHTML = dots;
        // Let the section switch paint before measuring
        requestAnimationFrame(position);
    }

    function next() {
        if (i >= STEPS.length - 1) { done(); return; }
        i++; render();
    }

    btnNext.addEventListener('click', next);
    btnBack.addEventListener('click', function () { if (i > 0) { i--; render(); } });
    btnSkip.addEventListener('click', done);
    window.addEventListener('resize', position);

    function start() { i = 0; root.hidden = false; render(); }
    // Exposed so the Help (?) modal can replay the tour anytime.
    window.bkStartTour = start;

    // Auto-run only for brand-new businesses that haven't finished it (desktop).
    if (!TOUR_DONE && window.matchMedia('(min-width: 992px)').matches) {
        setTimeout(start, 900);
    }
})();
</script>
