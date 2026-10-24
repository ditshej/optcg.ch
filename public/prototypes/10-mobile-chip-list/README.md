# 10 · Mobile chip list

**Idea.** One long page with every link, grouped by room, plus a sticky strip of ten chips (one per room) at the top. A tap on a chip scrolls to the room. While scrolling, the chip of the current room is highlighted and slid into the middle of the strip (scroll-spy). A small "Top" button appears in the thumb zone after the first screen.

**Use.** Swipe the strip sideways, tap a chip to jump. Chips are plain anchors, so they work without JavaScript. With JavaScript the URL stays tidy and the strip follows the page.

**Look.** Same Blueprint look as 04: grid paper, chamfered chips, yellow marker for the current room. Smooth scroll is off with `prefers-reduced-motion`.

**Strengths**
- No overlay, no hidden content: the browser find, back and scroll behave as expected.
- Everything is reachable in one gesture type (scroll).
- Simple, robust, works without JavaScript.

**Weaknesses**
- The strip sits at the top, outside the thumb zone on large phones.
- With many links the page gets long; empty rooms still take space.
- Ten chips need sideways swiping to see all rooms.
