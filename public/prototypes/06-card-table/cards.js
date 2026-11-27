(() => {
    const cards = [...document.querySelectorAll('.card')];
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    const fine = window.matchMedia('(hover: hover) and (pointer: fine)');

    // Only the visible side takes part in focus order and the accessibility tree.
    const sync = (card) => {
        const flipped = card.classList.contains('flipped');
        card.querySelector('.front').inert = flipped;
        card.querySelector('.back').inert = !flipped;
        card.querySelector('.flip').setAttribute('aria-expanded', String(flipped));
    };

    const setFlipped = (card, flipped, focusBack) => {
        card.classList.toggle('flipped', flipped);
        card.querySelector('.tilt').style.removeProperty('--rx');
        card.querySelector('.tilt').style.removeProperty('--ry');
        sync(card);
        const delay = reduce.matches ? 0 : 380; // the card is edge-on half way through the turn
        if (flipped && focusBack) setTimeout(() => card.querySelector('.back a, .back .turn')?.focus({ preventScroll: true }), delay);
        if (!flipped && focusBack) card.querySelector('.flip').focus({ preventScroll: true });
    };

    cards.forEach((card) => {
        const name = card.querySelector('.front h2').textContent;
        const count = card.querySelectorAll('.back .links li').length;

        const flip = document.createElement('button');
        flip.type = 'button';
        flip.className = 'flip';
        flip.setAttribute('aria-expanded', 'false');
        flip.setAttribute('aria-label', `${name}: turn over to show ${count} ${count === 1 ? 'link' : 'links'}`);
        card.querySelector('.front .frame').after(flip);

        const turn = document.createElement('button');
        turn.type = 'button';
        turn.className = 'turn';
        turn.setAttribute('aria-label', `Turn ${name} face up again`);
        turn.textContent = '↺';
        card.querySelector('.back .frame').prepend(turn);

        flip.addEventListener('click', () => {
            cards.forEach((other) => other !== card && other.classList.contains('flipped') && setFlipped(other, false, false));
            setFlipped(card, true, true);
        });
        turn.addEventListener('click', () => setFlipped(card, false, true));
        card.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && card.classList.contains('flipped')) setFlipped(card, false, true);
        });

        // Tilt follows the mouse on the card that is face up on the table.
        const tilt = card.querySelector('.tilt');
        let frame = 0;
        card.addEventListener('pointermove', (event) => {
            if (event.pointerType !== 'mouse' || reduce.matches || !fine.matches || card.classList.contains('flipped')) return;
            const box = card.getBoundingClientRect();
            const px = (event.clientX - box.left) / box.width - 0.5;
            const py = (event.clientY - box.top) / box.height - 0.5;
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(() => {
                tilt.classList.add('live');
                tilt.style.setProperty('--ry', `${(px * 14).toFixed(2)}deg`);
                tilt.style.setProperty('--rx', `${(-py * 14).toFixed(2)}deg`);
            });
        });
        card.addEventListener('pointerleave', () => {
            cancelAnimationFrame(frame);
            tilt.classList.remove('live');
            tilt.style.removeProperty('--rx');
            tilt.style.removeProperty('--ry');
        });

        sync(card);
    });

    // A link such as #library turns that card over.
    const fromHash = () => {
        const card = cards.find((c) => c.id === location.hash.slice(1));
        if (card) setFlipped(card, true, false);
    };
    window.addEventListener('hashchange', fromHash);
    fromHash();
})();
