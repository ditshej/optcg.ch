(() => {
    const $ = (id) => document.getElementById(id);
    const story = $('story');
    const pin = $('pin');
    const stage = $('stage');
    const cam = $('cam');
    const svg = $('scene');
    const sheet = $('sheet');
    const body = $('sheet-body');
    const panel = $('pn-in');
    const chips = [...$('chips').querySelectorAll('.chip')];
    const tiles = [];
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    const sideMode = window.matchMedia('(min-width: 700px) and (orientation: landscape)');
    const Ship = window.SunnyShip;
    const ROOMS = Ship.ROOMS;

    // The story: overview, then every topic from bow to stern, then back to the overview before the list.
    const ORDER = Object.keys(ROOMS).sort((a, b) => ['bow', 'mid', 'stern'].indexOf(ROOMS[a].area) - ['bow', 'mid', 'stern'].indexOf(ROOMS[b].area) || ROOMS[a].box[0] - ROOMS[b].box[0]);
    const STOPS = [null, ...ORDER, null];
    const SEG = STOPS.length - 1;
    const HOLD = 0.58; // share of each scroll segment where the camera rests on a stop
    const SEG_VH = 80;

    const view = { yaw: -40, pitch: 38 };
    let showDims = true;
    let W = 0;
    let H = 0;
    let fitted = null;
    let zoom = null; // zoom of an opened room: { k, tx, ty, id }
    let current = null;
    let path = []; // camera per stop: { k, tx, ty }
    let shown = -2;
    const f1 = (n) => n.toFixed(1);
    const smooth = (t) => t * t * (3 - 2 * t);

    const relax = (items, minDist, bounds) => {
        for (let n = 0; n < 40; n++) {
            let moved = false;
            for (let i = 0; i < items.length; i++) for (let j = i + 1; j < items.length; j++) {
                const dx = items[j].x - items[i].x;
                const dy = items[j].y - items[i].y;
                const d = Math.hypot(dx, dy) || 0.01;
                if (d < minDist) { const k = (minDist - d) / 2 / d; items[i].x -= dx * k; items[i].y -= dy * k; items[j].x += dx * k; items[j].y += dy * k; moved = true; }
            }
            for (const it of items) { it.x = Math.max(bounds[0], Math.min(bounds[2], it.x)); it.y = Math.max(bounds[1], Math.min(bounds[3], it.y)); }
            if (!moved) break;
        }
    };

    const placeBadges = () => {
        const items = chips.map((c) => { const a = Ship.anchor(fitted, view, c.dataset.room); return { c, a, x: a[0], y: a[1] }; });
        relax(items, 34, [14, 14, W - 14, H - 14]);
        for (const it of items) {
            it.c.style.setProperty('--x', f1(it.x) + 'px');
            it.c.style.setProperty('--y', f1(it.y) + 'px');
        }
    };
    const render = () => {
        svg.innerHTML = Ship.draw(fitted, view, { dims: showDims });
        placeBadges();
        mark(current, true);
        paintCurrent();
    };

    // ---------- camera path ----------
    const planPath = () => {
        path = STOPS.map((id) => {
            if (!id) return { k: 1, tx: 0, ty: 0 };
            const [, , , dx, , dz] = ROOMS[id].box;
            const a = Ship.anchor(fitted, view, id);
            const k = Math.max(1.8, Math.min(2.8, (W * 0.5) / (dx * fitted.S * 1.1), (H * 0.5) / (dz * fitted.S * 2)));
            return { k, tx: W / 2 - a[0] * k, ty: H / 2 - a[1] * k };
        });
    };
    let stopNow = 0;
    const camAt = (p) => {
        const seg = Math.min(SEG - 1e-6, Math.max(0, p * SEG));
        const i = Math.floor(seg);
        const f = seg - i;
        const t = f < HOLD ? 0 : smooth((f - HOLD) / (1 - HOLD));
        const A = path[i];
        const B = path[i + 1];
        const k = A.k * Math.pow(B.k / A.k, t); // geometric, so the zoom feels even
        const cx = A.tx + (B.tx - A.tx) * t;
        const cy = A.ty + (B.ty - A.ty) * t;
        stopNow = f < HOLD + (1 - HOLD) / 2 ? i : i + 1;
        return { k, tx: cx, ty: cy, i, f, t };
    };
    const applyCam = (c) => {
        cam.style.setProperty('--ik', f1(1 / c.k));
        cam.style.transform = `translate3d(${f1(c.tx)}px, ${f1(c.ty)}px, 0) scale(${c.k.toFixed(4)})`;
    };

    // ---------- panel ----------
    const AREA_OF = (id) => (id ? ROOMS[id].area : null);
    const setPanel = (idx) => {
        if (idx === shown) return;
        shown = idx;
        const id = STOPS[idx];
        panel.replaceChildren();
        if (!id || idx === 0) {
            panel.innerHTML = '<p class="k">Thousand Sunny</p><h2>Sail the ship</h2><p class="desc">Scroll down: the camera runs from the bow to the stern and stops at every topic. Tap a room to jump straight into it.</p>';
        } else if (idx === STOPS.length - 1) {
            panel.innerHTML = '<p class="k">End of the route</p><h2>All topics</h2><p class="desc">The full list follows below.</p>';
        } else {
            const d = $('list-' + id);
            const k = document.createElement('p');
            k.className = 'k';
            k.textContent = `${Ship.AREA[ROOMS[id].area]} · stop ${String(idx).padStart(2, '0')}/${ORDER.length} · topic ${d.dataset.no}`;
            const h = document.createElement('h2');
            h.textContent = d.querySelector('.p-top').textContent;
            const n = document.createElement('span');
            n.className = 'cnt';
            n.textContent = d.querySelector('.p-cnt').textContent + ' links';
            h.append(n);
            const wrap = document.createElement('div');
            wrap.className = 'pl';
            wrap.append((d.querySelector('.links') || d.querySelector('.empty')).cloneNode(true));
            const open = document.createElement('button');
            open.type = 'button';
            open.className = 'zoom';
            open.textContent = 'Zoom into the room';
            open.addEventListener('click', () => openRoom(id, open));
            panel.append(k, h, wrap, open);
        }
        panel.classList.remove('swap');
        void panel.offsetWidth;
        panel.classList.add('swap');
    };
    const paintCurrent = () => {
        const id = STOPS[stopNow];
        svg.querySelectorAll('.rm.cur').forEach((g) => g.classList.remove('cur'));
        chips.forEach((c) => c.classList.toggle('cur', c.dataset.room === id));
        if (id) svg.querySelector(`.rm[data-room="${CSS.escape(id)}"]`)?.classList.add('cur');
        document.querySelectorAll('#route li').forEach((li) => li.classList.toggle('on', li.dataset.area === AREA_OF(id)));
    };

    // ---------- scroll ----------
    let ticking = false;
    function onScroll() {
        ticking = false;
        if (reduce.matches || !fitted) return;
        const max = story.offsetHeight - pin.offsetHeight;
        const p = Math.min(1, Math.max(0, (window.scrollY - story.offsetTop) / Math.max(1, max)));
        const c = camAt(p);
        if (!zoom) applyCam(c);
        const idx = c.f < HOLD + (1 - HOLD) / 2 ? c.i : c.i + 1;
        // the text fades out before the camera leaves a stop and back in when it arrives
        const travel = c.f > HOLD ? (c.f - HOLD) / (1 - HOLD) : 0;
        const o = travel < 0.5 ? 1 - Math.min(1, travel / 0.3) : Math.min(1, (travel - 0.7) / 0.3);
        setPanel(idx);
        panel.style.opacity = f1(Math.max(0, o));
        if (paintStop !== stopNow) { paintStop = stopNow; paintCurrent(); }
        document.documentElement.style.setProperty('--prog', f1(p * 100) + '%');
    }
    let paintStop = -1;
    window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });

    const resize = () => {
        W = stage.clientWidth;
        H = stage.clientHeight;
        if (!W || !H) return;
        showDims = window.innerWidth >= 640;
        story.style.setProperty('--rail', reduce.matches ? 'auto' : `calc(100dvh + ${SEG * SEG_VH}vh)`);
        view.yaw = W / H < 0.8 ? -62 : -40;
        view.pitch = W / H < 0.8 ? 40 : 38;
        svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
        fitted = Ship.fit(W, H, { rest: view, limits: { yaw: [view.yaw, view.yaw], pitch: [view.pitch, view.pitch] }, padX: 0.04, fillY: 0.86, maxScale: 9, dims: showDims });
        planPath();
        render();
        onScroll();
        if (reduce.matches) { cam.style.transform = ''; panel.parentElement.hidden = true; } else panel.parentElement.hidden = false;
    };
    new ResizeObserver(resize).observe(stage);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(resize);
    reduce.addEventListener('change', resize);

    // ---------- tap a room: zoom in, sheet or side panel (as in prototype 15) ----------
    const hl = () => {};
    const openRoom = (id, from) => open(id, from);
    const zoomTo = (id) => {
        const r = ROOMS[id];
        if (!r || reduce.matches) return;
        const [, , , dx, , dz] = r.box;
        const a = Ship.anchor(fitted, view, id);
        // measured against the visible stage; the sheet covers the lower part on portrait screens
        const rect = stage.getBoundingClientRect();
        let visW = W;
        let visH = H;
        const oy = rect.top;
        if (sideMode.matches) visW = Math.min(W, window.innerWidth - rect.left - Math.min(420, window.innerWidth * 0.5));
        else visH = Math.max(120, window.innerHeight * 0.38 - oy);
        const k = Math.max(1.6, Math.min(window.innerWidth >= 640 ? 2.2 : 2.6, (visW * 0.5) / (dx * fitted.S * 0.8), (visH * 0.5) / (dz * fitted.S * 1.4)));
        zoom = { k, tx: visW / 2 - a[0] * k, ty: visH / 2 - a[1] * k, id };
        stage.classList.add('zoomed', 'free');
        applyCam(zoom);
    };
    const zoomOut = () => {
        zoom = null;
        stage.classList.remove('zoomed');
        onScroll();
        setTimeout(() => { if (!zoom) stage.classList.remove('free'); }, 700);
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
    onScroll();
    const start = location.hash.replace(/^#(list-)?/, '');
    if (start && $('list-' + start)) {
        history.replaceState(null, '', location.pathname + location.search);
        open(start);
    }
})();
