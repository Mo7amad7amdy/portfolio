/* Privacy-friendly analytics beacon (first-party, no third parties). */
(() => {
    'use strict';
    const me = document.currentScript;
    const id = me && me.dataset.visit;
    const url = me && me.dataset.endpoint;
    if (!id || !url) return;

    const start = Date.now();
    let hiddenFor = 0;
    let hiddenAt = document.hidden ? Date.now() : null;
    let maxScroll = 0;
    let queue = [];

    const active = () => {
        const hidden = hiddenFor + (hiddenAt ? Date.now() - hiddenAt : 0);
        return Math.max(0, Math.round((Date.now() - start - hidden) / 1000));
    };

    const onScroll = () => {
        const el = document.documentElement;
        const pct = Math.round(((el.scrollTop || window.scrollY) + window.innerHeight) / el.scrollHeight * 100);
        if (pct > maxScroll) maxScroll = Math.min(100, pct);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    let tz = null;
    try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone; } catch (e) { /* old browser */ }

    const send = () => {
        const body = JSON.stringify({
            v: id, d: active(), s: maxScroll,
            w: screen.width, h: screen.height, vw: window.innerWidth,
            tz, l: navigator.language, e: queue.splice(0),
        });
        try {
            if (navigator.sendBeacon && navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }))) return;
        } catch (e) { /* fall through */ }
        fetch(url, { method: 'POST', body, keepalive: true, headers: { 'Content-Type': 'application/json' } }).catch(() => {});
    };
    const track = (n, t) => { queue.push({ n, t: t || null }); send(); };

    // First ping confirms a real browser; then a heartbeat while the tab is visible (max 15 min).
    setTimeout(send, 1500);
    const beat = setInterval(() => {
        if (!document.hidden) send();
        if (active() > 900) clearInterval(beat);
    }, 30000);

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) { hiddenAt = Date.now(); send(); }
        else if (hiddenAt) { hiddenFor += Date.now() - hiddenAt; hiddenAt = null; }
    });
    window.addEventListener('pagehide', send);

    // Clicks worth knowing about.
    document.addEventListener('click', (e) => {
        const a = e.target.closest && e.target.closest('a[href]');
        if (!a) return;
        const href = a.getAttribute('href') || '';
        let host = '';
        try { host = new URL(a.href).hostname.replace(/^www\./, ''); } catch (err) { /* relative */ }

        if (a.hasAttribute('download') || /\.pdf($|\?)/i.test(href)) return track('cv_download', href.split('/').pop());
        if (href.startsWith('mailto:')) return track('email_click');
        if (/wa\.me|whatsapp/i.test(href)) return track('whatsapp_click');
        if (a.closest('.social-icons, .contact-links') || /\bme\b/.test(a.rel)) return track('social_click', host);
        if (a.closest('.work-card, .work-list')) return track('project_click', a.href);
        if (host && host !== location.hostname.replace(/^www\./, '')) return track('outbound_click', a.href);
    }, true);

    // Which sections people actually reach.
    if ('IntersectionObserver' in window) {
        const seen = new Set();
        const io = new IntersectionObserver((entries) => {
            entries.forEach((en) => {
                const sid = en.target.id;
                if (en.isIntersecting && sid && !seen.has(sid)) {
                    seen.add(sid);
                    queue.push({ n: 'section', t: sid });
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.35 });
        document.querySelectorAll('main section[id]').forEach((s) => io.observe(s));
    }

    // Contact form: the server records the submit; pass the visit id along.
    document.querySelectorAll('form.contact-form').forEach((f) => {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = '_visit'; input.value = id;
        f.appendChild(input);
    });
})();
