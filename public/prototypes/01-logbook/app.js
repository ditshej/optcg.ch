// Scroll-spy and reveal-on-scroll, both with IntersectionObserver. No dependencies.
document.documentElement.classList.add('js');

const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
const links = new Map([...document.querySelectorAll('[data-spy]')].map((a) => [a.dataset.spy, a]));
const nav = document.querySelector('.side ol');

function setCurrent(id) {
  links.forEach((a, key) => {
    if (key === id) {
      a.setAttribute('aria-current', 'true');
      // Keep the active entry visible in the sidebar (or the chip row on phones).
      const box = nav.getBoundingClientRect();
      const el = a.getBoundingClientRect();
      if (nav.scrollWidth > nav.clientWidth) nav.scrollTo({ left: nav.scrollLeft + el.left - box.left - 24, behavior: reduce ? 'auto' : 'smooth' });
    } else {
      a.removeAttribute('aria-current');
    }
  });
}

// A room is "current" while it crosses a band near the top of the viewport.
const spy = new IntersectionObserver((entries) => {
  entries.forEach((e) => { if (e.isIntersecting) setCurrent(e.target.id); });
}, { rootMargin: '-20% 0px -70% 0px' });
document.querySelectorAll('[data-room]').forEach((s) => spy.observe(s));

// Stagger rows inside each room and fade them in once.
document.querySelectorAll('.links').forEach((ul) => [...ul.children].forEach((li, i) => li.style.setProperty('--i', Math.min(i, 6))));
const reveal = new IntersectionObserver((entries, obs) => {
  entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('in'); obs.unobserve(e.target); } });
}, { rootMargin: '0px 0px -8% 0px' });
document.querySelectorAll('.reveal').forEach((el) => reveal.observe(el));
