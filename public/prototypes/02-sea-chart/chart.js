(() => {
    const dialog = document.getElementById('note');
    const body = dialog.querySelector('.note-body');
    let opener = null;

    // Hover / focus draws the route from the home port to the place.
    const routes = new Map();
    document.querySelectorAll('.route').forEach((path) => {
        const id = path.dataset.room;
        if (!routes.has(id)) routes.set(id, []);
        routes.get(id).push(path);
    });
    const setRoute = (id, on) => (routes.get(id) || []).forEach((p) => p.classList.toggle('on', on));

    document.querySelectorAll('[data-room]').forEach((el) => {
        if (el.classList.contains('route')) return;
        const id = el.dataset.room;
        el.addEventListener('pointerenter', () => setRoute(id, true));
        el.addEventListener('pointerleave', () => setRoute(id, false));
        el.addEventListener('focus', () => setRoute(id, true));
        el.addEventListener('blur', () => setRoute(id, false));
    });

    const open = (id, from) => {
        const section = document.getElementById(id);
        if (!section || typeof dialog.showModal !== 'function') return false;
        body.replaceChildren(section.cloneNode(true));
        body.firstElementChild.removeAttribute('id');
        opener = from || null;
        if (!dialog.open) dialog.showModal();
        dialog.scrollTop = 0;
        history.replaceState(null, '', '#' + id);
        return true;
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-room]');
        if (!link || event.metaKey || event.ctrlKey) return;
        if (open(link.dataset.room, link)) event.preventDefault();
    });

    // Click on the backdrop closes the note.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('close', () => {
        history.replaceState(null, '', location.pathname + location.search);
        if (opener) opener.focus();
    });
    window.addEventListener('hashchange', () => {
        const id = location.hash.slice(1);
        if (id) open(id);
    });
    if (location.hash.length > 1) open(location.hash.slice(1));
})();
