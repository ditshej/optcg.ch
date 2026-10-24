# 02 · Sea Chart

**Idea.** The rooms are places on a hand-drawn nautical chart: graticule, tick frame, wave lines, soundings, a compass rose and a red course that visits every place in order. A place opens a "chart note" (a `<dialog>`) with its links. Phones get a second, portrait chart, plus a numbered legend.

**Animation technique.** SVG line drawing with `stroke-dashoffset`. All lines use `pathLength="1"`, so no JavaScript has to measure them. The chart draws itself on load (waves first, then the course). Hovering or focusing a place (or its legend entry) draws a thick route from the home port to that place.

**Look.** Parchment, ink and one vermilion accent. IM Fell English and IM Fell English SC (OFL, in `fonts/`). Islands are generated from a fixed seed, so the chart looks the same on every request.

**Strengths**
- Very distinct character; the page is the map.
- Without JavaScript every place is an anchor to a full list of its links below the chart.
- Keyboard: markers and legend are links, `Esc` closes the note, focus returns to the marker.

**Weaknesses**
- One extra click: link targets sit in a note, not on the map.
- Place positions are hand-set for ten rooms (a fallback zigzag exists for more); a new room needs a coordinate.
- Dense map labels need care on narrow screens; the portrait chart is the answer, but it is a second drawing to maintain.
- Uses `stroke-dashoffset`, which is not compositor-only; fine for a few paths.
