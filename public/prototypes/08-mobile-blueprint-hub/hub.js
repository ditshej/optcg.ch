(() => {
    const $ = (id) => document.getElementById(id);
    const stage = $('stage');
    const map = $('map');
    const chart = map.querySelector('.chart');
    const sheet = $('sheet');
    const body = $('sheet-body');
    const input = $('q');
    const status = $('status');
    const rows = [...document.querySelectorAll('#result-list li')];
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    const VB = { w: 390, h: 520 };
    const ZOOM = 1.4;
    let openId = null;
    let opener = null;

    // ---------- search: instant filter in the browser ----------
    const filter = () => {
        const terms = input.value.toLowerCase().split(/\s+/).filter(Boolean);
        const on = terms.length > 0;
        let hits = 0;
        rows.forEach((li) => {
            const match = on && terms.every((t) => li.dataset.q.includes(t));
            li.hidden = !match;
            if (match) hits++;
        });
        stage.classList.toggle('searching', on);
        map.inert = on;
        status.textContent = on ? (hits === 0 ? 'No match' : hits + (hits === 1 ? ' link' : ' links')) : '';
    };
    input.addEventListener('input', filter);
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && input.value) { input.value = ''; filter(); }
    });
    filter();

    // ---------- zoom into a compartment ----------
    const zoomTo = (id) => {
        const cell = chart.querySelector(`.cell[data-room="${CSS.escape(id)}"] .fill`);
        if (!cell || reduce.matches) return;
        const W = map.clientWidth;
        const H = map.clientHeight;
        const k = Math.min(W / VB.w, H / VB.h);
        const ox = (W - VB.w * k) / 2;
        const oy = (H - VB.h * k) / 2;
        const cx = ox + (+cell.getAttribute('x') + +cell.getAttribute('width') / 2) * k;
        const cy = oy + (+cell.getAttribute('y') + +cell.getAttribute('height') / 2) * k;
        const top = map.getBoundingClientRect().top;
        const visible = window.innerHeight * 0.38 - top; // map area not covered by the sheet
        const tx = W / 2 - ZOOM * cx;
        const ty = visible / 2 - ZOOM * cy;
        chart.style.transform = `translate(${tx}px, ${ty}px) scale(${ZOOM})`;
    };
    const zoomOut = () => { chart.style.transform = ''; };

    // ---------- bottom sheet ----------
    const fill = (id) => {
        const d = $('list-' + id);
        $('sheet-k').textContent = 'Detail ' + d.dataset.no + ' · ' + d.dataset.topic;
        $('sheet-h').textContent = d.querySelector('.p-name').textContent;
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
            setCell(openId, false);
            openId = id;
            fill(id);
            setCell(id, true);
            zoomTo(id);
            return;
        }
        openId = id;
        opener = from || null;
        fill(id);
        setCell(id, true);
        stage.classList.add('zoomed');
        sheet.showModal();
        sheet.style.transform = '';
        requestAnimationFrame(() => requestAnimationFrame(() => sheet.classList.add('in')));
        zoomTo(id);
        $('sheet-h').focus({ preventScroll: true });
    };

    const setCell = (id, on) => {
        const c = chart.querySelector(`.cell[data-room="${CSS.escape(id)}"]`);
        if (c) c.classList.toggle('on', on);
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
            setCell(id, false);
            if (opener && document.contains(opener)) opener.focus({ preventScroll: true });
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

    chart.addEventListener('click', (e) => {
        const cell = e.target.closest('.cell');
        if (!cell) return;
        e.preventDefault();
        open(cell.dataset.room, cell);
    });
    $('close').addEventListener('click', requestClose);
    sheet.addEventListener('cancel', (e) => { e.preventDefault(); requestClose(); });
    sheet.addEventListener('click', (e) => { if (e.target === sheet) requestClose(); }); // backdrop

    // Parts list: the same room opens as a sheet when JavaScript is on.
    document.querySelectorAll('.all summary').forEach((s) => s.addEventListener('click', (e) => {
        e.preventDefault();
        open(s.parentElement.dataset.room, s);
    }));

    // ---------- swipe down on the handle area closes the sheet ----------
    const grab = $('grab');
    let drag = null;
    grab.addEventListener('pointerdown', (e) => {
        if (e.target.closest('button')) return;
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
    const start = location.hash.slice(1);
    if (start && $('list-' + start)) {
        history.replaceState(null, '', location.pathname + location.search);
        open(start);
    }
})();
