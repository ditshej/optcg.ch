(() => {
    const $ = (id) => document.getElementById(id);
    const stage = $('stage');
    const cam = $('cam');
    const svg = $('scene');
    const sheet = $('sheet');
    const body = $('sheet-body');
    const chipsEl = $('chips');
    const chips = [...chipsEl.querySelectorAll('.chip')];
    const tiles = [...document.querySelectorAll('.tile')];
    const compactQuery = window.matchMedia('(max-width: 639px), (max-height: 520px)');
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    const sideMode = window.matchMedia('(min-width: 700px) and (orientation: landscape)');
    const D = Math.PI / 180;

    // ---------- the ship, in model units: x bow to stern (0..100), y beam (0 far side .. 30 near side), z up ----------
    const DECK = 32;
    const HULL = [[0, 2], [6, 6], [14, 10], [30, 13.5], [70, 13.5], [88, 12.5], [100, 11]]; // x, half beam at deck level
    const KEEL = 0.45; // half beam at the keel, relative to the deck
    const hw = (i, z) => HULL[i][1] * (KEEL + (1 - KEEL) * (z / DECK));
    const MID = 15;

    // Where each room lies: box [x, y, z, dx, dy, dz], anchor for the label, chip offset in px at the reference scale.
    const ROOMS = {
        figurehead: { box: [-4, 11, 32, 8, 8, 10], chip: [-6, 78] },
        helm: { box: [17, 9, 40, 11, 12, 8], chip: [-96, -38] },
        'crows-nest': { box: [48, 11, 64, 8, 8, 6], chip: [-104, -4] },
        'aquarium-bar': { box: [34, 6, 16, 32, 18, 11], chip: [-96, 18] },
        'soldier-dock': { box: [34, 8, 2, 32, 14, 9], chip: [-88, 66] },
        galley: { box: [74, 5, 40, 13, 20, 8], chip: [-70, 44] },
        'sick-bay': { box: [87, 6, 40, 12, 18, 8], chip: [60, 56] },
        library: { box: [76, 6, 48, 15, 18, 8], chip: [-50, -34] },
        'navigation-room': { box: [91, 8, 48, 9, 14, 8], chip: [58, -34] },
        'usopp-factory': { box: [72, 6, 16, 20, 18, 11], chip: [28, 104] },
    };
    const BLOCKS = [ // ship parts that are not rooms: cabins, dining room, engine
        [14, 7, 32, 16, 16, 8, 'cabin'],
        [74, 4, 32, 26, 22, 8, 'cabin'],
        [74, 5, 16, 0.001, 0.001, 0.001, 'none'],
    ];

    // ---------- camera ----------
    const view = { yaw: -40, pitch: 38 };
    const REST = { yaw: -40, pitch: 38 };
    const LIMIT = { yaw: [-75, -8], pitch: [28, 56] };
    let W = 0;
    let H = 0;
    let S = 1;
    let C = [0, 0];
    let cx = 0;
    let cy = 0;
    const proj = (x, y, z, v = view) => {
        const a = v.yaw * D;
        const p = v.pitch * D;
        const rx = x * Math.cos(a) - y * Math.sin(a);
        const ry = x * Math.sin(a) + y * Math.cos(a);
        return [cx + S * (rx - C[0]), cy + S * (ry * Math.sin(p) - z * Math.cos(p) - C[1])];
    };
    const depth = (x, y, z) => {
        const a = view.yaw * D;
        const p = view.pitch * D;
        return (x * Math.sin(a) + y * Math.cos(a)) * Math.sin(p) + z * Math.cos(p);
    };

    const KEYPOINTS = [[-8, 15, 32], [-8, 15, 44], [100, 15, 56], [52, 15, 70], [0, 15, 0], [100, 4, 56], [100, 26, 0], [30, 30, 0], [80, 33, 12], [100, 26, 56]];
    const fit = () => {
        // Scale for the whole turning range so the ship never leaves the stage; centre at the rest view.
        let minx = 1e9, maxx = -1e9, miny = 1e9, maxy = -1e9;
        const pts = [];
        for (let i = 0; i < HULL.length; i++) pts.push([HULL[i][0], MID - HULL[i][1], DECK], [HULL[i][0], MID + HULL[i][1], DECK], [HULL[i][0], MID + HULL[i][1] * KEEL, 0]);
        pts.push(...KEYPOINTS);
        for (const v of [REST, { yaw: LIMIT.yaw[0], pitch: REST.pitch }, { yaw: LIMIT.yaw[1], pitch: REST.pitch }, { yaw: REST.yaw, pitch: LIMIT.pitch[0] }, { yaw: REST.yaw, pitch: LIMIT.pitch[1] }]) {
            const a = v.yaw * D;
            const p = v.pitch * D;
            for (const [x, y, z] of pts) {
                const rx = x * Math.cos(a) - y * Math.sin(a);
                const sy = (x * Math.sin(a) + y * Math.cos(a)) * Math.sin(p) - z * Math.cos(p);
                if (v === REST) { minx = Math.min(minx, rx); maxx = Math.max(maxx, rx); miny = Math.min(miny, sy); maxy = Math.max(maxy, sy); }
            }
        }
        const padX = W < 520 ? 0.06 : 0.1;
        const bw = maxx - minx;
        const bh = maxy - miny;
        S = Math.min((W * (1 - 2 * padX)) / bw, (H * 0.74) / bh);
        S = Math.min(S, 9);
        C = [(minx + maxx) / 2, (miny + maxy) / 2];
        cx = W / 2;
        cy = H * 0.5;
    };

    // ---------- drawing primitives ----------
    const f1 = (n) => n.toFixed(1);
    const pts2 = (list) => list.map(([x, y, z]) => proj(x, y, z).map(f1).join(',')).join(' ');
    const poly = (list, cls, extra = '') => `<polygon class="${cls}" points="${pts2(list)}"${extra}/>`;
    const line = (list, cls) => `<polyline class="${cls}" points="${pts2(list)}"/>`;

    const FACES = [ // normal, corner indices (box corners by bit: x=1, y=2, z=4)
        { n: [0, 0, 1], c: [4, 5, 7, 6], k: 'top' },
        { n: [0, 1, 0], c: [2, 3, 7, 6], k: 'near' },
        { n: [0, -1, 0], c: [0, 1, 5, 4], k: 'far' },
        { n: [1, 0, 0], c: [1, 3, 7, 5], k: 'aft' },
        { n: [-1, 0, 0], c: [0, 2, 6, 4], k: 'fore' },
    ];
    const facing = (n) => {
        const a = view.yaw * D;
        const p = view.pitch * D;
        return (n[0] * Math.sin(a) + n[1] * Math.cos(a)) * Math.sin(p) + n[2] * Math.cos(p) > 0.001;
    };
    const box = (x, y, z, dx, dy, dz, cls) => {
        const c = [0, 1, 2, 3, 4, 5, 6, 7].map((i) => [x + (i & 1 ? dx : 0), y + (i & 2 ? dy : 0), z + (i & 4 ? dz : 0)]);
        let s = '';
        for (const f of FACES) if (facing(f.n)) s += poly(f.c.map((i) => c[i]), `${cls} f-${f.k}`);
        return s;
    };
    const ring = (plane, c, r, n, rot = 0) => { // circle in a plane perpendicular to x or y
        const out = [];
        for (let i = 0; i < n; i++) {
            const t = rot + (i * 2 * Math.PI) / n;
            const u = r * Math.cos(t);
            const w = r * Math.sin(t);
            out.push(plane === 'x' ? [c[0], c[1] + u, c[2] + w] : [c[0] + u, c[1], c[2] + w]);
        }
        return out;
    };

    const hullPlan = (z) => {
        const left = HULL.map((h, i) => [h[0], MID - hw(i, z), z]);
        const right = HULL.map((h, i) => [h[0], MID + hw(i, z), z]).reverse();
        return left.concat(right);
    };

    const scene = () => {
        let s = '';
        // Drafting grid under the ship
        for (let x = -20; x <= 120; x += 10) s += line([[x, -12, -6], [x, 42, -6]], 'gridl');
        for (let y = -10; y <= 40; y += 10) s += line([[-20, y, -6], [120, y, -6]], 'gridl');

        // Far wall of the hull (inside surface), stern wall and the sectioned bow
        for (let i = 0; i < HULL.length - 1; i++) {
            s += poly([[HULL[i][0], MID - hw(i, DECK), DECK], [HULL[i + 1][0], MID - hw(i + 1, DECK), DECK], [HULL[i + 1][0], MID - hw(i + 1, 0), 0], [HULL[i][0], MID - hw(i, 0), 0]], 'wall');
        }
        const n = HULL.length - 1;
        s += poly([[100, MID - hw(n, DECK), DECK], [100, MID + hw(n, DECK), DECK], [100, MID + hw(n, 0), 0], [100, MID - hw(n, 0), 0]], 'wall back');
        // Floors: hold, tween deck. The main deck is a ghost outline so the levels stay visible.
        for (const z of [2, 16]) {
            s += poly(hullPlan(z), 'floor');
            for (let x = 10; x < 100; x += 10) {
                const i = HULL.findIndex((h) => h[0] >= x);
                const w = i <= 0 ? HULL[0][1] : HULL[i - 1][1] + ((HULL[i][1] - HULL[i - 1][1]) * (x - HULL[i - 1][0])) / (HULL[i][0] - HULL[i - 1][0]);
                const ww = w * (KEEL + (1 - KEEL) * (z / DECK));
                s += line([[x, MID - ww, z], [x, MID + ww, z]], 'plank');
            }
        }
        // Water line
        s += poly(hullPlan(12), 'water');
        // Near wall: cut away, only ribs and the cut edge remain
        const near = HULL.map((h, i) => [h[0], MID + hw(i, DECK), DECK]);
        const keelN = HULL.map((h, i) => [h[0], MID + hw(i, 0), 0]);
        s += line(near, 'cut') + line(keelN, 'cut');
        for (let i = 1; i < HULL.length; i++) s += line([near[i], keelN[i]], 'rib');
        s += line([[0, MID, DECK], [0, MID, 0]], 'rib');

        // Objects, far to near
        const items = [];
        const add = (d, svgStr, id) => items.push({ d, svgStr, id });
        for (const [x, y, z, dx, dy, dz, k] of BLOCKS) {
            if (k === 'none') continue;
            add(depth(x + dx / 2, y + dy / 2, z + dz / 2), box(x, y, z, dx, dy, dz, 'blk'));
        }
        for (const [id, r] of Object.entries(ROOMS)) {
            const [x, y, z, dx, dy, dz] = r.box;
            const g = `<g class="rm" data-room="${id}">${box(x, y, z, dx, dy, dz, 'face')}</g>`;
            add(depth(x + dx / 2, y + dy / 2, z + dz / 2) + 0.01, g, id);
        }
        // Lawn deck, tree, mast, sail
        add(depth(50, 15, 33) - 4, poly([[34, 4, 33], [66, 4, 33], [66, 26, 33], [34, 26, 33]], 'lawn'));
        const tree = ring('y', [58, 10, 41], 5, 14);
        add(depth(58, 10, 38), line([[58, 10, 33], [58, 10, 38]], 'trunk') + poly(ring('x', [58, 10, 41], 5, 14), 'canopy') + poly(tree, 'canopy'));
        add(depth(52, 15, 40) - 1, poly([[52, 5, 58], [52, 25, 58], [52, 25, 44], [52, 5, 44]], 'sail') + line([[52, 5, 58], [52, 25, 58]], 'yard') + line([[52, 15, 58], [52, 15, 44]], 'thin'));
        add(depth(52, 15, 50), line([[52, 15, 33], [52, 15, 64]], 'mast'));
        add(depth(52, 15, 65), line([[52, 15, 70], [52, 15, 64]], 'mast') + line([[52, 15, 70], [8, 15, 44]], 'rigl') + line([[52, 15, 70], [88, 15, 56]], 'rigl'));
        // Stern flag
        add(depth(98, 15, 60), line([[98, 15, 56], [98, 15, 66]], 'mast') + poly([[98, 15, 66], [98, 15, 62], [93, 15, 64]], 'sail'));
        // Lion head at the bow (faces forward)
        const m = [];
        for (let i = 0; i < 28; i++) {
            const t = (i * 2 * Math.PI) / 28;
            const r = i % 2 ? 5.2 : 7.4;
            m.push([-5, 15 + r * Math.cos(t), 38 + r * Math.sin(t)]);
        }
        add(depth(-5, 15, 38) + 0.02, poly(m, 'mane') + poly(ring('x', [-6, 15, 37.5], 3.8, 16), 'lface')
            + poly(ring('x', [-6, 11.6, 41], 1.5, 8), 'lear') + poly(ring('x', [-6, 18.4, 41], 1.5, 8), 'lear')
            + poly([[-7, 15, 37.5], [-7, 14, 36], [-7, 16, 36]], 'lnose') + line([[-7, 13.2, 38.6], [-7, 14.4, 38.6]], 'thin') + line([[-7, 15.6, 38.6], [-7, 16.8, 38.6]], 'thin'));
        s += items.sort((a, b) => a.d - b.d).map((i) => i.svgStr).join('');

        // Paddle wheel on the near side, outside the cut wall
        const wc = [80, MID + hw(4, 12) + 3, 12];
        let wheel = poly(ring('y', wc, 8, 20), 'wheel') + poly(ring('y', wc, 5.6, 20), 'wheel');
        for (let i = 0; i < 10; i++) {
            const t = (i * Math.PI) / 5;
            wheel += line([[wc[0] + 1.2 * Math.cos(t), wc[1], wc[2] + 1.2 * Math.sin(t)], [wc[0] + 9 * Math.cos(t), wc[1], wc[2] + 9 * Math.sin(t)]], 'spoke');
        }
        s += wheel;
        return s;
    };

    // ---------- render ----------
    let current = null;
    const render = () => {
        svg.innerHTML = scene();
        if (current) {
            const g = svg.querySelector(`.rm[data-room="${CSS.escape(current)}"]`);
            if (g) g.classList.add('on');
        }
        placeChips();
    };
    let zoom = null; // { k, tx, ty, id }
    const placeChips = () => {
        const f = Math.max(0.62, Math.min(1.25, S / 5.2));
        let leaders = '';
        svg.querySelector('.leaders')?.remove();
        chips.forEach((chip) => {
            const r = ROOMS[chip.dataset.room];
            const [bx, by, bz, dx, dy, dz] = r.box;
            const a = proj(bx + dx / 2, by + dy / 2, bz + dz / 2);
            const base = zoom ? [a[0] * zoom.k + zoom.tx, a[1] * zoom.k + zoom.ty] : a;
            const len = Math.hypot(r.chip[0], r.chip[1]);
            const off = compactQuery.matches ? [(r.chip[0] / len) * 20, (r.chip[1] / len) * 20] : [r.chip[0] * f, r.chip[1] * f];
            const p = zoom ? base : [a[0] + off[0], a[1] + off[1]];
            chip.style.setProperty('--x', f1(p[0]) + 'px');
            chip.style.setProperty('--y', f1(p[1]) + 'px');
            leaders += `<line class="lead" data-for="${chip.dataset.room}" x1="${f1(a[0])}" y1="${f1(a[1])}" x2="${f1(p[0])}" y2="${f1(p[1])}"/><circle class="dot" cx="${f1(a[0])}" cy="${f1(a[1])}" r="3"/>`;
        });
        svg.insertAdjacentHTML('beforeend', `<g class="leaders">${leaders}</g>`);
    };
    const applyZoom = () => {
        cam.style.transform = `translate(${zoom.tx}px, ${zoom.ty}px) scale(${zoom.k})`;
    };
    let raf = 0;
    const queue = () => { if (!raf) raf = requestAnimationFrame(() => { raf = 0; render(); }); };

    const resize = () => {
        stage.classList.toggle('compact', compactQuery.matches);
        W = stage.clientWidth;
        H = stage.clientHeight;
        svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
        fit();
        render();
    };
    new ResizeObserver(resize).observe(stage);

    // ---------- turning the ship ----------
    const clamp = (v, [lo, hi]) => Math.max(lo, Math.min(hi, v));
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
        view.yaw = clamp(drag.yaw + dx * 0.3, LIMIT.yaw);
        if (!drag.touch) view.pitch = clamp(drag.pitch - dy * 0.25, LIMIT.pitch);
        queue();
    });
    const endDrag = () => { drag = null; stage.classList.remove('turning'); };
    stage.addEventListener('pointerup', endDrag);
    stage.addEventListener('pointercancel', endDrag);
    stage.addEventListener('click', (e) => { if (moved) { e.stopPropagation(); e.preventDefault(); moved = false; } }, true);

    const glide = (to) => { // short eased turn for the buttons; a jump with reduced motion
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
    $('ctl').addEventListener('click', (e) => {
        const b = e.target.closest('button');
        if (!b || zoom) return;
        if (b.hasAttribute('data-reset')) glide({ yaw: REST.yaw, pitch: REST.pitch });
        else glide({ yaw: clamp(view.yaw + 14 * +b.dataset.turn, LIMIT.yaw), pitch: view.pitch });
    });

    // ---------- camera move into a room ----------
    const zoomTo = (id) => {
        const r = ROOMS[id];
        if (!r || reduce.matches) return;
        const [bx, by, bz, dx, dy, dz] = r.box;
        const a = proj(bx + dx / 2, by + dy / 2, bz + dz / 2);
        const top = stage.getBoundingClientRect().top;
        let visW = W;
        let visH = H;
        if (sideMode.matches) visW = Math.min(W, window.innerWidth - Math.min(420, window.innerWidth * 0.5));
        else visH = Math.max(120, window.innerHeight * 0.38 - top);
        const k = Math.max(1.6, Math.min(window.innerWidth >= 640 ? 2.2 : 2.6, (visW * 0.5) / (dx * S * 0.8), (visH * 0.5) / (dz * S * 1.4)));
        zoom = { k, tx: visW / 2 - a[0] * k, ty: visH / 2 - a[1] * k, id };
        stage.classList.add('zoomed');
        chips.forEach((c) => c.classList.toggle('keep', c.dataset.room === id));
        applyZoom();
        placeChips();
    };
    const zoomOut = () => {
        zoom = null;
        cam.style.transform = '';
        stage.classList.remove('zoomed');
        chips.forEach((c) => c.classList.remove('keep'));
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
    svg.addEventListener('pointerover', (e) => {
        const g = e.target.closest('.rm');
        stage.querySelectorAll('.chip.hot').forEach((c) => c.classList.remove('hot'));
        if (g) chips.find((c) => c.dataset.room === g.dataset.room)?.classList.add('hot');
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

    const start = location.hash.replace(/^#(list-)?/, '');
    if (start && $('list-' + start)) {
        history.replaceState(null, '', location.pathname + location.search);
        open(start);
    }
})();
