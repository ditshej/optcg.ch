# 05 · Harbour Grid

**Idea:** A modern bento layout. The ten rooms are tiles of different sizes in a four-column grid (two columns on phones), in a strict four-colour palette: navy, teal, sun yellow and paper. Light and dark themes are both first-class and switchable with a button (default: system). Typography is Schibsted Grotesk with JetBrains Mono for metadata. Each tile is a native `<details>` element, so rooms open and links work without JavaScript.

**Animation technique:** FLIP (First, Last, Invert, Play) with the Web Animations API. When a room opens, it takes a full row and the other tiles reflow; `app.js` measures every tile before and after and animates the difference with `transform` only (the background layer scales, the text only translates, so type never stretches). Easing is a spring-like `linear()` curve with a small overshoot; the same curve drives the hover lift on tiles and link cards. Falls back to a cubic-bezier overshoot where `linear()` is missing. `prefers-reduced-motion` removes all of it.

**Strengths**
- The clearest overview of the set: all rooms visible at once, sized by importance.
- Works without JavaScript (`<details name="rooms">`), keyboard-friendly (Enter/Space, Esc closes).
- Dark mode is a real design, not an inverted one.

**Weaknesses**
- Opening a room moves the layout; on a phone the opened room pushes the rest down.
- The bento sizes are hand-set for ten rooms (`.t1 … .t10`); more rooms fall back to 1×1 tiles.
- Least "One Piece" of the set; the character is a label, not a visual.

**Fonts:** Schibsted Grotesk, JetBrains Mono (SIL OFL, licenses in `fonts/`).
