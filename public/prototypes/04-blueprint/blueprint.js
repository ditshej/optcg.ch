(() => {
    const page = document.getElementById('page');
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    let current = null; // { section, cell, opener }

    // List entry and compartment highlight each other.
    const mirror = (id, on) => document.querySelectorAll(`[data-room="${CSS.escape(id)}"]`).forEach((el) => el.classList.toggle('hl', on));
    document.querySelectorAll('.cell, .parts a').forEach((el) => {
        el.addEventListener('pointerenter', () => mirror(el.dataset.room, true));
        el.addEventListener('pointerleave', () => mirror(el.dataset.room, false));
        el.addEventListener('focus', () => mirror(el.dataset.room, true));
        el.addEventListener('blur', () => mirror(el.dataset.room, false));
    });

    // Runs a DOM change as a view transition when the browser can and the user allows it.
    const transition = (update) => {
        if (!document.startViewTransition || reduce.matches) {
            update();
            return Promise.resolve();
        }
        return document.startViewTransition(update).finished.catch(() => {});
    };

    const cellFor = (id) => document.querySelector(`.section .cell[data-room="${CSS.escape(id)}"] .fill`);

    // SVG shapes cannot carry a view-transition-name, so a ghost box stands in for the compartment.
    const ghostFor = (cell) => {
        const box = cell.getBoundingClientRect();
        const ghost = document.createElement('div');
        ghost.className = 'ghost';
        ghost.setAttribute('aria-hidden', 'true');
        Object.assign(ghost.style, { left: box.left + 'px', top: box.top + 'px', width: box.width + 'px', height: box.height + 'px' });
        document.body.append(ghost);
        return ghost;
    };

    const show = (id, opener, push) => {
        const section = document.getElementById(id);
        if (!section || !section.classList.contains('room')) return;
        const cell = cellFor(id);
        const origin = cell; // the drawing is the origin, also when opened from the parts list
        section.querySelectorAll('.links li').forEach((li, i) => li.style.setProperty('--i', i));
        const ghost = origin ? ghostFor(origin) : null;
        if (push) history.pushState({ room: id }, '', '#' + id);
        transition(() => {
            if (ghost) ghost.remove();
            section.style.viewTransitionName = 'room';
            section.classList.add('open');
            page.inert = true;
            document.body.style.overflow = 'hidden';
        }).then(() => {
            section.style.viewTransitionName = '';
            section.focus({ preventScroll: true });
        });
        current = { section, cell: origin, opener, pushed: push };
    };

    const hide = () => {
        if (!current) return;
        const { section, cell, opener } = current;
        current = null;
        let ghost = null;
        section.style.viewTransitionName = 'room';
        transition(() => {
            section.style.viewTransitionName = '';
            section.classList.remove('open');
            page.inert = false;
            document.body.style.overflow = '';
            if (cell) ghost = ghostFor(cell);
        }).then(() => {
            if (ghost) ghost.remove();
        });
        const target = opener && document.contains(opener) ? opener : document.querySelector('.cell');
        if (target) target.focus({ preventScroll: true });
    };

    // Leave through the history entry we pushed, so the back button and Esc agree.
    const close = () => {
        if (!current) return;
        if (current.pushed) {
            history.back();
        } else {
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
        if (event.key === 'Escape' && current) {
            event.preventDefault();
            close();
        }
    });

    window.addEventListener('popstate', () => {
        const id = location.hash.slice(1);
        if (id && !current) show(id, null, false);
        else if (!id && current) hide();
    });

    if (location.hash.length > 1) show(location.hash.slice(1), null, false);
})();
