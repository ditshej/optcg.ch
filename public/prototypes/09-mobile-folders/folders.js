(() => {
    const page = document.getElementById('page');
    const scrim = document.querySelector('.scrim');
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    let current = null; // { sheet, opener, pushed }

    const focusable = (root) => [...root.querySelectorAll('a[href], button:not([disabled])')].filter((el) => el.offsetParent !== null);

    const show = (id, opener, push) => {
        const sheet = document.getElementById(id);
        if (!sheet || !sheet.classList.contains('sheet')) return;
        if (current) return;
        if (push) history.pushState({ room: id }, '', '#' + id);
        sheet.classList.add('open');
        sheet.style.transform = '';
        scrim.hidden = false;
        page.inert = true;
        document.body.style.overflow = 'hidden';
        sheet.focus({ preventScroll: true });
        current = { sheet, opener, pushed: push };
    };

    const hide = () => {
        if (!current) return;
        const { sheet, opener } = current;
        current = null;
        const finish = () => {
            sheet.classList.remove('open', 'closing', 'dragging');
            sheet.style.transform = '';
            scrim.hidden = true;
        };
        page.inert = false;
        document.body.style.overflow = '';
        if (reduce.matches) finish();
        else {
            sheet.classList.remove('dragging');
            sheet.style.transform = '';
            sheet.classList.add('closing');
            sheet.addEventListener('animationend', finish, { once: true });
        }
        const target = opener && document.contains(opener) ? opener : document.querySelector('.tile-head');
        if (target) target.focus({ preventScroll: true });
    };

    // Leave through the history entry we pushed, so the back button, Esc and the swipe agree.
    const close = () => {
        if (!current) return;
        if (current.pushed) history.back();
        else {
            hide();
            history.replaceState(null, '', location.pathname + location.search);
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-room]');
        if (link && !event.metaKey && !event.ctrlKey && !event.shiftKey) {
            event.preventDefault();
            show(link.dataset.room, link, true);
            return;
        }
        if (event.target.closest('[data-close]')) close();
    });

    document.addEventListener('keydown', (event) => {
        if (!current) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
        } else if (event.key === 'Tab') {
            // Focus trap: the page behind is inert, wrap inside the sheet.
            const items = focusable(current.sheet);
            if (!items.length) return;
            const first = items[0];
            const last = items[items.length - 1];
            if (event.shiftKey && (document.activeElement === first || document.activeElement === current.sheet)) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    window.addEventListener('popstate', () => {
        const id = location.hash.slice(1);
        if (id && !current) show(id, null, false);
        else if (!id && current) hide();
    });

    // Swipe down on the sheet header (and on the list when it is scrolled to the top) closes it.
    let drag = null;
    const start = (event) => {
        if (!current || event.target.closest('.close')) return;
        const list = event.target.closest('.links, .empty');
        if (list && list.scrollTop > 0) return;
        drag = { y: event.clientY, id: event.pointerId, dy: 0, t: event.timeStamp };
    };
    document.addEventListener('pointerdown', (event) => {
        if (event.target.closest('.sheet-head, .links, .empty')) start(event);
    });
    document.addEventListener('pointermove', (event) => {
        if (!drag || event.pointerId !== drag.id || !current) return;
        drag.dy = Math.max(0, event.clientY - drag.y);
        if (drag.dy > 6) {
            current.sheet.classList.add('dragging');
            current.sheet.style.transform = `translateY(${drag.dy}px)`;
        }
    });
    const end = (event) => {
        if (!drag || event.pointerId !== drag.id) return;
        const { dy, t } = drag;
        drag = null;
        if (!current) return;
        const fast = dy > 40 && event.timeStamp - t < 250;
        if (dy > 110 || fast) {
            close();
            return;
        }
        current.sheet.classList.remove('dragging');
        current.sheet.style.transform = '';
    };
    document.addEventListener('pointerup', end);
    document.addEventListener('pointercancel', end);

    if (location.hash.length > 1) show(location.hash.slice(1), null, false);
})();
