/* Living portrait — a WebGL "AI photo" effect built from one image + a depth map.
 *
 *  - Real depth parallax (Depth Anything V2 map): the face turns toward the cursor,
 *    nose moves more than cheeks, shoulders counter-move.
 *  - Eyes blink on a natural random rhythm and glance toward the cursor.
 *  - Subtle breathing and idle head sway.
 *  - Relighting: a warm key light follows the cursor using normals from the depth map.
 *
 * Usage: const lp = LivingPortrait.create(canvas, { image, depth, closed?, meta });
 *        lp.render(mouseX, mouseY, timeMs)   // mouse in -1..1
 * Returns null when WebGL is unavailable so the caller can fall back.
 */
(() => {
    'use strict';

    const VERT = `
attribute vec2 aPos;
varying vec2 vUv;
void main() {
    vUv = vec2(aPos.x * 0.5 + 0.5, 0.5 - aPos.y * 0.5); // y down, like image space
    gl_Position = vec4(aPos, 0.0, 1.0);
}`;

    const FRAG = `
precision highp float;
varying vec2 vUv;
uniform sampler2D uImage;
uniform sampler2D uDepth;
uniform sampler2D uClosed;   // same photo with the eyes closed (optional)
uniform float uHasClosed;
uniform vec2  uMouse;
uniform float uTime;
uniform float uBlink;
uniform float uFocus;
uniform vec2  uPad;
uniform vec2  uStrength;
uniform vec4  uEyeL;
uniform vec4  uEyeR;
uniform vec4  uHead;
uniform vec2  uTexel;

float ell(vec2 p, vec4 e) { vec2 q = (p - e.xy) / e.zw; return dot(q, q); }

vec2 gaze(vec2 uv, vec4 e) {
    float k = ell(uv, e);
    if (k >= 1.0) return uv;
    float w = 1.0 - k;
    w = w * w * (3.0 - 2.0 * w);
    // Shift the eye content toward the cursor (fades out while blinking).
    return uv - vec2(uMouse.x * e.z * 0.16, uMouse.y * e.w * 0.25) * w * (1.0 - uBlink);
}

// Blink: an upper eyelid made of real skin sampled just above the eye slides down
// over the opening, with a darker rim, a lash line and a soft shadow below it.
vec4 eyelid(vec4 c, vec2 uv, vec4 e) {
    if (uBlink < 0.001) return c;
    // The eye ellipse (e.zw) is generous for the gaze warp; the visible opening
    // is roughly half as tall, so the lid works in those units.
    float openH = e.w * 0.55;
    vec2 q = (uv - e.xy) / vec2(e.z * 1.18, openH);   // a bit wider so the corners close too
    float ax = abs(q.x);
    if (ax >= 1.0 || q.y < -3.0 || q.y > 2.4) return c;

    float w2 = 1.0 - ax * ax;
    float hTop = 1.15 * pow(w2, 0.35);                      // upper lid arch (fuller than an ellipse)
    float hBot = 1.25 * sqrt(w2);                           // lower lash line
    float lidY = mix(-hTop, hBot, uBlink);                  // where the lid edge is now
    float band = 1.0;                                        // upper-lid skin band used for the lid
    float gap = 0.3;                                         // skip the lash line right above the opening
    float top = -hTop - 0.12;
    float xFade = 1.0 - smoothstep(0.85, 1.0, ax);
    if (q.y < top || q.y > lidY + 0.2) {
        // Below the lid: a soft contact shadow on the lower part of the eye.
        float under = smoothstep(lidY, lidY + 0.3, q.y) * (1.0 - smoothstep(lidY + 0.3, lidY + 0.9, q.y));
        c.rgb *= 1.0 - under * 0.2 * xFade * uBlink;
        return c;
    }

    // Only the eye opening is covered. The lid is the skin just above the lash line,
    // slid down (no stretching, so it stays sharp) and mirrored so it never reaches the brow.
    float d = max(lidY - q.y, 0.0);
    float m = band - abs(band - mod(d, 2.0 * band));
    float srcQ = -hTop - gap - m;
    float t = clamp(d / max(lidY + hTop, 0.001), 0.0, 1.0);   // 0 at the lid edge, 1 at the top
    t = 1.0 - t;
    float sy = e.y + srcQ * openH;
    float hx = e.z * 0.02;
    vec4 lid = (texture2D(uImage, vec2(uv.x - hx, sy)) + texture2D(uImage, vec2(uv.x, sy)) * 2.0
              + texture2D(uImage, vec2(uv.x + hx, sy))) * 0.25;

    // Even out folds with the band's average tone, and shade the rounded lid.
    vec2 s1 = vec2(0.45 * e.z, 0.0);
    float yb = e.y - openH * (hTop + gap + band * 0.5);
    vec4 tone = (texture2D(uImage, vec2(e.x, yb) - s1) + texture2D(uImage, vec2(e.x, yb))
               + texture2D(uImage, vec2(e.x, yb) + s1)) / 3.0;
    float stretch = 1.0;
    lid.rgb = mix(lid.rgb, tone.rgb * 0.92, 0.3 * smoothstep(0.0, 0.3, uBlink));
    // Rounded lid: slightly lit on the curve, in shadow toward the edge.
    lid.rgb *= mix(1.0, 0.82, smoothstep(0.3, 1.0, t) * uBlink);

    // Lash line on the lid edge.
    // Lash line on the lid edge: thin, tapered toward the corners.
    float lw = 0.03 + 0.09 * w2;
    float lash = (1.0 - smoothstep(lw * 0.4, lw, abs(q.y - lidY - lw * 0.3)))
               * smoothstep(0.1, 0.4, uBlink) * (1.0 - smoothstep(0.55, 1.0, ax));
    lid.rgb = mix(lid.rgb, vec3(0.1, 0.06, 0.045) * lid.a, lash * 0.7);

    float edge = (1.0 - smoothstep(lidY + 0.06, lidY + 0.2, q.y)) * smoothstep(top, top + 0.15, q.y);
    return mix(c, lid, edge * xFade);
}

// Blink with a real closed-eyes photo: the closed version is revealed from the top
// down, following the lid's arch, with a soft shadow on the moving lid edge.
vec4 blinkWipe(vec4 c, vec2 uv, vec4 e) {
    vec2 q = (uv - e.xy) / vec2(e.z * 1.45, e.w * 1.1);
    float ax = abs(q.x);
    if (ax >= 1.0 || q.y < -2.0 || q.y > 1.4) return c;
    vec4 closed = texture2D(uClosed, uv);
    float diff = length(closed.rgb - c.rgb);
    if (diff < 0.004) return c;                               // outside the retouched area
    float arch = pow(1.0 - ax * ax, 0.35);
    float lidY = mix(-arch * 0.75 - 0.15, 1.35, uBlink);
    float m = 1.0 - smoothstep(lidY - 0.18, lidY + 0.02, q.y);
    vec4 outc = mix(c, closed, m);
    // Shadow line on the lid edge while it moves (not when fully open/closed).
    float edge = (1.0 - smoothstep(0.0, 0.12, abs(q.y - lidY + 0.05))) * smoothstep(0.06, 0.18, diff)
               * (1.0 - smoothstep(0.55, 0.85, ax));
    outc.rgb *= 1.0 - edge * 0.35 * sin(3.14159 * clamp(uBlink, 0.0, 1.0));
    return outc;
}

void main() {
    vec2 p = (vUv - uPad) / (1.0 - 2.0 * uPad);

    // Breathing: tiny vertical scale anchored at the bottom edge.
    float breath = sin(uTime * 1.25) * 0.5 + 0.5;
    p.y = 1.0 - (1.0 - p.y) / (1.0 + breath * 0.0055);

    // Head weight: the head turns more than the body.
    float hm = 1.0 - smoothstep(0.25, 1.0, ell(p, uHead));
    vec2 sway = vec2(sin(uTime * 0.63), sin(uTime * 0.81 + 1.3)) * 0.12 * hm;
    vec2 off = (uMouse + sway) * uStrength * (1.0 + hm * 1.5);

    // Parallax occlusion by fixed-point iteration on the depth map.
    vec2 uv = p;
    for (int i = 0; i < 12; i++) {
        float d = texture2D(uDepth, uv).r;
        uv = p - off * (d - uFocus);
    }

    uv = gaze(uv, uEyeL);
    uv = gaze(uv, uEyeR);

    if (uv.x < 0.0 || uv.y < 0.0 || uv.x > 1.0 || uv.y > 1.0) { gl_FragColor = vec4(0.0); return; }
    vec4 c = texture2D(uImage, uv); // premultiplied
    if (uHasClosed > 0.5) {
        if (uBlink > 0.001) {
            c = blinkWipe(c, uv, uEyeL);
            c = blinkWipe(c, uv, uEyeR);
        }
    } else {
        c = eyelid(c, uv, uEyeL);
        c = eyelid(c, uv, uEyeR);
    }

    // Relight from the depth normals: warm key light that follows the cursor.
    vec2 t = uTexel * 2.0;
    float dx = texture2D(uDepth, uv + vec2(t.x, 0.0)).r - texture2D(uDepth, uv - vec2(t.x, 0.0)).r;
    float dy = texture2D(uDepth, uv + vec2(0.0, t.y)).r - texture2D(uDepth, uv - vec2(0.0, t.y)).r;
    vec3 n = normalize(vec3(-dx * 18.0, dy * 18.0, 1.0));
    vec3 L = normalize(vec3(uMouse.x * 0.9, -uMouse.y * 0.7, 1.0));
    float diff = clamp(dot(n, L) - n.z * L.z, -0.6, 0.6);   // only the change caused by the light moving
    float spec = pow(max(dot(reflect(-L, n), vec3(0.0, 0.0, 1.0)), 0.0), 18.0);
    // Multiplicative so shadows keep the skin's hue (no grey cast), warm additive sheen on top.
    c.rgb *= 1.0 + diff * 0.2;
    c.rgb += vec3(1.0, 0.78, 0.5) * max(diff, 0.0) * 0.06 * c.a + vec3(1.0, 0.85, 0.65) * spec * 0.04 * hm * c.a;

    gl_FragColor = c;
}`;

    function compile(gl, type, src) {
        const s = gl.createShader(type);
        gl.shaderSource(s, src);
        gl.compileShader(s);
        if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) {
            throw new Error(gl.getShaderInfoLog(s) || 'shader compile failed');
        }
        return s;
    }

    function loadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.decoding = 'async';
            img.onload = () => resolve(img);
            img.onerror = () => reject(new Error(`failed to load ${src}`));
            img.src = src;
        });
    }

    function texture(gl, img, unit) {
        const tex = gl.createTexture();
        gl.activeTexture(gl.TEXTURE0 + unit);
        gl.bindTexture(gl.TEXTURE_2D, tex);
        gl.pixelStorei(gl.UNPACK_PREMULTIPLY_ALPHA_WEBGL, true);
        gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, img);
        gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
        gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
        gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
        gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
        return tex;
    }

    /** Natural blink rhythm: 2.5–6s apart, ~160ms, sometimes a double blink. */
    function blinker() {
        let next = 1200;
        let start = -1;
        let double = false;
        return (t) => {
            if (start < 0 && t >= next) {
                start = t;
                double = Math.random() < 0.18;
            }
            if (start < 0) return 0;
            const dur = 170;
            const e = t - start;
            const total = double ? dur * 2 + 90 : dur;
            if (e >= total) {
                start = -1;
                next = t + 2500 + Math.random() * 3500;
                return 0;
            }
            const local = double && e > dur ? Math.max(0, e - dur - 90) : e;
            if (local <= 0 || local >= dur) return 0;
            const x = local / dur; // fast close, slightly slower open
            return x < 0.4 ? Math.sin((x / 0.4) * Math.PI / 2) : Math.cos(((x - 0.4) / 0.6) * Math.PI / 2);
        };
    }

    function create(canvas, { image, depth, closed, meta }) {
        const gl = canvas.getContext('webgl', { premultipliedAlpha: true, alpha: true, antialias: false })
            || canvas.getContext('experimental-webgl');
        if (!gl || !meta || !Array.isArray(meta.eyes) || meta.eyes.length < 2) return null;

        let prog;
        try {
            prog = gl.createProgram();
            gl.attachShader(prog, compile(gl, gl.VERTEX_SHADER, VERT));
            gl.attachShader(prog, compile(gl, gl.FRAGMENT_SHADER, FRAG));
            gl.linkProgram(prog);
            if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) throw new Error('link failed');
        } catch (err) {
            console.warn('[living-portrait]', err);
            return null;
        }
        gl.useProgram(prog);

        const buf = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, buf);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
        const aPos = gl.getAttribLocation(prog, 'aPos');
        gl.enableVertexAttribArray(aPos);
        gl.vertexAttribPointer(aPos, 2, gl.FLOAT, false, 0, 0);

        const u = {};
        ['uImage', 'uDepth', 'uClosed', 'uHasClosed', 'uMouse', 'uTime', 'uBlink', 'uFocus', 'uPad', 'uStrength', 'uEyeL', 'uEyeR', 'uHead', 'uTexel']
            .forEach((n) => { u[n] = gl.getUniformLocation(prog, n); });

        const [eyeA, eyeB] = [...meta.eyes].sort((a, b) => a.x - b.x);
        const head = meta.head || { x: 0.5, y: 0.28, rx: 0.28, ry: 0.3 };
        const PAD = 0.05;
        gl.uniform1i(u.uImage, 0);
        gl.uniform1i(u.uDepth, 1);
        gl.uniform1i(u.uClosed, 2);
        gl.uniform1f(u.uHasClosed, 0);
        gl.uniform1f(u.uFocus, meta.focus ?? 0.36);
        gl.uniform2f(u.uPad, PAD, PAD);
        gl.uniform2f(u.uStrength, meta.strength?.[0] ?? 0.05, meta.strength?.[1] ?? 0.035);
        gl.uniform4f(u.uEyeL, eyeA.x, eyeA.y, eyeA.rx, eyeA.ry);
        gl.uniform4f(u.uEyeR, eyeB.x, eyeB.y, eyeB.rx, eyeB.ry);
        gl.uniform4f(u.uHead, head.x, head.y, head.rx, head.ry);

        const state = { ready: false, failed: false, aspect: 1.25 };
        const blink = blinker();

        const ready = Promise.all([loadImage(image), loadImage(depth)]).then(([img, dep]) => {
            texture(gl, img, 0);
            texture(gl, dep, 1);
            gl.uniform2f(u.uTexel, 1 / dep.naturalWidth, 1 / dep.naturalHeight);
            state.aspect = img.naturalHeight / img.naturalWidth;
            state.ready = true;
            // The closed-eyes photo is optional; without it the procedural lid is used.
            if (closed) {
                loadImage(closed).then((cl) => {
                    texture(gl, cl, 2);
                    gl.uniform1f(u.uHasClosed, 1);
                }).catch((err) => console.warn('[living-portrait]', err));
            }
        }).catch((err) => {
            console.warn('[living-portrait]', err);
            state.failed = true;
            throw err;
        });

        function resize() {
            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            const w = Math.round(canvas.clientWidth * dpr);
            const h = Math.round(canvas.clientHeight * dpr);
            if (w && h && (canvas.width !== w || canvas.height !== h)) {
                canvas.width = w;
                canvas.height = h;
                gl.viewport(0, 0, w, h);
            }
        }

        function render(mx, my, now) {
            if (!state.ready) return;
            resize();
            gl.uniform2f(u.uMouse, mx, my);
            gl.uniform1f(u.uTime, now / 1000);
            gl.uniform1f(u.uBlink, blink(now));
            gl.clearColor(0, 0, 0, 0);
            gl.clear(gl.COLOR_BUFFER_BIT);
            gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
        }

        return { ready, render, state, pad: PAD };
    }

    window.LivingPortrait = { create };
})();
