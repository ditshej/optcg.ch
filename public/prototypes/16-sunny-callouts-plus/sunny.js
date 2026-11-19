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

    // ---------- zoom into a room ----------
    const shown = () => charts.find((c) => c.getClientRects().length > 0);
    const zoomTo = (id) => {
        const svg = shown();
        charts.forEach((c) => { if (c !== svg) c.style.transform = ''; });
        if (!svg || reduce.matches) return;
        const edge = svg.querySelector(`.zone[data-room="${q(id)}"] .edge`);
        if (!edge) return;
        const bb = edge.getBBox();
        const vb = svg.viewBox.baseVal;
        const W = wrap.clientWidth;
        const H = wrap.clientHeight;
        const k = Math.min(W / vb.width, H / vb.height);
        const ox = (W - vb.width * k) / 2 - vb.x * k;
        const oy = (H - vb.height * k) / 2 - vb.y * k;
        const cx = ox + (bb.x + bb.width / 2) * k;
        const cy = oy + (bb.y + bb.height / 2) * k;
        const visW = land.matches ? Math.max(240, W - sheet.offsetWidth) : W; // the side panel covers the right part
        const s = Math.max(1.5, Math.min(2.1, (visW * 0.42) / (bb.width * k), (H * 0.45) / (bb.height * k)));
        svg.style.transform = `translate(${visW / 2 - s * cx}px, ${H / 2 - s * cy}px) scale(${s})`;
    };
    const zoomOut = () => charts.forEach((c) => { c.style.transform = ''; });

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

    const show = (id, from) => {
        if (!$('list-' + id)) return;
        if (openId) { // switch rooms without stacking history entries
            mark(openId, 'on', false);
            openId = id;
            fill(id);
            mark(id, 'on', true);
            zoomTo(id);
            return;
        }
        openId = id;
        opener = from || null;
        fill(id);
        mark(id, 'on', true);
        stage.classList.add('zoomed');
        sheet.showModal();
        sheet.style.transform = '';
        requestAnimationFrame(() => requestAnimationFrame(() => sheet.classList.add('in')));
        zoomTo(id);
        $('sheet-h').focus({ preventScroll: true });
    };

    const hide = () => {
        if (!openId) return;
        const id = openId;
        openId = null;
        sheet.classList.remove('in');
        sheet.style.transform = '';
        stage.classList.remove('zoomed');
        zoomOut();
        let done = false;
        const finish = () => {
            if (done) return;
            done = true;
            sheet.close();
            mark(id, 'on', false);
            if (opener && opener.getClientRects().length) opener.focus({ preventScroll: true });
        };
        if (reduce.matches) finish();
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
        rz = setTimeout(() => { if (openId) zoomTo(openId); }, 120);
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
