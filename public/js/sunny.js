// Enhancement for the Sunny chart: a tap on a callout or room zooms into the room and opens its
// links in a bottom sheet (phone) or side panel (landscape). Without this script every callout is
// a plain link to the room's section further down the page.
(() => {
    const $ = (id) => document.getElementById(id);
    const stage = $('stage');
    const charts = [...stage.querySelectorAll('.chart')];
    const sheet = $('sheet');
    const body = $('sheet-body');
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    const land = window.matchMedia('(min-aspect-ratio: 1/1)'); // side panel instead of bottom sheet
    const triggers = '.stage [data-room], .topics [data-room]';
    let openId = null;
    let opener = null;
    let view = null; // the current zoom: where the chart sits for the open room, plus the finger pan on top
    const pan = { x: 0, y: 0 };

    const q = (id) => CSS.escape(id);
    const linked = (id) => document.querySelectorAll(
        `.zone[data-room="${q(id)}"], .callout[data-room="${q(id)}"], .topics a[data-room="${q(id)}"]`,
    );
    const mark = (id, cls, on) => linked(id).forEach((el) => el.classList.toggle(cls, on));

    // Callouts open a dialog, so say that they are buttons.
    document.querySelectorAll('.callout, .topics a').forEach((el) => {
        el.setAttribute('role', 'button');
        el.setAttribute('aria-haspopup', 'dialog');
        el.addEventListener('keydown', (e) => {
            if (e.key === ' ') { e.preventDefault(); el.click(); }
        });
    });

    // Hover and keyboard focus on a room or its callout light up both. Focus after a tap or click
    // (for example when the sheet closes and focus goes back to the callout) does not.
    let keyboard = false;
    document.addEventListener('keydown', () => { keyboard = true; }, true);
    document.addEventListener('pointerdown', () => { keyboard = false; }, true);
    const hl = (on) => (e) => {
        if (e.type.startsWith('pointer') && e.pointerType !== 'mouse') return;
        if (on && e.type === 'focusin' && !keyboard) return;
        const el = e.target.closest(triggers);
        if (el) mark(el.dataset.room, 'hl', on);
    };
    document.addEventListener('pointerover', hl(true));
    document.addEventListener('pointerout', hl(false));
    document.addEventListener('focusin', hl(true));
    document.addEventListener('focusout', hl(false));

    // Zoom into a room: the visible part of the stage is what the sheet or panel leaves free.
    const shown = () => charts.find((c) => c.getClientRects().length > 0);
    const applyView = () => {
        if (!view) return;
        // Keep at least a strip of the chart in the free area so the ship cannot leave the screen.
        const lim = (v, vis, size) => Math.min(vis * 0.15, Math.max(vis * 0.85 - size, v));
        const tx = lim(view.tx + pan.x, view.visW, view.s * view.W) - view.tx;
        const ty = lim(view.ty + pan.y, view.visH, view.s * view.H) - view.ty;
        pan.x = tx;
        pan.y = ty;
        view.svg.style.transform = `translate(${view.tx + tx}px, ${view.ty + ty}px) scale(${view.s})`;
    };
    const zoomTo = (id, keepPan) => {
        view = null;
        if (!keepPan) pan.x = pan.y = 0;
        const svg = shown();
        charts.forEach((c) => { if (c !== svg) c.style.transform = ''; });
        if (!svg || reduce.matches) return;
        const edge = svg.querySelector(`.zone[data-room="${q(id)}"] .edge`);
        if (!edge) return;
        const bb = edge.getBBox();
        const vb = svg.viewBox.baseVal;
        const W = stage.clientWidth;
        const H = stage.clientHeight;
        const k = Math.min(W / vb.width, H / vb.height);
        const cx = (W - vb.width * k) / 2 + (bb.x + bb.width / 2 - vb.x) * k;
        const cy = (H - vb.height * k) / 2 + (bb.y + bb.height / 2 - vb.y) * k;
        const top = stage.getBoundingClientRect().top;
        const visW = land.matches ? Math.max(240, W - sheet.offsetWidth) : W;
        const visH = land.matches ? H : Math.max(120, Math.min(H, window.innerHeight - sheet.offsetHeight - top));
        const s = Math.max(1.5, Math.min(2.1, (visW * 0.42) / (bb.width * k), (visH * 0.5) / (bb.height * k)));
        view = { svg, s, W, H, visW, visH, tx: visW / 2 - s * cx, ty: visH / 2 - s * cy };
        applyView();
    };
    const zoomOut = () => {
        view = null;
        charts.forEach((c) => { c.style.transform = ''; });
    };

    // Fill the sheet from the room's section (the no-JavaScript copy of the same list).
    const fill = (id) => {
        const section = $(id);
        $('sheet-kicker').textContent = `${section.dataset.number} · ${section.dataset.sub} · ${section.dataset.count}`;
        $('sheet-title').textContent = section.dataset.topic;
        body.replaceChildren(...[...section.querySelector('.room-section-inner').children].map((n) => n.cloneNode(true)));
        body.querySelectorAll('.link-tile').forEach((li, i) => li.style.setProperty('--i', Math.min(i, 10))); // staggered entrance
        body.scrollTop = 0;
    };

    const show = (id, from) => {
        if (!$(id)) return;
        if (openId) { // switch rooms without stacking history entries
            if (from) opener = from;
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
        requestAnimationFrame(() => requestAnimationFrame(() => sheet.classList.add('in')));
        zoomTo(id);
        $('sheet-title').focus({ preventScroll: true });
    };

    const hide = () => {
        if (!openId) return;
        const id = openId;
        openId = null;
        sheet.classList.remove('in');
        stage.classList.remove('zoomed');
        zoomOut();
        let done = false;
        const finish = () => {
            if (done) return;
            done = true;
            sheet.close();
            sheet.style.transform = '';
            mark(id, 'on', false);
            mark(id, 'hl', false);
            // Keyboard users get focus back on the callout; after a tap, click or pull nothing
            // stays focused (the dialog would restore focus and show a focus ring).
            if (!keyboard) document.activeElement?.blur();
            else if (opener && opener.getClientRects().length) opener.focus({ preventScroll: true });
        };
        if (reduce.matches) finish();
        else {
            sheet.addEventListener('transitionend', finish, { once: true });
            setTimeout(finish, 450);
        }
    };

    // The history entry is the single owner of the open state: Back closes the sheet.
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

    // Focus goes back to a control that is visible (the room zones are not focusable).
    const controlFor = (el) => {
        if (!el.matches('.zone')) return el;
        return [...document.querySelectorAll(`.callout[data-room="${q(el.dataset.room)}"], .topics a[data-room="${q(el.dataset.room)}"]`)]
            .find((c) => c.getClientRects().length > 0) || el;
    };
    document.addEventListener('click', (e) => {
        const el = e.target.closest(triggers);
        if (!el) return;
        e.preventDefault();
        open(el.dataset.room, controlFor(el));
    });
    // Keep Tab inside the open sheet: the last control wraps to the first and the other way round.
    sheet.addEventListener('keydown', (e) => {
        if (e.key !== 'Tab') return;
        const items = [...sheet.querySelectorAll('a[href], button')].filter((el) => el.getClientRects().length > 0);
        if (!items.length) return;
        const first = items[0];
        const last = items[items.length - 1];
        const at = document.activeElement;
        if (e.shiftKey && (at === first || at === $('sheet-title'))) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && at === last) { e.preventDefault(); first.focus(); }
    });
    $('sheet-close').addEventListener('click', requestClose);
    sheet.addEventListener('cancel', (e) => { e.preventDefault(); requestClose(); });

    // The backdrop is the free part of the chart above the sheet. A drag there pans the chart
    // (single finger or mouse); a tap (under 8 px of movement) opens the room under the finger,
    // or closes the sheet when there is none. The page below is inert and gets no events, so the room
    // is looked up by geometry.
    const roomAt = (x, y) => {
        const hit = (el) => {
            const r = el.getBoundingClientRect();
            return x >= r.left && x <= r.right && y >= r.top && y <= r.bottom;
        };
        const callout = [...stage.querySelectorAll('.callout[data-room]')].find((el) => el.getClientRects().length && hit(el));
        if (callout) return callout;
        return [...stage.querySelectorAll('.zone[data-room]')].find((el) => {
            const svg = el.ownerSVGElement;
            const edge = el.querySelector('.edge');
            if (!svg || !edge || !svg.getClientRects().length || !hit(el)) return false;
            const m = edge.getScreenCTM();
            return !!m && edge.isPointInFill(new DOMPoint(x, y).matrixTransform(m.inverse()));
        });
    };
    const onBackdrop = (e) => {
        if (e.target !== sheet) return false;
        const r = sheet.getBoundingClientRect();
        return e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom;
    };
    let grab = null;
    sheet.addEventListener('pointerdown', (e) => {
        if (!openId || e.button > 0 || !e.isPrimary || !onBackdrop(e)) return;
        grab = { x: e.clientX, y: e.clientY, px: pan.x, py: pan.y, moved: false };
        sheet.setPointerCapture(e.pointerId);
    });
    sheet.addEventListener('pointermove', (e) => {
        if (!grab) return;
        const dx = e.clientX - grab.x;
        const dy = e.clientY - grab.y;
        if (!grab.moved && Math.hypot(dx, dy) < 8) return;
        if (!view) return; // nothing zoomed (reduced motion): there is nothing to pan
        if (!grab.moved) view.svg.style.transition = 'none';
        grab.moved = true;
        pan.x = grab.px + dx;
        pan.y = grab.py + dy;
        applyView();
    });
    sheet.addEventListener('pointerup', (e) => {
        if (!grab) return;
        const g = grab;
        grab = null;
        if (view) view.svg.style.transition = '';
        if (g.moved || Math.hypot(e.clientX - g.x, e.clientY - g.y) >= 8) return;
        const el = roomAt(e.clientX, e.clientY);
        if (!el) requestClose();
        else if (el.dataset.room !== openId) { // switch rooms: replace the entry, so Back still closes
            history.replaceState({ room: el.dataset.room }, '', '#' + el.dataset.room);
            show(el.dataset.room, controlFor(el));
        } // a tap on the open room does nothing
    });
    sheet.addEventListener('pointercancel', () => {
        grab = null;
        if (view) view.svg.style.transition = '';
    });

    // Pull the bottom sheet down by its head to close it; a short pull snaps back.
    const head = sheet.querySelector('.sheet-head');
    let drag = null;
    head.addEventListener('pointerdown', (e) => {
        if (land.matches || !openId || e.button > 0 || e.target.closest('button')) return;
        drag = { y: e.clientY, t: performance.now(), dy: 0 };
        head.setPointerCapture(e.pointerId);
        sheet.style.transition = 'none';
    });
    head.addEventListener('pointermove', (e) => {
        if (!drag) return;
        drag.dy = Math.max(0, e.clientY - drag.y);
        sheet.style.transform = `translateY(${drag.dy}px)`;
    });
    const endDrag = () => {
        if (!drag) return;
        const fast = drag.dy / Math.max(1, performance.now() - drag.t) > 0.5;
        const away = drag.dy > sheet.offsetHeight / 4 || (fast && drag.dy > 30);
        drag = null;
        sheet.style.transition = '';
        sheet.style.transform = away ? 'translateY(100%)' : '';
        if (away) requestClose();
    };
    head.addEventListener('pointerup', endDrag);
    head.addEventListener('pointercancel', endDrag);

    // Turning the device: sheet and side panel swap, the zoom is recomputed.
    let rz = 0;
    window.addEventListener('resize', () => {
        clearTimeout(rz);
        rz = setTimeout(() => { if (openId) zoomTo(openId, true); }, 120);
    });

    // Deep link (optcg.ch/#library): open the room once, with a clean history underneath.
    const start = location.hash.slice(1);
    if (start && $(start) && $(start).matches('.room-section')) {
        history.replaceState(null, '', location.pathname + location.search);
        open(start);
    }
})();
