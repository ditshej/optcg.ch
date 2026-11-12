# 06 · Card Table

**Idea.** Every room is a trading card with an own frame (not any official layout): link count as "cost", name banner, line art window, type line, rules text with a preview of the first links, character and number. The cards lie on a felt table. Turning a card over shows its links as a list.

**Animation technique.** CSS 3D: the card flips with `rotateY` on a `preserve-3d` element (`backface-visibility: hidden` on both faces). On a mouse the face-up card tilts with the pointer (`rotateX/rotateY`, driven by `requestAnimationFrame`). The cards are dealt in with a short staggered entrance. Everything uses `transform` and `opacity`.

**Look.** Felt green, cream cards, one accent colour per room (all pass WCAG AA with cream text). Alfa Slab One and Source Sans 3 (OFL, in `fonts/`). Sizes use container query units, so a card scales as a unit.

**Strengths**
- Clear TCG reference without using any official artwork.
- The hidden face is `inert`, so keyboard and screen readers only see the visible side; `Esc` turns the card back.
- Works without JavaScript: both faces are printed one after the other.

**Weaknesses**
- A card is a small area for long link lists; the back scrolls.
- Ten tall cards make a long page on a phone (one per row).
- Per-room line art is own, simple and needs a drawing for each new room.
- The tilt is mouse only; touch gets the flip without it.
