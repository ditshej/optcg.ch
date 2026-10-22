(() => {
    const $ = (id) => document.getElementById(id);
    const stage = $('stage');
    const cam = $('cam');
    const svg = $('scene');
    const leadSvg = $('leaders');
    const sheet = $('sheet');
    const body = $('sheet-body');
    const chips = [...$('chips').querySelectorAll('.chip')];
    const tiles = [...document.querySelectorAll('.tile')];
    const compactQuery = window.matchMedia('(max-width: 639px), (max-width: 899px) and (orientation: portrait)');
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    const sideMode = window.matchMedia('(min-width: 700px) and (orientation: landscape)');
    const Ship = window.SunnyShip;
    const ROOMS = Ship.ROOMS;

    // ---------- camera: turning is limited around a rest view, so the ship can never be seen upside down ----------
    const REST_WIDE = { yaw: -40, pitch: 38 }; // landscape stage: bow lower left, stern upper right
    const REST_TALL = { yaw: -62, pitch: 40 }; // portrait stage: the ship stands up, bow (lion) at the bottom
    let rest = REST_WIDE;
    const view = { ...rest };
    const span = { yaw: 26, pitch: [26, 54] };
    const limits = () => ({ yaw: [rest.yaw - span.yaw, rest.yaw + span.yaw], pitch: span.pitch });
    let showDims = true;
    let W = 0;
    let H = 0;
    let fitted = null;
    let zoom = null; // { k, tx, ty, id }
    let current = null;
    const clamp = (v, [lo, hi]) => Math.max(lo, Math.min(hi, v));
    const f1 = (n) => n.toFixed(1);

    // ---------- callouts: two columns outside the ship, order and height fixed at the rest view ----------
    const layout = { slots: new Map(), edge: 14 };
    const MIN_GAP = 8;
    const relax = (items, minDist, bounds) => { // push apart anything closer than minDist, keep inside bounds
        for (let n = 0; n < 40; n++) {
            let moved = false;
            for (let i = 0; i < items.length; i++) {
                for (let j = i + 1; j < items.length; j++) {
                    const dx = items[j].x - items[i].x;
                    const dy = items[j].y - items[i].y;
                    const d = Math.hypot(dx, dy) || 0.01;
                    if (d < minDist) {
                        const k = (minDist - d) / 2 / d;
                        items[i].x -= dx * k; items[i].y -= dy * k; items[j].x += dx * k; items[j].y += dy * k;
                        moved = true;
                    }
                }
            }
            for (const it of items) { it.x = Math.max(bounds[0], Math.min(bounds[2], it.x)); it.y = Math.max(bounds[1], Math.min(bounds[3], it.y)); }
            if (!moved) break;
        }
    };
    const planSlots = (compact) => {
        layout.slots.clear();
        layout.left = layout.edge;
        layout.right = W - layout.edge;
        if (compact) return;
        const bb = Ship.bounds(fitted, rest);
        const widest = Math.max(...chips.map((c) => c.offsetWidth));
        layout.left = Math.max(layout.edge, Math.min((W - widest) / 2 - 24, bb[0] - 56 - widest));
        layout.right = Math.min(W - layout.edge, Math.max((W + widest) / 2 + 24, bb[2] + 56 + widest));
        const info = chips.map((c) => ({ id: c.dataset.room, w: c.offsetWidth, h: c.offsetHeight, a: Ship.anchor(fitted, rest, c.dataset.room) }));
        const byX = [...info].sort((p, q) => p.a[0] - q.a[0]);
        const half = Math.ceil(byX.length / 2);
        [byX.slice(0, half), byX.slice(half)].forEach((col, side) => {
            col.sort((p, q) => p.a[1] - q.a[1]);
            const tall = Math.max(...info.map((i) => i.h));
            const gap = tall + MIN_GAP;
            const top = tall / 2 + 10;
            const bottom = H - tall / 2 - 10;
            const ys = col.map((c) => Math.max(top, Math.min(bottom, c.a[1])));
            for (let i = 1; i < ys.length; i++) ys[i] = Math.max(ys[i], ys[i - 1] + gap);
            for (let i = ys.length - 1; i >= 0; i--) { if (ys[i] > bottom - (ys.length - 1 - i) * gap) ys[i] = bottom - (ys.length - 1 - i) * gap; }
            for (let i = 1; i < ys.length; i++) ys[i] = Math.max(ys[i], ys[i - 1] + gap);
            col.forEach((c, i) => layout.slots.set(c.id, { side: side ? 'r' : 'l', y: ys[i], w: c.w, h: c.h }));
        });
    };

    const placeLabels = () => {
        const compact = stage.classList.contains('compact');
        let lines = '';
        if (compact) {
            const items = chips.map((c) => { const a = Ship.anchor(fitted, view, c.dataset.room); return { c, a, x: a[0], y: a[1] + 0 }; });
            relax(items, 36, [20, 20, W - 20, H - 20]);
            for (const it of items) {
                it.c.style.setProperty('--x', f1(it.x) + 'px');
                it.c.style.setProperty('--y', f1(it.y) + 'px');
                if (Math.hypot(it.x - it.a[0], it.y - it.a[1]) > 4) lines += `<line class="lead" x1="${f1(it.a[0])}" y1="${f1(it.a[1])}" x2="${f1(it.x)}" y2="${f1(it.y)}"/>`;
                lines += `<circle class="dot" cx="${f1(it.a[0])}" cy="${f1(it.a[1])}" r="3"/>`;
            }
        } else {
            for (const c of chips) {
                const slot = layout.slots.get(c.dataset.room);
                if (!slot) continue;
                const a = Ship.anchor(fitted, view, c.dataset.room);
                const left = slot.side === 'l';
                const x = left ? layout.left : layout.right - slot.w;
                c.style.setProperty('--x', f1(x) + 'px');
                c.style.setProperty('--y', f1(slot.y - slot.h / 2) + 'px');
                c.classList.toggle('r', !left);
                const ex = left ? x + slot.w : x; // chip edge where the leader ends
                const kx = ex + (left ? 22 : -22);
                const path = `${f1(a[0])},${f1(a[1])} ${f1(kx)},${f1(slot.y)} ${f1(ex)},${f1(slot.y)}`;
                lines += `<g class="ld" data-for="${c.dataset.room}"><polyline class="lead-case" points="${path}"/><polyline class="lead" points="${path}"/><circle class="dot" cx="${f1(a[0])}" cy="${f1(a[1])}" r="3.5"/></g>`;
            }
        }
        leadSvg.innerHTML = lines;
        const hot = chips.find((c) => c.classList.contains('hot'));
        if (hot) leadSvg.querySelector(`.ld[data-for="${CSS.escape(hot.dataset.room)}"]`)?.classList.add('hot');
    };

    // ---------- render ----------
    const render = () => {
        svg.innerHTML = Ship.draw(fitted, view, { dims: showDims });
        if (current) svg.querySelector(`.rm[data-room="${CSS.escape(current)}"]`)?.classList.add('on');
        placeLabels();
        if (hotId) hl(hotId, true);
    };
    let raf = 0;
    const queue = () => { if (!raf) raf = requestAnimationFrame(() => { raf = 0; render(); }); };

    const resize = () => {
        const compact = compactQuery.matches;
        showDims = (!compact || window.innerWidth >= 640) && stage.clientHeight >= 420;
        stage.classList.toggle('compact', compact);
        stage.classList.toggle('slim', !compact && stage.clientWidth < 900);
        W = stage.clientWidth;
        H = stage.clientHeight;
        if (!W || !H) return;
        const tall = W / H < 0.95;
        const nextRest = tall ? REST_TALL : REST_WIDE;
        if (nextRest !== rest) { rest = nextRest; Object.assign(view, rest); }
        svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
        leadSvg.setAttribute('viewBox', `0 0 ${W} ${H}`);
        const widest = compact ? 0 : Math.max(...chips.map((c) => c.offsetWidth));
        const padX = compact ? 0.04 : Math.min(0.3, (widest + layout.edge + 26) / W);
        fitted = Ship.fit(W, H, { rest, limits: limits(), padX, fillY: compact ? 0.84 : (H < 420 ? 0.86 : 0.9), maxScale: 9, dims: showDims });
        planSlots(compact);
        render();
    };
    new ResizeObserver(resize).observe(stage);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(resize);

    // ---------- turning the ship ----------
    let drag = null;
    let moved = false;
    stage.addEventListener('pointerdown', (e) => {
        if (e.target.closest('.chip, .ctl') || zoom) return;
        drag = { x: e.clientX, y: e.clientY, yaw: view.yaw, pitch: view.pitch, touch: e.pointerType === 'touch' };
        moved = false;
    });
    stage.addEventListener('pointermove', (e) => {
        if (!drag) return;
        const dx = e.clientX - drag.x;
        const dy = e.clientY - drag.y;
        if (!moved && Math.hypot(dx, dy) > 6) { moved = true; stage.setPointerCapture(e.pointerId); stage.classList.add('turning'); }
        if (!moved) return;
        view.yaw = clamp(drag.yaw + dx * 0.3, limits().yaw);
        if (!drag.touch) view.pitch = clamp(drag.pitch - dy * 0.25, limits().pitch);
        queue();
    });
    const endDrag = () => { drag = null; stage.classList.remove('turning'); };
    stage.addEventListener('pointerup', endDrag);
    stage.addEventListener('pointercancel', endDrag);
    stage.addEventListener('click', (e) => { if (moved) { e.stopPropagation(); e.preventDefault(); moved = false; } }, true);

    const glide = (to) => { // short eased turn for buttons and keys; a jump with reduced motion
        const from = { yaw: view.yaw, pitch: view.pitch };
        if (reduce.matches) { Object.assign(view, to); render(); return; }
        const t0 = performance.now();
        const step = (t) => {
            const k = Math.min(1, (t - t0) / 320);
            const e = 1 - Math.pow(1 - k, 3);
            view.yaw = from.yaw + (to.yaw - from.yaw) * e;
            view.pitch = from.pitch + (to.pitch - from.pitch) * e;
            render();
            if (k < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
    };
    const nudge = (dyaw, dpitch) => glide({ yaw: clamp(view.yaw + dyaw, limits().yaw), pitch: clamp(view.pitch + dpitch, limits().pitch) });
    $('ctl').addEventListener('click', (e) => {
        const b = e.target.closest('button');
        if (!b || zoom) return;
        if (b.hasAttribute('data-reset')) glide({ yaw: rest.yaw, pitch: rest.pitch });
        else if (b.dataset.yaw) nudge(13 * +b.dataset.yaw, 0);
        else nudge(0, 6 * +b.dataset.pitch);
    });
    stage.addEventListener('keydown', (e) => { // arrow keys turn the ship while the stage itself has focus
        if (e.target !== stage || zoom) return;
        const m = { ArrowLeft: [-13, 0], ArrowRight: [13, 0], ArrowUp: [0, 6], ArrowDown: [0, -6] }[e.key];
        if (m) { e.preventDefault(); nudge(...m); }
    });

    // ---------- camera move into a room ----------
    const hl = (id, on) => {
        svg.querySelector(`.rm[data-room="${CSS.escape(id)}"]`)?.classList.toggle('hot', on);
        chips.find((c) => c.dataset.room === id)?.classList.toggle('hot', on);
        leadSvg.querySelector(`.ld[data-for="${CSS.escape(id)}"]`)?.classList.toggle('hot', on);
    };
    const zoomTo = (id) => {
        const r = ROOMS[id];
        if (!r || reduce.matches) return;
        const [, , , dx, , dz] = r.box;
        const a = Ship.anchor(fitted, view, id);
        const top = stage.getBoundingClientRect().top;
        let visW = W;
        let visH = H;
        if (sideMode.matches) visW = Math.min(W, window.innerWidth - Math.min(420, window.innerWidth * 0.5));
        else visH = Math.max(120, window.innerHeight * 0.38 - top);
        const k = Math.max(1.6, Math.min(window.innerWidth >= 640 ? 2.2 : 2.6, (visW * 0.5) / (dx * fitted.S * 0.8), (visH * 0.5) / (dz * fitted.S * 1.4)));
        zoom = { k, tx: visW / 2 - a[0] * k, ty: visH / 2 - a[1] * k, id };
        stage.classList.add('zoomed');
        cam.style.transform = `translate(${zoom.tx}px, ${zoom.ty}px) scale(${zoom.k})`;
    };
    const zoomOut = () => {
        zoom = null;
        cam.style.transform = '';
        stage.classList.remove('zoomed');
    };

    // ---------- sheet ----------
    let openId = null;
    let opener = null;
    const fill = (id) => {
        const d = $('list-' + id);
        $('sheet-k').textContent = 'Detail ' + d.dataset.no + ' · ' + d.dataset.sub;
        $('sheet-h').textContent = d.querySelector('.p-top').textContent;
        body.replaceChildren();
        const desc = document.createElement('p');
        desc.className = 'desc';
        desc.textContent = d.dataset.desc;
        body.append(desc);
        const clone = (d.querySelector('.links') || d.querySelector('.empty')).cloneNode(true);
        clone.querySelectorAll('li').forEach((li, i) => li.style.setProperty('--n', i));
        body.append(clone);
        body.scrollTop = 0;
    };
    const mark = (id, on) => {
        current = on ? id : null;
        const g = svg.querySelector(`.rm[data-room="${CSS.escape(id)}"]`);
        if (g) g.classList.toggle('on', on);
        chips.concat(tiles).forEach((c) => c.classList.toggle('on', on && c.dataset.room === id));
    };

    const show = (id, from) => {
        if (!$('list-' + id)) return;
        if (openId) {
            mark(openId, false);
            openId = id;
            fill(id);
            mark(id, true);
            zoomTo(id);
            return;
        }
        openId = id;
        opener = from || null;
        fill(id);
        mark(id, true);
        sheet.showModal();
        sheet.style.transform = '';
        requestAnimationFrame(() => requestAnimationFrame(() => sheet.classList.add('in')));
        if (reduce.matches) stage.classList.add('zoomed');
        zoomTo(id);
        $('sheet-h').focus({ preventScroll: true });
    };
    const hide = () => {
        if (!openId) return;
        const id = openId;
        openId = null;
        sheet.classList.remove('in');
        sheet.style.transform = '';
        zoomOut();
        let done = false;
        const finish = () => {
            if (done) return;
            done = true;
            sheet.close();
            mark(id, false);
            if (opener && document.contains(opener)) opener.focus({ preventScroll: true });
        };
        if (reduce.matches) finish();
        else {
            sheet.addEventListener('transitionend', finish, { once: true });
            setTimeout(finish, 500);
        }
    };
    const open = (id, from) => {
        history.pushState({ room: id }, '', '#' + id);
        show(id, from);
    };
    const requestClose = () => {
        if (history.state && history.state.room) history.back();
        else hide();
    };
    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.room) show(e.state.room);
        else hide();
    });

    chips.concat(tiles).forEach((c) => c.addEventListener('click', (e) => { e.preventDefault(); open(c.dataset.room, c); }));
    svg.addEventListener('click', (e) => {
        const g = e.target.closest('.rm');
        if (g) open(g.dataset.room, chips.find((c) => c.dataset.room === g.dataset.room));
    });
    let hotId = null;
    const setHot = (id) => { if (hotId) hl(hotId, false); hotId = id; if (id) hl(id, true); };
    svg.addEventListener('pointerover', (e) => { const g = e.target.closest('.rm'); const id = g ? g.dataset.room : null; if (id !== hotId) setHot(id); });
    svg.addEventListener('pointerleave', () => setHot(null));
    chips.forEach((c) => {
        c.addEventListener('pointerenter', () => setHot(c.dataset.room));
        c.addEventListener('pointerleave', () => setHot(null));
        c.addEventListener('focus', () => setHot(c.dataset.room));
        c.addEventListener('blur', () => setHot(null));
    });
    $('close').addEventListener('click', requestClose);
    sheet.addEventListener('cancel', (e) => { e.preventDefault(); requestClose(); });
    sheet.addEventListener('click', (e) => { if (e.target === sheet) requestClose(); });

    const grab = $('grab');
    let sw = null;
    grab.addEventListener('pointerdown', (e) => {
        if (sideMode.matches || e.target.closest('button')) return;
        sw = { y: e.clientY, t: e.timeStamp, dy: 0, v: 0 };
        grab.setPointerCapture(e.pointerId);
        sheet.classList.add('drag');
    });
    grab.addEventListener('pointermove', (e) => {
        if (!sw) return;
        const dy = Math.max(0, e.clientY - sw.y);
        sw.v = (dy - sw.dy) / Math.max(1, e.timeStamp - sw.t);
        sw.dy = dy;
        sw.t = e.timeStamp;
        sheet.style.transform = `translateY(${dy}px)`;
    });
    const release = () => {
        if (!sw) return;
        const { dy, v } = sw;
        sw = null;
        sheet.classList.remove('drag');
        if (dy > 90 || v > 0.6) requestClose();
        else sheet.style.transform = '';
    };
    grab.addEventListener('pointerup', release);
    grab.addEventListener('pointercancel', release);

    resize();
    const start = location.hash.replace(/^#(list-)?/, '');
    if (start && $('list-' + start)) {
        history.replaceState(null, '', location.pathname + location.search);
        open(start);
    }
})();
