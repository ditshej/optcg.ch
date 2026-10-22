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
    const zoomTo = (id) => {
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
        svg.style.transform = `translate(${visW / 2 - s * cx}px, ${visH / 2 - s * cy}px) scale(${s})`;
    };
    const zoomOut = () => charts.forEach((c) => { c.style.transform = ''; });

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
            mark(id, 'on', false);
            mark(id, 'hl', false);
            if (opener && opener.getClientRects().length) opener.focus({ preventScroll: true });
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
    sheet.addEventListener('click', (e) => { if (e.target === sheet) requestClose(); }); // backdrop

    // Turning the device: sheet and side panel swap, the zoom is recomputed.
    let rz = 0;
    window.addEventListener('resize', () => {
        clearTimeout(rz);
        rz = setTimeout(() => { if (openId) zoomTo(openId); }, 120);
    });

    // Deep link (optcg.ch/#library): open the room once, with a clean history underneath.
    const start = location.hash.slice(1);
    if (start && $(start) && $(start).matches('.room-section')) {
        history.replaceState(null, '', location.pathname + location.search);
        open(start);
    }
})();
