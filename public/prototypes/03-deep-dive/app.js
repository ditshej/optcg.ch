// Main animation is pure CSS (animation-timeline). This file only provides the fallback for
// browsers without scroll-driven animations: IntersectionObserver reveal and a scroll listener for the gauge.
if (!CSS.supports('animation-timeline: view()')) {
  const root = document.documentElement;
  root.classList.add('no-sdt', 'js');
  const io = new IntersectionObserver((entries, obs) => {
    entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('in'); obs.unobserve(e.target); } });
  }, { rootMargin: '0px 0px -10% 0px' });
  document.querySelectorAll('.rise, .layer h2').forEach((el) => io.observe(el));
  const update = () => {
    const max = root.scrollHeight - innerHeight;
    root.style.setProperty('--progress', max > 0 ? Math.min(1, scrollY / max) : 0);
  };
  addEventListener('scroll', update, { passive: true });
  update();
}
