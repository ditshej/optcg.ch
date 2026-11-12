# 03 · Deep Dive

**Idea:** One vertical journey from the sunlit surface to the abyss. Every room is a depth layer: the background is one continuous gradient that gets darker with each room, the text colour flips from dark to light when the water gets dark, and a fixed depth gauge on the right shows where you are (hover or focus a tick to see the room name). Typography is huge, wide, heavy Archivo (variable width axis); links are plain ruled rows with round icons, so the colour and scale carry the design.

**Animation technique:** CSS scroll-driven animations: `animation-timeline: view()` makes titles and link rows rise as they enter, `animation-timeline: scroll(root)` moves the gauge marker and fades the surface waves. The waves also drift with a looping `transform` animation. Fallback for browsers without support (Firefox at the time of writing): `IntersectionObserver` reveal plus a passive scroll listener for the gauge, in `app.js`. Everything is visible without JavaScript. `prefers-reduced-motion` turns the reveal and the wave drift off; the gauge marker keeps tracking the scroll position because it is a position indicator, not decoration.

**Strengths**
- Strongest atmosphere and the clearest "one ship, many decks" story.
- Scroll-driven animation runs on the compositor, no JS in modern browsers.
- Depth gauge doubles as room navigation.

**Weaknesses**
- Colour is the structure: the layer text colours need care when rooms are added (computed in `index.php`, WCAG AA checked for the current 10).
- Long page; finding one room means scrolling or using the gauge.
- Needs a fallback path for browsers without `animation-timeline`.

**Fonts:** Archivo (SIL OFL, license in `fonts/`).
