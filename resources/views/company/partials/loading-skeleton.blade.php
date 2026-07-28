{{--
  Navigation progress bar — replaces the old full-screen skeleton overlay.
  The old overlay hid already-rendered server HTML until DOMContentLoaded
  (~1s+ of artificial blank time on every page). This 2px gold bar gives
  loading feedback without ever hiding content.
--}}
<style>
#bk-nav-progress {
    position: fixed;
    top: 0;
    inset-inline-start: 0;
    width: 0;
    height: 2.5px;
    z-index: 99999;
    background: linear-gradient(90deg, var(--bk-accent), #B7CA90);
    box-shadow: 0 0 8px rgba(75,93,52,.6);
    opacity: 0;
    pointer-events: none;
    transition: width .25s ease, opacity .3s ease;
}
#bk-nav-progress.active { opacity: 1; }
@media (prefers-reduced-motion: reduce) {
    #bk-nav-progress { transition: none; }
}
</style>

<div id="bk-nav-progress" role="progressbar" aria-hidden="true"></div>

<script>
(function () {
    var bar = document.getElementById('bk-nav-progress');
    var timer = null;

    function start() {
        if (!bar) return;
        bar.classList.add('active');
        bar.style.width = '30%';
        var w = 30;
        clearInterval(timer);
        timer = setInterval(function () {
            // Creep toward 90% while the next page loads
            w = Math.min(90, w + (90 - w) * 0.08);
            bar.style.width = w + '%';
        }, 250);
    }

    // Trigger on real navigations (links + form submits), skip new-tab/hash/js links
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href]');
        if (!a) return;
        if (a.target === '_blank' || a.hasAttribute('download')) return;
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
        var href = a.getAttribute('href') || '';
        if (href === '' || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
        start();
    }, true);
    document.addEventListener('submit', function () { start(); }, true);

    // Finish instantly if the page was restored from bfcache
    window.addEventListener('pageshow', function () {
        clearInterval(timer);
        if (bar) { bar.style.width = '0'; bar.classList.remove('active'); }
    });
})();
</script>
