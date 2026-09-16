/* Portfolio — public site interactions (no build step, no dependencies) */
(() => {
    'use strict';

    document.documentElement.classList.add('js');

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

    /* ---------------- Nav: scrolled state + mobile toggle ---------------- */
    const nav = document.querySelector('[data-nav]');
    const toggle = document.querySelector('[data-nav-toggle]');
    if (nav) {
        const onScroll = () => nav.classList.toggle('is-scrolled', window.scrollY > 20);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        toggle?.addEventListener('click', () => {
            const open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(open));
        });
        nav.querySelectorAll('.nav-links a').forEach((a) => a.addEventListener('click', () => {
            nav.classList.remove('is-open');
            toggle?.setAttribute('aria-expanded', 'false');
        }));
    }

    /* ---------------- Reveal on scroll ---------------- */
    const revealables = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && !reduceMotion) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        revealables.forEach((el) => io.observe(el));
    } else {
        revealables.forEach((el) => el.classList.add('is-visible'));
    }

    /* ---------------- Hero: face follows the pointer ----------------
     * The portrait is two cut-out layers: body and head. The head rotates in 3D
     * around the neck toward the cursor, the body follows a little, and the
     * background elements move the opposite way for depth. Values are eased
     * every frame, and the loop sleeps when the hero is off-screen or settled.
     */
    const stage = document.querySelector('[data-face-stage]');
    if (stage && !reduceMotion) {
        const hero = stage.closest('.hero') || document.body;
        const head = stage.querySelector('[data-head]');
        const body = stage.querySelector('[data-body]');
        const spot = hero.querySelector('[data-spot]');
        const depthEls = [...stage.querySelectorAll('[data-depth]')];
        const isLayers = stage.dataset.mode === 'layers';

        // Tuned for the head/body split: enough turn to read as "looking", small
        // enough that the neck seam never opens up.
        const HEAD = isLayers
            ? { rotY: 20, rotX: 12, rotZ: 2, moveX: 18, moveY: 10 }
            : { rotY: 12, rotX: 9, rotZ: 0, moveX: 8, moveY: 6 };

        const target = { x: 0, y: 0 };
        const current = { x: 0, y: 0 };
        let visible = true;
        let running = false;
        let lastMove = 0;
        let idleT = 0;

        const aim = (clientX, clientY) => {
            const r = stage.getBoundingClientRect();
            // Aim point ≈ the eyes (upper-middle of the portrait).
            const ex = r.left + r.width * 0.48;
            const ey = r.top + r.height * 0.32;
            // Normalise against the distance to the viewport edge so the head
            // reaches full turn when the cursor is at the edge of the screen.
            const dx = clientX - ex;
            const dy = clientY - ey;
            target.x = clamp(dx / Math.max(ex, window.innerWidth - ex), -1, 1);
            target.y = clamp(dy / Math.max(ey, window.innerHeight - ey, 1), -1, 1);
            lastMove = performance.now();
            if (spot) {
                const hr = hero.getBoundingClientRect();
                spot.style.setProperty('--sx', `${clientX - hr.left}px`);
                spot.style.setProperty('--sy', `${clientY - hr.top}px`);
            }
            start();
        };

        window.addEventListener('pointermove', (e) => aim(e.clientX, e.clientY), { passive: true });
        document.documentElement.addEventListener('mouseleave', () => { target.x = 0; target.y = 0; start(); });

        const render = () => {
            const { x, y } = current;
            if (head) {
                head.style.transform =
                    `translate3d(${(x * HEAD.moveX).toFixed(2)}px, ${(y * HEAD.moveY).toFixed(2)}px, 0) ` +
                    `rotateY(${(x * HEAD.rotY).toFixed(2)}deg) ` +
                    `rotateX(${(-y * HEAD.rotX).toFixed(2)}deg) ` +
                    `rotateZ(${(x * HEAD.rotZ).toFixed(2)}deg)`;
                // Light comes from the side the head turns toward.
                if (isLayers) {
                    head.style.filter = `drop-shadow(${(-x * 10).toFixed(1)}px 18px 24px rgba(0,0,0,.35)) brightness(${(1 + x * 0.04 - y * 0.03).toFixed(3)})`;
                }
            }
            if (body) {
                body.style.transform =
                    `translate3d(${(x * 5).toFixed(2)}px, ${(y * 2).toFixed(2)}px, 0) rotateY(${(x * 4).toFixed(2)}deg)`;
            }
            depthEls.forEach((el) => {
                const d = parseFloat(el.dataset.depth) || 0;
                el.style.transform = `translate3d(${(x * 14 * d).toFixed(2)}px, ${(y * 10 * d).toFixed(2)}px, 0)`;
            });
        };

        const tick = (now) => {
            // Idle: after 4s without input, glance around slowly so the effect is
            // discoverable on touch devices too.
            if (now - lastMove > 4000) {
                idleT += 0.012;
                target.x = Math.sin(idleT) * 0.55;
                target.y = Math.sin(idleT * 0.7) * 0.25 - 0.05;
            }

            current.x += (target.x - current.x) * 0.085;
            current.y += (target.y - current.y) * 0.085;
            render();

            const settled = Math.abs(target.x - current.x) < 0.0005 && Math.abs(target.y - current.y) < 0.0005;
            const idle = now - lastMove > 4000;
            if (visible && (!settled || idle) && !document.hidden) {
                requestAnimationFrame(tick);
            } else {
                running = false;
            }
        };

        function start() {
            if (running || !visible) return;
            running = true;
            requestAnimationFrame(tick);
        }

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(([entry]) => {
                visible = entry.isIntersecting;
                if (visible) start();
            }).observe(hero);
        }
        document.addEventListener('visibilitychange', () => { if (!document.hidden) start(); });

        lastMove = performance.now();
        start();
    }

    /* ---------------- Project cards: cursor glow ---------------- */
    if (!reduceMotion) {
        document.querySelectorAll('[data-tilt]').forEach((card) => {
            card.addEventListener('pointermove', (e) => {
                const r = card.getBoundingClientRect();
                card.style.setProperty('--mx', `${e.clientX - r.left}px`);
                card.style.setProperty('--my', `${e.clientY - r.top}px`);
            });
        });
    }

    /* ---------------- Contact: scroll to the form after submit ---------------- */
    const alert = document.querySelector('.contact-form .alert, .contact-form .error');
    if (alert) alert.closest('section')?.scrollIntoView({ block: 'start' });
})();
