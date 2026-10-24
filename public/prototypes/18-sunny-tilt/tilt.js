(() => {
    const $ = (id) => document.getElementById(id);
    const wrap = $('sheetwrap');
    const stage = $('stage');
    const charts = [...wrap.querySelectorAll('.chart')];
    const sheet = $('sheet');
    const body = $('sheet-body');
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    const land = window.matchMedia('(min-aspect-ratio: 1/1)'); // side panel instead of bottom sheet
    let openId = null;
    let busy = false; // the tilt is under way, the sheet is not open yet
    let opener = null;

    const q = (id) => CSS.escape(id);
    const linked = (id) => document.querySelectorAll(
        `.zone[data-room="${q(id)}"], .callout[data-room="${q(id)}"], .themes a[data-room="${q(id)}"]`,
    );
    const mark = (id, cls, on) => linked(id).forEach((el) => el.classList.toggle(cls, on));

    // ---------- hover and focus on a room or its label light up both ----------
    document.addEventListener('pointerover', (e) => {
        if (e.pointerType !== 'mouse') return;
        const el = e.target.closest('.stage [data-room], .themes [data-room]');
        if (el) mark(el.dataset.room, 'hl', true);
    });
    document.addEventListener('pointerout', (e) => {
        if (e.pointerType !== 'mouse') return;
        const el = e.target.closest('.stage [data-room], .themes [data-room]');
        if (el) mark(el.dataset.room, 'hl', false);
    });
    document.addEventListener('focusin', (e) => {
        const el = e.target.closest('.stage [data-room], .themes [data-room]');
        if (el) mark(el.dataset.room, 'hl', true);
    });
    document.addEventListener('focusout', (e) => {
        const el = e.target.closest('.stage [data-room], .themes [data-room]');
        if (el) mark(el.dataset.room, 'hl', false);
    });

    // ---------- tilt the ship, then fly into a room ----------
    const shown = () => charts.find((c) => c.getClientRects().length > 0);
    // Where the room sits on screen once the ship is tilted: measured with the tilt applied and no transition.
    const tiltedRect = (svg, id) => {
        const scene = svg.querySelector('.scene');
        const zone = svg.querySelector(`.zone[data-room="${q(id)}"] .edge`);
        const was = stage.classList.contains('tilted');
        const cam = svg.style.transform;
        svg.style.transition = 'none';
        scene.style.transition = 'none';
        svg.style.transform = '';
        stage.classList.add('tilted');
        svg.querySelectorAll('.layer').forEach((l) => { l.style.transition = 'none'; });
        const r = zone.getBoundingClientRect();
        const w = wrap.getBoundingClientRect();
        if (!was) stage.classList.remove('tilted');
        void svg.offsetWidth;
        svg.style.transform = cam;
        svg.style.transition = '';
        scene.style.transition = '';
        svg.querySelectorAll('.layer').forEach((l) => { l.style.transition = ''; });
        return { x: r.left - w.left + r.width / 2, y: r.top - w.top + r.height / 2, w: r.width, h: r.height };
    };
    const flyTo = (id) => {
        const svg = shown();
        charts.forEach((c) => { if (c !== svg) c.style.transform = ''; });
        if (!svg || reduce.matches) return;
        const W = wrap.clientWidth;
        const H = wrap.clientHeight;
        const r = tiltedRect(svg, id);
        const visW = land.matches ? Math.max(240, W - Math.min(440, window.innerWidth * 0.42)) : W; // the side panel covers the right part
        const visH = land.matches ? H : H * 0.9;
        const k = Math.max(1.3, Math.min(2.2, (visW * 0.36) / r.w, (visH * 0.4) / r.h));
        svg.style.transform = `translate(${visW / 2 - k * r.x}px, ${visH / 2 - k * r.y}px) scale(${k})`;
    };
    const flyOut = () => charts.forEach((c) => { c.style.transform = ''; });

    // ---------- sheet / side panel ----------
    const fill = (id) => {
        const d = $('list-' + id);
        $('sheet-k').textContent = 'Detail ' + d.dataset.no + ' · ' + d.dataset.sub;
        $('sheet-h').textContent = d.dataset.topic;
        body.replaceChildren();
        const desc = document.createElement('p');
        desc.className = 'desc';
        desc.textContent = d.dataset.desc;
        body.append(desc);
        const list = d.querySelector('.links') || d.querySelector('.empty');
        const clone = list.cloneNode(true);
        clone.querySelectorAll('li').forEach((li, i) => li.style.setProperty('--n', i));
        body.append(clone);
        body.scrollTop = 0;
    };

    let timer = 0;
    const show = (id, from) => {
        if (!$('list-' + id)) return;
        if (openId) { // switch rooms without stacking history entries; the ship stays tilted
            mark(openId, 'on', false);
            openId = id;
            fill(id);
            mark(id, 'on', true);
            flyTo(id);
            return;
        }
        openId = id;
        opener = from || null;
        mark(id, 'on', true);
        sheet.style.transform = '';
        const reveal = () => {
            fill(id);
            sheet.showModal();
            requestAnimationFrame(() => requestAnimationFrame(() => sheet.classList.add('in')));
            $('sheet-h').focus({ preventScroll: true });
        };
        if (reduce.matches) { // no motion: the ship stays flat and the panel opens at once
            stage.classList.add('zoomed');
            reveal();
            return;
        }
        // 1. tilt into the isometric view, 2. fly into the room, 3. open the sheet
        stage.classList.add('tilted');
        flyTo(id);
        busy = true;
        timer = setTimeout(() => { busy = false; if (openId === id) reveal(); }, 760);
    };

    const hide = () => {
        if (!openId) return;
        const id = openId;
        openId = null;
        clearTimeout(timer);
        busy = false;
        const wasShown = sheet.open;
        sheet.classList.remove('in');
        sheet.style.transform = '';
        stage.classList.remove('zoomed');
        flyOut();
        stage.classList.remove('tilted'); // tilt back to the flat elevation
        let done = false;
        const finish = () => {
            if (done) return;
            done = true;
            if (sheet.open) sheet.close();
            mark(id, 'on', false);
            if (opener && opener.getClientRects().length) opener.focus({ preventScroll: true });
        };
        if (reduce.matches || !wasShown) finish();
        else {
            sheet.addEventListener('transitionend', finish, { once: true });
            setTimeout(finish, 450);
        }
    };

    // The history entry is the single owner of the open state.
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

    // A tap on a room, a callout or a topic opens it; the focus returns to a control that is visible.
    const controlFor = (el) => {
        if (!el.matches('.zone')) return el;
        const id = el.dataset.room;
        return [...document.querySelectorAll(`.callout[data-room="${q(id)}"], .themes a[data-room="${q(id)}"]`)]
            .find((c) => c.getClientRects().length > 0) || el;
    };
    document.addEventListener('click', (e) => {
        const el = e.target.closest('.stage [data-room], .themes [data-room]');
        if (!el) return;
        e.preventDefault();
        if (busy) return;
        open(el.dataset.room, controlFor(el));
    });
    $('close').addEventListener('click', requestClose);
    sheet.addEventListener('cancel', (e) => { e.preventDefault(); requestClose(); });
    sheet.addEventListener('click', (e) => { if (e.target === sheet) requestClose(); }); // backdrop

    // Parts list: the same room opens as a sheet when JavaScript is on.
    document.querySelectorAll('.all summary').forEach((s) => s.addEventListener('click', (e) => {
        e.preventDefault();
        open(s.parentElement.dataset.room, s);
    }));

    // Turning the device: the sheet becomes a side panel (or back) and the zoom is recomputed.
    let rz = 0;
    window.addEventListener('resize', () => {
        clearTimeout(rz);
        rz = setTimeout(() => { if (openId) flyTo(openId); }, 120);
    });

    // ---------- swipe the handle area away: down for the sheet, right for the side panel ----------
    const grab = $('grab');
    let drag = null;
    grab.addEventListener('pointerdown', (e) => {
        if (e.target.closest('button')) return;
        drag = { p: land.matches ? e.clientX : e.clientY, t: e.timeStamp, d: 0, v: 0 };
        grab.setPointerCapture(e.pointerId);
        sheet.classList.add('drag');
    });
    grab.addEventListener('pointermove', (e) => {
        if (!drag) return;
        const d = Math.max(0, (land.matches ? e.clientX : e.clientY) - drag.p);
        drag.v = (d - drag.d) / Math.max(1, e.timeStamp - drag.t);
        drag.d = d;
        drag.t = e.timeStamp;
        sheet.style.transform = land.matches ? `translateX(${d}px)` : `translateY(${d}px)`;
    });
    const release = () => {
        if (!drag) return;
        const { d, v } = drag;
        drag = null;
        sheet.classList.remove('drag');
        if (d > 90 || v > 0.6) requestClose();
        else sheet.style.transform = '';
    };
    grab.addEventListener('pointerup', release);
    grab.addEventListener('pointercancel', release);

    // Deep link: open the room once, with a clean history underneath.
    const start = location.hash.slice(1);
    if (start && $('list-' + start)) {
        history.replaceState(null, '', location.pathname + location.search);
        open(start);
    }
})();
