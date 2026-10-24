(() => {
    const bar = document.getElementById('bar');
    const ind = document.getElementById('ind');
    const tabs = [...bar.querySelectorAll('a[data-tab]')];
    const panels = [...document.querySelectorAll('.panel')];
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    let current = -1;

    bar.setAttribute('role', 'tablist');
    tabs.forEach((t, i) => {
        t.setAttribute('role', 'tab');
        t.setAttribute('aria-controls', panels[i].id);
        t.tabIndex = -1;
        panels[i].setAttribute('role', 'tabpanel');
    });
    ind.style.width = 100 / tabs.length + '%';

    const select = (i, { focus = false, hash = true } = {}) => {
        if (i === current || !panels[i]) return;
        const dir = current < 0 ? 0 : Math.sign(i - current);
        tabs.forEach((t, k) => {
            t.setAttribute('aria-selected', String(k === i));
            t.tabIndex = k === i ? 0 : -1;
        });
        panels.forEach((p, k) => {
            p.hidden = k !== i;
            p.classList.remove('from-left', 'from-right');
        });
        if (dir && !reduce.matches) {
            panels[i].classList.add(dir > 0 ? 'from-right' : 'from-left');
            void panels[i].offsetWidth; // restart the animation
        }
        ind.style.transform = `translateX(${i * 100}%)`;
        current = i;
        window.scrollTo(0, 0);
        if (focus) tabs[i].focus();
        if (hash) history.replaceState(null, '', '#' + panels[i].id);
    };

    tabs.forEach((t, i) => t.addEventListener('click', (e) => { e.preventDefault(); select(i); }));
    bar.addEventListener('keydown', (e) => {
        const step = { ArrowRight: 1, ArrowLeft: -1 }[e.key];
        if (step) { e.preventDefault(); select((current + step + tabs.length) % tabs.length, { focus: true }); }
        if (e.key === 'Home') { e.preventDefault(); select(0, { focus: true }); }
        if (e.key === 'End') { e.preventDefault(); select(tabs.length - 1, { focus: true }); }
    });

    const start = panels.findIndex((p) => '#' + p.id === location.hash);
    select(start >= 0 ? start : 0, { hash: false });
    window.addEventListener('hashchange', () => {
        const k = panels.findIndex((p) => '#' + p.id === location.hash);
        if (k >= 0) select(k, { hash: false });
    });
})();
