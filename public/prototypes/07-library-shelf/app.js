// Pull a book off the shelf, fly it to the middle and open it.
// Techniques: CSS 3D transforms (cover hinge, perspective) plus the Web Animations API (flight from the spine's exact position).
// Without this file every spine is a link to #room-id and the CSS :target rule shows the open book.
const reduce = matchMedia('(prefers-reduced-motion: reduce)');
const spines = [...document.querySelectorAll('.spine')];
let active = null; // { dialog, spine, pushed }

const dialogFor = (id) => document.getElementById(id);

// Staggered table-of-contents entries.
document.querySelectorAll('.toc').forEach((ol) => [...ol.children].forEach((li, i) => li.style.setProperty('--n', Math.min(i, 8))));

const settle = (anim) => anim.finished.catch(() => {});

async function openBook(spine, { pushHistory = true } = {}) {
  if (active) return;
  const dialog = dialogFor(spine.dataset.book);
  const book = dialog.querySelector('.book3d');
  active = { dialog, spine, pushed: false };
  if (pushHistory) { history.pushState({ book: dialog.id }, '', '#' + dialog.id); active.pushed = true; }

  const from = spine.getBoundingClientRect();
  if (!reduce.matches) {
    // 1. The book slides forward off the shelf.
    await settle(spine.animate(
      [{ transform: 'translate3d(0,-14px,30px) rotateY(-6deg)' }, { transform: 'translate3d(0,-26px,120px) rotateY(0)' }],
      { duration: 220, easing: 'ease-out', fill: 'forwards' },
    ));
  }
  spine.classList.add('is-out');
  spine.getAnimations().forEach((a) => a.cancel());
  dialog.showModal();
  dialog.querySelector('.close').focus({ preventScroll: true });

  if (!reduce.matches) {
    // 2. Fly from the spine's rectangle to the centre; the book turns from its side to its front.
    const coverRect = dialog.querySelector('.cover').getBoundingClientRect();
    const sx = from.width / coverRect.width;
    const sy = from.height / coverRect.height;
    const dx = from.left + from.width / 2 - (coverRect.left + coverRect.width / 2);
    const dy = from.top + from.height / 2 - (coverRect.top + coverRect.height / 2);
    await settle(book.animate(
      [
        { transform: `translate(${dx}px, ${dy}px) scale(${sx}, ${sy}) rotateY(-80deg)`, opacity: 0.6 },
        { transform: 'none', opacity: 1 },
      ],
      { duration: 520, easing: 'cubic-bezier(.2, .8, .3, 1)' },
    ));
  }
  // 3. The cover swings open (CSS transition on .is-open).
  dialog.classList.add('is-open');
}

async function closeBook() {
  if (!active) return;
  const { dialog, spine, pushed } = active;
  active = null;
  const book = dialog.querySelector('.book3d');
  dialog.classList.add('is-closing');
  dialog.classList.remove('is-open');
  if (!reduce.matches) {
    await new Promise((r) => setTimeout(r, 650));
    const from = spine.getBoundingClientRect();
    const coverRect = dialog.querySelector('.cover').getBoundingClientRect();
    const dx = from.left + from.width / 2 - (coverRect.left + coverRect.width / 2);
    const dy = from.top + from.height / 2 - (coverRect.top + coverRect.height / 2);
    await settle(book.animate(
      [{ transform: 'none', opacity: 1 }, { transform: `translate(${dx}px, ${dy}px) scale(${from.width / coverRect.width}, ${from.height / coverRect.height}) rotateY(-80deg)`, opacity: 0.6 }],
      { duration: 420, easing: 'cubic-bezier(.5, 0, .8, .4)', fill: 'forwards' },
    ));
  }
  dialog.close();
  book.getAnimations().forEach((a) => a.cancel());
  dialog.classList.remove('is-closing');
  spine.classList.remove('is-out');
  spine.focus({ preventScroll: true });
  if (pushed && location.hash === '#' + dialog.id) history.back();
}

spines.forEach((spine) => spine.addEventListener('click', (event) => {
  if (event.metaKey || event.ctrlKey) return;
  event.preventDefault();
  openBook(spine);
}));

document.querySelectorAll('[data-close]').forEach((a) => a.addEventListener('click', (event) => {
  event.preventDefault();
  closeBook();
}));

// Esc: let the dialog's own close be replaced by our animated one.
document.querySelectorAll('dialog.reading').forEach((dialog) => {
  dialog.addEventListener('cancel', (event) => { event.preventDefault(); closeBook(); });
  // Click on the dark area around the book closes it.
  dialog.addEventListener('click', (event) => { if (event.target === dialog) closeBook(); });
});

// Back button closes the book; a #room-id in the URL opens it on load.
addEventListener('popstate', () => {
  if (active && location.hash !== '#' + active.dialog.id) {
    active.pushed = false;
    closeBook();
  } else if (!active && location.hash.length > 1) {
    const spine = spines.find((s) => '#' + s.dataset.book === location.hash);
    if (spine) openBook(spine, { pushHistory: false });
  }
});
const initial = spines.find((s) => '#' + s.dataset.book === location.hash);
if (initial) openBook(initial, { pushHistory: false });
