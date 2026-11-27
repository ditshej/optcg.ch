(() => {
    const $ = (id) => document.getElementById(id);
    const stage = $('stage');
    const scroller = $('scroller');
    const pan = $('pan');
    const chart = $('chart');
    const sheet = $('sheet');
    const body = $('sheet-body');
    const tiles = [...document.querySelectorAll('.tile')];
    const stations = [...document.querySelectorAll('.st')];
    const view = $('view');
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    const sideMode = window.matchMedia('(min-width: 700px) and (orientation: landscape)');
    const VB = { w: 1200, h: 600 };
    const ORDER = ['bow', 'mid', 'stern'];
    let openId = null;
    let opener = null;

    // ---------- phone panorama: three snap stations, overview strip, tiles of the current station ----------
    const scrollable = () => scroller.scrollWidth > scroller.clientWidth + 4;
    const stationIndex = () => {
        const max = scroller.scrollWidth - scroller.clientWidth;
        return max > 0 ? Math.round((scroller.scrollLeft / max) * 2) : 1;
    };
    let ticking = false;
    const sync = () => {
        ticking = false;
        const pano = scrollable();
        const cur = ORDER[stationIndex()];
        tiles.forEach((t) => { t.hidden = pano && t.dataset.station !== cur; });
        stations.forEach((s) => s.setAttribute('aria-current', pano && s.dataset.station === cur ? 'true' : 'false'));
        if (view && pano) {
            const w = scroller.clientWidth / scroller.scrollWidth;
            const x = scroller.scrollLeft / scroller.scrollWidth;
            view.style.width = w * 100 + '%';
            view.style.transform = `translateX(${(x / w) * 100}%)`;
            view.style.left = '.6rem';
        }
    };
    const queue = () => { if (!ticking) { ticking = true; requestAnimationFrame(sync); } };
    scroller.addEventListener('scroll', queue, { passive: true });
    window.addEventListener('resize', queue);
    stations.forEach((s) => s.addEventListener('click', () => {
        const i = ORDER.indexOf(s.dataset.station);
        const max = scroller.scrollWidth - scroller.clientWidth;
        scroller.scrollTo({ left: (max * i) / 2, behavior: reduce.matches ? 'auto' : 'smooth' });
    }));
    // Start the panorama at the bow; the tiles then show the bow topics.
    scroller.scrollLeft = 0;
    sync();

    // ---------- camera: zoom the whole ship so that one room fills the free area ----------
    const zoomTo = (id) => {
        const room = chart.querySelector(`.room[data-room="${CSS.escape(id)}"]`);
        if (!room) return;
        if (reduce.matches) return;
        const rx = +room.dataset.x;
        const ry = +room.dataset.y;
        const rw = +room.dataset.w;
        const rh = +room.dataset.h;
        const W = scroller.clientWidth;
        const H = scroller.clientHeight;
        const bw = chart.clientWidth;
        const bh = chart.clientHeight;
        const s = Math.min(bw / VB.w, bh / VB.h); // rest scale of the drawing
        const ox = (bw - VB.w * s) / 2;
        const oy = (bh - VB.h * s) / 2;
        const top = stage.getBoundingClientRect().top;
        let visW = W;
        let visH = H;
        if (sideMode.matches) visW = Math.min(W, window.innerWidth - Math.min(420, window.innerWidth * 0.5));
        else visH = Math.max(120, window.innerHeight * 0.38 - top);
        const k = Math.max(1.3, Math.min(window.innerWidth >= 640 ? 2.2 : 3.2, (0.8 * visW) / (rw * s), (0.62 * visH) / (rh * s)));
        const u = ox + (rx + rw / 2 - 0) * s; // room centre in pan coordinates (viewBox x starts at 0)
        const v = oy + (ry + rh / 2 - 20) * s; // viewBox y starts at 20
        pan.style.transform = `translate(${visW / 2 + scroller.scrollLeft - u * k}px, ${visH / 2 - v * k}px) scale(${k})`;
    };
    const zoomOut = () => { pan.style.transform = ''; };

    // ---------- sheet ----------
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
        const r = chart.querySelector(`.room[data-room="${CSS.escape(id)}"]`);
        if (r) r.classList.toggle('on', on);
        tiles.forEach((t) => t.classList.toggle('on', on && t.dataset.room === id));
    };

    const show = (id, from) => {
        if (!$('list-' + id)) return;
        if (openId) { // switch rooms without stacking history entries
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
        zoomOut();
        let done = false;
        const finish = () => {
            if (done) return;
            done = true;
            sheet.close();
            stage.classList.remove('zoomed');
            mark(id, false);
            if (opener && document.contains(opener)) opener.focus({ preventScroll: true });
        };
        if (reduce.matches) finish();
        else {
            sheet.addEventListener('transitionend', finish, { once: true });
            setTimeout(finish, 500);
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

    // Rooms in the drawing and tiles open the sheet; without JavaScript they jump to the list.
    const bind = (el) => el.addEventListener('click', (e) => {
        e.preventDefault();
        open(el.dataset.room, el);
    });
    chart.querySelectorAll('.room').forEach(bind);
    tiles.forEach(bind);
    $('close').addEventListener('click', requestClose);
    sheet.addEventListener('cancel', (e) => { e.preventDefault(); requestClose(); });
    sheet.addEventListener('click', (e) => { if (e.target === sheet) requestClose(); }); // backdrop

    // ---------- swipe down on the handle area closes the sheet (bottom sheet only) ----------
    const grab = $('grab');
    let drag = null;
    grab.addEventListener('pointerdown', (e) => {
        if (sideMode.matches || e.target.closest('button')) return;
        drag = { y: e.clientY, t: e.timeStamp, dy: 0, v: 0 };
        grab.setPointerCapture(e.pointerId);
        sheet.classList.add('drag');
    });
    grab.addEventListener('pointermove', (e) => {
        if (!drag) return;
        const dy = Math.max(0, e.clientY - drag.y);
        drag.v = (dy - drag.dy) / Math.max(1, e.timeStamp - drag.t);
        drag.dy = dy;
        drag.t = e.timeStamp;
        sheet.style.transform = `translateY(${dy}px)`;
    });
    const release = () => {
        if (!drag) return;
        const { dy, v } = drag;
        drag = null;
        sheet.classList.remove('drag');
        if (dy > 90 || v > 0.6) requestClose();
        else sheet.style.transform = '';
    };
    grab.addEventListener('pointerup', release);
    grab.addEventListener('pointercancel', release);

    // Deep link: open the room once, with a clean history underneath.
    const start = location.hash.replace(/^#(list-)?/, '');
    if (start && $('list-' + start)) {
        history.replaceState(null, '', location.pathname + location.search);
        open(start);
    }
})();
