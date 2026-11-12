(() => {
    const strip = document.querySelector('.chips ol');
    const chips = [...document.querySelectorAll('[data-chip]')];
    const rooms = [...document.querySelectorAll('[data-room]')];
    const up = document.querySelector('.up');
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    let active = null;

    // Moves the strip, not the page: the active chip slides to the middle.
    const reveal = (chip) => {
        const target = chip.offsetLeft - (strip.clientWidth - chip.offsetWidth) / 2;
        strip.scrollTo({ left: target, behavior: reduce.matches ? 'auto' : 'smooth' });
    };

    const mark = (id) => {
        if (id === active) return;
        active = id;
        chips.forEach((chip) => {
            const on = chip.dataset.chip === id;
            if (on) chip.setAttribute('aria-current', 'true');
            else chip.removeAttribute('aria-current');
            if (on) reveal(chip);
        });
    };

    // Scroll-spy: the room whose top edge last passed the line below the strip is current.
    const line = () => document.querySelector('.chips').offsetHeight + 24;
    const spy = () => {
        let id = rooms[0].id;
        for (const room of rooms) {
            if (room.getBoundingClientRect().top <= line()) id = room.id;
        }
        const bottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;
        mark(bottom ? rooms[rooms.length - 1].id : id);
        const far = window.scrollY > window.innerHeight * 0.8;
        up.hidden = false;
        up.classList.toggle('out', !far);
        up.tabIndex = far ? 0 : -1;
        up.setAttribute('aria-hidden', far ? 'false' : 'true');
    };

    let queued = false;
    window.addEventListener('scroll', () => {
        if (queued) return;
        queued = true;
        requestAnimationFrame(() => {
            queued = false;
            spy();
        });
    }, { passive: true });
    window.addEventListener('resize', spy);

    // Chips are plain anchors; keep the URL tidy and move focus to the room heading.
    chips.forEach((chip) => chip.addEventListener('click', (event) => {
        const room = document.getElementById(chip.dataset.chip);
        if (!room) return;
        event.preventDefault();
        history.replaceState(null, '', '#' + room.id);
        room.scrollIntoView({ behavior: reduce.matches ? 'auto' : 'smooth', block: 'start' });
        mark(room.id);
    }));

    up.addEventListener('click', (event) => {
        event.preventDefault();
        window.scrollTo({ top: 0, behavior: reduce.matches ? 'auto' : 'smooth' });
        history.replaceState(null, '', location.pathname + location.search);
    });

    spy();
})();
