// FLIP layout animation for the bento grid, spring-eased with a linear() curve.
// Without this file the tiles still open and close through the native <details> element.
const grid = document.getElementById('grid');
const tiles = [...grid.querySelectorAll('.tile')];
const reduce = matchMedia('(prefers-reduced-motion: reduce)');
const spring = getComputedStyle(document.documentElement).getPropertyValue('--spring').trim();
const supportsLinear = CSS.supports('animation-timing-function', 'linear(0, 1)');
const easing = supportsLinear ? spring : 'cubic-bezier(.3, 1.35, .5, 1)';
const DURATION = 650;

function measure() {
  return new Map(tiles.map((t) => [t, t.getBoundingClientRect()]));
}

function flip(before, opening) {
  const after = measure();
  tiles.forEach((tile) => {
    const a = before.get(tile);
    const b = after.get(tile);
    const dx = a.left - b.left;
    const dy = a.top - b.top;
    const sx = a.width / b.width;
    const sy = a.height / b.height;
    const resized = Math.abs(sx - 1) > 0.01 || Math.abs(sy - 1) > 0.01;
    if (!dx && !dy && !resized) return;
    const opts = { duration: DURATION, easing, fill: 'backwards' };
    // Tiles move as a whole; only the background layer scales so text is never stretched.
    tile.animate([{ transform: `translate(${dx}px, ${dy}px)` }, { transform: 'none' }], opts);
    if (resized) {
      tile.querySelector('.bg').animate([{ transform: `scale(${sx}, ${sy})` }, { transform: 'none' }], opts);
      const fade = tile.querySelectorAll('.panel, .blurb');
      fade.forEach((el) => el.animate([{ opacity: 0 }, { opacity: 1 }], { duration: DURATION * 0.6, delay: DURATION * 0.25, easing: 'ease-out', fill: 'backwards' }));
    }
  });
}

function toggle(tile) {
  const before = measure();
  const willOpen = !tile.open;
  // Keep the grid's exclusive behaviour explicit so both transitions are measured in one pass.
  tiles.forEach((t) => { if (t !== tile) t.open = false; });
  tile.open = willOpen;
  if (!reduce.matches) flip(before);
  if (willOpen) tile.scrollIntoView({ block: 'nearest', behavior: reduce.matches ? 'auto' : 'smooth' });
}

tiles.forEach((tile) => {
  tile.querySelector('summary').addEventListener('click', (event) => {
    event.preventDefault();
    toggle(tile);
  });
});

// Esc closes the open room and returns focus to its tile.
document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  const open = tiles.find((t) => t.open);
  if (!open) return;
  toggle(open);
  open.querySelector('summary').focus();
});

// Theme switch: default follows the system; the button forces light or dark for this visit only.
const root = document.documentElement;
const button = document.getElementById('theme');
const label = button.querySelector('.theme-text');
const systemDark = matchMedia('(prefers-color-scheme: dark)');
function current() { return root.dataset.theme || (systemDark.matches ? 'dark' : 'light'); }
function paint() { label.textContent = current() === 'dark' ? 'Dark' : 'Light'; button.setAttribute('aria-pressed', String(current() === 'dark')); }
button.addEventListener('click', () => { root.dataset.theme = current() === 'dark' ? 'light' : 'dark'; paint(); });
paint();
